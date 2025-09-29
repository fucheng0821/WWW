// 模块滑动功能
document.addEventListener('DOMContentLoaded', function() {
    // 客户见证滑动功能
    const testimonialsContainers = document.querySelectorAll('.testimonials-slider');
    testimonialsContainers.forEach(container => {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        container.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });
        
        container.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        container.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        });
    });
    
    // 新闻资讯滑动功能
    const newsContainers = document.querySelectorAll('.news-slider');
    newsContainers.forEach(container => {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        container.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });
        
        container.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        container.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        });
    });
    
    // 触摸设备支持
    const touchContainers = document.querySelectorAll('.testimonials-slider, .news-slider');
    touchContainers.forEach(container => {
        let startX;
        let startY;
        
        container.addEventListener('touchstart', (e) => {
            startX = e.touches[0].pageX;
            startY = e.touches[0].pageY;
        });
        
        container.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;
            
            const endX = e.touches[0].pageX;
            const endY = e.touches[0].pageY;
            
            const diffX = startX - endX;
            const diffY = startY - endY;
            
            // 如果水平滑动距离大于垂直滑动距离，则阻止默认行为
            if (Math.abs(diffX) > Math.abs(diffY)) {
                e.preventDefault();
                container.scrollLeft += diffX;
            }
        });
        
        container.addEventListener('touchend', () => {
            startX = null;
            startY = null;
        });
    });
    
    // 客户评价轮播功能
    const initTestimonialsCarousel = function() {
        const carousel = document.querySelector('.testimonials-wrapper');
        if (!carousel) return;
        
        const slides = carousel.querySelectorAll('.testimonial-slide');
        const indicators = carousel.querySelectorAll('.indicator');

        
        if (slides.length <= 1) return;
        
        // 初始化第一个幻灯片
        slides[0].classList.add('active');
        let currentIndex = 0;
        let intervalId = null;
        
        const showSlide = function(index) {
            // 隐藏所有幻灯片
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            if (indicators.length > 0) {
                indicators.forEach(indicator => indicator.classList.remove('active'));
            }
            
            // 显示指定幻灯片
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            if (indicators[index]) {
                indicators[index].classList.add('active');
            }
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
        
        // 绑定指示器事件
        if (indicators.length > 0) {
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', function() {
                    stopAutoPlay();
                    showSlide(index);
                    startAutoPlay();
                });
            });
        }
        
        // 鼠标悬停时暂停自动播放
        if (carousel) {
            carousel.addEventListener('mouseenter', stopAutoPlay);
            carousel.addEventListener('mouseleave', startAutoPlay);
        }
        
        // 开始自动播放
        startAutoPlay();
    };
    
    // 初始化客户评价轮播
    initTestimonialsCarousel();
    
    // 确保DOM完全加载后再初始化
    setTimeout(initTestimonialsCarousel, 100);
});