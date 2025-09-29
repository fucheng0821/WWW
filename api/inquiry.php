<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 引入PHPMailer类
if (file_exists('../static/PHPMailer/PHPMailer.php')) {
    require_once '../static/PHPMailer/PHPMailer.php';
    require_once '../static/PHPMailer/SMTP.php';
    require_once '../static/PHPMailer/Exception.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// 检查数据库连接
if (!isset($db) || !$db) {
    error_log('Inquiry API error: Database connection failed');
    json_response(['success' => false, 'message' => '数据库连接失败'], 500);
}

// 记录请求信息用于调试
error_log('Inquiry API request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Inquiry API error: Method not allowed');
    json_response(['success' => false, 'message' => '只支持POST请求'], 405);
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    error_log('Inquiry API error: Invalid JSON data');
    json_response(['success' => false, 'message' => '无效的JSON数据'], 400);
}

// 验证必填字段
$required_fields = ['name', 'phone', 'service_type', 'message'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $error_msg = "请填写{$field}字段";
        error_log('Inquiry API error: ' . $error_msg);
        json_response(['success' => false, 'message' => $error_msg], 400);
    }
}

// 清理输入数据
$clean_data = clean_input($data);

// 验证手机号
if (!validate_phone($clean_data['phone'])) {
    json_response(['success' => false, 'message' => '请输入正确的手机号码'], 400);
}

// 验证邮箱（如果提供）
if (!empty($clean_data['email']) && !validate_email($clean_data['email'])) {
    json_response(['success' => false, 'message' => '请输入正确的邮箱地址'], 400);
}

// 验证验证码
if (empty($clean_data['captcha'])) {
    json_response(['success' => false, 'message' => '请输入验证码'], 400);
}

session_start();
if (!isset($_SESSION['captcha']) || strtoupper($clean_data['captcha']) !== strtoupper($_SESSION['captcha'])) {
    json_response(['success' => false, 'message' => '对不起，您的验证码输入错误，请重新输入！'], 400);
}

// 验证通过后清除验证码
unset($_SESSION['captcha']);

try {
    // 检查是否重复提交（同一手机号5分钟内）
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM inquiries 
                          WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute([$clean_data['phone']]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        json_response(['success' => false, 'message' => '您已经提交过询价，请稍后再试'], 429);
    }
    
    // 检查表结构，确定要插入的字段
    $stmt = $db->prepare("DESCRIBE inquiries");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $column_names = [];
    foreach ($columns as $column) {
        $column_names[] = $column['Field'];
    }
    
    // 构建插入语句
    $fields = ['name', 'phone', 'service_type', 'project_description'];
    $values = [
        $clean_data['name'],
        $clean_data['phone'],
        $clean_data['service_type'],
        $clean_data['message']  // 使用message字段作为project_description
    ];
    
    // 根据实际表结构添加可选字段
    if (in_array('company', $column_names)) {
        $fields[] = 'company';
        $values[] = $clean_data['company'] ?? '';
    }
    
    if (in_array('email', $column_names)) {
        $fields[] = 'email';
        $values[] = $clean_data['email'] ?? '';
    }
    
    if (in_array('budget', $column_names)) {
        $fields[] = 'budget';
        $values[] = $clean_data['project_budget'] ?? '';
    }
    
    // 添加其他可选字段
    $optional_fields = [
        'requirements' => '',
        'timeline' => '',
        'source' => 'website',
        'status' => 'pending'
    ];
    
    foreach ($optional_fields as $field => $default_value) {
        if (in_array($field, $column_names)) {
            $fields[] = $field;
            $values[] = $default_value;
        }
    }
    
    // 如果存在priority字段，则添加
    if (in_array('priority', $column_names)) {
        $fields[] = 'priority';
        $values[] = 'normal';
    }
    
    // 构建SQL语句
    $field_placeholders = implode(', ', $fields);
    $value_placeholders = implode(', ', array_fill(0, count($fields), '?'));
    
    $sql = "INSERT INTO inquiries ($field_placeholders) VALUES ($value_placeholders)";
    
    // 插入询价数据
    $stmt = $db->prepare($sql);
    $success = $stmt->execute($values);
    
    if ($success) {
        $inquiry_id = $db->lastInsertId();
        
        // 发送邮件通知管理员
        $mail_result = send_inquiry_notification($inquiry_id, $clean_data);
        if (DEBUG_MODE) {
            error_log("邮件发送结果: " . ($mail_result ? "成功" : "失败"));
        }
        
        // 添加邮件发送状态到响应
        $message = '询价提交成功，我们会尽快与您联系！';
        if (DEBUG_MODE && !$mail_result) {
            $message .= ' (邮件通知发送失败)';
        }
        json_response(['success' => true, 'message' => $message, 'id' => $inquiry_id]);
    } else {
        json_response(['success' => false, 'message' => '提交失败，请稍后重试'], 500);
    }
    
} catch(PDOException $e) {
    error_log("询价提交错误: " . $e->getMessage());
    // 返回更详细的错误信息（仅在调试模式下）
    $message = DEBUG_MODE ? '系统错误: ' . $e->getMessage() : '系统错误，请稍后重试';
    json_response(['success' => false, 'message' => $message], 500);
}

/**
 * 发送询价通知邮件
 */
