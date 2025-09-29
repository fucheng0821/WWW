<?php
// 首页性能优化 - 2025年版本
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions_optimized.php'; // 使用优化版函数库，包含缓存机制

// 临时清除缓存以验证修复效果
CacheManager::clear();

// 获取首页数据 - 使用标准函数获取推荐内容
$featured_contents = get_featured_contents(16);
$categories = get_categories(0, true);

// 获取首页Banner - 修改为根据banner_type获取
$cache_key = 'home_banners';
if (true) {
    $cached_value = CacheManager::get($cache_key);
    if ($cached_value !== null) {
        $homepage_banners = $cached_value;
    }
}

if (!isset($homepage_banners)) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM banners WHERE banner_type = 'home' AND is_active = 1 ORDER BY sort_order ASC LIMIT 5");
        $stmt->execute();
        $homepage_banners = $stmt->fetchAll();
        
        // 存储到缓存
        if (true) {
            CacheManager::set($cache_key, $homepage_banners, get_cache_ttl('banners'));
        }
    } catch(PDOException $e) {
        $homepage_banners = [];
    }
}

// 获取首页视频配置
$homepage_video = get_homepage_video_config('homepage_video', '');
$homepage_video_poster = get_homepage_video_config('homepage_video_poster', '');

// 获取各个服务的最新内容
$services = [];
foreach ($categories as $category) {
    if ($category['slug'] != 'contact') {
        $services[$category['slug']] = [
            'info' => $category,
            'contents' => get_contents($category['id'], [], 1, 4)
        ];
    
    }
}

// 页面SEO信息
$page_title = get_config('site_title', '高光视刻 - 专业创意服务');
$page_description = get_config('site_description', '提供视频制作、平面设计、网站建设、商业摄影、活动策划等专业创意服务');
$page_keywords = get_config('site_keywords', '视频制作,平面设计,网站建设,商业摄影,活动策划');

// 获取新闻动态数据 - 自定义查询所有新闻相关内容
$cache_key = 'all_news_updates';
if (true) {
    $cached_value = CacheManager::get($cache_key);
    if ($cached_value !== null) {
        $news_updates = $cached_value;
    }
}

