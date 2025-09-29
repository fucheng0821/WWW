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
$page_title = "联系信息设置";

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // 处理文本表单字段
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $config_key = substr($key, 7); // 去掉 'config_' 前缀
                $config_value = trim($value);
                
                // 更新配置
                $stmt = $db->prepare("UPDATE config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                $stmt->execute([$config_value, $config_key]);
            }
        }
        
        // 处理微信二维码文件上传
        if (isset($_FILES['config_wechat_qr']) && $_FILES['config_wechat_qr']['error'] === UPLOAD_ERR_OK) {
            // 确保uploads目录存在
            $upload_dir = '../../../uploads/images/qrcodes';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // 获取文件信息
            $file_ext = strtolower(pathinfo($_FILES['config_wechat_qr']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            
            if (in_array($file_ext, $allowed_exts)) {
                // 生成唯一文件名
                $filename = 'wechat_qr_' . time() . '.' . $file_ext;
                $filepath = $upload_dir . '/' . $filename;
                
                if (move_uploaded_file($_FILES['config_wechat_qr']['tmp_name'], $filepath)) {
                    // 保存文件路径到配置（添加前导斜杠确保从网站根目录开始）
                    $file_url = '/uploads/images/qrcodes/' . $filename;
                    
                    // 检查是否已存在wechat_qr配置
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM config WHERE config_key = ?");
                    $check_stmt->execute(['wechat_qr']);
                    $exists = $check_stmt->fetchColumn() > 0;
                    
                    if ($exists) {
                        // 更新现有配置
                        $update_stmt = $db->prepare("UPDATE config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                        $update_stmt->execute([$file_url, 'wechat_qr']);
                    } else {
                        // 插入新配置
                        $insert_stmt = $db->prepare("INSERT INTO config (config_key, config_value, config_group, config_type, config_title, config_description, sort_order, is_required, is_system, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $insert_stmt->execute(['wechat_qr', $file_url, 'contact', 'image', '微信二维码', '网站显示的微信二维码图片', 8, 0, 0]);
                    }
                } else {
                    throw new Exception('文件上传失败');
                }
            } else {
                throw new Exception('不支持的文件类型，仅支持jpg、jpeg、png、gif、svg');
            }
        }
        
        $db->commit();
        $success_message = '联系信息更新成功！';
        
    } catch(Exception $e) {
        $db->rollBack();
        $error_message = '更新失败：' . $e->getMessage();
    }
}

// 获取联系信息配置
try {
    $stmt = $db->prepare("SELECT * FROM config WHERE config_group = ? ORDER BY sort_order ASC");
    $stmt->execute(['contact']);
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
    <title>联系我们设置 - 移动管理后台</title>
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
                <h1>联系我们设置</h1>
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
                <h3 class="section-title">联系信息设置</h3>
                
                <?php if (empty($configs)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
                        <h5>暂无联系信息配置</h5>
                        <p class="text-muted">请先初始化配置表</p>
                    </div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
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
                                              rows="3"
                                              <?php echo $config['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($config['config_value']); ?></textarea>
                                <?php else: ?>
                                    <input type="text" 
                                           name="config_<?php echo $config['config_key']; ?>" 
                                           class="form-control"
                                           value="<?php echo htmlspecialchars($config['config_value']); ?>"
                                           <?php echo $config['is_required'] ? 'required' : ''; ?>>
                                <?php endif; ?>
                                
                                <?php if ($config['config_description']): ?>
                                    <div class="form-text">
                                        <?php echo htmlspecialchars($config['config_description']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- 微信二维码上传 -->
                        <div class="mb-3">
                            <label class="form-label">微信二维码</label>
                            
                            <!-- 先尝试获取当前微信二维码配置 -->
                            <?php 
                            $wechat_qr_config = null;
                            try {
                                $stmt = $db->prepare("SELECT config_value FROM config WHERE config_key = ?");
                                $stmt->execute(['wechat_qr']);
                                $result = $stmt->fetch();
                                if ($result) {
                                    $wechat_qr_config = $result['config_value'];
                                }
                            } catch(Exception $e) {
                                // 忽略错误
                            }
                            ?>
                            
                            <!-- 文件上传组件 -->
                            <div class="mb-2">
                                <input type="file" 
                                       name="config_wechat_qr" 
                                       accept=".jpg,.jpeg,.png,.gif,.svg" 
                                       class="form-control">
                                <div class="form-text">
                                    支持jpg、jpeg、png、gif、svg格式，建议尺寸为200×200像素
                                </div>
                            </div>
                            
                            <!-- 当前二维码预览 -->
                            <div class="wechat-qr-preview mt-2">
                                <?php if (!empty($wechat_qr_config)): ?>
                                    <img src="<?php echo htmlspecialchars($wechat_qr_config); ?>" alt="微信二维码">
                                    <p class="form-text">当前微信二维码</p>
                                <?php else: ?>
                                    <div class="border border-dashed border-secondary rounded p-3 text-center">
                                        <p class="text-muted mb-0">暂未上传微信二维码</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>返回
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>保存联系信息
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-eye me-2"></i>联系信息预览</h5>
            </div>
            <div class="card-body">
                <div class="contact-preview">
                    <?php
                    $contact_data = [];
                    foreach ($configs as $config) {
                        $contact_data[$config['config_key']] = $config['config_value'];
                    }
                    ?>
                    
                    <div class="contact-item">
                        <i class="fas fa-home"></i>
                        <div class="contact-info">
                            <strong><?php echo htmlspecialchars($contact_data['contact_company'] ?? '公司名称'); ?></strong>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="contact-info">
                            <?php echo nl2br(htmlspecialchars($contact_data['contact_address'] ?? '公司地址')); ?>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div class="contact-info">
                            <a href="tel:<?php echo $contact_data['contact_phone'] ?? ''; ?>">
                                <?php echo htmlspecialchars($contact_data['contact_phone'] ?? '联系电话'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="contact-info">
                            <a href="tel:<?php echo $contact_data['contact_mobile'] ?? ''; ?>">
                                <?php echo htmlspecialchars($contact_data['contact_mobile'] ?? '手机号码'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div class="contact-info">
                            <a href="mailto:<?php echo $contact_data['contact_email'] ?? ''; ?>">
                                <?php echo htmlspecialchars($contact_data['contact_email'] ?? '邮箱地址'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fab fa-qq"></i>
                        <div class="contact-info">
                            QQ: <?php echo htmlspecialchars($contact_data['contact_qq'] ?? 'QQ号码'); ?>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fab fa-weixin"></i>
                        <div class="contact-info">
                            微信: <?php echo htmlspecialchars($contact_data['contact_wechat'] ?? '微信号'); ?>
                        </div>
                    </div>
                    
                    <!-- 微信二维码预览 -->
                    <?php if (!empty($wechat_qr_config)): ?>
                    <div class="contact-item pt-3">
                        <i class="fab fa-weixin"></i>
                        <div class="contact-info">
                            <p class="mb-2">微信二维码:</p>
                            <img src="<?php echo htmlspecialchars($wechat_qr_config); ?>" style="max-width: 100px; max-height: 100px; border: 1px solid #eee;" alt="微信二维码">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/mobile-admin.js"></script>
</body>
</html>