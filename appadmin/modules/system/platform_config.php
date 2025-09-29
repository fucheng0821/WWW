<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platform = $_POST['platform'] ?? '';
    $config_data = $_POST['config_data'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!empty($platform)) {
        try {
            // 检查是否已存在配置
            $stmt = $db->prepare("SELECT id FROM platform_configs WHERE platform = ?");
            $stmt->execute([$platform]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 更新配置
                $stmt = $db->prepare("UPDATE platform_configs SET config_data = ?, is_active = ?, updated_at = NOW() WHERE platform = ?");
                $stmt->execute([$config_data, $is_active, $platform]);
            } else {
                // 创建新配置
                $stmt = $db->prepare("INSERT INTO platform_configs (platform, config_data, is_active, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$platform, $config_data, $is_active]);
            }
            
            $message = '平台配置保存成功';
            $message_type = 'success';
        } catch(PDOException $e) {
            $message = '保存配置失败: ' . $e->getMessage();
            $message_type = 'error';
            error_log($message);
        }
    }
}

// 获取现有配置
try {
    // 创建平台配置表（如果不存在）
    $db->exec("CREATE TABLE IF NOT EXISTS platform_configs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(50) NOT NULL UNIQUE,
        config_data TEXT,
        is_active TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // 获取所有配置
    $stmt = $db->query("SELECT * FROM platform_configs ORDER BY platform");
    $configs = $stmt->fetchAll();
} catch(PDOException $e) {
    $configs = [];
    error_log("获取平台配置失败: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>平台配置管理 - 移动管理后台</title>
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
                <h1>平台配置管理</h1>
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
                <h1>平台配置管理</h1>
                <p>第三方平台发布配置</p>
            </div>
            
            <?php if (isset($message)): ?>
            <div class="message-toast <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="data-table">
                <div class="table-container">
                    <?php if (empty($configs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-cogs"></i>
                        <p>暂无平台配置</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($configs as $config): ?>
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <h4><?php echo htmlspecialchars(strtoupper($config['platform'])); ?></h4>
                                <p>配置状态: 
                                    <span class="status-badge <?php echo $config['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $config['is_active'] ? '已启用' : '已禁用'; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="row-meta">
                                <span>更新时间: <?php echo format_date($config['updated_at']); ?></span>
                            </div>
                        </div>
                        <div class="row-actions">
                            <a href="javascript:void(0);" class="action-btn edit" onclick="editConfig('<?php echo $config['platform']; ?>')">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 20px;">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回系统设置
                </a>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/mobile-admin.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    // 消息提示自动隐藏
    document.addEventListener('DOMContentLoaded', function() {
        const messageToast = document.querySelector('.message-toast');
        if (messageToast) {
            setTimeout(() => {
                messageToast.style.opacity = '0';
                setTimeout(() => {
                    messageToast.remove();
                }, 300);
            }, 3000);
        }
    });
    
    // 编辑配置功能（简化版）
    function editConfig(platform) {
        alert('编辑平台配置: ' + platform + '\n\n在实际应用中，这里会打开一个编辑表单。');
    }
    </script>
</body>
</html>