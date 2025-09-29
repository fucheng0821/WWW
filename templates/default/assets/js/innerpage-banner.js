/**
 * 内页Banner轮播通用脚本
 * 适配content.php、detail.php、list.php、channel.php等页面的Banner轮播功能
 */

// 等待DOM加载完成
document.addEventListener('DOMContentLoaded', function() {
    // 尝试获取各种可能的轮播容器元素
    const carouselContainers = [
        document.getElementById('inner-banner-carousel'),
        document.querySelector('.inner-banner'),
        document.querySelector('.carousel-container')
    ];
    
    // 找到第一个存在的轮播容器
    let carousel = null;
    for (let container of carouselContainers) {
        if (container) {
            carousel = container;
            break;
        }
    }
    
    if (!carousel) return;
    
    // 尝试获取各种可能的幻灯片元素
    const slides = carousel.querySelectorAll('.carousel-slide, .banner-slide');
    if (slides.length === 0) return;
    
    // 尝试获取各种可能的指示器元素
    let indicators = [];
    const indicatorSelectors = [
        '.carousel-indicators .indicator',
        '.banner-indicators li',
        '.indicator'
    ];
    
    for (let selector of indicatorSelectors) {
        indicators = carousel.querySelectorAll(selector);
        if (indicators.length > 0) break;
    }
    
    // 尝试获取各种可能的控制按钮
    const prevBtn = carousel.querySelector('#carousel-prev, .banner-prev, .carousel-control.prev');
    const nextBtn = carousel.querySelector('#carousel-next, .banner-next, .carousel-control.next');
    
    let currentIndex = 0;
    
    // 初始化第一张幻灯片
    if (slides.length > 0) {
        slides[0].classList.add('active');
        if (indicators.length > 0) {
            indicators[0].classList.add('active');
        }
    }
    
    // 显示指定索引的幻灯片
    function showSlide(index) {
        // 隐藏所有幻灯片
        slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // 移除所有指示器的活跃状态
        indicators.forEach(indicator => {
            indicator.classList.remove('active');
        });
        
        // 显示当前幻灯片并高亮当前指示器
        slides[index].classList.add('active');
        if (indicators.length > 0 && indicators[index]) {
            indicators[index].classList.add('active');
        }
        currentIndex = index;
    }
    
    // 上一张幻灯片按钮点击事件
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            let newIndex = currentIndex - 1;
            if (newIndex < 0) newIndex = slides.length - 1;
            showSlide(newIndex);
        });
    }
    
    // 下一张幻灯片按钮点击事件
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            let newIndex = currentIndex + 1;
            if (newIndex >= slides.length) newIndex = 0;
            showSlide(newIndex);
        });
    }
    
    // 指示器点击事件
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            showSlide(index);
        });
    });
    
    // 自动轮播
    let autoSlideInterval = setInterval(function() {
        let newIndex = currentIndex + 1;
        if (newIndex >= slides.length) newIndex = 0;
        showSlide(newIndex);
    }, 5000);
    
    // 鼠标悬停时停止自动轮播，离开时重新开始
    if (carousel) {
        carousel.addEventListener('mouseenter', function() {
            clearInterval(autoSlideInterval);
        });
        
        carousel.addEventListener('mouseleave', function() {
            autoSlideInterval = setInterval(function() {
                let newIndex = currentIndex + 1;
                if (newIndex >= slides.length) newIndex = 0;
                showSlide(newIndex);
            }, 5000);
        });
    }
});