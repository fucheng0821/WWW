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
$page_title = "首页设置";

$errors = [];
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // 更新首页视频设置
        $homepage_video = $_POST['homepage_video'] ?? '';
        $homepage_video_poster = $_POST['homepage_video_poster'] ?? '';
        
        // 保存到系统配置表
        $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value, config_group, description) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->execute([
            'homepage_video', 
            $homepage_video, 
            'homepage', 
            '首页视频地址',
            $homepage_video
        ]);
        
        $stmt->execute([
            'homepage_video_poster', 
            $homepage_video_poster, 
            'homepage', 
            '首页视频封面图',
            $homepage_video_poster
        ]);
        
        // 更新"为什么选择我们"板块的配置
        $features = [];
        for ($i = 1; $i <= 4; $i++) {
            $feature = [
                'title' => $_POST["feature_{$i}_title"] ?? '',
                'description' => $_POST["feature_{$i}_description"] ?? '',
                'image' => $_POST["feature_{$i}_image"] ?? ''
            ];
            $features[] = $feature;
        }
        
        // 保存到系统配置表
        $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value, config_group, description) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->execute([
            'homepage_features', 
            json_encode($features), 
            'homepage', 
            '首页为什么选择我们板块配置',
            json_encode($features)
        ]);
        
        // 更新合作伙伴图片配置
        $partners = [];
        for ($i = 1; $i <= 6; $i++) {
            $partner = [
                'image' => $_POST["partner_{$i}_image"] ?? ''
            ];
            $partners[] = $partner;
        }
        
        // 保存到系统配置表
        $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value, config_group, description) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->execute([
            'homepage_partners', 
            json_encode($partners), 
            'homepage', 
            '首页合作伙伴图片配置',
            json_encode($partners)
        ]);
        
        $db->commit();
        $success = '首页设置更新成功！';
        
    } catch(Exception $e) {
        $db->rollBack();
        $errors[] = '更新失败：' . $e->getMessage();
    }
}

// 获取首页视频设置
try {
    $stmt = $db->prepare("SELECT config_key, config_value FROM system_config WHERE config_key IN (?, ?)");
    $stmt->execute(['homepage_video', 'homepage_video_poster']);
    $video_configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $homepage_video = $video_configs['homepage_video'] ?? '';
    $homepage_video_poster = $video_configs['homepage_video_poster'] ?? '';
} catch(Exception $e) {
    $errors[] = '获取视频配置失败：' . $e->getMessage();
    $homepage_video = '';
    $homepage_video_poster = '';
}

// 获取"为什么选择我们"板块的配置
try {
    $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    $stmt->execute(['homepage_features']);
    $result = $stmt->fetch();
    
    if ($result && $result['config_value']) {
        $features = json_decode($result['config_value'], true);
    } else {
        // 默认配置
        $features = [
            [
                'title' => '专业团队',
                'description' => '资深设计师和制作团队，为您提供专业的创意服务',
                'image' => 'https://picsum.photos/600/400?random=1'
            ],
            [
                'title' => '一流设备',
                'description' => '专业的拍摄设备和制作软件，保证作品的高品质',
                'image' => 'https://picsum.photos/600/400?random=2'
            ],
            [
                'title' => '按时交付',
                'description' => '严格的项目管理流程，确保按时按质完成项目',
                'image' => 'https://picsum.photos/600/400?random=3'
            ],
            [
                'title' => '贴心服务',
                'description' => '全程跟踪服务，及时沟通，让您省心放心',
                'image' => 'https://picsum.photos/600/400?random=4'
            ]
        ];
    }
} catch(Exception $e) {
    $errors[] = '获取配置失败：' . $e->getMessage();
    $features = [];
}

