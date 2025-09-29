<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理测试邮件
$test_result = '';
if (isset($_POST['test_mail']) && $_POST['test_mail']) {
    // 获取当前表单数据
    $configs = [
        'mail_smtp_host' => $_POST['mail_smtp_host'] ?? '',
        'mail_smtp_port' => $_POST['mail_smtp_port'] ?? '',
        'mail_smtp_username' => $_POST['mail_smtp_username'] ?? '',
        'mail_smtp_password' => $_POST['mail_smtp_password'] ?? '',
        'mail_smtp_encryption' => $_POST['mail_smtp_encryption'] ?? '',
        'mail_from_address' => $_POST['mail_from_address'] ?? '',
        'mail_from_name' => $_POST['mail_from_name'] ?? '',
        'mail_admin_address' => $_POST['mail_admin_address'] ?? ''
    ];
    
    $test_result = test_mail_connection($configs);
}

// 处理表单提交
if ($_POST && !isset($_POST['test_mail'])) {
    try {
        // 更新邮件配置
        $configs = [
            'mail_smtp_host' => $_POST['mail_smtp_host'] ?? '',
            'mail_smtp_port' => $_POST['mail_smtp_port'] ?? '',
            'mail_smtp_username' => $_POST['mail_smtp_username'] ?? '',
            'mail_smtp_password' => $_POST['mail_smtp_password'] ?? '',
            'mail_smtp_encryption' => $_POST['mail_smtp_encryption'] ?? '',
            'mail_from_address' => $_POST['mail_from_address'] ?? '',
            'mail_from_name' => $_POST['mail_from_name'] ?? '',
            'mail_admin_address' => $_POST['mail_admin_address'] ?? ''
        ];
        
        foreach ($configs as $key => $value) {
            // 检查配置项是否存在
            $check_stmt = $db->prepare("SELECT id FROM system_config WHERE config_key = ?");
            $check_stmt->execute([$key]);
            
            if ($check_stmt->rowCount() > 0) {
                // 更新配置
                $update_stmt = $db->prepare("UPDATE system_config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                $update_stmt->execute([$value, $key]);
            } else {
                // 插入新配置
                $insert_stmt = $db->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?)");
                $insert_stmt->execute([$key, $value]);
            }
        }
        
        // 重定向并显示成功消息
        header('Location: mail.php?success=' . urlencode('邮件配置保存成功'));
        exit;
        
    } catch(PDOException $e) {
        $error_msg = '保存失败: ' . $e->getMessage();
    }
}

