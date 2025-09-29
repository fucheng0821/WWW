/**
 * 首页优化脚本 - 处理询价表单和图片懒加载等首页特定功能
 * 减少内联脚本，提升首页加载性能
 */

// 等待DOM加载完成
document.addEventListener('DOMContentLoaded', function() {
    // 图片懒加载实现
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const dataSrc = img.getAttribute('data-src');
                        
                        if (dataSrc) {
                            // 设置图片源并添加加载完成处理
                            img.setAttribute('src', dataSrc);
                            img.removeAttribute('data-src');
                            
                            // 添加加载成功的CSS类
                            img.onload = function() {
                                img.classList.add('lazyloaded');
                            };
                            
                            // 处理加载失败的情况
                            img.onerror = function() {
                                // 可以在这里设置一个占位图或者隐藏图片
                                console.error('图片加载失败:', dataSrc);
                            };
                        }
                        
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '0px 0px 200px 0px' // 提前200px开始加载
            });
            
            // 观察所有带有lazyload类的图片
            document.querySelectorAll('img.lazyload').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // 降级处理：如果浏览器不支持IntersectionObserver，则立即加载所有图片
            document.querySelectorAll('img.lazyload').forEach(img => {
                const dataSrc = img.getAttribute('data-src');
                if (dataSrc) {
                    img.setAttribute('src', dataSrc);
                    img.removeAttribute('data-src');
                    img.classList.add('lazyloaded');
                }
            });
        }
    }
    
    // 初始化图片懒加载
    initLazyLoad();
    
    // 初始化轮播图
    function initCarousel() {
        if (window.layui && window.layui.carousel) {
            var carousel = layui.carousel;
            
            // 首页轮播图
            if (document.getElementById('hero-carousel')) {
                carousel.render({
                    elem: '#hero-carousel',
                    width: '100%',
                    height: '100%',
                    arrow: 'hover',
                    indicator: 'inside',
                    autoplay: true,
                    interval: 5000,
                    anim: 'default'
                });
            }
        }
    }
    
    // 检查LayUI是否已加载，如果已加载则初始化轮播图，否则等待加载完成
    if (window.layui) {
        initCarousel();
    } else {
        // 监听LayUI加载完成事件
        document.addEventListener('layuiReady', function() {
            initCarousel();
        });
        
        // 为LayUI添加加载完成事件
        var originalLayuiUse = window.layui && window.layui.use;
        if (originalLayuiUse) {
            window.layui.use = function() {
                var result = originalLayuiUse.apply(this, arguments);
                document.dispatchEvent(new CustomEvent('layuiReady'));
                return result;
            };
        }
    }
    
    // 客户见证轮播功能
    function initTestimonialsSlider() {
        try {
            const slider = document.querySelector('.testimonials-slider');
            const slides = document.querySelectorAll('.testimonial-slide');
            const indicators = document.querySelectorAll('.indicator');
            
            if (!slider || slides.length === 0) {
                console.warn('Testimonials slider not found or no slides available');
                return;
            }
            
            let currentIndex = 0;
            const totalSlides = slides.length;
            
            // 更新激活状态
            function updateActiveSlide(index) {
                // 更新幻灯片
                slides.forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
                
                // 更新指示器
                indicators.forEach((indicator, i) => {
                    indicator.classList.toggle('active', i === index);
                });
                
                currentIndex = index;
            }
            
            // 下一张
            function nextSlide() {
                const nextIndex = (currentIndex + 1) % totalSlides;
                updateActiveSlide(nextIndex);
            }
            
            // 上一张
            function prevSlide() {
                const prevIndex = (currentIndex - 1 + totalSlides) % totalSlides;
                updateActiveSlide(prevIndex);
            }
            
            // 跳转到指定幻灯片
            function goToSlide(index) {
                if (index >= 0 && index < totalSlides) {
                    updateActiveSlide(index);
                }
            }
            
            // 绑定指示器事件
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    goToSlide(index);
                });
            });
            
            // 自动播放（可选）
            let autoplayInterval = setInterval(nextSlide, 5000);
            
            // 鼠标悬停时暂停自动播放
            slider.addEventListener('mouseenter', () => {
                clearInterval(autoplayInterval);
            });
            
            slider.addEventListener('mouseleave', () => {
                autoplayInterval = setInterval(nextSlide, 5000);
            });
            
        } catch (error) {
            console.error('Error initializing testimonials slider:', error);
        }
    }
    
    // 处理首页询价表单提交 - 增强版带完整错误处理
    function handleInquiryForm(formId) {
        try {
            var inquiryForm = document.getElementById(formId);
            if (!inquiryForm) {
                console.warn('Form not found:', formId);
                return;
            }
            
            inquiryForm.addEventListener('submit', function(e) {
                try {
                    e.preventDefault();
                    
                    // 显示提交中状态
                    var submitBtn = this.querySelector('button[type="submit"]');
                    if (!submitBtn) {
                        console.warn('Submit button not found in form:', formId);
                        return;
                    }
                    
                    var originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
                    submitBtn.disabled = true;
                
                    var formData = new FormData(this);
                    var data = {
                        name: formData.get('name'),
                        phone: formData.get('phone'),
                        service_type: formData.get('service_type'),
                        project_budget: formData.get('budget'),
                        message: formData.get('message') || '来自首页询价模块',
                        captcha: formData.get('captcha'),
                        email: '',
                        company: '',
                        project_deadline: ''
                    };
                    
                    // 发送询价请求
                    fetch('/api/inquiry.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('网络响应错误: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.success) {
                            alert('询价提交成功，我们会尽快与您联系！');
                            this.reset();
                            // 刷新验证码
                            var captchaImage = this.querySelector('.captcha-image');
                            if (captchaImage) {
                                captchaImage.src = '/api/captcha.php?' + Math.random();
                            }
                        } else {
                            alert('提交失败：' + (result.message || '未知错误'));
                            // 刷新验证码
                            var captchaImage = this.querySelector('.captcha-image');
                            if (captchaImage) {
                                captchaImage.src = '/api/captcha.php?' + Math.random();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('提交错误:', error);
                        // 检查是否是网络问题
                        if (error.message.includes('Failed to fetch') || error.message.includes('网络')) {
                            alert('网络连接异常，请检查网络设置或稍后重试');
                        } else if (error.message.includes('404')) {
                            alert('请求的接口不存在，请联系网站管理员');
                        } else if (error.message.includes('500')) {
                            alert('服务器内部错误，请稍后重试或联系网站管理员');
                        } else {
                            alert('提交失败，请稍后重试\n错误信息: ' + error.message);
                        }
                    })
                    .finally(() => {
                        // 恢复按钮状态
                        if (submitBtn) {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    });
                } catch (error) {
                    console.error('Error handling form submission:', formId, error);
                    // 确保按钮状态恢复
                    var submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        } catch (error) {
            console.error('Error setting up form handler:', formId, error);
        }
    }
    
    // 初始化首页询价表单
    try {
        handleInquiryForm('main-inquiry-form');
    } catch (error) {
        console.error('Error initializing inquiry forms:', error);
    }
    
    // 初始化客户见证轮播
    try {
        initTestimonialsSlider();
    } catch (error) {
        console.error('Error initializing testimonials slider:', error);
    }
    
    // 延迟加载非关键资源
    function lazyLoadNonCriticalResources() {
        // 延迟加载非关键的第三方库或资源
        setTimeout(() => {
            // 例如，加载社交媒体分享按钮等非核心功能
        }, 1000);
    }
    
    // 执行延迟加载
    lazyLoadNonCriticalResources();
});