function send_inquiry_notification($inquiry_id, $data) {
    global $db;
    
    // 记录邮件发送尝试
    error_log("开始发送询价通知邮件，询价ID: {$inquiry_id}");
    
    // 获取邮件配置
    try {
        // 使用system_config表而不是config表
        $config_stmt = $db->prepare("SELECT config_key, config_value FROM system_config WHERE config_key LIKE 'mail_%'");
        $config_stmt->execute();
        $mail_configs = $config_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        error_log("获取邮件配置: " . count($mail_configs) . " 项");
    } catch(PDOException $e) {
        error_log("获取邮件配置失败: " . $e->getMessage());
        return false;
    }
    
    // 获取管理员邮箱
    $admin_email = $mail_configs['mail_admin_address'] ?? get_config('admin_email', 'admin@gaoguangshike.cn');
    
    if (empty($admin_email)) {
        error_log("未设置管理员邮箱，无法发送询价通知");
        return false;
    }
    
    error_log("管理员邮箱: {$admin_email}");
    
    // 构建邮件内容
    $subject = "新询价通知 - 高光视刻";
    $message = "
        <html>
        <head>
            <title>新询价通知</title>
        </head>
        <body>
            <h2>新询价通知</h2>
            <p><strong>询价ID:</strong> {$inquiry_id}</p>
            <p><strong>姓名:</strong> " . htmlspecialchars($data['name']) . "</p>
            <p><strong>电话:</strong> " . htmlspecialchars($data['phone']) . "</p>
            <p><strong>服务类型:</strong> " . htmlspecialchars($data['service_type']) . "</p>
            <p><strong>预算范围:</strong> " . htmlspecialchars($data['project_budget'] ?? '') . "</p>
            <p><strong>详细需求:</strong> " . nl2br(htmlspecialchars($data['message'])) . "</p>
            <p><strong>提交时间:</strong> " . date('Y-m-d H:i:s') . "</p>
        </body>
        </html>
    ";
    
    // 检查是否配置了SMTP
    if (!empty($mail_configs['mail_smtp_host']) && !empty($mail_configs['mail_smtp_port'])) {
        error_log("使用SMTP发送邮件");
        // 使用SMTP发送邮件
        return send_smtp_email($admin_email, $subject, $message, $mail_configs);
    } else {
        error_log("使用PHP内置mail函数发送邮件");
        // 使用PHP内置mail函数发送邮件
        // 设置邮件头
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        
        // 设置发件人
        $from_address = $mail_configs['mail_from_address'] ?? 'noreply@gaoguangshike.cn';
        $from_name = $mail_configs['mail_from_name'] ?? '高光视刻网站';
        $headers .= "From: {$from_name} <{$from_address}>" . "\r\n";
        
        // 发送邮件
        $result = mail($admin_email, $subject, $message, $headers);
        
        if (!$result) {
            error_log("发送询价通知邮件失败");
        } else {
            error_log("发送询价通知邮件成功");
        }
        
        return $result;
    }
}

/**
 * 使用SMTP发送邮件
 */
function send_smtp_email($to, $subject, $message, $configs) {
    // 检查PHPMailer是否存在
    if (!file_exists('../static/PHPMailer/PHPMailer.php')) {
        error_log("PHPMailer文件缺失，无法使用SMTP发送邮件");
        return false;
    }
    
    // 引入PHPMailer类
    require_once '../static/PHPMailer/PHPMailer.php';
    require_once '../static/PHPMailer/SMTP.php';
    require_once '../static/PHPMailer/Exception.php';
    
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log("PHPMailer类未找到");
        return false;
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // 启用调试模式（仅在调试时）
        if (DEBUG_MODE) {
            $mail->SMTPDebug = 2; // 启用详细调试
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer [$level] $str");
            };
        }
        
        error_log("SMTP配置: Host={$configs['mail_smtp_host']}, Port={$configs['mail_smtp_port']}, Username={$configs['mail_smtp_username']}");
        
        // 服务器设置
        $mail->isSMTP();
        $mail->Host = $configs['mail_smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $configs['mail_smtp_username'];
        $mail->Password = $configs['mail_smtp_password'];
        $mail->SMTPSecure = $configs['mail_smtp_encryption'] ?? 'ssl';
        $mail->Port = $configs['mail_smtp_port'];
        
        // 发件人
        $from_address = $configs['mail_from_address'] ?? $configs['mail_smtp_username'];
        $from_name = $configs['mail_from_name'] ?? '高光视刻网站';
        $mail->setFrom($from_address, $from_name);
        error_log("发件人: {$from_name} <{$from_address}>");
        
        // 收件人
        $mail->addAddress($to);
        error_log("收件人: {$to}");
        
        // 内容
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // 发送邮件
        $result = $mail->send();
        
        if (DEBUG_MODE) {
            error_log("SMTP邮件发送" . ($result ? "成功" : "失败") . ": " . ($result ? "邮件已发送到 $to" : $mail->ErrorInfo));
        }
        
        if ($result) {
            error_log("SMTP邮件发送成功");
        } else {
            error_log("SMTP邮件发送失败: " . $mail->ErrorInfo);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("SMTP邮件发送异常: " . $e->getMessage());
        if (DEBUG_MODE) {
            error_log("SMTP详细错误信息: " . $mail->ErrorInfo);
        }
        return false;
    }
}

?>