// 获取邮件配置
try {
    // 使用system_config表而不是config表
    $config_stmt = $db->prepare("SELECT config_key, config_value FROM system_config WHERE config_key LIKE 'mail_%'");
    $config_stmt->execute();
    $mail_configs = $config_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(PDOException $e) {
    $mail_configs = [];
    $error_msg = '获取配置失败: ' . $e->getMessage();
}

// 处理测试邮件
function test_mail_connection($configs) {
    // 检查必要配置
    if (empty($configs['mail_smtp_host']) || empty($configs['mail_smtp_port']) || empty($configs['mail_smtp_username']) || empty($configs['mail_smtp_password'])) {
        return '❌ 请先填写完整的SMTP配置信息（服务器、端口、用户名、密码）。';
    }
    
    // 检查PHPMailer是否存在
    if (!file_exists('../../../static/PHPMailer/PHPMailer.php')) {
        // 使用PHP内置mail函数测试
        $to = $configs['mail_admin_address'] ?? 'admin@gaoguangshike.cn';
        $subject = '邮件配置测试';
        $message = '这是一封测试邮件，用于验证邮件配置是否正确。';
        $headers = 'From: ' . ($configs['mail_from_address'] ?? 'noreply@gaoguangshike.cn');
        
        if (mail($to, $subject, $message, $headers)) {
            return '✅ PHP内置mail函数测试成功，但建议使用SMTP以获得更好的邮件发送效果。';
        } else {
            return '❌ PHP内置mail函数测试失败，请检查服务器邮件配置。';
        }
    }
    
    // 使用SMTP测试
    require_once '../../../static/PHPMailer/PHPMailer.php';
    require_once '../../../static/PHPMailer/SMTP.php';
    require_once '../../../static/PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
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
        
        // 收件人
        $to_address = $configs['mail_admin_address'] ?? 'admin@gaoguangshike.cn';
        $mail->addAddress($to_address);
        
        // 内容
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '邮件配置测试';
        $mail->Body = '<h2>邮件配置测试成功</h2><p>这是一封测试邮件，用于验证您的SMTP邮件配置是否正确。</p><p>时间: ' . date('Y-m-d H:i:s') . '</p>';
        
        // 发送邮件
        if ($mail->send()) {
            return '✅ SMTP邮件发送测试成功！';
        } else {
            return '❌ SMTP邮件发送失败: ' . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        return '❌ SMTP邮件发送异常: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件设置 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <h2>邮件设置</h2>
                </div>
                
                <div class="layui-card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_msg)): ?>
                        <div class="layui-alert layui-alert-danger">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($test_result): ?>
                        <div class="layui-alert layui-alert-info">
                            <?php echo $test_result; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form class="layui-form" method="POST" style="max-width: 800px;">
                        <div class="layui-form-item">
                            <label class="layui-form-label">SMTP服务器</label>
                            <div class="layui-input-block">
                                <input type="text" name="mail_smtp_host" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_host'] ?? ''); ?>" placeholder="如：smtp.qq.com" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">邮件服务器SMTP地址</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">SMTP端口</label>
                            <div class="layui-input-block">
                                <input type="text" name="mail_smtp_port" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_port'] ?? ''); ?>" placeholder="如：465 或 587" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">邮件服务器SMTP端口</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">SMTP用户名</label>
                            <div class="layui-input-block">
                                <input type="text" name="mail_smtp_username" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_username'] ?? ''); ?>" placeholder="邮件账户用户名" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">通常是您的邮箱地址</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">SMTP密码</label>
                            <div class="layui-input-block">
                                <input type="password" name="mail_smtp_password" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_password'] ?? ''); ?>" placeholder="邮件账户密码或授权码" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">注意：某些邮箱服务商需要使用授权码而非密码</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">加密方式</label>
                            <div class="layui-input-block">
                                <select name="mail_smtp_encryption" lay-verify="">
                                    <option value="">无加密</option>
                                    <option value="ssl" <?php echo (isset($mail_configs['mail_smtp_encryption']) && $mail_configs['mail_smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo (isset($mail_configs['mail_smtp_encryption']) && $mail_configs['mail_smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                </select>
                                <div class="layui-form-mid layui-word-aux">邮件传输加密方式</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">发件人邮箱</label>
                            <div class="layui-input-block">
                                <input type="email" name="mail_from_address" value="<?php echo htmlspecialchars($mail_configs['mail_from_address'] ?? ''); ?>" placeholder="发送邮件时显示的发件人邮箱地址" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">发件人名称</label>
                            <div class="layui-input-block">
                                <input type="text" name="mail_from_name" value="<?php echo htmlspecialchars($mail_configs['mail_from_name'] ?? ''); ?>" placeholder="发送邮件时显示的发件人名称" class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员邮箱</label>
                            <div class="layui-input-block">
                                <input type="email" name="mail_admin_address" value="<?php echo htmlspecialchars($mail_configs['mail_admin_address'] ?? ''); ?>" placeholder="接收系统通知的管理员邮箱地址" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">询价通知等系统邮件将发送到此邮箱</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button class="layui-btn" lay-submit lay-filter="formDemo">保存配置</button>
                                <button type="submit" name="test_mail" value="1" class="layui-btn layui-btn-primary">测试邮件发送</button>
                                <a href="index.php" class="layui-btn layui-btn-primary">返回系统设置</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="layui-card" style="margin-top: 30px;">
                        <div class="layui-card-header">
                            <h3>常见邮件服务商配置示例</h3>
                        </div>
                        <div class="layui-card-body">
                            <table class="layui-table">
                                <thead>
                                    <tr>
                                        <th>服务商</th>
                                        <th>SMTP服务器</th>
                                        <th>端口</th>
                                        <th>加密方式</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>QQ邮箱</td>
                                        <td>smtp.qq.com</td>
                                        <td>465</td>
                                        <td>SSL</td>
                                    </tr>
                                    <tr>
                                        <td>163邮箱</td>
                                        <td>smtp.163.com</td>
                                        <td>465</td>
                                        <td>SSL</td>
                                    </tr>
                                    <tr>
                                        <td>126邮箱</td>
                                        <td>smtp.126.com</td>
                                        <td>465</td>
                                        <td>SSL</td>
                                    </tr>
                                    <tr>
                                        <td>Gmail</td>
                                        <td>smtp.gmail.com</td>
                                        <td>587</td>
                                        <td>TLS</td>
                                    </tr>
                                    <tr>
                                        <td>Outlook</td>
                                        <td>smtp-mail.outlook.com</td>
                                        <td>587</td>
                                        <td>TLS</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="layui-text">
                                <p><strong>注意事项：</strong></p>
                                <ol>
                                    <li>某些邮箱服务商（如QQ邮箱、163邮箱）需要使用授权码而非密码</li>
                                    <li>请确保您的邮箱已开启SMTP服务</li>
                                    <li>测试前请确认配置信息正确无误</li>
                                    <li>如果PHPMailer库缺失，请先<a href="../../../install_phpmailer.php" target="_blank">安装PHPMailer库</a></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'element'], function(){
        var form = layui.form;
        var element = layui.element;
        element.render();
    });
    </script>
</body>
</html>