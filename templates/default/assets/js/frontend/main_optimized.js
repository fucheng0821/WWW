!function(){"use strict";function t(){document.addEventListener("DOMContentLoaded",function(){e(),o(),i(),n(),r()})}function e(){const t=document.querySelector(".mobile-menu-toggle"),e=document.querySelector(".mobile-nav"),o=document.querySelector("body");t&&(t.addEventListener("click",function(){e.classList.toggle("active"),o.classList.toggle("nav-open")}),document.addEventListener("click",function(t){!e.contains(t.target)&&!t.target.closest(".mobile-menu-toggle")&&e.classList.contains("active")&&(e.classList.remove("active"),o.classList.remove("nav-open"))}))}function o(){const t=document.querySelector(".back-to-top");t&&(window.addEventListener("scroll",function(){window.pageYOffset>300?t.classList.add("visible"):t.classList.remove("visible")}),t.addEventListener("click",function(){window.scrollTo({top:0,behavior:"smooth"})}))}function i(){document.querySelectorAll('a[href^="#"]').forEach(t=>{t.addEventListener("click",function(e){e.preventDefault();const o=document.querySelector(this.getAttribute("href"));o&&window.scrollTo({top:o.offsetTop-80,behavior:"smooth"})})})}function n(){if("IntersectionObserver"in window){const t=new IntersectionObserver((e,o)=>{e.forEach(e=>{if(e.isIntersecting){const t=e.target.getAttribute("data-src");t&&(e.target.src=t),o.unobserve(e.target)}})},{});document.querySelectorAll(".lazy-load").forEach(e=>{t.observe(e)})}}function r(){if("IntersectionObserver"in window){const t=new IntersectionObserver((e,o)=>{e.forEach(e=>{e.isIntersecting&&(e.target.classList.add("fade-in-up"),o.unobserve(e.target))})},{});document.querySelectorAll(".animate-on-scroll").forEach(e=>{t.observe(e)})}}function a(){const t=document.querySelectorAll(".feature-item");t.forEach(e=>{const o=e.querySelector(".feature-image img");if(o){const i=o.getAttribute("data-hover-src");i&&(e.addEventListener("mouseenter",function(){o.setAttribute("data-original-src",o.src),o.src=i}),e.addEventListener("mouseleave",function(){o.src=o.getAttribute("data-original-src")}))}})}function c(){const t=document.querySelector(".testimonials-carousel");if(t){let e=0,o=!1,i=t.querySelectorAll(".testimonial-slide"),n=t.querySelectorAll(".carousel-indicators button");function r(){i.forEach(t=>t.classList.remove("active")),n.forEach(t=>t.classList.remove("active")),i[e].classList.add("active"),n[e].classList.add("active")}function a(){e=(e+1)%i.length,r()}function c(){e=(e+i.length-1)%i.length,r()}r(),t.querySelector(".carousel-control.prev")?.addEventListener("click",c),t.querySelector(".carousel-control.next")?.addEventListener("click",a),n.forEach((t,o)=>{t.addEventListener("click",()=>(e=o,r()))}),t.addEventListener("mouseenter",()=>{clearInterval(o)}),t.addEventListener("mouseleave",()=>{o=setInterval(a,5000)}),o=setInterval(a,5000)}}function l(){try{if(window.layui){layui.use(["carousel","form","layer"],function(){const t=layui.carousel,e=layui.form,o=layui.layer;document.getElementById("hero-carousel")&&t.render({elem:"#hero-carousel",width:"100%",height:"100%",arrow:"hover",indicator:"inside",autoplay:!0,interval:5e3}),document.getElementById("testimonials-carousel")&&t.render({elem:"#testimonials-carousel",width:"100%",height:"200px",arrow:"none",indicator:"outside",autoplay:!0,interval:4e3}),e.verify({phone:function(t){return t&&!/^1[3-9]\d{9}$/.test(t)?"请输入正确的手机号码":void 0}}),e.on("submit(inquiry)",function(t){const i=o.load(2,{shade:.3});return fetch("api/inquiry.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(t.field)}).then(t=>t.json()).then(t=>{o.close(i),t.success?(o.msg("提交成功，我们会尽快与您联系！",{icon:1,time:3e3}),document.getElementById("inquiry-form").reset(),e.render()):o.msg(t.message||"提交失败，请稍后重试",{icon:2,time:3e3})}).catch(()=>{o.close(i),o.msg("网络错误，请稍后重试",{icon:2,time:3e3})}),!1}),window.resetForm=function(t){document.getElementById(t).reset(),e.render()})}}catch(t){console.error("LayUI initialization error:",t)}}function u(){const t={debounce:function(t,e,o){let i;return function(){const n=this,r=arguments,a=function(){i=null,o||t.apply(n,r)},c=o&&!i;clearTimeout(i),i=setTimeout(a,e),c&&t.apply(n,r)}},throttle:function(t,e){let o;return function(){const i=arguments,n=this;!o&&(t.apply(n,i),o=setTimeout(()=>{o=!1},e))}},formatDate:function(t,e){e=e||"Y-m-d H:i:s";const o=new Date(t),i={Y:o.getFullYear(),m:(o.getMonth()+1).toString().padStart(2,"0"),d:o.getDate().toString().padStart(2,"0"),H:o.getHours().toString().padStart(2,"0"),i:o.getMinutes().toString().padStart(2,"0"),s:o.getSeconds().toString().padStart(2,"0")};return e.replace(/[Ymdmis]/g,function(t){return i[t]})},getQueryParam:function(t){return new URLSearchParams(window.location.search).get(t)},setCookie:function(t,e,o){let i="";if(o){const n=new Date;n.setTime(n.getTime()+24*o*60*60*1e3),i="; expires="+n.toUTCString()}document.cookie=t+"="+(e||"")+i+"; path=/"},getCookie:function(t){const e=t+"=";for(let o=document.cookie.split(";");o.length>0;){const i=o.shift().trim();if(0===i.indexOf(e))return i.substring(e.length,i.length)}return null},setStorage:function(t,e){try{return localStorage.setItem(t,JSON.stringify(e)),!0}catch(t){return!1}},getStorage:function(t){try{const e=localStorage.getItem(t);return e?JSON.parse(e):null}catch(t){return null}},ajax:function(t){const e={method:"GET",headers:{"Content-Type":"application/json"},timeout:1e4},o=Object.assign(e,t);return new Promise((e,i)=>{const n=new XMLHttpRequest;n.timeout=o.timeout,n.onreadystatechange=function(){if(4===n.readyState)if(n.status>=200&&n.status<300)try{e(JSON.parse(n.responseText))}catch(t){e(n.responseText)}else i(new Error("HTTP Error: "+n.status))},n.onerror=function(){i(new Error("Network Error"))},n.ontimeout=function(){i(new Error("Request Timeout"))},n.open(o.method,o.url,!0);if(o.headers)for(let t in o.headers)n.setRequestHeader(t,o.headers[t]);if(o.data)n.send("object"==typeof o.data?JSON.stringify(o.data):o.data);else n.send()})}};window.Utils=t}function s(){document.addEventListener("DOMContentLoaded",function(){const t=[".service-card",".portfolio-item",".news-card",".partner-item"].map(e=>document.querySelectorAll(e)).flat();t.forEach(t=>{t.addEventListener("mouseenter",function(){this.style.transform="translateY(-5px)"}),t.addEventListener("mouseleave",function(){this.style.transform="translateY(0)"})});const e=document.querySelector(".site-header");e&&window.addEventListener("scroll",function(){window.pageYOffset>100?e.classList.add("scrolled"):e.classList.remove("scrolled")});const o=document.querySelector(".loading-screen");o&&setTimeout(()=>{o.classList.add("hidden")},1e3);window.addEventListener("load",function(){setTimeout(()=>{const t=document.querySelectorAll('[data-animate]');t.forEach((e,o)=>{setTimeout(()=>{e.classList.add("animate")},100*o)})},500)});const i=document.querySelectorAll(".animate-number");if(i.length>0){const t=new IntersectionObserver((t,e)=>{t.forEach(t=>{if(t.isIntersecting){const o=t.target,i=parseInt(o.getAttribute("data-number"),10),n=2e3,r=i/(n/16);let a=0;const c=setInterval(()=>{(a+=r)>=i&&(a=i,clearInterval(c)),o.textContent=Math.floor(a)},16);e.unobserve(o)}})},{threshold:.5});i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease",setTimeout(()=>{t.style.opacity="1",t.style.transform="translateY(0)"},300),t.textContent="0",t.style.minWidth="30px",t.style.display="inline-block"}),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),// 自定义动画类
class Animations {
  static fadeIn(element, duration = 500) {
    element.style.opacity = '0';
    element.style.transition = `opacity ${duration}ms ease`;
    element.classList.add('animated');
    setTimeout(() => {
      element.style.opacity = '1';
    }, 50);
  }

  static slideUp(element, duration = 500) {
    const height = element.offsetHeight;
    element.style.height = `${height}px`;
    element.style.overflow = 'hidden';
    element.style.opacity = '0';
    element.style.transform = 'translateY(20px)';
    element.style.transition = `all ${duration}ms ease`;
    element.classList.add('animated');
    setTimeout(() => {
      element.style.height = 'auto';
      element.style.opacity = '1';
      element.style.transform = 'translateY(0)';
    }, 50);
  }

  static zoomIn(element, duration = 500) {
    element.style.opacity = '0';
    element.style.transform = 'scale(0.9)';
    element.style.transition = `all ${duration}ms ease`;
    element.classList.add('animated');
    setTimeout(() => {
      element.style.opacity = '1';
      element.style.transform = 'scale(1)';
    }, 50);
  }
}

// 模块函数整合
function initClientTestimonialsSlider() {
  const testimonialWrapper = document.querySelector('.testimonial-wrapper');
  const testimonialSlides = document.querySelectorAll('.testimonial-slide');
  const prevBtn = document.querySelector('.testimonial-prev');
  const nextBtn = document.querySelector('.testimonial-next');
  const indicators = document.querySelectorAll('.testimonial-indicator');
  
  if (!testimonialWrapper || testimonialSlides.length === 0) return;
  
  let currentIndex = 0;
  const totalSlides = testimonialSlides.length;
  const slideWidth = testimonialSlides[0].offsetWidth;
  let autoPlayInterval;
  
  // 初始化显示
  function updateSlider() {
    testimonialWrapper.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
    testimonialWrapper.style.transition = 'transform 0.5s ease';
    
    // 更新指示器
    indicators.forEach((indicator, index) => {
      if (index === currentIndex) {
        indicator.classList.add('active');
      } else {
        indicator.classList.remove('active');
      }
    });
  }
  
  // 下一张
  function goToNextSlide() {
    currentIndex = (currentIndex + 1) % totalSlides;
    updateSlider();
  }
  
  // 上一张
  function goToPrevSlide() {
    currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
    updateSlider();
  }
  
  // 跳转到指定幻灯片
  function goToSlide(index) {
    currentIndex = index;
    updateSlider();
  }
  
  // 自动播放
  function startAutoPlay() {
    autoPlayInterval = setInterval(goToNextSlide, 5000);
  }
  
  // 停止自动播放
  function stopAutoPlay() {
    clearInterval(autoPlayInterval);
  }
  
  // 事件监听
  if (prevBtn) prevBtn.addEventListener('click', () => { stopAutoPlay(); goToPrevSlide(); startAutoPlay(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { stopAutoPlay(); goToNextSlide(); startAutoPlay(); });
  
  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => { stopAutoPlay(); goToSlide(index); startAutoPlay(); });
  });
  
  // 鼠标悬停暂停/离开继续
  testimonialWrapper.addEventListener('mouseenter', stopAutoPlay);
  testimonialWrapper.addEventListener('mouseleave', startAutoPlay);
  
  // 响应式调整
  window.addEventListener('resize', () => {
    updateSlider();
  });
  
  // 开始自动播放
  startAutoPlay();
}

function initNewsSlider() {
  const newsWrapper = document.querySelector('.news-slider-wrapper');
  const newsSlides = document.querySelectorAll('.news-slide');
  const prevBtn = document.querySelector('.news-prev');
  const nextBtn = document.querySelector('.news-next');
  
  if (!newsWrapper || newsSlides.length === 0) return;
  
  let currentIndex = 0;
  const slidesPerView = window.innerWidth < 768 ? 1 : 3;
  const totalSlides = newsSlides.length;
  const slideWidth = 100 / slidesPerView;
  
  // 设置幻灯片样式
  newsSlides.forEach(slide => {
    slide.style.width = `${slideWidth}%`;
  });
  
  // 更新轮播
  function updateSlider() {
    const maxIndex = Math.ceil(totalSlides / slidesPerView) - 1;
    currentIndex = Math.min(currentIndex, maxIndex);
    newsWrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
    newsWrapper.style.transition = 'transform 0.5s ease';
  }
  
  // 下一页
  function goToNextSlide() {
    const maxIndex = Math.ceil(totalSlides / slidesPerView) - 1;
    if (currentIndex < maxIndex) {
      currentIndex++;
      updateSlider();
    }
  }
  
  // 上一页
  function goToPrevSlide() {
    if (currentIndex > 0) {
      currentIndex--;
      updateSlider();
    }
  }
  
  // 事件监听
  if (prevBtn) prevBtn.addEventListener('click', goToPrevSlide);
  if (nextBtn) nextBtn.addEventListener('click', goToNextSlide);
  
  // 响应式调整
  window.addEventListener('resize', () => {
    updateSlider();
  });
}

function initTouchSwipe() {
  const touchElements = document.querySelectorAll('.swipeable');
  let touchStartX = 0;
  let touchEndX = 0;
  let touchStartY = 0;
  let touchEndY = 0;
  
  touchElements.forEach(element => {
    element.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
      touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    
    element.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      touchEndY = e.changedTouches[0].screenY;
      handleSwipe(element);
    }, { passive: true });
  });
  
  function handleSwipe(element) {
    const diffX = touchEndX - touchStartX;
    const diffY = touchEndY - touchStartY;
    
    // 判断是水平滑动还是垂直滑动
    if (Math.abs(diffX) > Math.abs(diffY)) {
      // 水平滑动
      if (diffX > 50) {
        // 向右滑动
        const prevBtn = element.querySelector('.prev, .testimonial-prev, .news-prev');
        if (prevBtn) prevBtn.click();
      } else if (diffX < -50) {
        // 向左滑动
        const nextBtn = element.querySelector('.next, .testimonial-next, .news-next');
        if (nextBtn) nextBtn.click();
      }
    }
  }
}

function initEnhancedEffects() {
  // 服务卡片悬停效果
  const serviceCards = document.querySelectorAll('.service-card');
  serviceCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-10px)';
      card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.1)';
      card.style.transition = 'all 0.3s ease';
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0)';
      card.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
    });
  });
  
  // 案例项目悬停效果
  const projectCards = document.querySelectorAll('.project-card');
  projectCards.forEach(card => {
    const overlay = card.querySelector('.project-overlay');
    if (overlay) {
      card.addEventListener('mouseenter', () => {
        overlay.style.opacity = '1';
        overlay.style.transform = 'translateY(0)';
        overlay.style.transition = 'all 0.3s ease';
      });
      
      card.addEventListener('mouseleave', () => {
        overlay.style.opacity = '0';
        overlay.style.transform = 'translateY(20px)';
      });
    }
  });
  
  // 新闻卡片悬停效果
  const newsCards = document.querySelectorAll('.news-card');
  newsCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-5px)';
      card.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
      card.style.transition = 'all 0.3s ease';
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0)';
      card.style.boxShadow = '0 5px 10px rgba(0, 0, 0, 0.05)';
    });
  });
  
  // 合作伙伴悬停效果
  const partnerLogos = document.querySelectorAll('.partner-logo');
  partnerLogos.forEach(logo => {
    logo.addEventListener('mouseenter', () => {
      logo.style.transform = 'scale(1.1)';
      logo.style.transition = 'transform 0.3s ease';
    });
    
    logo.addEventListener('mouseleave', () => {
      logo.style.transform = 'scale(1)';
    });
  });
  
  // 微信二维码显示控制
  const wechatIcon = document.querySelector('.wechat-icon');
  const wechatQr = document.querySelector('.wechat-qr');
  
  if (wechatIcon && wechatQr) {
    wechatIcon.addEventListener('mouseenter', () => {
      wechatQr.style.display = 'block';
    });
    
    wechatIcon.addEventListener('mouseleave', () => {
      wechatQr.style.display = 'none';
    });
  }
}

