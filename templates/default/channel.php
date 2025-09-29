<?php
// 频道页模板
// 确保 $category 变量已设置

// 获取内页Banner
$innerpage_banners = get_innerpage_banners(5);

// 页面SEO信息
$page_title = (!empty($category['meta_title'])) ? $category['meta_title'] : $category['name'];
$page_description = (!empty($category['meta_description'])) ? $category['meta_description'] : $category['description'];
$page_keywords = (!empty($category['meta_keywords'])) ? $category['meta_keywords'] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/channel.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/dynamic-banner.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/enhanced-placeholder.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="channel-page">
    <!-- 导航栏 -->
    <?php include __DIR__ . '/header.php'; ?>
    
    <!-- 内页Banner 3D轮播图 -->
    <?php if (!empty($innerpage_banners)): ?>
    <section class="inner-banner-section">
        <div class="dynamic-inner-carousel">
            <?php foreach ($innerpage_banners as $index => $banner): ?>
            <div class="carousel-slide-3d <?php echo $index === 0 ? 'active' : ''; ?>" 
                 style="background-image: url('<?php echo htmlspecialchars($banner['image_url']); ?>');">
                <div class="inner-banner-content-3d">
                    <?php if (!empty($banner['title'])): ?>
                    <h2><?php echo htmlspecialchars($banner['title']); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($banner['link_url'])): ?>
                    <div class="inner-banner-buttons">
                        <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" class="btn-primary">了解更多</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- 轮播控制按钮 -->
            <button class="carousel-control-3d prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-control-3d next">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- 轮播指示器 -->
            <div class="carousel-indicators-3d">
                <?php for ($i = 0; $i < count($innerpage_banners); $i++): ?>
                <span class="indicator-3d <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
                <?php endfor; ?>
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
    
    <!-- 彩条状面包屑导航 -->
    <section class="breadcrumb-section" style="height: 50px; display: flex; align-items: center;">
        <div class="container">
            <div class="custom-breadcrumb" style="height: 40px; width: 100%;">
                <?php 
                // 使用统一的面包屑导航函数
                $breadcrumb_data = generate_breadcrumb_data($category['id']);
                echo render_breadcrumb_html($breadcrumb_data); 
                ?>
            </div>
        </div>
    </section>
    
    <!-- 频道内容 -->
    <section class="channel-content">
        <div class="container">
            <div class="content-wrapper">
                <?php 
                // Check if 'content' key exists in the category array to avoid undefined index warning
                if (isset($category['content'])) {
                    echo $category['content'];
                } else {
                    // Provide a default message or check if there's a description we can use
                    echo !empty($category['description']) ? htmlspecialchars($category['description']) : '暂无内容';
                }
                ?>
            </div>
        </div>
    </section>
    
    <!-- 询价模块 -->
    <section class="inquiry-section">
        <div class="container">
            <h2>对<?php echo $category['name']; ?>服务感兴趣？</h2>
            <p>立即联系我们的专业顾问，获取免费咨询和报价</p>
            <a href="<?php echo url('contact/'); ?>" class="btn-primary">
                <i class="fas fa-comment"></i> 立即询价
            </a>
        </div>
    </section>
    
    <!-- 网站底部 -->
    <?php include __DIR__ . '/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js" defer></script>
    <script src="<?php echo url('templates/default/assets/js/main.js'); ?>" defer></script>
    <script src="<?php echo url('templates/default/assets/js/dynamic-banner.js'); ?>" defer></script>
</body>
</html>