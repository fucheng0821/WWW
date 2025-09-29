<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取发布日志
try {
    // 创建发布日志表（如果不存在）
    $db->exec("CREATE TABLE IF NOT EXISTS publish_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_id INT NOT NULL,
        platform VARCHAR(50) NOT NULL,
        status ENUM('success', 'failed') NOT NULL,
        message TEXT,
        published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_content_id (content_id),
        INDEX idx_platform (platform)
    )");
    
    // 获取发布日志列表
    $stmt = $db->prepare("SELECT pl.*, c.title as content_title FROM publish_logs pl LEFT JOIN contents c ON pl.content_id = c.id ORDER BY pl.published_at DESC LIMIT 50");
    $stmt->execute();
    $logs = $stmt->fetchAll();
} catch(PDOException $e) {
    $logs = [];
    error_log("获取发布日志失败: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>发布日志 - 移动管理后台</title>
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
                <h1>发布日志</h1>
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
                    <li class="menu-item">
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
                    <li class="menu-item active">
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
                <h1>发布日志</h1>
                <p>内容发布记录</p>
            </div>
            
            <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <p>暂无发布记录</p>
            </div>
            <?php else: ?>
            <div class="data-table">
                <div class="table-container">
                    <?php foreach ($logs as $log): ?>
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <h4><?php echo htmlspecialchars($log['content_title'] ?? '未知内容'); ?></h4>
                                <p>
                                    <strong>平台:</strong> <?php echo htmlspecialchars($log['platform']); ?> | 
                                    <strong>时间:</strong> <?php echo format_date($log['published_at']); ?>
                                </p>
                                <?php if (!empty($log['message'])): ?>
                                <p><strong>消息:</strong> <?php echo htmlspecialchars($log['message']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="row-meta">
                                <span class="status-badge <?php echo $log['status'] == 'success' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $log['status'] == 'success' ? '成功' : '失败'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-actions" style="margin-top: 20px;">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回系统设置
                </a>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/mobile-admin.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
</body>
</html>