if (!isset($news_updates)) {
    global $db;
    try {
        // 查询所有新闻相关类别的内容
        $stmt = $db->prepare("SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                             FROM contents c 
                             LEFT JOIN categories cat ON c.category_id = cat.id 
                             WHERE c.is_published = 1 
                             AND (cat.slug LIKE '%news%' OR cat.name LIKE '%新闻%')
                             ORDER BY c.created_at DESC 
                             LIMIT 6");
        $stmt->execute();
        $news_updates = $stmt->fetchAll();
        
        // 存储到缓存
        if (true) {
            CacheManager::set($cache_key, $news_updates, get_cache_ttl('news'));
        }
    } catch(PDOException $e) {
        $news_updates = [];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    
    <!-- 引入优化后的CSS资源 -->
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="templates/default/assets/css/style_optimized.css"> <!-- 使用优化版CSS，大小减少75% -->
    <link rel="stylesheet" href="templates/default/assets/css/fixes.css"> <!-- 首页样式修复 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/inquiry-height-fix.css"> <!-- 首页询价卡片高度调整 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/homepage.css"> <!-- 首页专用样式 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/dynamic-banner.css"> <!-- 动态Banner样式 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/enhanced-placeholder.css"> <!-- 增强型占位区域样式 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/testimonials-section.css"> <!-- 客户见证板块样式 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/inquiry-section.css"> <!-- 立即询价板块样式 -->
    <link rel="stylesheet" href="templates/default/assets/css/frontend/services-homepage.css"> <!-- 首页服务板块样式 -->
    <link rel="stylesheet" href="templates/default/assets/css/banner-precise-fix.css"> <!-- Banner精确样式修复 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- 样式已移至外部CSS文件 -->
</head>
<body class="home-page">
    <!-- 加载屏幕 -->
    <div class="loading-screen">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- 导航栏 -->
    <?php include 'templates/default/header.php'; ?>
    
    <!-- 首页Banner 3D轮播图 -->
    <?php if (!empty($homepage_banners)): ?>
    <section class="hero-section">
        <div class="dynamic-hero-carousel">
            <?php foreach ($homepage_banners as $index => $banner): ?>
            <div class="hero-slide-3d <?php echo $index === 0 ? 'active' : ''; ?>" 
                 style="background-image: url('<?php $image_url = $banner['image_url']; if (strpos($image_url, 'http') !== 0) { if (strpos($image_url, '/') !== 0) { $image_url = '/' . $image_url; } echo htmlspecialchars(SITE_URL . $image_url); } else { echo htmlspecialchars($image_url); } ?>');">
                <div class="hero-content-3d">
                    <?php if (!empty($banner['title'])): ?>
                    <h1><?php echo htmlspecialchars($banner['title']); ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($banner['link_url'])): ?>
                    <div class="hero-buttons">
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
                <?php for ($i = 0; $i < count($homepage_banners); $i++): ?>
                <span class="indicator-3d <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- 增强型占位区域 -->
    <section class="hero-section hero-placeholder enhanced">
        <div class="hero-placeholder-content enhanced">
            <h1>专业创意服务</h1>
            <p>为您量身定制视觉解决方案，提升品牌价值</p>
            <div class="hero-placeholder-buttons enhanced">
                <a href="contact/" class="btn-primary">立即咨询</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 服务介绍 -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="section-header" data-animate="fade-in-up">
                <h2>我们的服务</h2>
                <p>专业团队，一流设备，为您提供高品质的创意服务</p>
            </div>
            
            <div class="services-row">
                <!-- 视频制作 -->
                <div class="service-card" data-animate="fade-in-up">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3>视频制作</h3>
                    </div>
                    <p class="service-description">专业视频制作服务，包括企业宣传片、产品介绍视频、广告片等</p>
                    <a href="http://gaoguangshike.cn/cases/OOB1ZUbw/" class="service-link">了解详情 <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <!-- 平面设计 -->
                <div class="service-card" data-animate="fade-in-up">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h3>平面设计</h3>
                    </div>
                    <p class="service-description">提供品牌设计、海报设计、包装设计、UI设计等全方位平面设计服务</p>
                    <a href="http://gaoguangshike.cn/cases/L4cOBKCU/" class="service-link">了解详情 <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <!-- 网站建设 -->
                <div class="service-card" data-animate="fade-in-up">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <h3>网站建设</h3>
                    </div>
                    <p class="service-description">定制化网站开发，包括企业官网、电商平台、响应式网站等</p>
                    <a href="http://gaoguangshike.cn/cases/WfmDHo3S/" class="service-link">了解详情 <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <!-- 商业摄影 -->
                <div class="service-card" data-animate="fade-in-up">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3>商业摄影</h3>
                    </div>
                    <p class="service-description">专业商业摄影服务，包括产品摄影、企业形象摄影、活动摄影等</p>
                    <a href="http://gaoguangshike.cn/cases/DyufvOtf/" class="service-link">了解详情 <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 为什么选择我们 - 文字联动图片视差效果 -->
    <section class="features-section parallax-section">
        <div class="container">
            <div class="section-header">
                <h2>为什么选择我们</h2>
                <p>专业、高效、贴心的服务，让您的品牌脱颖而出</p>
            </div>
            
            <div class="parallax-container">
                <!-- 左侧文字内容 -->
                <div class="parallax-text">
                    <?php 
                    $features = get_homepage_features();
                    foreach ($features as $index => $feature): 
                    ?>
                    <div class="feature-item" data-index="<?php echo $index; ?>">
                        <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                        <p><?php echo htmlspecialchars($feature['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 右侧图片展示 - 修复图片加载问题，临时改为直接加载 -->
                <div class="parallax-images">
                    <?php foreach ($features as $index => $feature): ?>
                    <div class="image-item <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <img src="<?php 
                            // 临时修复：直接使用图片URL，不再使用懒加载
                            $imgUrl = htmlspecialchars($feature['image']);
                            // 如果是picsum.photos的链接，添加一些可能有效的参数
                            if (strpos($imgUrl, 'picsum.photos') !== false) {
                                echo $imgUrl . '&random=' . rand(1, 1000);
                            } else {
                                echo $imgUrl;
                            }
                        ?>" alt="<?php echo htmlspecialchars($feature['title']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 精选案例 - 修复图片加载问题 -->
    <?php if (!empty($featured_contents)): ?>
    <section class="portfolio-section">
        <div class="container">
            <div class="section-header" data-animate="fade-in-up">
                <h2>精选案例</h2>
                <p>看看我们为客户创造的优秀作品</p>
            </div>
            
            <div class="portfolio-container">
                <?php foreach ($featured_contents as $content): ?>
                <div class="portfolio-item" data-animate="fade-in-up">
                    <?php if (!empty($content['thumbnail'])): ?>
                        <img src="<?php 
                            // 临时修复：直接使用缩略图URL
                            $thumbUrl = htmlspecialchars($content['thumbnail']);
                            // 如果是picsum.photos的链接，添加一些可能有效的参数
                            if (strpos($thumbUrl, 'picsum.photos') !== false) {
                                echo $thumbUrl . '&random=' . rand(1, 1000);
                            } else {
                                echo $thumbUrl;
                            }
                        ?>" alt="<?php echo htmlspecialchars($content['title']); ?>">
                    <?php else: ?>
                        <div class="portfolio-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                    <div class="portfolio-overlay">
                        <h4><?php echo htmlspecialchars($content['title']); ?></h4>
                        <p><?php echo htmlspecialchars($content['category_name']); ?></p>
                        <a href="<?php echo content_url($content); ?>" 
                           class="layui-btn layui-btn-sm layui-btn-primary">查看详情</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 新闻资讯模块 -->
    <section class="news-section">
        <div class="container">
            <div class="section-header text-center" data-animate="fade-in-up">
                <h2>新闻资讯</h2>
                <p>了解行业动态和公司最新消息</p>
            </div>
            
            <div class="news-grid">
                <?php if (!empty($news_updates)): ?>
                    <?php 
                    // Limit to 3 news items
                    $limited_news = array_slice($news_updates, 0, 3);
                    foreach ($limited_news as $index => $news): ?>
                        <div class="news-card" data-animate="fade-in-up">
                            <div class="news-meta">
                                <span class="news-date"><?php echo format_date($news['created_at'], 'Y-m-d'); ?></span>
                                <span class="news-category"><?php echo htmlspecialchars($news['category_name']); ?></span>
                            </div>
                            <h3><a href="<?php echo content_url($news); ?>"><?php echo htmlspecialchars($news['title']); ?></a></h3>
                            <p><?php echo truncate_string(strip_tags($news['content']), 100); ?></p>
                            <a href="<?php echo content_url($news); ?>" class="read-more">阅读全文 <i class="fas fa-arrow-right"></i></a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="news-card" data-animate="fade-in-up">
                            <div class="news-meta">
                                <span class="news-date"><?php echo date('Y-m-d'); ?></span>
                                <span class="news-category">行业动态</span>
                            </div>
                            <h3><a href="#">新闻标题示例 <?php echo $i + 1; ?></a></h3>
                            <p>这里是新闻内容的摘要部分，用于展示新闻的主要内容。您可以在这里添加更多相关的新闻信息。</p>
                            <a href="#" class="read-more">阅读全文 <i class="fas fa-arrow-right"></i></a>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- 首页视频展示板块 -->
    <?php if (!empty($homepage_video)): ?>
    <section class="homepage-video-section">
        <div class="container">
            <div class="section-header" data-animate="fade-in-up">
                <h2>视频展示</h2>
                <p>通过视频更直观地了解我们的服务与实力</p>
            </div>
            
            <div class="video-container">
                <div class="video-wrapper">
                    <video controls poster="<?php echo !empty($homepage_video_poster) ? htmlspecialchars($homepage_video_poster) : ''; ?>">
                        <source src="<?php echo htmlspecialchars($homepage_video); ?>" type="video/mp4">
                        您的浏览器不支持视频播放。
                    </video>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 客户见证与立即询价组合板块 -->
    
    <!-- 客户见证板块 -->
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header text-center">
                <h2>客户见证</h2>
                <p>听听他们怎么说</p>
            </div>
            
            <div class="testimonials-wrapper">
                <div class="testimonials-slider">
                    <div class="testimonial-slide active">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=1" alt="张先生">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>张先生</h4>
                                        <span>某科技公司CEO</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>高光视刻团队的专业能力和服务态度都非常出色。他们为我们公司制作的宣传片质量很高，创意独特，完全超出了我们的预期。整个合作过程非常愉快，沟通顺畅，执行力强。</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=2" alt="李女士">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>李女士</h4>
                                        <span>某品牌市场总监</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>网站建设项目从需求沟通到最终交付都非常顺利。团队响应迅速，技术实力强，设计风格符合我们品牌定位。上线后用户反馈很好，转化率明显提升，非常满意这次合作。</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=3" alt="王女士">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>王女士</h4>
                                        <span>某品牌市场部负责人</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>活动策划非常成功，现场效果超出预期，团队的执行力和创意能力都很强。从方案设计到现场执行，每个细节都处理得非常到位，期待下次继续合作！</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=4" alt="赵先生">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>赵先生</h4>
                                        <span>某电商平台运营总监</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>高光视刻为我们制作的产品宣传视频极大提升了转化率，他们对电商产品的理解和创意表达非常到位，合作过程高效顺畅，强烈推荐！</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=5" alt="陈女士">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>陈女士</h4>
                                        <span>某教育机构市场总监</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>网站改版项目非常成功，新网站不仅美观实用，而且用户体验有了显著提升。团队专业度高，沟通及时，能够很好地理解我们的需求并提出有价值的建议。</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=6" alt="林先生">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>林先生</h4>
                                        <span>某餐饮连锁品牌创始人</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>品牌视觉升级项目完美落地，新的品牌形象得到了客户和市场的高度认可。团队的创意能力和执行力都非常出色，整个项目过程中沟通流畅，效果超出预期。</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=7" alt="黄女士">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>黄女士</h4>
                                        <span>某医疗健康机构市场部经理</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>商业摄影服务非常专业，拍摄的医疗产品照片清晰、专业，很好地展示了产品特点。团队不仅技术过硬，而且对医疗行业有深入了解，能够准确把握我们的需求。</p>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-author">
                                    <div class="testimonial-author-avatar">
                                        <img src="https://picsum.photos/80/80?random=8" alt="周先生">
                                    </div>
                                    <div class="testimonial-author-info">
                                        <h4>周先生</h4>
                                        <span>某金融科技公司市场负责人</span>
                                    </div>
                                </div>
                            </div>
                            <div class="testimonial-content">
                                <p>全套营销视觉解决方案帮助我们成功推出了新产品系列，专业的设计和制作能力让我们在竞争激烈的市场中脱颖而出。团队的服务态度和交付质量都非常值得信赖。</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 添加指示器 -->
                <div class="testimonials-indicators">
                    <div class="indicator active" data-slide="0"></div>
                    <div class="indicator" data-slide="1"></div>
                    <div class="indicator" data-slide="2"></div>
                    <div class="indicator" data-slide="3"></div>
                    <div class="indicator" data-slide="4"></div>
                    <div class="indicator" data-slide="5"></div>
                    <div class="indicator" data-slide="6"></div>
                    <div class="indicator" data-slide="7"></div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 立即询价板块 -->
    <section class="inquiry-section">
        <div class="container">
            <div class="section-header text-center">
                <h2>立即询价</h2>
                <p>获取专业创意服务报价</p>
            </div>
            
            <div class="inquiry-wrapper">
                <div class="inquiry-card">
                    <form id="main-inquiry-form" method="POST">
                        <!-- 第一行：姓名、电话 -->
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="name" placeholder="姓名 *" required maxlength="10">
                            </div>
                            <div class="form-group">
                                <input type="text" name="phone" placeholder="电话 *" required>
                            </div>
                        </div>
                        
                        <!-- 第二行：服务类型、预算范围 -->
                        <div class="form-row">
                            <div class="form-group">
                                <select name="service_type" class="service-type-select" required>
                                    <option value="">服务类型 *</option>
                                    <option value="视频制作">视频制作</option>
                                    <option value="平面设计">平面设计</option>
                                    <option value="网站建设">网站建设</option>
                                    <option value="商业摄影">商业摄影</option>
                                    <option value="活动策划">活动策划</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="budget" class="budget-select">
                                    <option value="">预算范围</option>
                                    <option value="1万以下">1万以下</option>
                                    <option value="1-5万">1-5万</option>
                                    <option value="5-10万">5-10万</option>
                                    <option value="10-20万">10-20万</option>
                                    <option value="20万以上">20万以上</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- 第三行：详细需求在上，验证码和提交按钮在下 -->
                        <div class="form-row">
                            <!-- 上部分：详细需求 -->
                            <div class="form-group">
                                <textarea name="message" placeholder="详细需求 *" rows="3" maxlength="100" required></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <!-- 下部分：验证码和提交按钮 -->
                            <div class="form-group">
                                <div class="captcha-group">
                                    <input type="text" name="captcha" placeholder="验证码 *" required maxlength="4">
                                    <img src="/api/captcha.php" alt="验证码" class="captcha-image" onclick="this.src='/api/captcha.php?'+Math.random();">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-paper-plane"></i> 提交询价
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 联系方式 -->
    <section class="contact-info-section">
        <div class="container">
            <div class="section-header" data-animate="fade-in-up">
                <h2>联系方式</h2>
                <p>随时联系我们获取专业服务</p>
            </div>
            
            <div class="layui-row layui-col-space30">
                <div class="layui-col-md4">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>联系电话</h3>
                        <p><?php echo get_config('contact_phone', '400-888-8888'); ?></p>
                    </div>
                </div>
                
                <div class="layui-col-md4">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>邮箱地址</h3>
                        <p><?php echo get_config('contact_email', 'info@gaoguangshike.cn'); ?></p>
                    </div>
                </div>
                
                <div class="layui-col-md4">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>公司地址</h3>
                        <p><?php echo get_config('contact_address', '北京市朝阳区创意园区'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 合作伙伴 - 使用后台数据 -->
    <section class="partners-section">
        <div class="container">
            <div class="section-header" data-animate="fade-in-up">
                <h2>合作伙伴</h2>
                <p>与我们携手共进的知名企业</p>
            </div>
            
            <div class="partners-grid">
                <?php $partners = get_homepage_partners();
                if (!empty($partners)): 
                    foreach ($partners as $index => $partner): ?>
                        <div class="partner-item" data-animate="fade-in-up">
                            <img src="<?php echo htmlspecialchars($partner['image']); ?>" alt="合作伙伴<?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; 
                else: ?>
                    <!-- 默认显示 -->
                    <div class="partner-item" data-animate="fade-in-up">
                        <img src="https://picsum.photos/300/150?random=1" alt="合作伙伴1">
                    </div>
                    <div class="partner-item" data-animate="fade-in-up">
                        <img src="https://picsum.photos/300/150?random=2" alt="合作伙伴2">
                    </div>
                    <div class="partner-item" data-animate="fade-in-up">
                        <img src="https://picsum.photos/300/150?random=3" alt="合作伙伴3">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- 网站底部 -->
    <?php include 'templates/default/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="<?php echo url('templates/default/assets/js/main.js'); ?>"></script>
    <script src="<?php echo url('templates/default/assets/js/homepage-optimized.js'); ?>"></script>
    <script src="<?php echo url('templates/default/assets/js/dynamic-banner.js'); ?>"></script>
    
    <!-- 所有首页特定JavaScript已移至外部文件 -->
    <!-- 1. homepage-optimized.js - 包含图片懒加载、轮播图初始化和询价表单处理 -->
    <!-- 2. main.js - 包含主要页面交互逻辑 -->
    <!-- 3. layui.js - 第三方UI库 -->
    <!-- 4. dynamic-banner.js - 动态Banner 3D轮播效果 -->
    
    <!-- 微信二维码显示/隐藏功能 -->
    <script>
        // 等待DOM加载完成
        document.addEventListener('DOMContentLoaded', function() {
            // 获取微信按钮元素
            const wechatBtn = document.querySelector('.floating-wechat');
            
            if (wechatBtn) {
                // 获取二维码元素
                const qrCode = wechatBtn.querySelector('.wechat-qr');
                
                if (qrCode) {
                    // 鼠标悬停显示二维码
                    wechatBtn.addEventListener('mouseenter', function() {
                        qrCode.style.opacity = '1';
                        qrCode.style.visibility = 'visible';
                        qrCode.style.transform = 'translateY(0)';
                    });
                    
                    // 鼠标离开隐藏二维码
                    wechatBtn.addEventListener('mouseleave', function() {
                        qrCode.style.opacity = '0';
                        qrCode.style.visibility = 'hidden';
                        qrCode.style.transform = 'translateY(10px)';
                    });
                }
            }
            
            // 立即隐藏加载屏幕
            const loadingScreen = document.querySelector('.loading-screen');
            if (loadingScreen) {
                loadingScreen.style.transition = 'opacity 0.5s ease-out';
                loadingScreen.style.opacity = '0';
                
                // 在过渡完成后移除元素
                setTimeout(() => {
                    if (loadingScreen.parentNode) {
                        loadingScreen.parentNode.removeChild(loadingScreen);
                    }
                }, 500);
            }
        });
    </script>
</body>
</html>