<?php
// 联系我们页面模板

// 获取内页Banner
$innerpage_banners = get_innerpage_banners(5);

// 页面SEO信息
$page_title = '联系我们 - ' . get_config('site_name', '高光视刻');
$page_description = '联系我们获取专业的视频制作、平面设计、网站建设、商业摄影、活动策划等创意服务';
$page_keywords = '联系我们,创意服务,视频制作,平面设计,网站建设,商业摄影,活动策划';
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
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/contact.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/contact-form.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/dynamic-banner.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/enhanced-placeholder.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('templates/default/assets/css/frontend/responsive.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="contact-page">
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
            <h2>专业创意服务</h2>
            <p>为您量身定制视觉解决方案，提升品牌价值</p>
            <div class="inner-banner-placeholder-buttons enhanced">
                <a href="<?php echo url('contact/'); ?>" class="btn-primary banner-btn">立即咨询</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- 彩条状面包屑导航 -->
    <section class="breadcrumb-section" style="height: 50px; display: flex; align-items: center;">
        <div class="container">
            <div class="custom-breadcrumb" style="height: 40px; width: 100%;">
                <?php 
                // 使用统一的面包屑导航函数，根据当前栏目动态生成面包屑
                $breadcrumb_data = generate_breadcrumb_data($category['id']);
                echo render_breadcrumb_html($breadcrumb_data); 
                ?>
            </div>
        </div>
    </section>
    
    <!-- 联系信息展示 -->
    <section class="contact-info-section">
        <div class="container">
            <div class="layui-row layui-col-space30">
                <div class="layui-col-md12">
                    <div class="section-header">
                        <h2>联系我们</h2>
                        <p>如果您有任何疑问或需求，请通过以下方式与我们联系，我们的专业团队将尽快为您提供帮助。</p>
                    </div>
                </div>
                
                <div class="layui-col-md4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>联系电话</h3>
                        <p><?php echo get_config('contact_phone', '400-888-8888'); ?></p>
                    </div>
                </div>
                
                <div class="layui-col-md4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>电子邮箱</h3>
                        <p><?php echo get_config('contact_email', 'info@gaoguangshike.cn'); ?></p>
                    </div>
                </div>
                
                <div class="layui-col-md4">
                    <div class="contact-card">
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
    
    <!-- 联系表单 -->
    <section class="contact-form-section">
        <div class="container">
            <div class="layui-row">
                <div class="layui-col-md12">
                    <div class="section-header">
                        <h2>立即咨询</h2>
                        <p>填写以下表单，我们的专业顾问将在24小时内与您联系</p>
                    </div>
                    
                    <div class="contact-form-wrapper">
                        <form id="contact-form" class="contact-form" method="POST">
                            <div class="layui-row layui-col-space30">
                                <div class="layui-col-md6">
                                    <div class="form-group">
                                        <label for="name">姓名 <span class="required">*</span></label>
                                        <input type="text" id="name" name="name" required placeholder="请输入您的姓名">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">电话 <span class="required">*</span></label>
                                        <input type="tel" id="phone" name="phone" required placeholder="请输入您的联系电话">
                                    </div>
                                    
                                    <!-- Removed email field as requested -->
                                    
                                    <div class="form-group">
                                        <label for="service_type">服务类型</label>
                                        <select id="service_type" name="service_type">
                                            <option value="">请选择服务类型</option>
                                            <option value="video-production">视频制作</option>
                                            <option value="graphic-design">平面设计</option>
                                            <option value="web-development">网站建设</option>
                                            <option value="commercial-photography">商业摄影</option>
                                            <option value="event-planning">活动策划</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="layui-col-md6">
                                    <div class="form-group">
                                        <label for="message">详细需求 <span class="required">*</span></label>
                                        <textarea id="message" name="message" rows="6" required placeholder="请详细描述您的需求"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="captcha">验证码 <span class="required">*</span></label>
                                        <div class="captcha-container">
                                            <input type="text" id="captcha" name="captcha" required placeholder="请输入验证码">
                                            <img src="<?php echo url('api/captcha.php'); ?>" alt="验证码" class="captcha-image" onclick="this.src='<?php echo url('api/captcha.php'); ?>?' + Math.random()">
                                        </div>
                                    </div>
                                    
                                    <div class="form-submit">
                                        <button type="submit" class="btn-primary form-btn">提交咨询</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- 网站底部 -->
    <?php include __DIR__ . '/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js" defer></script>
    <script src="<?php echo url('templates/default/assets/js/main.js'); ?>" defer></script>
    <script src="<?php echo url('templates/default/assets/js/dynamic-banner.js'); ?>" defer></script>
    
    <!-- 表单验证和提交处理 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contact-form');
        
        // 提取验证码刷新功能为函数
        function refreshCaptcha() {
            document.querySelector('.captcha-image').src = '<?php echo url('api/captcha.php'); ?>?' + Math.random();
        }
        
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // 获取表单数据
                const formData = new FormData(this);
                const jsonData = Object.fromEntries(formData.entries());
                
                // 设置默认值
                jsonData.service_type = jsonData.service_type || '';
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // 显示提交状态
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
                submitBtn.disabled = true;
                
                // 发送请求
                fetch('<?php echo url('api/inquiry.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(jsonData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('提交成功！我们会尽快与您联系。');
                        this.reset();
                    } else {
                        alert('提交失败：' + (data.message || '请检查您的输入信息'));
                    }
                    refreshCaptcha();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('网络错误，请稍后重试');
                    refreshCaptcha();
                })
                .finally(() => {
                    // 恢复按钮状态
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        }
    });
    </script>
</body>
</html>