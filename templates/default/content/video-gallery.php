<?php
// 视频展示模板
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

// 获取视频列表数据
$video_list = [];
if (!empty($content['video_list'])) {
    $video_list = json_decode($content['video_list'], true);
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
    
    <!-- 视频画廊样式 -->
    <style>
        .video-gallery {
            margin: 30px 0;
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .video-item {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .video-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .video-wrapper {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background: #000;
        }
        
        .video-wrapper video,
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .video-caption {
            padding: 15px;
        }
        
        .video-caption h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        
        .video-caption p {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        .video-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #999;
        }
        
        .video-duration {
            background: rgba(0,0,0,0.7);
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            position: absolute;
            bottom: 10px;
            right: 10px;
        }
        
        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <?php include '../header.php'; ?>
    
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
                    
                    <!-- 视频画廊 -->
                    <?php if (!empty($video_list)): ?>
                    <div class="video-gallery">
                        <h2>视频展示</h2>
                        <div class="video-grid">
                            <?php foreach ($video_list as $index => $video): ?>
                            <div class="video-item">
                                <div class="video-wrapper">
                                    <?php if (!empty($video['type']) && $video['type'] === 'external'): ?>
                                        <!-- 外部视频链接 -->
                                        <iframe src="<?php echo htmlspecialchars($video['url']); ?>" allowfullscreen></iframe>
                                    <?php else: ?>
                                        <!-- 本地视频文件 -->
                                        <video controls preload="metadata" <?php echo !empty($video['poster']) ? 'poster="' . htmlspecialchars($video['poster']) . '"' : ''; ?>>
                                            <source src="<?php echo htmlspecialchars($video['url']); ?>" type="video/mp4">
                                            您的浏览器不支持视频播放。
                                        </video>
                                    <?php endif; ?>
                                    <?php if (!empty($video['duration'])): ?>
                                    <div class="video-duration"><?php echo htmlspecialchars($video['duration']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="video-caption">
                                    <?php if (!empty($video['title'])): ?>
                                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <?php endif; ?>
                                    <?php if (!empty($video['description'])): ?>
                                    <p><?php echo htmlspecialchars($video['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="video-meta">
                                        <span><?php echo !empty($video['date']) ? format_date($video['date']) : ''; ?></span>
                                        <span><?php echo !empty($video['size']) ? format_bytes($video['size']) : ''; ?></span>
                                    </div>
                                </div>
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
    <script>
        layui.use(['element', 'util'], function(){
            var element = layui.element;
            var util = layui.util;
            
            // 视频播放控制
            var videos = document.querySelectorAll('video');
            videos.forEach(function(video) {
                video.addEventListener('play', function() {
                    // 暂停其他视频
                    videos.forEach(function(otherVideo) {
                        if (otherVideo !== video && !otherVideo.paused) {
                            otherVideo.pause();
                        }
                    });
                });
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
        
        // 格式化文件大小
        function format_bytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>