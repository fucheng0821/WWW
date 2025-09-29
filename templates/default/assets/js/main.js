// 前端网站主要JavaScript功能
// 全局函数，确保在所有页面都可访问
window.initMobileNavigation = function() {
    try {
        // 移动端导航切换 - 抽屉式设计
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
        const mobileNavClose = document.querySelector('.mobile-nav-close');
        
        // 移除之前可能绑定的事件监听器，防止重复绑定
        if (mobileMenuToggle) {
            // 确保移除之前的事件监听器
            const newToggle = mobileMenuToggle.cloneNode(true);
            mobileMenuToggle.parentNode.replaceChild(newToggle, mobileMenuToggle);
            newToggle.addEventListener('click', handleMobileMenuToggle);
        }
        
        if (mobileNavClose) {
            // 确保移除之前的事件监听器
            const newClose = mobileNavClose.cloneNode(true);
            mobileNavClose.parentNode.replaceChild(newClose, mobileNavClose);
            newClose.addEventListener('click', handleMobileNavClose);
        }
        
        if (mobileNavOverlay) {
            // 确保移除之前的事件监听器
            const newOverlay = mobileNavOverlay.cloneNode(true);
            mobileNavOverlay.parentNode.replaceChild(newOverlay, mobileNavOverlay);
            newOverlay.addEventListener('click', handleMobileNavOverlayClick);
        }
        
        // 按ESC键关闭导航
        function handleEscKey(e) {
            if (e.key === 'Escape') {
                closeMobileNav();
            }
        }
        
        // 使用AbortController添加事件监听器，支持干净地移除
        const escKeyController = new AbortController();
        const { signal } = escKeyController;
        document.addEventListener('keydown', handleEscKey, { signal });
        
        // 保存控制器引用以便在需要时移除监听器
        window.__escKeyController = escKeyController;
        
        // 处理移动端菜单切换
        function handleMobileMenuToggle(e) {
            e.preventDefault();
            e.stopPropagation();
            const overlay = document.querySelector('.mobile-nav-overlay');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // 防止背景滚动
                document.body.style.position = 'fixed';
                document.body.style.top = `-${window.scrollY}px`;
                document.body.style.width = '100%';
            }
        }
        
        // 处理移动端导航关闭
        function handleMobileNavClose(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileNav();
        }
        
        // 处理遮罩层点击
        function handleMobileNavOverlayClick(e) {
            if (e.target === e.currentTarget) {
                e.preventDefault();
                e.stopPropagation();
                closeMobileNav();
            }
        }
        
        // 关闭移动端导航
        function closeMobileNav() {
            const overlay = document.querySelector('.mobile-nav-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                
                // 恢复背景滚动
                const scrollY = document.body.style.top;
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.overflow = '';
                window.scrollTo(0, parseInt(scrollY || '0') * -1);
            }
        }
    } catch (error) {
        // 即使出错，也要尝试基本的功能实现
        try {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
            const mobileNavClose = document.querySelector('.mobile-nav-close');
            
            if (mobileMenuToggle && mobileNavOverlay) {
                mobileMenuToggle.onclick = function() {
                    mobileNavOverlay.classList.toggle('active');
                    document.body.style.overflow = mobileNavOverlay.classList.contains('active') ? 'hidden' : '';
                };
            }
            
            if (mobileNavClose && mobileNavOverlay) {
                mobileNavClose.onclick = function() {
                    mobileNavOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                };
            }
        } catch (fallbackError) {
            // 错误处理
        }
    }
};

