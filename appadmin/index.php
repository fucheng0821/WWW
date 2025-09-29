<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

check_admin_auth();

// 获取系统统计信息
try {
    $stats = [];
    
    // 栏目总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM categories");
    $stmt->execute();
    $stats['categories'] = $stmt->fetch()['total'];
    
    // 内容总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contents");
    $stmt->execute();
    $stats['contents'] = $stmt->fetch()['total'];
    
    // 已发布内容数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contents WHERE is_published = 1");
    $stmt->execute();
    $stats['published_contents'] = $stmt->fetch()['total'];
    
    // 询价总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inquiries");
    $stmt->execute();
    $stats['inquiries'] = $stmt->fetch()['total'];
    
    // 待处理询价数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inquiries WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_inquiries'] = $stmt->fetch()['total'];
    
    // 今日新增内容
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contents WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['today_contents'] = $stmt->fetch()['total'];
    
    // 今日新增询价
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inquiries WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['today_inquiries'] = $stmt->fetch()['total'];
    
    // 最新内容
    $stmt = $db->prepare("SELECT c.id, c.title, c.created_at, cat.name as category_name 
                          FROM contents c 
                          LEFT JOIN categories cat ON c.category_id = cat.id 
                          ORDER BY c.created_at DESC LIMIT 5");
    $stmt->execute();
    $latest_contents = $stmt->fetchAll();
    
    // 最新询价
    $stmt = $db->prepare("SELECT id, name, service_type, created_at, status FROM inquiries ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $latest_inquiries = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $stats = [];
    $latest_contents = [];
    $latest_inquiries = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>移动管理后台 - <?php echo get_config('site_name', '高光视刻'); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="assets/css/mobile-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/mobile-admin.js"></script>
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
                <h1>管理后台</h1>
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
                    <li class="menu-item active">
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>控制台</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="modules/category/">
                            <i class="fas fa-folder"></i>
                            <span>栏目管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="modules/content/">
                            <i class="fas fa-file-alt"></i>
                            <span>内容管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="modules/inquiry/">
                            <i class="fas fa-comment"></i>
                            <span>询价管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="modules/template/">
                            <i class="fas fa-paint-brush"></i>
                            <span>模板管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="modules/system/">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="logout.php">
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
            <div class="dashboard-stats">
                <h2 class="section-title">数据统计</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['categories'] ?? 0; ?></h3>
                            <p>栏目总数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['contents'] ?? 0; ?></h3>
                            <p>内容总数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['inquiries'] ?? 0; ?></h3>
                            <p>询价总数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-red">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pending_inquiries'] ?? 0; ?></h3>
                            <p>待处理询价</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2 class="section-title">快速操作</h2>
                <div class="actions-grid">
                    <a href="modules/content/add.php" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>添加内容</span>
                    </a>
                    <a href="modules/category/add.php" class="action-card">
                        <i class="fas fa-folder-plus"></i>
                        <span>添加栏目</span>
                    </a>
                    <a href="modules/inquiry/" class="action-card">
                        <i class="fas fa-comments"></i>
                        <span>查看询价</span>
                    </a>
                    <a href="modules/system/" class="action-card">
                        <i class="fas fa-cogs"></i>
                        <span>系统设置</span>
                    </a>
                </div>
            </div>
            
            <div class="recent-activity">
                <div class="activity-section">
                    <h2>最新内容</h2>
                    <div class="activity-list">
                        <?php if (empty($latest_contents)): ?>
                            <p class="no-data">暂无内容</p>
                        <?php else: ?>
                            <?php foreach ($latest_contents as $content): ?>
                            <div class="activity-item">
                                <div class="item-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="item-content">
                                    <h4>
                                        <a href="modules/content/edit.php?id=<?php echo $content['id']; ?>">
                                            <?php echo truncate_string($content['title'], 30); ?>
                                        </a>
                                    </h4>
                                    <p class="item-meta">
                                        <span class="category"><?php echo $content['category_name'] ?? '未分类'; ?></span>
                                        <span class="time"><?php echo format_date($content['created_at'], 'm-d H:i'); ?></span>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="activity-section">
                    <h2>最新询价</h2>
                    <div class="activity-list">
                        <?php if (empty($latest_inquiries)): ?>
                            <p class="no-data">暂无询价</p>
                        <?php else: ?>
                            <?php foreach ($latest_inquiries as $inquiry): ?>
                            <div class="activity-item">
                                <div class="item-icon">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div class="item-content">
                                    <h4>
                                        <a href="modules/inquiry/view.php?id=<?php echo $inquiry['id']; ?>">
                                            <?php echo $inquiry['name']; ?>
                                        </a>
                                    </h4>
                                    <p class="item-meta">
                                        <span class="service"><?php echo $inquiry['service_type']; ?></span>
                                        <span class="time"><?php echo format_date($inquiry['created_at'], 'm-d H:i'); ?></span>
                                    </p>
                                    <div class="item-status">
                                        <?php 
                                        $status_map = ['pending' => '待处理', 'processing' => '处理中', 'completed' => '已完成'];
                                        $status_class = ['pending' => 'status-pending', 'processing' => 'status-processing', 'completed' => 'status-completed'];
                                        echo '<span class="status-badge ' . ($status_class[$inquiry['status']] ?? '') . '">' . ($status_map[$inquiry['status']] ?? $inquiry['status']) . '</span>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
        
        // 初始化导航
        element.render();
        
        // 页面加载完成提示
        layer.msg('移动后台加载完成', {icon: 1, time: 1000});
    });
    </script>
</body>
</html>