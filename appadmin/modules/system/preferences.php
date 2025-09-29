<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

check_admin_auth();

// 获取当前用户ID
$admin_id = $_SESSION['admin_id'] ?? 0;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'default';
    $layout = $_POST['layout'] ?? 'default';
    $language = $_POST['language'] ?? 'zh-CN';
    
    try {
        // 检查是否已存在偏好设置
        $stmt = $db->prepare("SELECT id FROM admin_preferences WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $preference = $stmt->fetch();
        
        if ($preference) {
            // 更新现有设置
            $stmt = $db->prepare("UPDATE admin_preferences SET theme = ?, layout = ?, language = ? WHERE admin_id = ?");
            $stmt->execute([$theme, $layout, $language, $admin_id]);
        } else {
            // 创建新设置
            $stmt = $db->prepare("INSERT INTO admin_preferences (admin_id, theme, layout, language) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, $theme, $layout, $language]);
        }
        
        // 保存到会话
        $_SESSION['admin_theme'] = $theme;
        $_SESSION['admin_layout'] = $layout;
        $_SESSION['admin_language'] = $language;
        
        $message = '设置已保存';
        $message_type = 'success';
    } catch(PDOException $e) {
        $message = '保存设置失败: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// 获取当前用户的偏好设置
try {
    $stmt = $db->prepare("SELECT theme, layout, language FROM admin_preferences WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $preferences = $stmt->fetch();
    
    if (!$preferences) {
        $preferences = [
            'theme' => 'default',
            'layout' => 'default',
            'language' => 'zh-CN'
        ];
    }
} catch(PDOException $e) {
    $preferences = [
        'theme' => 'default',
        'layout' => 'default',
        'language' => 'zh-CN'
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>个性化设置 - 移动管理后台</title>
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
                <h1>个性化设置</h1>
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
                <h1>个性化设置</h1>
                <p>自定义您的管理后台界面</p>
            </div>
            
            <?php if (isset($message)): ?>
            <div class="message-toast <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo $message; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>主题风格</label>
                        <select name="theme" class="form-control">
                            <option value="default" <?php echo $preferences['theme'] === 'default' ? 'selected' : ''; ?>>默认主题</option>
                            <option value="dark" <?php echo $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>深色主题</option>
                            <option value="blue" <?php echo $preferences['theme'] === 'blue' ? 'selected' : ''; ?>>蓝色主题</option>
                            <option value="green" <?php echo $preferences['theme'] === 'green' ? 'selected' : ''; ?>>绿色主题</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>布局模式</label>
                        <select name="layout" class="form-control">
                            <option value="default" <?php echo $preferences['layout'] === 'default' ? 'selected' : ''; ?>>默认布局</option>
                            <option value="compact" <?php echo $preferences['layout'] === 'compact' ? 'selected' : ''; ?>>紧凑布局</option>
                            <option value="spacious" <?php echo $preferences['layout'] === 'spacious' ? 'selected' : ''; ?>>宽松布局</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>语言</label>
                        <select name="language" class="form-control">
                            <option value="zh-CN" <?php echo $preferences['language'] === 'zh-CN' ? 'selected' : ''; ?>>简体中文</option>
                            <option value="en" <?php echo $preferences['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 保存设置
                        </button>
                        <button type="reset" class="btn-secondary">
                            <i class="fas fa-undo"></i> 重置
                        </button>
                    </div>
                </form>
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
    </script>
</body>
</html>