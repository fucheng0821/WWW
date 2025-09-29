/**
 * 动态Banner 3D轮播脚本
 * 为首页和内页提供现代化的3D轮播效果
 */

// 等待DOM加载完成
document.addEventListener('DOMContentLoaded', function() {
    // 初始化首页Banner 3D轮播
    initHero3DCarousel();
    
    // 初始化内页Banner 3D轮播
    initInner3DCarousel();
});

// 初始化首页Banner 3D轮播
function initHero3DCarousel() {
    const heroCarousel = document.querySelector('.dynamic-hero-carousel');
    if (!heroCarousel) return;
    
    const slides = heroCarousel.querySelectorAll('.hero-slide-3d');
    if (slides.length === 0) return;
    
    const indicators = heroCarousel.querySelectorAll('.indicator-3d');
    const prevBtn = heroCarousel.querySelector('.carousel-control-3d.prev');
    const nextBtn = heroCarousel.querySelector('.carousel-control-3d.next');
    
    let currentIndex = 0;
    let autoSlideInterval;
    
    // 初始化第一张幻灯片
    updateSlidePosition();
    
    // 更新幻灯片位置
    function updateSlidePosition() {
        slides.forEach((slide, index) => {
            slide.classList.remove('active', 'prev', 'next');
            
            if (index === currentIndex) {
                slide.classList.add('active');
            } else if (index === (currentIndex - 1 + slides.length) % slides.length) {
                slide.classList.add('prev');
            } else if (index === (currentIndex + 1) % slides.length) {
                slide.classList.add('next');
            }
        });
        
        // 更新指示器
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentIndex);
        });
    }
    
    // 显示指定索引的幻灯片
    function showSlide(index) {
        currentIndex = index;
        updateSlidePosition();
    }
    
    // 上一张幻灯片
    function prevSlide() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        updateSlidePosition();
    }
    
    // 下一张幻灯片
    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        updateSlidePosition();
    }
    
    // 上一张按钮点击事件
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            prevSlide();
            resetAutoSlide();
        });
    }
    
    // 下一张按钮点击事件
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            nextSlide();
            resetAutoSlide();
        });
    }
    
    // 指示器点击事件
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            showSlide(index);
            resetAutoSlide();
        });
    });
    
    // 自动轮播
    function startAutoSlide() {
        autoSlideInterval = setInterval(function() {
            nextSlide();
        }, 5000);
    }
    
    // 重置自动轮播
    function resetAutoSlide() {
        clearInterval(autoSlideInterval);
        startAutoSlide();
    }
    
    // 鼠标悬停时停止自动轮播，离开时重新开始
    if (heroCarousel) {
        heroCarousel.addEventListener('mouseenter', function() {
            clearInterval(autoSlideInterval);
        });
        
        heroCarousel.addEventListener('mouseleave', function() {
            resetAutoSlide();
        });
    }
    
    // 启动自动轮播
    startAutoSlide();
}

// 初始化内页Banner 3D轮播
function initInner3DCarousel() {
    const innerCarousel = document.querySelector('.dynamic-inner-carousel');
    if (!innerCarousel) return;
    
    const slides = innerCarousel.querySelectorAll('.carousel-slide-3d');
    if (slides.length === 0) return;
    
    const indicators = innerCarousel.querySelectorAll('.indicator-3d');
    const prevBtn = innerCarousel.querySelector('.carousel-control-3d.prev');
    const nextBtn = innerCarousel.querySelector('.carousel-control-3d.next');
    
    let currentIndex = 0;
    let autoSlideInterval;
    
    // 初始化第一张幻灯片
    updateSlidePosition();
    
    // 更新幻灯片位置
    function updateSlidePosition() {
        slides.forEach((slide, index) => {
            slide.classList.remove('active', 'prev', 'next');
            
            if (index === currentIndex) {
                slide.classList.add('active');
            } else if (index === (currentIndex - 1 + slides.length) % slides.length) {
                slide.classList.add('prev');
            } else if (index === (currentIndex + 1) % slides.length) {
                slide.classList.add('next');
            }
        });
        
        // 更新指示器
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentIndex);
        });
    }
    
    // 显示指定索引的幻灯片
    function showSlide(index) {
        currentIndex = index;
        updateSlidePosition();
    }
    
    // 上一张幻灯片
    function prevSlide() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        updateSlidePosition();
    }
    
    // 下一张幻灯片
    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        updateSlidePosition();
    }
    
    // 上一张按钮点击事件
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            prevSlide();
            resetAutoSlide();
        });
    }
    
    // 下一张按钮点击事件
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            nextSlide();
            resetAutoSlide();
        });
    }
    
    // 指示器点击事件
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            showSlide(index);
            resetAutoSlide();
        });
    });
    
    // 自动轮播
    function startAutoSlide() {
        autoSlideInterval = setInterval(function() {
            nextSlide();
        }, 5000);
    }
    
    // 重置自动轮播
    function resetAutoSlide() {
        clearInterval(autoSlideInterval);
        startAutoSlide();
    }
    
    // 鼠标悬停时停止自动轮播，离开时重新开始
    if (innerCarousel) {
        innerCarousel.addEventListener('mouseenter', function() {
            clearInterval(autoSlideInterval);
        });
        
        innerCarousel.addEventListener('mouseleave', function() {
            resetAutoSlide();
        });
    }
    
    // 启动自动轮播
    startAutoSlide();
}

// 页面可见性API支持 - 当页面不可见时暂停轮播
document.addEventListener('visibilitychange', function() {
    const heroCarousel = document.querySelector('.dynamic-hero-carousel');
    const innerCarousel = document.querySelector('.dynamic-inner-carousel');
    
    if (document.hidden) {
        // 页面隐藏时暂停所有轮播
        const intervals = document.querySelectorAll('[data-carousel-interval]');
        intervals.forEach(interval => {
            clearInterval(interval.dataset.carouselInterval);
        });
    } else {
        // 页面显示时重新启动轮播
        initHero3DCarousel();
        initInner3DCarousel();
    }
});