function initHeaderScrollEffects() {
  const header = document.querySelector('header');
  if (!header) return;
  
  let lastScrollTop = 0;
  
  function handleScroll() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
    
    lastScrollTop = scrollTop;
  }
  
  window.addEventListener('scroll', Utils.throttle(handleScroll, 100), { passive: true });
}

function initPageLoadAnimation() {
  const loadingScreen = document.querySelector('.loading-screen');
  
  if (loadingScreen) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        loadingScreen.style.opacity = '0';
        loadingScreen.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
          loadingScreen.style.display = 'none';
        }, 500);
      }, 500);
    });
  }
}

function initNumberAnimation() {
  const numberElements = document.querySelectorAll('.animate-number');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const element = entry.target;
        const targetNumber = parseInt(element.getAttribute('data-target'), 10);
        const duration = 2000; // 动画持续时间
        const startTime = performance.now();
        
        function updateNumber(currentTime) {
          const elapsedTime = currentTime - startTime;
          const progress = Math.min(elapsedTime / duration, 1);
          const currentNumber = Math.floor(progress * targetNumber);
          
          element.textContent = currentNumber.toLocaleString();
          
          if (progress < 1) {
            requestAnimationFrame(updateNumber);
          }
        }
        
        requestAnimationFrame(updateNumber);
        observer.unobserve(element);
      }
    });
  }, { threshold: 0.1 });
  
  numberElements.forEach(element => {
    observer.observe(element);
  });
}

