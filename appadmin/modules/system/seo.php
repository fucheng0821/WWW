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
$page_title = "SEO设置";

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $config_key = substr($key, 7); // 去掉 'config_' 前缀
                $config_value = trim($value);
                
                // 更新配置
                $stmt = $db->prepare("UPDATE config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                $stmt->execute([$config_value, $config_key]);
            }
        }
        
        $db->commit();
        $success_message = 'SEO配置更新成功！';
        
    } catch(Exception $e) {
        $db->rollBack();
        $error_message = '更新失败：' . $e->getMessage();
    }
}

// 获取SEO配置
try {
    $stmt = $db->prepare("SELECT * FROM config WHERE config_group = ? ORDER BY sort_order ASC");
    $stmt->execute(['seo']);
    $configs = $stmt->fetchAll();
} catch(Exception $e) {
    $configs = [];
    $error_message = '获取配置失败：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SEO设置 - 移动管理后台</title>
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
                <h1>SEO设置</h1>
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
                <h3 class="section-title">网站SEO设置</h3>
                
                <!-- SEO提示信息 -->
                <div class="info-box">
                    <h5><i class="fas fa-info-circle me-2"></i>SEO优化建议</h5>
                    <ul class="mb-0">
                        <li><strong>标题长度</strong>：建议控制在30-60个字符之间</li>
                        <li><strong>描述长度</strong>：建议控制在120-160个字符之间</li>
                        <li><strong>关键词</strong>：建议3-5个核心关键词，用英文逗号分隔</li>
                        <li><strong>原创性</strong>：确保标题和描述的原创性和相关性</li>
                    </ul>
                </div>
                
                <?php if (empty($configs)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>暂无SEO配置</h5>
                        <p class="text-muted">请先初始化配置表</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                    <?php foreach ($configs as $config): ?>
                        <div class="mb-3">
                            <label class="form-label">
                                <?php echo htmlspecialchars($config['config_title']); ?>
                                <?php if ($config['is_required']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            <?php if ($config['config_type'] === 'textarea'): ?>
                                <textarea name="config_<?php echo $config['config_key']; ?>" 
                                          class="form-control" 
                                          rows="4"
                                          <?php echo $config['is_required'] ? 'required' : ''; ?>
                                          data-config-key="<?php echo $config['config_key']; ?>"><?php echo htmlspecialchars($config['config_value']); ?></textarea>
                            <?php else: ?>
                                <input type="text" 
                                       name="config_<?php echo $config['config_key']; ?>" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($config['config_value']); ?>"
                                       <?php echo $config['is_required'] ? 'required' : ''; ?>
                                       data-config-key="<?php echo $config['config_key']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-text">
                                <?php echo htmlspecialchars($config['config_description']); ?>
                                <span class="char-count" id="count_<?php echo $config['config_key']; ?>">
                                    当前字符数：<?php echo mb_strlen($config['config_value'], 'UTF-8'); ?>
                                    <?php if ($config['config_key'] === 'seo_title'): ?>
                                        （建议30-60字符）
                                    <?php elseif ($config['config_key'] === 'seo_description'): ?>
                                        （建议120-160字符）
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>保存设置
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
<script src="../../assets/js/mobile-admin.js"></script>
</body>
</html>