<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

check_admin_auth();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback_type = $_POST['feedback_type'] ?? '';
    $feedback_content = $_POST['feedback_content'] ?? '';
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $admin_name = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '未知用户';
    
    if (!empty($feedback_content)) {
        try {
            // 创建反馈表（如果不存在）
            $db->exec("CREATE TABLE IF NOT EXISTS admin_feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                admin_name VARCHAR(100) NOT NULL,
                feedback_type VARCHAR(50) NOT NULL,
                feedback_content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'processing', 'resolved') DEFAULT 'pending'
            )");
            
            // 插入反馈
            $stmt = $db->prepare("INSERT INTO admin_feedback (admin_id, admin_name, feedback_type, feedback_content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, $admin_name, $feedback_type, $feedback_content]);
            
            $message = '感谢您的反馈！我们会认真考虑您的建议。';
            $message_type = 'success';
        } catch(PDOException $e) {
            $message = '提交反馈失败: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = '请填写反馈内容';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>用户反馈 - 移动管理后台</title>
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
                <h1>用户反馈</h1>
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
                <h1>用户反馈</h1>
                <p>帮助我们改进产品体验</p>
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
                        <label>反馈类型</label>
                        <select name="feedback_type" class="form-control" required>
                            <option value="">请选择反馈类型</option>
                            <option value="bug">问题报告</option>
                            <option value="feature">功能建议</option>
                            <option value="ui">界面优化</option>
                            <option value="performance">性能优化</option>
                            <option value="other">其他</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>反馈内容</label>
                        <textarea name="feedback_content" class="form-control" rows="6" placeholder="请详细描述您的反馈内容..." required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> 提交反馈
                        </button>
                        <button type="reset" class="btn-secondary">
                            <i class="fas fa-undo"></i> 重置
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="data-table" style="margin-top: 20px;">
                <div class="table-container">
                    <div class="setting-section">
                        <h3>反馈说明</h3>
                        <div class="setting-item">
                            <div class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>感谢您的反馈</h4>
                                    <p>您的意见对我们非常重要，将帮助我们不断改进产品体验。</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>反馈处理</h4>
                                    <p>我们会认真阅读每一条反馈，并在后续版本中考虑您的建议。</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-link">
                                <div class="setting-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="setting-content">
                                    <h4>隐私保护</h4>
                                    <p>您的反馈内容仅用于产品改进，不会泄露给第三方。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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