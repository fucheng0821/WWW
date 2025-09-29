// 增强版前端交互效果
document.addEventListener('DOMContentLoaded', function() {
    // 服务卡片悬停效果增强
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 案例项目悬停效果增强
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    portfolioItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 新闻卡片悬停效果增强
    const newsCards = document.querySelectorAll('.news-card');
    newsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 合作伙伴悬停效果增强
    const partnerItems = document.querySelectorAll('.partner-item');
    partnerItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 头部滚动效果
    const siteHeader = document.querySelector('.site-header');
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            siteHeader.classList.add('scrolled');
        } else {
            siteHeader.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    });
    
    // 页面加载动画
    const loadingScreen = document.querySelector('.loading-screen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.classList.add('hidden');
        }, 1000);
    }
    
    // 页面加载完成后触发动画
    window.addEventListener('load', function() {
        setTimeout(() => {
            const firstElements = document.querySelectorAll('[data-animate]');
            firstElements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('animate');
                }, index * 100);
            });
        }, 500);
    });
    
    // 数字动画效果
    const animateNumbers = document.querySelectorAll('.animate-number');
    if (animateNumbers.length > 0) {
        const numberObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const targetNumber = parseInt(element.getAttribute('data-number'));
                    const duration = 2000;
                    const step = targetNumber / (duration / 16);
                    let currentNumber = 0;
                    
                    const timer = setInterval(() => {
                        currentNumber += step;
                        if (currentNumber >= targetNumber) {
                            currentNumber = targetNumber;
                            clearInterval(timer);
                        }
                        element.textContent = Math.floor(currentNumber);
                    }, 16);
                    
                    numberObserver.unobserve(element);
                }
            });
        }, {
            threshold: 0.5
        });
        
        animateNumbers.forEach(el => numberObserver.observe(el));
    }
    
    // 微信二维码显示/隐藏
    const wechatBtn = document.querySelector('.floating-wechat');
    if (wechatBtn) {
        const qrCode = wechatBtn.querySelector('.wechat-qr');
        wechatBtn.addEventListener('mouseenter', function() {
            qrCode.style.opacity = '1';
            qrCode.style.visibility = 'visible';
            qrCode.style.transform = 'translateY(0)';
        });
        
        wechatBtn.addEventListener('mouseleave', function() {
            qrCode.style.opacity = '0';
            qrCode.style.visibility = 'hidden';
            qrCode.style.transform = 'translateY(10px)';
        });
    }
    
    // 页面元素动画
    const animateElements = document.querySelectorAll('[data-animate]');
    if (animateElements.length > 0) {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const animationClass = element.getAttribute('data-animate');
                    element.classList.add('animate');
                    animationObserver.unobserve(element);
                }
            });
        }, {
            threshold: 0.1
        });
        
        animateElements.forEach(el => animationObserver.observe(el));
    }
    
    // 内页Banner轮播控制
    const initInnerBannerCarousel = function() {
        const carousel = document.querySelector('.modern-carousel');
        if (!carousel) return;
        
        const slides = carousel.querySelectorAll('.carousel-slide');
        const indicators = carousel.querySelectorAll('.indicator');
        const prevBtn = carousel.querySelector('.carousel-control.prev');
        const nextBtn = carousel.querySelector('.carousel-control.next');
        
        if (slides.length <= 1) return;
        
        let currentIndex = 0;
        let intervalId = null;
        
        const showSlide = function(index) {
            // 隐藏所有幻灯片
            slides.forEach(slide => {
                slide.classList.remove('active');
                slide.style.opacity = '0';
            });
            indicators.forEach(indicator => indicator.classList.remove('active'));
            
            // 显示指定幻灯片
            slides[index].classList.add('active');
            slides[index].style.opacity = '1';
            indicators[index].classList.add('active');
            currentIndex = index;
        };
        
        const nextSlide = function() {
            let newIndex = currentIndex + 1;
            if (newIndex >= slides.length) newIndex = 0;
            showSlide(newIndex);
        };
        
        const prevSlide = function() {
            let newIndex = currentIndex - 1;
            if (newIndex < 0) newIndex = slides.length - 1;
            showSlide(newIndex);
        };
        
        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
        }
        
        // 指示器点击事件
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', function() {
                showSlide(index);
            });
        });
        
        // 自动轮播
        const startAutoPlay = function() {
            intervalId = setInterval(nextSlide, 5000);
        };
        
        const stopAutoPlay = function() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }
        };
        
        // 鼠标悬停时暂停自动播放
        carousel.addEventListener('mouseenter', stopAutoPlay);
        carousel.addEventListener('mouseleave', startAutoPlay);
        
        // 开始自动播放
        startAutoPlay();
    };
    
    // 初始化内页Banner轮播
    initInnerBannerCarousel();
});

// 自定义动画效果
const CustomAnimations = {
    // 淡入动画
    fadeIn: function(element, duration = 600) {
        element.style.opacity = '0';
        element.style.transition = `opacity ${duration}ms ease`;
        
        setTimeout(() => {
            element.style.opacity = '1';
        }, 100);
    },
    
    // 滑动动画
    slideIn: function(element, direction = 'up', duration = 600) {
        const transformMap = {
            'up': 'translateY(50px)',
            'down': 'translateY(-50px)',
            'left': 'translateX(50px)',
            'right': 'translateX(-50px)'
        };
        
        element.style.opacity = '0';
        element.style.transform = transformMap[direction];
        element.style.transition = `all ${duration}ms ease`;
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translate(0, 0)';
        }, 100);
    },
    
    // 缩放动画
    scaleIn: function(element, duration = 600) {
        element.style.opacity = '0';
        element.style.transform = 'scale(0.8)';
        element.style.transition = `all ${duration}ms ease`;
        
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'scale(1)';
        }, 100);
    }
};

// 导出到全局
window.CustomAnimations = CustomAnimations;