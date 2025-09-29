<?php
// 内容详情页模板
// 确保 $content 和 $category 变量已设置

// 获取内页Banner
$innerpage_banners = get_innerpage_banners(5);

// 页面SEO信息
$page_title = (!empty($content['seo_title'])) ? $content['seo_title'] : $content['title'];
$page_description = (!empty($content['seo_description'])) ? $content['seo_description'] : ((!empty($content['summary'])) ? $content['summary'] : strip_tags(substr($content['content'], 0, 200)));
$page_keywords = (!empty($content['seo_keywords'])) ? $content['seo_keywords'] : ((!empty($content['tags'])) ? $content['tags'] : '');

// 获取相关内容
$related_contents = get_contents($content['category_id'], 4, 0, true);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - ' . get_config('site_name', '高光视刻')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    
    <!-- 结构化数据 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?php echo htmlspecialchars($content['title']); ?>",
        "description": "<?php echo htmlspecialchars($page_description); ?>",
        "author": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars(get_config('site_name', '高光视刻')); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars(get_config('site_name', '高光视刻')); ?>"
        },
        "datePublished": "<?php echo $content['published_at'] ? date('c', strtotime($content['published_at'])) : date('c', strtotime($content['created_at'])); ?>",
        "dateModified": "<?php echo date('c', strtotime($content['updated_at'])); ?>"
    }
    </script>
    
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/dynamic-banner.css'); ?>"> <!-- 动态Banner样式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="content-page">
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
    <section class="inner-banner-section inner-banner-placeholder">
        <div class="inner-banner-placeholder-content">
            <h2><?php echo htmlspecialchars($category['name']); ?></h2>
            <p>专业创意服务，为您量身定制解决方案</p>
            <div class="inner-banner-placeholder-buttons">
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
                $breadcrumb_data = generate_breadcrumb_data($category['id'], $content);
                echo render_breadcrumb_html($breadcrumb_data); 
                ?>
            </div>
        </div>
    </section>
    
    <!-- 内容详情 -->
    <section class="content-detail">
        <div class="container">
            <div class="content-header">
                <h1 class="content-title"><?php echo $content['title']; ?></h1>
                <div class="content-meta">
                    <span>
                        <i class="far fa-calendar"></i>
                        <?php echo date('Y-m-d', strtotime($content['created_at'])); ?>
                    </span>
                    <span>
                        <i class="far fa-eye"></i>
                        <?php echo $content['view_count']; ?> 次浏览
                    </span>
                    <span>
                        <i class="far fa-folder"></i>
                        <?php echo $category['name']; ?>
                    </span>
                </div>
                <?php if ($content['summary']): ?>
                <div class="content-summary">
                    <?php echo $content['summary']; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="content-body">
                <?php echo $content['content']; ?>
            </div>
            
            <?php if ($content['tags']): ?>
            <div class="content-tags">
                <h3>标签</h3>
                <?php 
                $tags = explode(',', $content['tags']);
                foreach ($tags as $tag): 
                    $tag = trim($tag);
                    if ($tag): ?>
                <a href="#" class="tag-item"><?php echo $tag; ?></a>
                <?php endif; 
                endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($related_contents)): ?>
            <div class="related-content">
                <h3 class="related-title">相关推荐</h3>
                <div class="related-grid">
                    <?php foreach ($related_contents as $related): ?>
                    <a href="<?php echo content_url($related); ?>" class="related-item">
                        <h4><?php echo $related['title']; ?></h4>
                        <p><?php echo date('Y-m-d', strtotime($related['created_at'])); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
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