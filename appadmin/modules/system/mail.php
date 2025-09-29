<?php
session_start();
// 优化：先检查登录状态，避免不必要的文件加载

// 检查登录状态
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// 页面标题
$page_title = "邮件设置";

$test_result = '';
$error_msg = '';

// 先加载核心配置和数据库
require_once '../../includes/config.php';
require_once '../../includes/database.php';

// 确保数据库连接有效
if (!($db instanceof PDO)) {
    $error_msg = '数据库连接失败';
} else {
    // 获取邮件配置 - 优化：只在需要时执行数据库查询
    try {
        // 使用system_config表而不是config表
        $config_stmt = $db->prepare("SELECT config_key, config_value FROM system_config WHERE config_key LIKE ?");
        $config_stmt->execute(['mail_%']);
        $mail_configs = $config_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch(PDOException $e) {
        $mail_configs = [];
        $error_msg = '获取配置失败: ' . $e->getMessage();
    }
}

// 加载必要的函数
require_once '../../includes/functions.php';

// 处理测试邮件
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
    // 确保数据库连接有效
    if ($db instanceof PDO) {
        try {
            // 开始事务
            $db->beginTransaction();
            
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
            
            // 提交事务
            $db->commit();
            
            // 重定向并显示成功消息
            header('Location: mail.php?success=' . urlencode('邮件配置保存成功'));
            exit;
            
        } catch(PDOException $e) {
            // 回滚事务
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error_msg = '保存失败: ' . $e->getMessage();
        }
    } else {
        $error_msg = '数据库连接失败，无法保存配置';
    }
}