// 确保DOM加载完成后执行初始化
document.addEventListener('DOMContentLoaded', function() {
    try {
        // 初始化移动端导航功能 - 确保在所有页面都优先执行
        window.initMobileNavigation();
        
        // 初始化'为什么选择我们'板块的hover交互功能
        try {
            // 直接实现图片切换功能，确保交互正常工作
            const featureItems = document.querySelectorAll('.feature-item');
            const imageItems = document.querySelectorAll('.image-item');
            let currentImageIndex = 0;
            
            if (featureItems.length > 0 && imageItems.length > 0) {
                // 初始化：显示第一个图片
                if (imageItems[0]) {
                    imageItems[0].classList.add('active');
                }
                
                // 为每个特性项单独添加鼠标悬停事件
                featureItems.forEach(item => {
                    // 鼠标进入事件
                    item.addEventListener('mouseenter', function() {
                        const index = item.getAttribute('data-index');
                        if (index !== null) {
                            // 隐藏当前活动的图片
                            const currentActiveImage = document.querySelector('.image-item.active');
                            if (currentActiveImage) {
                                currentActiveImage.classList.remove('active');
                            }
                            
                            // 移除所有特性项的活跃状态
                            featureItems.forEach(feat => {
                                feat.classList.remove('active');
                            });
                            
                            // 显示对应图片
                            const targetImage = document.querySelector(`.image-item[data-index="${index}"]`);
                            if (targetImage) {
                                targetImage.classList.add('active');
                            }
                            
                            // 高亮当前特性项
                            this.classList.add('active');
                        }
                    });
                });
            }
        } catch (error) {
            // 错误处理
        }
        
        // 返回顶部按钮
        const backToTop = document.getElementById('backToTop');
        
        if (backToTop) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('show');
                } else {
                    backToTop.classList.remove('show');
                }
            });
            
            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
        
        // 平滑滚动
        const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
        smoothScrollLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#top') {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    return;
                }
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const offsetTop = target.offsetTop - 80; // 考虑固定头部高度
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    } catch (error) {
        // 如果主初始化失败，尝试只初始化移动端导航
        try {
            window.initMobileNavigation();
        } catch (navError) {
            // 错误处理
        }
    }
});

// 确保即使DOMContentLoaded事件错过，也能初始化移动端导航
window.addEventListener('load', function() {
    try {
        // 检查是否已经初始化
        const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
        if (mobileNavOverlay && !mobileNavOverlay.classList.contains('initialized')) {
            window.initMobileNavigation();
            mobileNavOverlay.classList.add('initialized');
        }
    } catch (error) {
        // 错误处理
    }
});

// 初始化移动端导航菜单
function initMobileNavigation() {
    // 获取移动端导航元素
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    const mobileNavClose = document.querySelector('.mobile-nav-close');
    const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');

    // 检查必要元素是否存在
    if (!mobileMenuToggle || !mobileNav) {
        return;
    }

    // 添加移动端菜单切换事件监听器
    mobileMenuToggle.addEventListener('click', function() {
        mobileNav.classList.add('active');
        document.body.style.overflow = 'hidden'; // 防止背景滚动
    });

    // 添加移动端导航关闭事件监听器
    if (mobileNavClose) {
        mobileNavClose.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            document.body.style.overflow = ''; // 恢复背景滚动
        });
    }

    // 添加移动端导航遮罩层点击事件监听器
    if (mobileNavOverlay) {
        mobileNavOverlay.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            document.body.style.overflow = ''; // 恢复背景滚动
        });
    }

    // 添加ESC键关闭移动端导航事件监听器
    const controller = new AbortController();
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && mobileNav.classList.contains('active')) {
            mobileNav.classList.remove('active');
            document.body.style.overflow = ''; // 恢复背景滚动
        }
    }, { signal: controller.signal });
}

// 初始化移动端导航
initMobileNavigation();

// 平滑滚动到锚点
function initSmoothScrolling() {
    // 为所有带有href属性且指向页面内锚点的链接添加点击事件监听器
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            // 阻止默认的锚点跳转行为
            e.preventDefault();
            
            // 获取目标元素
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                // 平滑滚动到目标元素
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// 初始化平滑滚动
initSmoothScrolling();

// 图片懒加载
function initImageLazyLoading() {
    // 创建IntersectionObserver实例
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // 如果图片进入视口
            if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.dataset.src;
                
                // 如果存在data-src属性
                if (src) {
                    // 设置图片src属性
                    img.src = src;
                    // 移除data-src属性
                    img.removeAttribute('data-src');
                    // 停止观察此元素
                    observer.unobserve(img);
                }
            }
        });
    });

    // 观察所有带有data-src属性的图片
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// 初始化图片懒加载
initImageLazyLoading();

// 表单验证
function initFormValidation() {
    // 为所有带有data-validate属性的表单添加提交事件监听器
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // 验证所有带有required属性的输入框
            this.querySelectorAll('[required]').forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    // 添加错误样式
                    input.classList.add('error');
                } else {
                    // 移除错误样式
                    input.classList.remove('error');
                }
            });
            
            // 如果表单验证失败，阻止提交
            if (!isValid) {
                e.preventDefault();
                // 显示错误提示
                alert('请填写所有必填字段');
            }
        });
    });
}

// 初始化表单验证
initFormValidation();