// 获取合作伙伴图片配置
try {
    $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    $stmt->execute(['homepage_partners']);
    $result = $stmt->fetch();
    
    if ($result && $result['config_value']) {
        $partners = json_decode($result['config_value'], true);
    } else {
        // 默认配置
        $partners = [
            ['image' => 'https://picsum.photos/300/150?random=1'],
            ['image' => 'https://picsum.photos/300/150?random=2'],
            ['image' => 'https://picsum.photos/300/150?random=3'],
            ['image' => 'https://picsum.photos/300/150?random=4'],
            ['image' => 'https://picsum.photos/300/150?random=5'],
            ['image' => 'https://picsum.photos/300/150?random=6']
        ];
    }
} catch(Exception $e) {
    $errors[] = '获取合作伙伴配置失败：' . $e->getMessage();
    $partners = [];
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            background-color: #fafafa;
        }
        .upload-area i {
            font-size: 2rem;
            color: #999;
            margin-bottom: 10px;
        }
        .upload-area:hover {
            border-color: #009688;
            background-color: #f0f9f8;
        }
        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        .preview-video {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .feature-card, .partner-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .layui-input, .layui-textarea {
            font-size: 0.9rem;
        }
        .layui-form-label {
            width: 80px;
            padding: 8px 0;
        }
        .layui-input-block {
            margin-left: 100px;
        }
    </style>
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
                <h1><?php echo $page_title; ?></h1>
            </div>
            <div class="header-right">
                <a href="index.php" class="layui-btn layui-btn-primary layui-btn-xs">
                    <i class="fas fa-arrow-left"></i>
                </a>
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
            <?php if (!empty($errors)): ?>
            <div class="layui-alert layui-alert-danger" style="margin: 10px;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="layui-alert layui-alert-success" style="margin: 10px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <form class="layui-form" method="POST" style="padding: 15px;">
                <!-- 首页视频设置 -->
                <div class="section-title">首页视频设置</div>
                
                <div class="feature-card">
                    <div class="layui-form-item">
                        <label class="layui-form-label">视频文件</label>
                        <div class="layui-input-block">
                            <div class="upload-area" id="videoUpload">
                                <i class="fas fa-video"></i>
                                <div>点击上传视频</div>
                                <div class="layui-word-aux">支持 MP4 格式，大小不超过 100MB</div>
                            </div>
                            <input type="hidden" 
                                   name="homepage_video" 
                                   id="homepage_video" 
                                   value="<?php echo htmlspecialchars($homepage_video); ?>" 
                                   class="layui-input">
                            <!-- 手动输入视频URL的输入框 -->
                            <div style="margin-top: 10px;">
                                <input type="text" 
                                       id="homepage_video_url" 
                                       placeholder="或直接输入视频URL" 
                                       value="<?php echo htmlspecialchars($homepage_video); ?>" 
                                       class="layui-input"
                                       onchange="updateVideoValue(this.value)">
                            </div>
                            <?php if (!empty($homepage_video)): ?>
                                <div style="margin-top: 10px;" id="videoPreview">
                                    <video controls class="preview-video">
                                        <source src="<?php echo htmlspecialchars($homepage_video); ?>" type="video/mp4">
                                        您的浏览器不支持视频播放。
                                    </video>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">视频封面图</label>
                        <div class="layui-input-block">
                            <div class="upload-area" id="posterUpload">
                                <i class="fas fa-image"></i>
                                <div>点击上传封面图</div>
                                <div class="layui-word-aux">建议尺寸: 1920x1080px，支持 JPG/PNG 格式</div>
                            </div>
                            <input type="hidden" 
                                   name="homepage_video_poster" 
                                   id="homepage_video_poster" 
                                   value="<?php echo htmlspecialchars($homepage_video_poster); ?>" 
                                   class="layui-input">
                            <!-- 手动输入封面图URL的输入框 -->
                            <div style="margin-top: 10px;">
                                <input type="text" 
                                       id="homepage_video_poster_url" 
                                       placeholder="或直接输入封面图URL" 
                                       value="<?php echo htmlspecialchars($homepage_video_poster); ?>" 
                                       class="layui-input"
                                       onchange="updatePosterValue(this.value)">
                            </div>
                            <?php if (!empty($homepage_video_poster)): ?>
                                <div style="margin-top: 10px;" id="posterPreview">
                                    <img src="<?php echo htmlspecialchars($homepage_video_poster); ?>" 
                                         alt="封面图预览" 
                                         class="preview-image">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 为什么选择我们设置 -->
                <div class="section-title">为什么选择我们</div>
                
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="feature-card">
                    <h3>特色 <?php echo $i; ?></h3>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">标题</label>
                        <div class="layui-input-block">
                            <input type="text" 
                                   name="feature_<?php echo $i; ?>_title" 
                                   placeholder="请输入标题" 
                                   value="<?php echo htmlspecialchars($features[$i-1]['title'] ?? ''); ?>" 
                                   class="layui-input" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">描述</label>
                        <div class="layui-input-block">
                            <textarea name="feature_<?php echo $i; ?>_description" 
                                      placeholder="请输入描述" 
                                      class="layui-textarea" 
                                      required><?php echo htmlspecialchars($features[$i-1]['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">图片</label>
                        <div class="layui-input-block">
                            <div class="upload-area" id="imageUpload<?php echo $i; ?>">
                                <i class="fas fa-image"></i>
                                <div>点击上传图片</div>
                                <div class="layui-word-aux">建议尺寸: 600x400px，支持 JPG/PNG/GIF 格式</div>
                            </div>
                            <input type="hidden" 
                                   name="feature_<?php echo $i; ?>_image" 
                                   id="feature_<?php echo $i; ?>_image" 
                                   value="<?php echo htmlspecialchars($features[$i-1]['image'] ?? ''); ?>" 
                                   class="layui-input">
                            <!-- 手动输入图片URL的输入框 -->
                            <div style="margin-top: 10px;">
                                <input type="text" 
                                       id="feature_<?php echo $i; ?>_image_url" 
                                       placeholder="或直接输入图片URL" 
                                       value="<?php echo htmlspecialchars($features[$i-1]['image'] ?? ''); ?>" 
                                       class="layui-input"
                                       onchange="updateImageValue(<?php echo $i; ?>, this.value)">
                            </div>
                            <?php if (!empty($features[$i-1]['image'])): ?>
                                <div style="margin-top: 10px;" id="imagePreview<?php echo $i; ?>">
                                    <?php 
                                    $image_url = $features[$i-1]['image'];
                                    // If it's a relative path, prepend SITE_URL
                                    if (strpos($image_url, 'http') !== 0 && strpos($image_url, '//') !== 0) {
                                        $image_url = SITE_URL . '/' . ltrim($image_url, '/');
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                         alt="预览图片" 
                                         class="preview-image">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
                
                <!-- 合作伙伴设置 -->
                <div class="section-title">合作伙伴</div>
                
                <div class="feature-card">
                    <div class="layui-row layui-col-space10">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="layui-col-xs6">
                            <div class="partner-card">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">合作伙伴 <?php echo $i; ?></label>
                                    <div class="layui-input-block">
                                        <div class="upload-area" id="partnerUpload<?php echo $i; ?>">
                                            <i class="fas fa-image"></i>
                                            <div>点击上传图片</div>
                                            <div class="layui-word-aux">建议尺寸: 300x150px</div>
                                        </div>
                                        <input type="hidden" 
                                               name="partner_<?php echo $i; ?>_image" 
                                               id="partner_<?php echo $i; ?>_image" 
                                               value="<?php echo htmlspecialchars($partners[$i-1]['image'] ?? ''); ?>" 
                                               class="layui-input">
                                        <!-- 手动输入图片URL的输入框 -->
                                        <div style="margin-top: 10px;">
                                            <input type="text" 
                                                   id="partner_<?php echo $i; ?>_image_url" 
                                                   placeholder="或直接输入图片URL" 
                                                   value="<?php echo htmlspecialchars($partners[$i-1]['image'] ?? ''); ?>" 
                                                   class="layui-input"
                                                   onchange="updatePartnerImageValue(<?php echo $i; ?>, this.value)">
                                        </div>
                                        <?php if (!empty($partners[$i-1]['image'])): ?>
                                            <div style="margin-top: 10px;" id="partnerImagePreview<?php echo $i; ?>">
                                                <?php 
                                                $partner_image_url = $partners[$i-1]['image'];
                                                // If it's a relative path, prepend SITE_URL
                                                if (strpos($partner_image_url, 'http') !== 0 && strpos($partner_image_url, '//') !== 0) {
                                                    $partner_image_url = SITE_URL . '/' . ltrim($partner_image_url, '/');
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($partner_image_url); ?>" 
                                                     alt="合作伙伴<?php echo $i; ?>预览" 
                                                     class="preview-image">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button type="submit" class="layui-btn layui-btn-normal layui-btn-fluid">
                            <i class="fas fa-save"></i> 保存设置
                        </button>
                        <a href="index.php" class="layui-btn layui-btn-primary layui-btn-fluid" style="margin-top: 10px;">
                            <i class="fas fa-arrow-left"></i> 返回
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/mobile-admin.js"></script>
    <script>
    // 更新视频值的函数
    function updateVideoValue(value) {
        document.getElementById('homepage_video').value = value;
        // 更新预览视频
        var previewContainer = document.getElementById('videoPreview');
        if (previewContainer) {
            if (value) {
                previewContainer.innerHTML = '<video controls class="preview-video"><source src="' + value + '" type="video/mp4">您的浏览器不支持视频播放。</video>';
            } else {
                previewContainer.innerHTML = '';
            }
        }
    }
    
    // 更新封面图值的函数
    function updatePosterValue(value) {
        document.getElementById('homepage_video_poster').value = value;
        // 更新预览图片
        var previewContainer = document.getElementById('posterPreview');
        if (previewContainer) {
            if (value) {
                previewContainer.innerHTML = '<img src="' + value + '" alt="封面图预览" class="preview-image">';
            } else {
                previewContainer.innerHTML = '';
            }
        }
    }
    
    // 更新图片值的函数
    function updateImageValue(index, value) {
        document.getElementById('feature_' + index + '_image').value = value;
        // 更新预览图片
        var previewContainer = document.getElementById('imagePreview' + index);
        if (previewContainer) {
            if (value) {
                previewContainer.innerHTML = '<img src="' + value + '" alt="预览图片" class="preview-image">';
            } else {
                previewContainer.innerHTML = '';
            }
        }
    }
    
    // 更新合作伙伴图片值的函数
    function updatePartnerImageValue(index, value) {
        document.getElementById('partner_' + index + '_image').value = value;
        // 更新预览图片
        var previewContainer = document.getElementById('partnerImagePreview' + index);
        if (previewContainer) {
            if (value) {
                // Check if the value is a relative path and prepend SITE_URL if needed
                var imageUrl = value;
                if (value.indexOf('http') !== 0 && value.indexOf('//') !== 0) {
                    imageUrl = '<?php echo SITE_URL; ?>/' + value.replace(/^\/+/, '');
                }
                previewContainer.innerHTML = '<img src="' + imageUrl + '" alt="合作伙伴' + index + '预览" class="preview-image">';
            } else {
                previewContainer.innerHTML = '';
            }
        }
    }
    
    layui.use(['form', 'upload'], function(){
        var form = layui.form;
        var upload = layui.upload;
        
        form.render();
        
        // 初始化视频上传
        upload.render({
            elem: '#videoUpload',
            url: '../../modules/content/upload.php',
            accept: 'video',
            exts: 'mp4|avi|mov|wmv|flv|webm|ogg|mkv',
            field: 'file',
            data: {type: 'video'}, // 指定文件类型为视频
            size: 102400, // 100MB
            done: function(res){
                if(res.success || res.location){
                    var videoUrl = res.location || '';
                    // 更新隐藏字段和URL输入框的值
                    document.getElementById('homepage_video').value = videoUrl;
                    document.getElementById('homepage_video_url').value = videoUrl;
                    
                    // 显示预览视频
                    var previewContainer = document.getElementById('videoPreview');
                    if (previewContainer) {
                        previewContainer.innerHTML = '<video controls class="preview-video"><source src="' + videoUrl + '" type="video/mp4">您的浏览器不支持视频播放。</video>';
                    } else {
                        var newPreview = document.createElement('div');
                        newPreview.id = 'videoPreview';
                        newPreview.style.marginTop = '10px';
                        newPreview.innerHTML = '<video controls class="preview-video"><source src="' + videoUrl + '" type="video/mp4">您的浏览器不支持视频播放。</video>';
                        this.item.parentNode.appendChild(newPreview);
                    }
                } else {
                    alert('上传失败：' + (res.message || res.error || '未知错误'));
                }
            },
            error: function(){
                alert('上传失败，请稍后重试');
            }
        });
        
        // 初始化封面图上传
        upload.render({
            elem: '#posterUpload',
            url: '../../modules/content/upload.php',
            accept: 'images',
            acceptMime: 'image/*',
            exts: 'jpg|jpeg|png|gif|webp',
            field: 'file',
            done: function(res){
                if(res.success || res.thumbnail || res.location){
                    var imageUrl = res.location || res.thumbnail || '';
                    // 更新隐藏字段和URL输入框的值
                    document.getElementById('homepage_video_poster').value = imageUrl;
                    document.getElementById('homepage_video_poster_url').value = imageUrl;
                    
                    // 显示预览图片
                    var previewContainer = document.getElementById('posterPreview');
                    if (previewContainer) {
                        previewContainer.innerHTML = '<img src="' + imageUrl + '" alt="封面图预览" class="preview-image">';
                    } else {
                        var newPreview = document.createElement('div');
                        newPreview.id = 'posterPreview';
                        newPreview.style.marginTop = '10px';
                        newPreview.innerHTML = '<img src="' + imageUrl + '" alt="封面图预览" class="preview-image">';
                        this.item.parentNode.appendChild(newPreview);
                    }
                } else {
                    alert('上传失败：' + (res.message || res.error || '未知错误'));
                }
            },
            error: function(){
                alert('上传失败，请稍后重试');
            }
        });
        
        // 为每个特色项初始化图片上传
        <?php for ($i = 1; $i <= 4; $i++): ?>
        upload.render({
            elem: '#imageUpload<?php echo $i; ?>',
            url: '../../modules/content/upload.php',
            accept: 'images',
            acceptMime: 'image/*',
            exts: 'jpg|jpeg|png|gif|webp',
            field: 'file',
            done: function(res){
                if(res.success || res.thumbnail || res.location){
                    var imageUrl = res.location || res.thumbnail || '';
                    // 更新隐藏字段和URL输入框的值
                    document.getElementById('feature_<?php echo $i; ?>_image').value = imageUrl;
                    document.getElementById('feature_<?php echo $i; ?>_image_url').value = imageUrl;
                    
                    // 显示预览图片
                    var previewContainer = document.getElementById('imagePreview<?php echo $i; ?>');
                    if (previewContainer) {
                        previewContainer.innerHTML = '<img src="' + imageUrl + '" alt="预览图片" class="preview-image">';
                    } else {
                        var newPreview = document.createElement('div');
                        newPreview.id = 'imagePreview<?php echo $i; ?>';
                        newPreview.style.marginTop = '10px';
                        newPreview.innerHTML = '<img src="' + imageUrl + '" alt="预览图片" class="preview-image">';
                        this.item.parentNode.appendChild(newPreview);
                    }
                } else {
                    alert('上传失败：' + (res.message || res.error || '未知错误'));
                }
            },
            error: function(){
                alert('上传失败，请稍后重试');
            }
        });
        <?php endfor; ?>
        
        // 为每个合作伙伴项初始化图片上传
        <?php for ($i = 1; $i <= 6; $i++): ?>
        upload.render({
            elem: '#partnerUpload<?php echo $i; ?>',
            url: '../../modules/content/upload.php',
            accept: 'images',
            acceptMime: 'image/*',
            exts: 'jpg|jpeg|png|gif|webp',
            field: 'file',
            done: function(res){
                if(res.success || res.thumbnail || res.location){
                    var imageUrl = res.location || res.thumbnail || '';
                    // 更新隐藏字段和URL输入框的值
                    document.getElementById('partner_<?php echo $i; ?>_image').value = imageUrl;
                    document.getElementById('partner_<?php echo $i; ?>_image_url').value = imageUrl;
                    
                    // 显示预览图片
                    var previewContainer = document.getElementById('partnerImagePreview<?php echo $i; ?>');
                    if (previewContainer) {
                        // Check if the imageUrl is a relative path and prepend SITE_URL if needed
                        var previewImageUrl = imageUrl;
                        if (imageUrl.indexOf('http') !== 0 && imageUrl.indexOf('//') !== 0) {
                            previewImageUrl = '<?php echo SITE_URL; ?>/' + imageUrl.replace(/^\/+/, '');
                        }
                        previewContainer.innerHTML = '<img src="' + previewImageUrl + '" alt="合作伙伴<?php echo $i; ?>预览" class="preview-image">';
                    } else {
                        var newPreview = document.createElement('div');
                        newPreview.id = 'partnerImagePreview<?php echo $i; ?>';
                        newPreview.style.marginTop = '10px';
                        newPreview.innerHTML = '<img src="' + previewImageUrl + '" alt="合作伙伴<?php echo $i; ?>预览" class="preview-image">';
                        this.item.parentNode.appendChild(newPreview);
                    }
                } else {
                    alert('上传失败：' + (res.message || res.error || '未知错误'));
                }
            },
            error: function(){
                alert('上传失败，请稍后重试');
            }
        });
        <?php endfor; ?>
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    
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