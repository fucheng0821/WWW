<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取询价ID
$inquiry_id = intval($_GET['id'] ?? 0);

if (empty($inquiry_id)) {
    header("Location: index.php?error=" . urlencode('无效的询价ID'));
    exit();
}

// 获取当前询价信息
try {
    $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = ?");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch();
    
    if (!$inquiry) {
        header("Location: index.php?error=" . urlencode('询价不存在'));
        exit();
    }
} catch(PDOException $e) {
    header("Location: index.php?error=" . urlencode('获取询价信息失败'));
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? 'pending');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    // 验证状态值
    $valid_status = ['pending', 'processing', 'completed'];
    if (!in_array($status, $valid_status)) {
        $status = 'pending';
    }
    
    try {
        // 更新询价状态
        $stmt = $db->prepare("UPDATE inquiries SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $admin_notes, $inquiry_id]);
        
        // 重定向到询价列表页面
        header("Location: index.php?message=" . urlencode('询价状态更新成功'));
        exit();
    } catch(PDOException $e) {
        $error_message = '更新询价状态失败: ' . $e->getMessage();
        error_log($error_message);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>编辑询价 - 移动管理后台</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/mobile-admin.css">
    <link rel="stylesheet" href="../../assets/css/mobile-modules.css">
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
                <h1>编辑询价</h1>
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
                        <a href="../../index.php">
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
                    <li class="menu-item active">
                        <a href="../inquiry/">
                            <i class="fas fa-comment"></i>
                            <span>询价管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../template/">
                            <i class="fas fa-paint-brush"></i>
                            <span>模板管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../system/">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>安全退出</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- 遮罩层 -->
        <div class="overlay" id="overlay"></div>
        
        <!-- 主要内容区域 -->
        <div class="mobile-main">
            <div class="module-header">
                <h1>编辑询价</h1>
                <p>更新询价状态和备注</p>
            </div>
            
            <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>询价信息</label>
                        <div class="form-control" style="background-color: #f8f9fa;">
                            <p><strong>姓名:</strong> <?php echo htmlspecialchars($inquiry['name']); ?></p>
                            <p><strong>电话:</strong> <?php echo htmlspecialchars($inquiry['phone']); ?></p>
                            <p><strong>邮箱:</strong> <?php echo htmlspecialchars($inquiry['email']); ?></p>
                            <p><strong>服务类型:</strong> <?php echo htmlspecialchars($inquiry['service_type']); ?></p>
                            <p><strong>提交时间:</strong> <?php echo format_date($inquiry['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>询价内容</label>
                        <div class="form-control" style="background-color: #f8f9fa; min-height: 100px;">
                            <?php echo nl2br(htmlspecialchars($inquiry['message'] ?? '')); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>状态</label>
                        <select name="status" class="form-control">
                            <option value="pending" <?php echo ($inquiry['status'] == 'pending') ? 'selected' : ''; ?>>待处理</option>
                            <option value="processing" <?php echo ($inquiry['status'] == 'processing') ? 'selected' : ''; ?>>处理中</option>
                            <option value="completed" <?php echo ($inquiry['status'] == 'completed') ? 'selected' : ''; ?>>已完成</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>管理员备注</label>
                        <textarea name="admin_notes" class="form-control" rows="4"><?php echo htmlspecialchars($inquiry['admin_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 更新状态
                        </button>
                        <a href="index.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/mobile-admin.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
</body>
</html>