function initElementVisibilityAnimation() {
  const animatedElements = document.querySelectorAll('.animate-on-scroll');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
        const element = entry.target;
        const animationType = element.getAttribute('data-animation') || 'fadeIn';
        
        if (Animations[animationType]) {
          Animations[animationType](element);
        } else {
          Animations.fadeIn(element);
        }
      }
    });
  }, { threshold: 0.1 });
  
  animatedElements.forEach(element => {
    observer.observe(element);
  });
}

function initInnerBannerSlider() {
  const bannerWrapper = document.querySelector('.inner-banner-wrapper');
  const bannerSlides = document.querySelectorAll('.inner-banner-slide');
  const prevBtn = document.querySelector('.inner-banner-prev');
  const nextBtn = document.querySelector('.inner-banner-next');
  const indicators = document.querySelectorAll('.inner-banner-indicator');
  
  if (!bannerWrapper || bannerSlides.length === 0) return;
  
  let currentIndex = 0;
  const totalSlides = bannerSlides.length;
  let autoPlayInterval;
  
  // 初始化显示
  function updateSlider() {
    bannerSlides.forEach((slide, index) => {
      slide.style.display = index === currentIndex ? 'block' : 'none';
      slide.style.opacity = index === currentIndex ? '1' : '0';
      slide.style.transition = 'opacity 0.5s ease';
    });
    
    // 更新指示器
    if (indicators.length > 0) {
      indicators.forEach((indicator, index) => {
        if (index === currentIndex) {
          indicator.classList.add('active');
        } else {
          indicator.classList.remove('active');
        }
      });
    }
  }
  
  // 下一张
  function goToNextSlide() {
    currentIndex = (currentIndex + 1) % totalSlides;
    updateSlider();
  }
  
  // 上一张
  function goToPrevSlide() {
    currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
    updateSlider();
  }
  
  // 跳转到指定幻灯片
  function goToSlide(index) {
    currentIndex = index;
    updateSlider();
  }
  
  // 自动播放
  function startAutoPlay() {
    autoPlayInterval = setInterval(goToNextSlide, 6000);
  }
  
  // 停止自动播放
  function stopAutoPlay() {
    clearInterval(autoPlayInterval);
  }
  
  // 事件监听
  if (prevBtn) prevBtn.addEventListener('click', () => { stopAutoPlay(); goToPrevSlide(); startAutoPlay(); });
  if (nextBtn) nextBtn.addEventListener('click', () => { stopAutoPlay(); goToNextSlide(); startAutoPlay(); });
  
  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => { stopAutoPlay(); goToSlide(index); startAutoPlay(); });
  });
  
  // 开始自动播放
  startAutoPlay();
  
  // 初始化显示第一张
  updateSlider();
}

