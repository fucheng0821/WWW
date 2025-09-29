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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .login-container {
            background: white;
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            width: 420px;
            max-width: 90%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b35, #f7931e, #ff6b35);
        }
        
        .login-logo {
            margin-bottom: 20px;
        }
        
        .login-logo i {
            font-size: 64px;
            color: #ff6b35;
            margin-bottom: 10px;
        }
        
        .login-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 40px;
        }
        
        .layui-form {
            text-align: left;
        }
        
        .layui-form-item {
            margin-bottom: 25px;
        }
        
        .layui-form-label {
            width: 0;
            padding: 0;
            display: none;
        }
        
        .layui-input-block {
            margin-left: 0;
            position: relative;
        }
        
        .layui-input {
            height: 50px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            padding: 0 50px 0 20px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .layui-input:focus {
            border-color: #ff6b35;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .input-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 18px;
            z-index: 1;
        }
        
        .login-btn {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-msg {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #e53e3e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .error-msg.show {
            display: block;
        }
        
        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #95a5a6;
            font-size: 12px;
        }
        

        
        @media (max-width: 480px) {
            .login-container {
                padding: 40px 30px;
                width: 95%;
            }
            
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="layui-icon layui-icon-website"></i>
        </div>
        <h1 class="login-title">高光视刻</h1>
        <p class="login-subtitle">管理后台登录</p>
        
        <?php if ($error): ?>
            <div class="error-msg show">
                <i class="layui-icon layui-icon-close-fill"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form class="layui-form" method="POST" id="loginForm">
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="text" name="username" required lay-verify="required" 
                           placeholder="请输入用户名" autocomplete="off" class="layui-input" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    <i class="layui-icon layui-icon-username input-icon"></i>
                </div>
            </div>
            
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="password" name="password" required lay-verify="required" 
                           placeholder="请输入密码" autocomplete="off" class="layui-input">
                    <i class="layui-icon layui-icon-password input-icon"></i>
                </div>
            </div>
            
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="login-btn" lay-submit lay-filter="login">
                        <i class="layui-icon layui-icon-ok"></i>
                        立即登录
                    </button>
                </div>
            </div>
        </form>
        
        <div class="login-footer">
            <p>© 2024 高光视刻. All rights reserved.</p>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
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