// 获取邮件配置
        if ($db instanceof PDO) {
            try {
                // 使用system_config表而不是config表
                $config_stmt = $db->prepare("SELECT config_key, config_value FROM system_config WHERE config_key LIKE ?");
                $config_stmt->execute(['mail_%']);
                $mail_configs = $config_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            } catch(PDOException $e) {
                $mail_configs = [];
                $error_msg = '获取配置失败: ' . $e->getMessage();
            }
        } else {
            $mail_configs = [];
            $error_msg = '数据库连接失败，无法获取配置';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>邮件设置 - 移动管理后台</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/mobile-admin.css">
    <link rel="stylesheet" href="../../assets/css/mobile-modules.css">
    <link rel="stylesheet" href="../../assets/css/mobile-custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="mobile-layout">
        <!-- 顶部导航栏 -->
        <div class="mobile-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="header-title">
                <h1>邮件设置</h1>
            </div>
            <div class="header-right">
                <button class="notification-btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationBadge" style="display: none;">0</span>
                </button>
            </div>
        </div>
        
        <!-- 侧边栏菜单 -->
        <div class="mobile-sidebar" id="mobileSidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '管理员'); ?></h3>
                        <p>在线</p>
                    </div>
                </div>
                <button class="close-sidebar" id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li class="menu-item">
                        <a href="../index.php">
                            <i class="fas fa-home"></i>
                            <span>控制台</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../category/">
                            <i class="fas fa-folder"></i>
                            <span>栏目管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../content/">
                            <i class="fas fa-file-alt"></i>
                            <span>内容管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../inquiry/">
                            <i class="fas fa-question-circle"></i>
                            <span>咨询管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../system/">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>退出登录</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- 遮罩层 -->
        <div class="overlay" id="overlay"></div>
        
        <!-- 主内容区域 -->
        <div class="mobile-main">
            <div class="form-container">
                <h3 class="section-title">邮件服务器设置</h3>
                
                <form method="POST" style="max-width: 800px;">
                    <div class="mb-3">
                        <label class="form-label">SMTP服务器</label>
                        <input type="text" name="mail_smtp_host" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_host'] ?? ''); ?>" placeholder="如：smtp.qq.com" class="form-control">
                        <div class="form-text">邮件服务器SMTP地址</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMTP端口</label>
                        <input type="text" name="mail_smtp_port" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_port'] ?? ''); ?>" placeholder="如：465 或 587" class="form-control">
                        <div class="form-text">邮件服务器SMTP端口</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMTP用户名</label>
                        <input type="text" name="mail_smtp_username" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_username'] ?? ''); ?>" placeholder="邮件账户用户名" class="form-control">
                        <div class="form-text">通常是您的邮箱地址</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SMTP密码</label>
                        <input type="password" name="mail_smtp_password" value="<?php echo htmlspecialchars($mail_configs['mail_smtp_password'] ?? ''); ?>" placeholder="邮件账户密码或授权码" class="form-control">
                        <div class="form-text">注意：某些邮箱服务商需要使用授权码而非密码</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">加密方式</label>
                        <select name="mail_smtp_encryption" class="form-select">
                            <option value="">无加密</option>
                            <option value="ssl" <?php echo (isset($mail_configs['mail_smtp_encryption']) && $mail_configs['mail_smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                            <option value="tls" <?php echo (isset($mail_configs['mail_smtp_encryption']) && $mail_configs['mail_smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                        </select>
                        <div class="form-text">邮件传输加密方式</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">发件人邮箱</label>
                        <input type="email" name="mail_from_address" value="<?php echo htmlspecialchars($mail_configs['mail_from_address'] ?? ''); ?>" placeholder="发送邮件时显示的发件人邮箱地址" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">发件人名称</label>
                        <input type="text" name="mail_from_name" value="<?php echo htmlspecialchars($mail_configs['mail_from_name'] ?? ''); ?>" placeholder="发送邮件时显示的发件人名称" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">管理员邮箱</label>
                        <input type="email" name="mail_admin_address" value="<?php echo htmlspecialchars($mail_configs['mail_admin_address'] ?? ''); ?>" placeholder="接收系统通知的管理员邮箱地址" class="form-control">
                        <div class="form-text">询价通知等系统邮件将发送到此邮箱</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>返回
                        </a>
                        <button type="submit" name="test_mail" value="1" class="btn btn-outline-primary">
                            <i class="fas fa-paper-plane me-2"></i>测试邮件发送
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>保存配置
                        </button>
                    </div>
                </form>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>常见邮件服务商配置示例</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped config-table">
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
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>注意事项：</h6>
                            <ul class="mb-0">
                                <li>某些邮箱服务商（如QQ邮箱、163邮箱）需要使用授权码而非密码</li>
                                <li>请确保您的邮箱已开启SMTP服务</li>
                                <li>测试前请确认配置信息正确无误</li>
                                <li>如果PHPMailer库缺失，请先安装PHPMailer库</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 优化：使用内联JavaScript减少HTTP请求，并添加错误处理 -->
    <script>
    // 立即执行函数，避免全局变量污染
    (function() {
        // 检查Bootstrap是否已加载
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            // 自动隐藏提示消息
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                if (alerts.length > 0) {
                    alerts.forEach(function(alert) {
                        try {
                            var bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        } catch(e) {
                            // 如果Bootstrap Alert初始化失败，使用简单的隐藏方式
                            alert.style.display = 'none';
                        }
                    });
                }
            }, 5000);
        }
    })();
    
    // 简单的表单验证
    document.addEventListener('DOMContentLoaded', function() {
        // 表单验证
        var mailForm = document.querySelector('form');
        if (mailForm) {
            mailForm.addEventListener('submit', function(e) {
                // 基本验证
                var host = this.querySelector('input[name="mail_smtp_host"]').value;
                var port = this.querySelector('input[name="mail_smtp_port"]').value;
                var fromAddress = this.querySelector('input[name="mail_from_address"]').value;
                
                // 如果是测试邮件，还需要验证用户名和密码
                var isTestMail = this.querySelector('input[name="test_mail"]') !== null;
                
                if (!host || !port) {
                    alert('SMTP服务器地址和端口是必填项');
                    e.preventDefault();
                    return false;
                }
                
                if (isTestMail) {
                    var username = this.querySelector('input[name="mail_smtp_username"]').value;
                    var password = this.querySelector('input[name="mail_smtp_password"]').value;
                    if (!username || !password) {
                        alert('测试邮件需要填写SMTP用户名和密码');
                        e.preventDefault();
                        return false;
                    }
                }
                
                // 简单的邮箱格式验证
                if (fromAddress && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fromAddress)) {
                    alert('请输入有效的发件人邮箱地址');
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
    </script>
</body>
</html>

<script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
<script src="../../assets/js/mobile-admin.js"></script>
</body>
</html>