// 页面性能监控
function monitorPerformance() {
  if ('performance' in window && 'timing' in window.performance) {
    const timing = window.performance.timing;
    const loadTime = timing.loadEventEnd - timing.navigationStart;
    
    // 记录页面加载时间
    if (loadTime > 0) {
      console.log(`页面加载时间: ${loadTime}ms`);
      // 可以在这里添加性能数据上报逻辑
    }
  }
}

// JavaScript错误监控
function monitorJavaScriptErrors() {
  window.addEventListener('error', (errorEvent) => {
    const errorInfo = {
      message: errorEvent.message,
      source: errorEvent.filename,
      line: errorEvent.lineno,
      column: errorEvent.colno,
      error: errorEvent.error ? errorEvent.error.stack : 'No stack available'
    };
    
    console.error('JavaScript错误:', errorInfo);
    // 可以在这里添加错误上报逻辑
  });
  
  // 捕获未处理的Promise拒绝
  window.addEventListener('unhandledrejection', (event) => {
    const rejectionInfo = {
      reason: event.reason,
      promise: event.promise
    };
    
    console.error('未处理的Promise拒绝:', rejectionInfo);
    // 可以在这里添加错误上报逻辑
  });
}

// 初始化所有功能
function initAllFeatures() {
  try {
    // 基础功能
    initMobileNavigation();
    initBackToTop();
    initSmoothScroll();
    initImageLazyLoading();
    initAnimationEffects();
    initFeatureItemHover();
    initClientTestimonialsSlider();
    initNewsSlider();
    initTouchSwipe();
    initLayuiComponents();
    
    // 增强效果
    initEnhancedEffects();
    initHeaderScrollEffects();
    initPageLoadAnimation();
    initNumberAnimation();
    initElementVisibilityAnimation();
    initInnerBannerSlider();
    
    // 性能监控
    monitorPerformance();
    monitorJavaScriptErrors();
    
    console.log('所有功能初始化完成');
  } catch (error) {
    console.error('初始化过程中出错:', error);
  }
}

// 页面加载完成后初始化所有功能
document.addEventListener('DOMContentLoaded', initAllFeatures);

// 导出工具函数和动画类到全局window对象
window.Utils = Utils;
window.Animations = Animations;i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{i.forEach(t=>{t.style.opacity="1",t.style.transform="translateY(0)"})},300),i.forEach(t=>t.textContent="0"),i.forEach(t=>t.style.minWidth="30px"),i.forEach(t=>t.style.display="inline-block"),i.forEach(t=>t.textContent="0"),i.forEach(t=>{t.style.opacity="0",t.style.transform="translateY(20px)",t.style.transition="opacity 0.5s ease, transform 0.5s ease"}),setTimeout(()=>{