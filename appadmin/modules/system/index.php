<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取系统配置统计
try {
    // 检查config表是否存在
    $table_check = $db->query("SHOW TABLES LIKE 'config'");
    $config_table_exists = $table_check->rowCount() > 0;
    
    if ($config_table_exists) {
        $config_stmt = $db->query("SELECT COUNT(*) as count FROM config");
        $config_count = $config_stmt->fetch()['count'];
    } else {
        $config_count = 0;
    }
    
    // 检查admins表统计
    $admin_stmt = $db->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM admins");
    $admin_stats = $admin_stmt->fetch();
    
} catch(PDOException $e) {
    $config_count = 0;
    $admin_stats = ['total' => 0, 'active' => 0];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>系统设置 - 移动管理后台</title>
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
                <h1>系统设置</h1>
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
            <!-- 统计卡片 -->
            <div class="dashboard-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $config_count; ?></h3>
                            <p>配置项数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $admin_stats['total']; ?></h3>
                            <p>管理员总数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $admin_stats['active']; ?></h3>
                            <p>活跃管理员</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-red">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo date('m-d'); ?></h3>
                            <p>今日日期</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 设置选项 -->
            <div class="data-table">
                <div class="table-container">
                    <!-- 基本设置 -->
                    <div class="setting-section">
                        <h3>基本设置</h3>
                        <div class="setting-item">
                            <a href="basic.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>网站基本信息</h4>
                                    <p>网站名称、描述、关键词等</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="seo.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>SEO设置</h4>
                                    <p>搜索引擎优化配置</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="contact.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>联系信息</h4>
                                    <p>公司联系方式设置</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="mail.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>邮件设置</h4>
                                    <p>邮件服务器配置</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 管理员设置 -->
                    <div class="setting-section">
                        <h3>管理员设置</h3>
                        <div class="setting-item">
                            <a href="admin.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>管理员管理</h4>
                                    <p>添加、编辑管理员账户</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="profile.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>个人资料</h4>
                                    <p>修改个人信息和密码</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="preferences.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>个性化设置</h4>
                                    <p>自定义界面主题和布局</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 数据备份 -->
                    <div class="setting-section">
                        <h3>数据管理</h3>
                        <div class="setting-item">
                            <a href="backup.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>数据备份</h4>
                                    <p>数据库备份与恢复</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="full_backup.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-file-archive"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>完整备份</h4>
                                    <p>网站文件和数据完整备份</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 平台配置 -->
                    <div class="setting-section">
                        <h3>平台配置</h3>
                        <div class="setting-item">
                            <a href="platform_config.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>平台配置管理</h4>
                                    <p>第三方平台发布配置</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                        
                        <div class="setting-item">
                            <a href="publish_logs.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>发布日志</h4>
                                    <p>查看内容发布记录</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 用户反馈 -->
                    <div class="setting-section">
                        <h3>用户反馈</h3>
                        <div class="setting-item">
                            <a href="feedback.php" class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>意见反馈</h4>
                                    <p>提交使用反馈和建议</p>
                                </div>
                                <div class="setting-arrow">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/mobile-admin.js"></script>
    <script>
        // 侧边栏功能
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.add('open');
            document.getElementById('overlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            document.body.style.overflow = '';
        });
        
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            document.body.style.overflow = '';
        });
    </script>
</body>
</html>