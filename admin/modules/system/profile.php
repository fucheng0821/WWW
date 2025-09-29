<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';

// 获取当前管理员信息
$admin_id = $_SESSION['admin_id'];
try {
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        header('Location: ../../logout.php');
        exit();
    }
} catch(Exception $e) {
    $errors[] = '获取用户信息失败：' . $e->getMessage();
    $admin = [];
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($username)) {
        $errors[] = '用户名不能为空';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '请输入有效的邮箱地址';
    }
    
    // 检查用户名是否重复（排除当前用户）
    if (!empty($username) && $username !== $admin['username']) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmt->execute([$username, $admin_id]);
            if ($stmt->fetch()) {
                $errors[] = '用户名已存在';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 检查邮箱是否重复（排除当前用户）
    if (!empty($email) && $email !== $admin['email']) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->fetch()) {
                $errors[] = '邮箱已存在';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果要修改密码，验证当前密码
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = '请输入当前密码';
        } elseif (!password_verify($current_password, $admin['password'])) {
            $errors[] = '当前密码错误';
        } elseif (strlen($new_password) < 6) {
            $errors[] = '新密码至少需要6位字符';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = '两次输入的新密码不一致';
        }
    }
    
    // 如果没有错误，更新数据
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // 更新包括密码
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE admins SET 
                    username = ?, email = ?, password = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $hashed_password, $admin_id]);
            } else {
                // 只更新基本信息
                $stmt = $db->prepare("
                    UPDATE admins SET 
                    username = ?, email = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $admin_id]);
            }
            
            // 更新会话信息
            $_SESSION['admin_username'] = $username;
            if (isset($_SESSION['admin_name'])) {
                $_SESSION['admin_name'] = $username;
            }
            
            $success = '基本资料更新成功！';
            
            // 重新获取更新后的数据
            $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();
            
        } catch(PDOException $e) {
            $errors[] = '更新失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>基本资料 - 高光视刻</title>
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
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>基本资料</h2>
                        <a href="index.php" class="layui-btn layui-btn-primary">
                            <i class="layui-icon layui-icon-return"></i> 返回系统设置
                        </a>
                    </div>
                </div>
                
                <div class="layui-card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="layui-alert layui-alert-danger">
                            <ul style="margin: 0; padding-left: 20px;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="layui-row layui-col-space20">
                        <!-- 左侧基本信息 -->
                        <div class="layui-col-md8">
                            <form class="layui-form" method="POST">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">用户名 *</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="username" placeholder="请输入用户名" 
                                               value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" 
                                               class="layui-input" required>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">邮箱地址</label>
                                    <div class="layui-input-block">
                                        <input type="email" name="email" placeholder="请输入邮箱地址" 
                                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" 
                                               class="layui-input">
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <div class="layui-card">
                                        <div class="layui-card-header">修改密码（可选）</div>
                                        <div class="layui-card-body">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">当前密码</label>
                                                <div class="layui-input-block">
                                                    <input type="password" name="current_password" placeholder="如需修改密码，请输入当前密码" 
                                                           class="layui-input">
                                                </div>
                                            </div>
                                            
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">新密码</label>
                                                <div class="layui-input-block">
                                                    <input type="password" name="new_password" placeholder="请输入新密码（至少6位）" 
                                                           class="layui-input">
                                                </div>
                                            </div>
                                            
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">确认新密码</label>
                                                <div class="layui-input-block">
                                                    <input type="password" name="confirm_password" placeholder="请再次输入新密码" 
                                                           class="layui-input">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <button type="submit" class="layui-btn layui-btn-normal">保存资料</button>
                                        <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- 右侧账户信息 -->
                        <div class="layui-col-md4">
                            <div class="layui-card">
                                <div class="layui-card-header">账户信息</div>
                                <div class="layui-card-body">
                                    <table class="layui-table">
                                        <tbody>
                                            <tr>
                                                <td width="80"><strong>用户ID</strong></td>
                                                <td><?php echo $admin['id'] ?? ''; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>状态</strong></td>
                                                <td>
                                                    <?php if (($admin['is_active'] ?? 0) == 1): ?>
                                                        <span class="layui-badge layui-bg-green">启用</span>
                                                    <?php else: ?>
                                                        <span class="layui-badge">禁用</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>创建时间</strong></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($admin['created_at'] ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>最后更新</strong></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($admin['updated_at'] ?? '')); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="layui-card">
                                <div class="layui-card-header">安全提示</div>
                                <div class="layui-card-body">
                                    <div class="layui-alert layui-alert-normal">
                                        <ul style="margin: 0; padding-left: 20px;">
                                            <li>请使用复杂密码，包含字母、数字</li>
                                            <li>定期修改密码以确保账户安全</li>
                                            <li>不要在公共场所登录管理后台</li>
                                            <li>退出时请点击"退出"按钮</li>
                                        </ul>
                                    </div>
                                </div>
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
        
        form.render();
        element.render();
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert-success, .layui-alert-danger');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    });
    </script>
</body>
</html>