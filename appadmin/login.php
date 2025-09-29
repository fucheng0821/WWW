<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// 检查是否为AJAX请求
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// 如果已登录，跳转到后台首页
if (isset($_SESSION['admin_id'])) {
    if ($is_ajax) {
        json_response(['success' => true, 'message' => '已登录', 'redirect' => 'index.php']);
    } else {
        redirect('index.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
        if ($is_ajax) {
            json_response(['success' => false, 'message' => $error], 400);
        }
    } else {
        try {
            $stmt = $db->prepare("SELECT id, username, password, real_name, role, is_active FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && verify_password($password, $admin['password'])) {
                if ($admin['is_active']) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['real_name'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['last_activity'] = time();
                    
                    // 更新最后登录时间
                    $update_stmt = $db->prepare("UPDATE admins SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->execute([$admin['id']]);
                    
                    if ($is_ajax) {
                        // AJAX请求返回JSON响应
                        json_response(['success' => true, 'message' => '登录成功', 'redirect' => 'index.php']);
                    } else {
                        // 普通请求重定向到首页
                        redirect('index.php');
                    }
                } else {
                    $error = '账户已被禁用';
                    if ($is_ajax) {
                        json_response(['success' => false, 'message' => $error], 403);
                    }
                }
            } else {
                $error = '用户名或密码错误';
                if ($is_ajax) {
                    json_response(['success' => false, 'message' => $error], 401);
                }
            }
        } catch(PDOException $e) {
            $error = '登录失败，请稍后重试';
            if ($is_ajax) {
                json_response(['success' => false, 'message' => $error], 500);
            }
        }
    }
    
    // 如果是AJAX请求且有错误，直接返回错误信息
    if ($is_ajax && $error) {
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理员登录 - <?php echo get_config('site_name', '高光视刻'); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="assets/css/mobile-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-cube"></i>
            </div>
            <h1><?php echo get_config('site_name', '高光视刻'); ?></h1>
            <p>移动管理后台</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" id="loginForm">
            <div class="form-group">
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" required placeholder="请输入用户名" 
                           autocomplete="off" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" required placeholder="请输入密码" 
                           autocomplete="off">
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    立即登录
                </button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>© <?php echo date('Y'); ?> <?php echo get_config('site_name', '高光视刻'); ?>. All rights reserved.</p>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="assets/js/mobile-login.js"></script>
    <script>
        layui.use(['form'], function(){
            var form = layui.form;
            
            // 监听提交
            form.on('submit(login)', function(data){
                // 如果是AJAX请求，阻止默认提交并使用AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // 登录成功，跳转到指定页面
                                window.location.href = response.redirect;
                            } else {
                                // 显示错误信息
                                alert(response.message);
                            }
                        } else {
                            alert('登录请求失败，请稍后重试');
                        }
                    }
                };
                
                // 发送数据
                var formData = new FormData(document.getElementById('loginForm'));
                var params = new URLSearchParams(formData).toString();
                xhr.send(params);
                
                // 阻止默认提交
                return false;
            });
        });
    </script>
</body>
</html>