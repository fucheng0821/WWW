<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// 检查登录状态
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// 页面标题
$page_title = "管理员设置";

$errors = [];
$success = '';

// 处理添加管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 验证输入
    if (empty($username)) {
        $errors[] = '用户名不能为空';
    }
    
    if (empty($password)) {
        $errors[] = '密码不能为空';
    } elseif (strlen($password) < 6) {
        $errors[] = '密码长度不能少于6位';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = '两次输入的密码不一致';
    }
    
    // 检查用户名是否重复
    if (!empty($username)) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = '用户名已存在';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 检查邮箱是否重复
    if (!empty($email)) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = '邮箱已存在';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果没有错误，插入数据
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO admins (username, email, password, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$username, $email, $hashed_password, $is_active]);
            
            $success = '管理员添加成功！';
            $_POST = []; // 清空表单
        } catch(PDOException $e) {
            $errors[] = '添加失败：' . $e->getMessage();
        }
    }
}

// 处理状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    $new_status = intval($_POST['new_status'] ?? 0);
    
    if ($admin_id > 0) {
        try {
            $stmt = $db->prepare("UPDATE admins SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $admin_id]);
            $success = '管理员状态更新成功！';
        } catch(PDOException $e) {
            $errors[] = '状态更新失败：' . $e->getMessage();
        }
    }
}

// 获取管理员列表
try {
    $stmt = $db->query("SELECT id, username, email, is_active, created_at, updated_at FROM admins ORDER BY created_at DESC");
    $admins = $stmt->fetchAll();
} catch(Exception $e) {
    $admins = [];
    $errors[] = '获取管理员列表失败：' . $e->getMessage();
}

// 处理成功和错误消息
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    
    // 将错误代码转换为友好的中文提示
    $error_messages = [
        'invalid_id' => '无效的管理员ID，请检查链接是否正确',
        'cannot_delete_self' => '无法删除当前登录的管理员账户',
        'admin_not_found' => '管理员不存在或已被删除',
        'permission_denied' => '权限不足，无法执行此操作',
        'database_error' => '数据库操作失败，请稍后重试'
    ];
    
    $errors[] = $error_messages[$error_code] ?? $error_code;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>管理员设置 - 移动管理后台</title>
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
                <h1>管理员设置</h1>
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
            <!-- 添加管理员表单 -->
            <div class="form-container">
                <h3 class="section-title">添加管理员</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">用户名 *</label>
                                <input type="text" name="username" placeholder="请输入用户名" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                       class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">邮箱地址</label>
                                <input type="email" name="email" placeholder="请输入邮箱地址" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">密码 *</label>
                                <input type="password" name="password" placeholder="请输入密码（至少6位）" 
                                       class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">确认密码 *</label>
                                <input type="password" name="confirm_password" placeholder="请再次输入密码" 
                                       class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" checked>
                            <label class="form-check-label" for="isActive">启用账户</label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary" onclick="hideAddForm()">
                            <i class="fas fa-times me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>添加管理员
                        </button>
                    </div>
                </form>
            </div>

            <!-- 管理员列表 -->
            <div class="form-container">
                <h3 class="section-title">管理员列表</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>最后更新</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">暂无管理员</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                            <span class="current-user">当前用户</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['email'] ?? '未设置'); ?></td>
                                    <td>
                                        <?php if ($admin['is_active']): ?>
                                            <span class="status-active">启用</span>
                                        <?php else: ?>
                                            <span class="status-inactive">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($admin['updated_at'])); ?></td>
                                    <td>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <?php if ($admin['is_active']): ?>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="toggleStatus(<?php echo $admin['id']; ?>, 0)">
                                                    <i class="fas fa-ban me-1"></i>禁用
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="toggleStatus(<?php echo $admin['id']; ?>, 1)">
                                                    <i class="fas fa-check me-1"></i>启用
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteAdmin(<?php echo $admin['id']; ?>)">
                                                <i class="fas fa-trash me-1"></i>删除
                                            </button>
                                        <?php else: ?>
                                            <span class="current-user">当前用户</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
<script src="../../assets/js/mobile-admin.js"></script>
</body>
</html>