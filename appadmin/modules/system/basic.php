<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// 初始化数据库连接
$db = Database::getInstance()->getConnection();
// 检查登录状态
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// 页面标题
$page_title = "基本设置";

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    $site_url = $_POST['site_url'] ?? '';
    $site_description = $_POST['site_description'] ?? '';
    $site_keywords = $_POST['site_keywords'] ?? '';
    $icp_number = $_POST['icp_number'] ?? '';
    $analytics_code = $_POST['analytics_code'] ?? '';
    
    // 更新配置
    set_config('site_name', $site_name);
    set_config('site_url', $site_url);
    set_config('site_description', $site_description);
    set_config('site_keywords', $site_keywords);
    set_config('icp_number', $icp_number);
    set_config('analytics_code', $analytics_code);
    
    $success_message = "设置已保存";
}

// 获取当前配置
$site_name = get_config('site_name');
$site_url = get_config('site_url');
$site_description = get_config('site_description');
$site_keywords = get_config('site_keywords');
$icp_number = get_config('icp_number');
$analytics_code = get_config('analytics_code');
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> - 移动管理后台</title>
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
                <h1>基本设置</h1>
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
                <h3 class="section-title">网站基本信息</h3>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="site_name" class="form-label">网站名称 *</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                        <div class="form-text">网站的名称，将显示在网站标题中</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_url" class="form-label">网站URL *</label>
                        <input type="url" class="form-control" id="site_url" name="site_url" value="<?php echo htmlspecialchars($site_url); ?>" required>
                        <div class="form-text">网站的完整URL地址，以http://或https://开头</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">网站描述</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($site_description); ?></textarea>
                        <div class="form-text">网站的简短描述，有助于SEO优化</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_keywords" class="form-label">网站关键词</label>
                        <input type="text" class="form-control" id="site_keywords" name="site_keywords" value="<?php echo htmlspecialchars($site_keywords); ?>">
                        <div class="form-text">网站关键词，多个关键词用逗号分隔</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icp_number" class="form-label">ICP备案号</label>
                        <input type="text" class="form-control" id="icp_number" name="icp_number" value="<?php echo htmlspecialchars($icp_number); ?>">
                        <div class="form-text">网站的ICP备案号，将显示在网站底部</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="analytics_code" class="form-label">统计代码</label>
                        <textarea class="form-control" id="analytics_code" name="analytics_code" rows="4"><?php echo htmlspecialchars($analytics_code); ?></textarea>
                        <div class="form-text">网站统计代码，如百度统计、Google Analytics等</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>保存设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/mobile-admin.js"></script>
</body>
</html>