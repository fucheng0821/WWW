<?php
session_start();
// 优化：减少不必要的require调用顺序，先检查会话

// 检查登录状态
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// 页面标题
$page_title = "个人资料";

$errors = [];
$success = '';

// 加载配置和数据库
require_once '../../includes/config.php';
require_once '../../includes/database.php';

// 确保数据库连接有效
if (!($db instanceof PDO)) {
    $errors[] = '数据库连接失败';
} else {
    // 获取当前管理员信息 - 优化：直接使用PDO对象，避免额外的连接检查函数调用
    try {
        $stmt = $db->prepare("SELECT id, username, email, real_name, role, last_login_at FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            $errors[] = '管理员信息不存在';
        }
    } catch(PDOException $e) {
        $errors[] = '获取管理员信息失败：' . $e->getMessage();
    }
}

// 加载必要的函数
require_once '../../includes/functions.php';
// 只提取需要的函数，减少不必要的加载
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
if (!function_exists('hash_password')) {
    // 定义简化版的密码哈希函数（如果原始函数不存在）
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
if (!function_exists('verify_password')) {
    // 定义简化版的密码验证函数（如果原始函数不存在）
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

// 处理个人信息更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $email = trim($_POST['email'] ?? '');
    $real_name = trim($_POST['real_name'] ?? '');
    
    // 验证输入
    if (!empty($email) && !validate_email($email)) {
        $errors[] = '邮箱格式不正确';
    }
    
    // 检查邮箱是否被其他管理员使用
    if (!empty($email) && $db instanceof PDO) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['admin_id']]);
            if ($stmt->fetch()) {
                $errors[] = '邮箱已被其他管理员使用';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果没有错误且数据库连接有效，更新数据
    if (empty($errors) && $db instanceof PDO) {
        try {
            $stmt = $db->prepare("UPDATE admins SET email = ?, real_name = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$email, $real_name, $_SESSION['admin_id']]);
            
            $success = '个人信息更新成功！';
            
            // 更新会话中的信息
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_real_name'] = $real_name;
            
            // 重新获取管理员信息
            $stmt = $db->prepare("SELECT id, username, email, real_name, role, last_login_at FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
        } catch(PDOException $e) {
            $errors[] = '更新失败：' . $e->getMessage();
        }
    }
}

// 处理密码修改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($current_password)) {
        $errors[] = '当前密码不能为空';
    }
    
    if (empty($new_password)) {
        $errors[] = '新密码不能为空';
    } elseif (strlen($new_password) < 6) {
        $errors[] = '新密码长度不能少于6位';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = '两次输入的新密码不一致';
    }
    
    // 验证当前密码是否正确
    if (empty($errors) && !empty($current_password) && $db instanceof PDO) {
        try {
            $stmt = $db->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $result = $stmt->fetch();
            
            if (!$result || !verify_password($current_password, $result['password'])) {
                $errors[] = '当前密码不正确';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果没有错误且数据库连接有效，更新密码
    if (empty($errors) && $db instanceof PDO) {
        try {
            $hashed_password = hash_password($new_password);
            $stmt = $db->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
            
            $success = '密码修改成功！';
        } catch(PDOException $e) {
            $errors[] = '密码修改失败：' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>个人资料 - 移动管理后台</title>
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
                <h1>个人资料</h1>
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
                <h3 class="section-title">个人信息设置</h3>
                
                <div class="profile-info">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-details">
                        <h4><?php echo htmlspecialchars($admin['username']); ?></h4>
                        <p>用户名</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">真实姓名</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($admin['real_name'] ?? '未设置'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">邮箱地址</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($admin['email'] ?? '未设置'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">角色</label>
                            <p class="form-control-plaintext">
                                <?php 
                                if ($admin['role'] === 'admin') {
                                    echo '超级管理员';
                                } else {
                                    echo '编辑员';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">最后登录时间</label>
                            <p class="form-control-plaintext">
                                <?php 
                                if ($admin['last_login_at']) {
                                    echo date('Y-m-d H:i:s', strtotime($admin['last_login_at']));
                                } else {
                                    echo '从未登录';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 更新个人信息表单 -->
            <div class="form-container">
                <h3 class="section-title">更新个人信息</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">用户名</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                                <div class="form-text">用户名不可修改</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">真实姓名</label>
                                <input type="text" name="real_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin['real_name'] ?? ''); ?>" 
                                       placeholder="请输入真实姓名">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">邮箱地址</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" 
                               placeholder="请输入邮箱地址">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>保存信息
                        </button>
                    </div>
                </form>
            </div>

            <!-- 修改密码表单 -->
            <div class="form-container">
                <h3 class="section-title">修改密码</h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">当前密码 *</label>
                        <input type="password" name="current_password" class="form-control" 
                               placeholder="请输入当前密码" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">新密码 *</label>
                        <input type="password" name="new_password" class="form-control" 
                               placeholder="请输入新密码（至少6位）" required>
                        <div class="form-text">密码长度至少6位，建议包含字母和数字</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">确认新密码 *</label>
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="请再次输入新密码" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>修改密码
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/mobile-admin.js"></script>
</body>
</html>