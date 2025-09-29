<?php
// 图片画廊模板
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// 获取内容ID
$content_id = $_GET['id'] ?? 0;

// 获取内容详情
$content = get_content_by_id($content_id);

if (!$content) {
    header('HTTP/1.0 404 Not Found');
    include '../../404.php';
    exit();
}

// 获取栏目信息
$category = get_category_by_id($content['category_id']);

// 生成面包屑导航
$breadcrumbs = generate_breadcrumb($category, $content);

// 页面SEO信息
$page_title = $content['title'] . ' - ' . get_config('site_title', '高光视刻');
$page_description = !empty($content['excerpt']) ? $content['excerpt'] : truncate_string(strip_tags($content['content']), 150);
$page_keywords = $content['keywords'] ?? get_config('site_keywords', '');

// 获取图片画廊数据
$gallery_images = [];
if (!empty($content['gallery_images'])) {
    $gallery_images = json_decode($content['gallery_images'], true);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    
    <!-- 引入CSS资源 -->
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
    
    <!-- 图片画廊样式 -->
    <style>
        .image-gallery {
            margin: 30px 0;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item {
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .gallery-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .gallery-caption {
            padding: 15px;
            background: #fff;
        }
        
        .gallery-caption h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        
        .gallery-caption p {
            margin: 0;
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .gallery-image {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <?php include '../header.php'; ?>
    
    <?php if (!empty($category['banner'])): ?>
    <!-- 内页Banner区域 -->
    <section class="inner-banner-section">
        <div class="inner-banner-content">
            <h2><?php echo htmlspecialchars($category['name']); ?></h2>
            <p>专业创意服务，为您量身定制解决方案</p>
            <div class="inner-banner-buttons">
                <a href="<?php echo url('contact/'); ?>" class="btn-primary">联系我们</a>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- 内页Banner占位区域 -->
    <section class="inner-banner-section inner-banner-placeholder enhanced">
        <div class="inner-banner-placeholder-content enhanced">
            <h2><?php echo htmlspecialchars($category['name']); ?></h2>
            <p>专业创意服务，为您量身定制解决方案</p>
            <div class="inner-banner-placeholder-buttons enhanced">
                <a href="<?php echo url('contact/'); ?>" class="btn-primary">联系我们</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 面包屑导航 -->
    <section class="breadcrumb-section" style="height: 50px; display: flex; align-items: center;">
        <div class="container">
            <div class="custom-breadcrumb" style="height: 40px; width: 100%;">
                <?php echo render_breadcrumb($category, $content); ?>
            </div>
        </div>
    </section>
    
    <!-- 主要内容区 -->
    <div class="main-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- 文章内容 -->
                <article class="content-article">
                    <header class="article-header">
                        <h1><?php echo htmlspecialchars($content['title']); ?></h1>
                        <div class="article-meta">
                            <span class="meta-item">
                                <i class="layui-icon layui-icon-time"></i>
                                <?php echo format_date($content['created_at']); ?>
                            </span>
                            <span class="meta-item">
                                <i class="layui-icon layui-icon-read"></i>
                                <?php echo $content['view_count']; ?> 次浏览
                            </span>
                            <?php if (!empty($category)): ?>
                            <span class="meta-item">
                                <i class="layui-icon layui-icon-tabs"></i>
                                <a href="<?php echo category_url($category); ?>"><?php echo htmlspecialchars($category['name']); ?></a>
                            </span>
                            <?php endif; ?>
                        </div>
                    </header>
                    
                    <div class="article-content">
                        <?php echo $content['content']; ?>
                    </div>
                    
                    <!-- 图片画廊 -->
                    <?php if (!empty($gallery_images)): ?>
                    <div class="image-gallery">
                        <h2>图片画廊</h2>
                        <div class="gallery-grid">
                            <?php foreach ($gallery_images as $index => $image): ?>
                            <div class="gallery-item">
                                <a href="<?php echo htmlspecialchars($image['url']); ?>" data-fancybox="gallery" data-caption="<?php echo htmlspecialchars($image['title'] ?? ''); ?>">
                                    <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="<?php echo htmlspecialchars($image['title'] ?? '图片 ' . ($index + 1)); ?>" class="gallery-image">
                                </a>
                                <?php if (!empty($image['title']) || !empty($image['description'])): ?>
                                <div class="gallery-caption">
                                    <?php if (!empty($image['title'])): ?>
                                    <h3><?php echo htmlspecialchars($image['title']); ?></h3>
                                    <?php endif; ?>
                                    <?php if (!empty($image['description'])): ?>
                                    <p><?php echo htmlspecialchars($image['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </article>
                
                <!-- 分享和操作按钮 -->
                <div class="article-actions">
                    <div class="action-buttons">
                        <button class="layui-btn layui-btn-primary" onclick="window.print()">
                            <i class="layui-icon layui-icon-print"></i> 打印
                        </button>
                        <button class="layui-btn layui-btn-primary" onclick="sharePage()">
                            <i class="layui-icon layui-icon-share"></i> 分享
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 网站底部 -->
    <?php include '../footer.php'; ?>
    
    <!-- 引入JavaScript资源 -->
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
    
    <script>
        layui.use(['element', 'util'], function(){
            var element = layui.element;
            var util = layui.util;
            
            // 初始化图片画廊
            $('[data-fancybox="gallery"]').fancybox({
                buttons: [
                    "zoom",
                    "slideShow",
                    "fullScreen",
                    "download",
                    "thumbs",
                    "close"
                ],
                lang: 'zh-CN',
                i18n: {
                    'zh-CN': {
                        CLOSE: '关闭',
                        NEXT: '下一张',
                        PREV: '上一张',
                        ERROR: '请求内容时发生错误。请稍后重试。',
                        PLAY_START: '开始播放',
                        PLAY_STOP: '停止播放',
                        FULL_SCREEN: '全屏',
                        THUMBS: '缩略图',
                        DOWNLOAD: '下载',
                        SHARE: '分享',
                        ZOOM: '缩放'
                    }
                }
            });
        });
        
        // 分享功能
        function sharePage() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                }).catch(console.error);
            } else {
                // 复制链接到剪贴板
                const el = document.createElement('textarea');
                el.value = window.location.href;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                layui.use('layer', function(){
                    var layer = layui.layer;
                    layer.msg('链接已复制到剪贴板');
                });
            }
        }
    </script>
</body>
</html>