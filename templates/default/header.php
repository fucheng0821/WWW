<!-- 模板优化 - 2025年版本 -->
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="<?php echo url(); ?>">
                    <i class="fas fa-cube"></i>
                    <span><?php echo get_config('site_name', '高光视刻'); ?></span>
                </a>
            </div>
            
            <nav class="main-nav">
                <ul class="nav-list">
                    <?php
                    $main_categories = get_categories(0, true);
                    $current_url = $_SERVER['REQUEST_URI'];
                    
                    foreach ($main_categories as $category):
                        $is_active = strpos($current_url, '/' . $category['slug'] . '/') !== false;
                        $sub_categories = get_categories($category['id'], true);
                    ?>
                    <li class="nav-item <?php echo $is_active ? 'active' : ''; ?>">
                        <a href="<?php echo url($category['slug'] . '/'); ?>"><?php echo $category['name']; ?></a>
                        <?php if (!empty($sub_categories)): ?>
                        <ul class="sub-nav">
                            <?php foreach ($sub_categories as $sub_category): ?>
                            <li>
                                <a href="<?php echo url($category['slug'] . '/' . $sub_category['slug'] . '/'); ?>">
                                    <?php echo $sub_category['name']; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <div class="header-actions">
                <a href="<?php echo url('contact/'); ?>" class="btn-inquiry">
                    <i class="fas fa-comment"></i> 在线询价
                </a>
                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- 移动端导航 - 抽屉式设计 -->
<div class="mobile-nav-overlay">
    <div class="mobile-nav">
        <div class="mobile-nav-header">
            <div class="logo">
                <i class="fas fa-cube"></i>
                <span><?php echo get_config('site_name', '高光视刻'); ?></span>
            </div>
            <button class="mobile-nav-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-nav-list">
            <!-- 首页链接 -->
            <li class="mobile-nav-item">
                <a href="<?php echo url(); ?>">首页</a>
            </li>
            <?php foreach ($main_categories as $category): ?>
            <li class="mobile-nav-item">
                <a href="<?php echo url($category['slug'] . '/'); ?>"><?php echo $category['name']; ?></a>
                <?php 
                $sub_categories = get_categories($category['id'], true);
                if (!empty($sub_categories)): 
                ?>
                <ul class="mobile-sub-nav">
                    <?php foreach ($sub_categories as $sub_category): ?>
                    <li>
                        <a href="<?php echo url($category['slug'] . '/' . $sub_category['slug'] . '/'); ?>">
                            <?php echo $sub_category['name']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="mobile-nav-footer">
            <a href="<?php echo url('contact/'); ?>" class="btn-inquiry-mobile">
                <i class="fas fa-comment"></i> 在线询价
            </a>
        </div>
    </div>
</div>