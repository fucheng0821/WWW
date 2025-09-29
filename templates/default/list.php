<?php
// 列表页模板
// 确保 $category 变量已设置

// 获取内页Banner
$innerpage_banners = get_innerpage_banners(5);

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// 获取栏目下的内容
$contents = get_contents($category['id'], $per_page, $offset, true);

// 获取总数用于分页
try {
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM contents WHERE category_id = ? AND is_published = 1");
    $count_stmt->execute([$category['id']]);
    $total = $count_stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
} catch(PDOException $e) {
    $total = 0;
    $total_pages = 0;
}

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
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/news.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/news-list.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/dynamic-banner.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/enhanced-placeholder.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="list-page news-page">
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
            <h2>行业资讯与洞察</h2>
            <p>掌握最新行业动态，获取专业创意见解</p>
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
    
    <!-- 内容列表 -->
    <section class="content-list">
        <div class="container">
            <?php if (empty($contents)): ?>
            <div class="empty-state">
                <div class="portfolio-placeholder">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>暂无内容</h3>
                <p>该栏目下还没有发布的内容</p>
            </div>
            <?php else: ?>
            <div class="content-grid">
                <?php foreach ($contents as $content): ?>
                <div class="content-card">
                    <?php if ($content['thumbnail']): ?>
                    <div class="card-image">
                        <?php 
                        // 处理缩略图URL，确保正确显示
                        $thumbnail_url = $content['thumbnail'];
                        // 检查是否已经是完整URL
                        if (strpos($thumbnail_url, 'http') !== 0 && strpos($thumbnail_url, '//') !== 0) {
                            // 如果不是以/开头，则添加/前缀
                            if (substr($thumbnail_url, 0, 1) !== '/') {
                                $thumbnail_url = '/' . $thumbnail_url;
                            }
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($thumbnail_url); ?>" alt="<?php echo htmlspecialchars($content['title']); ?>">
                    </div>
                    <?php else: ?>
                    <div class="card-image">
                        <i class="fas fa-image"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3 class="card-title">
                            <a href="<?php echo content_url($content); ?>">
                                <?php echo htmlspecialchars($content['title']); ?>
                            </a>
                        </h3>
                        <p class="card-summary"><?php echo htmlspecialchars($content['summary'] ?: truncate_string(strip_tags($content['content']), 100)); ?></p>
                        <div class="card-meta">
                            <span class="card-date">
                                <i class="far fa-calendar"></i>
                                <?php echo date('Y-m-d', strtotime($content['created_at'])); ?>
                            </span>
                            <span class="card-views">
                                <i class="far fa-eye"></i>
                                <?php echo $content['view_count']; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 分页 -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">上一页</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">下一页</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- 询价模块 -->
    <section class="inquiry-section">
        <div class="container">
            <h2>需要专业<?php echo $category['name']; ?>服务？</h2>
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