// 动态加载更多内容
function initLoadMore() {
    // 获取"加载更多"按钮
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const page = parseInt(this.dataset.page) || 1;
            const category = this.dataset.category || '';
            
            // 显示加载状态
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 加载中...';
            this.disabled = true;
            
            // 发送AJAX请求获取更多内容
            fetch(`/api/load_more.php?page=${page + 1}&category=${category}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.html) {
                        // 将新内容添加到容器中
                        document.querySelector('.content-container').insertAdjacentHTML('beforeend', data.html);
                        // 更新按钮状态
                        this.dataset.page = page + 1;
                        this.innerHTML = '加载更多';
                        this.disabled = false;
                        
                        // 如果没有更多内容，隐藏按钮
                        if (!data.hasMore) {
                            this.style.display = 'none';
                        }
                    } else {
                        throw new Error('加载失败');
                    }
                })
                .catch(error => {
                    // 显示错误信息
                    alert('加载失败，请重试');
                    this.innerHTML = '加载更多';
                    this.disabled = false;
                });
        });
    }
}

// 初始化加载更多功能
initLoadMore();

// 懒加载图片
const lazyImages = document.querySelectorAll('img[data-src]');
if (lazyImages.length > 0) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
}

// 动画效果
const animateElements = document.querySelectorAll('.animate-on-scroll');
if (animateElements.length > 0) {
    const animationObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, {
        threshold: 0.1
    });
    
    animateElements.forEach(el => animationObserver.observe(el));
}

// 特性项悬停切换图片功能
try {
    // 直接使用图片切换功能的简化实现
    const featureItems = document.querySelectorAll('.feature-item');
    const imageItems = document.querySelectorAll('.image-item');
    let currentImageIndex = 0;
    
    if (featureItems.length > 0 && imageItems.length > 0) {
        // 初始化：显示第一个图片
        if (imageItems[0]) {
            imageItems[0].classList.add('active');
        }
        
        // 为每个特性项单独添加鼠标悬停事件
        featureItems.forEach(item => {
            // 鼠标进入事件
            item.addEventListener('mouseenter', function() {
                const index = item.getAttribute('data-index');
                if (index !== null) {
                    switchImage(parseInt(index));
                }
            });
            
            // 鼠标离开容器事件 - 防止快速移动时的问题
            item.addEventListener('mouseleave', function(e) {
                // 检查鼠标是否真的离开了整个容器
                const container = document.querySelector('.parallax-text');
                if (container && !container.contains(e.relatedTarget)) {
                    // 鼠标完全离开容器时可以添加额外逻辑
                }
            });
        });
        
        // 为每个特性项单独添加点击事件
        featureItems.forEach(item => {
            item.addEventListener('click', function() {
                const index = item.getAttribute('data-index');
                if (index !== null) {
                    switchImage(parseInt(index));
                }
            });
        });
        
        // 图片切换函数 - 改进版
        function switchImage(index) {
            // 如果是当前显示的图片，不执行切换
            if (index === currentImageIndex) {
                return;
            }
            
            // 隐藏当前活动的图片
            const currentActiveImage = document.querySelector('.image-item.active');
            if (currentActiveImage) {
                currentActiveImage.classList.remove('active');
            }
            
            // 移除所有特性项的活跃状态
            featureItems.forEach(feat => {
                feat.classList.remove('active');
            });
            
            // 显示对应图片
            const targetImage = imageItems[index];
            const targetFeature = featureItems[index];
            
            if (targetImage) {
                targetImage.classList.add('active');
            }
            
            if (targetFeature) {
                targetFeature.classList.add('active');
            }
            
            // 更新当前索引
            currentImageIndex = index;
        }
    }
} catch (error) {
    console.error('Error in feature items handling:', error);
}

// 全局滚动事件监听，确保滚动功能正常
try {
    window.addEventListener('wheel', function() {
        // 确保滚动时不会阻止默认行为
        // 不添加任何阻止默认行为的代码
    }, { passive: true });
} catch (error) {
    console.error('Error adding scroll event listener:', error);
}

// LayUI初始化
try {
    if (window.layui) {
        layui.use(['carousel', 'form', 'layer'], function(){
            var carousel = layui.carousel;
            var form = layui.form;
            var layer = layui.layer;
            

    

    
            // 表单验证
            form.verify({
                phone: function(value) {
                    if (value && !/^1[3-9]\d{9}$/.test(value)) {
                        return '请输入正确的手机号码';
                    }
                }
            });
    
            // 联系表单提交
            form.on('submit(inquiry)', function(data) {
                var loadingIndex = layer.load(2, {shade: 0.3});
                
                // 发送AJAX请求
                fetch('api/inquiry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data.field)
                })
                .then(response => response.json())
                .then(result => {
                    layer.close(loadingIndex);
                    if (result.success) {
                        layer.msg('提交成功，我们会尽快与您联系！', {
                            icon: 1,
                            time: 3000
                        });
                        document.getElementById('inquiry-form').reset();
                        form.render(); // 重新渲染表单
                    } else {
                        layer.msg(result.message || '提交失败，请稍后重试', {
                            icon: 2,
                            time: 3000
                        });
                    }
                })
                .catch(error => {
                    layer.close(loadingIndex);
                    layer.msg('网络错误，请稍后重试', {
                        icon: 2,
                        time: 3000
                    });
                });
                
                return false; // 阻止表单默认提交
            });
            
            // 表单重置
            window.resetForm = function(formId) {
                document.getElementById(formId).reset();
                form.render();
            };
        });
    }
} catch (error) {
    console.error('LayUI initialization error:', error);
}

// 工具函数
const Utils = {
    // 防抖函数
    debounce: function(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    // 节流函数
    throttle: function(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // 格式化日期
    formatDate: function(date, format) {
        format = format || 'Y-m-d H:i:s';
        var d = new Date(date);
        var map = {
            'Y': d.getFullYear(),
            'm': (d.getMonth() + 1).toString().padStart(2, '0'),
            'd': d.getDate().toString().padStart(2, '0'),
            'H': d.getHours().toString().padStart(2, '0'),
            'i': d.getMinutes().toString().padStart(2, '0'),
            's': d.getSeconds().toString().padStart(2, '0')
        };
        
        return format.replace(/[Ymdmis]/g, function(match) {
            return map[match];
        });
    },
    
    // 获取查询参数
    getQueryParam: function(name) {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },
    
    // Cookie操作
    setCookie: function(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    },
    
    getCookie: function(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    },
    
    // 本地存储
    setStorage: function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            return false;
        }
    },
    
    getStorage: function(key) {
        try {
            var value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (e) {
            return null;
        }
    },
    
    // AJAX请求封装
    ajax: function(options) {
        var defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: 10000
        };
        
        options = Object.assign(defaults, options);
        
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.timeout = options.timeout;
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject(new Error('HTTP Error: ' + xhr.status));
                    }
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Network Error'));
            };
            
            xhr.ontimeout = function() {
                reject(new Error('Request Timeout'));
            };
            
            xhr.open(options.method, options.url, true);
            
            // 设置请求头
            if (options.headers) {
                for (var header in options.headers) {
                    xhr.setRequestHeader(header, options.headers[header]);
                }
            }
            
            // 发送请求
            if (options.data) {
                if (typeof options.data === 'object') {
                    xhr.send(JSON.stringify(options.data));
                } else {
                    xhr.send(options.data);
                }
            } else {
                xhr.send();
            }
        });
    }
};

// 页面性能监控
window.addEventListener('load', function() {
    // 性能监控
    if ('performance' in window) {
        var perfData = performance.timing;
        var pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        
        // 发送性能数据（可选）
        if (pageLoadTime > 0) {
            console.log('页面加载时间:', pageLoadTime + 'ms');
        }
    }
});

// 错误监控

// 错误监控
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', {
        message: e.message,
        source: e.filename,
        line: e.lineno,
        column: e.colno,
        stack: e.error ? e.error.stack : ''
    });
});

// 导出到全局
window.Utils = Utils;

// 添加隐藏加载屏幕的函数
function hideLoadingScreen() {
    const loadingScreen = document.querySelector('.loading-screen');
    if (loadingScreen) {
        // 添加淡出效果
        loadingScreen.style.transition = 'opacity 0.5s ease-out';
        loadingScreen.style.opacity = '0';
        
        // 在过渡完成后移除元素
        setTimeout(() => {
            if (loadingScreen.parentNode) {
                loadingScreen.parentNode.removeChild(loadingScreen);
            }
        }, 500);
    }
}

// 在页面加载完成后隐藏加载屏幕
window.addEventListener('load', function() {
    hideLoadingScreen();
});

// 如果DOMContentLoaded事件已经触发，也隐藏加载屏幕
document.addEventListener('DOMContentLoaded', function() {
    // 检查页面是否已经加载完成
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        hideLoadingScreen();
    }
});

// 添加一个备用的隐藏加载屏幕方法，在5秒后自动隐藏
setTimeout(function() {
    hideLoadingScreen();
}, 5000);
