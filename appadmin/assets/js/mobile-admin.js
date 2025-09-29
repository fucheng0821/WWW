// 移动端管理后台JavaScript功能

document.addEventListener('DOMContentLoaded', function() {
    // 获取DOM元素
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('overlay');
    const notificationBtn = document.getElementById('notificationBtn');
    
    // 侧边栏开关功能
    if (menuToggle && mobileSidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            mobileSidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // 添加侧边栏打开动画
            setTimeout(() => {
                mobileSidebar.style.transform = 'translateX(0)';
            }, 10);
        });
    }
    
    if (closeSidebar && mobileSidebar && overlay) {
        closeSidebar.addEventListener('click', function() {
            // 添加侧边栏关闭动画
            mobileSidebar.style.transform = 'translateX(-100%)';
            
            // 等待动画完成后移除类
            setTimeout(() => {
                mobileSidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }, 300);
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            // 添加侧边栏关闭动画
            mobileSidebar.style.transform = 'translateX(-100%)';
            
            // 等待动画完成后移除类
            setTimeout(() => {
                mobileSidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }, 300);
        });
    }
    
    // 点击侧边栏菜单项后自动关闭侧边栏
    const menuItems = document.querySelectorAll('.menu-item a');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            // 添加侧边栏关闭动画
            mobileSidebar.style.transform = 'translateX(-100%)';
            
            // 等待动画完成后移除类
            setTimeout(() => {
                mobileSidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }, 300);
        });
    });
    
    // 通知按钮功能
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // 创建通知面板
            const notificationPanel = document.createElement('div');
            notificationPanel.className = 'notification-panel';
            notificationPanel.innerHTML = `
                <div class="notification-header">
                    <h3>通知</h3>
                    <button class="close-notification">×</button>
                </div>
                <div class="notification-content">
                    <div class="notification-item">
                        <p>暂无新通知</p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notificationPanel);
            
            // 添加关闭功能
            const closeBtn = notificationPanel.querySelector('.close-notification');
            closeBtn.addEventListener('click', function() {
                notificationPanel.remove();
            });
        });
    }
    
    // 页面切换动画
    const links = document.querySelectorAll('a[href]:not([target="_blank"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // 排除锚点链接和JavaScript链接
            const href = this.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                // 添加加载动画
                const loader = document.createElement('div');
                loader.className = 'page-loader';
                loader.innerHTML = '<div class="spinner"></div>';
                document.body.appendChild(loader);
                
                // 设置页面切换效果
                document.body.style.opacity = '0.8';
            }
        });
    });
    
    // 返回顶部功能
    const backToTopButton = document.createElement('button');
    backToTopButton.className = 'back-to-top';
    backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopButton.title = '返回顶部';
    document.body.appendChild(backToTopButton);
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('show');
        } else {
            backToTopButton.classList.remove('show');
        }
    });
    
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        
        // 添加点击反馈
        this.style.transform = 'scale(0.9)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });
    
    // 表单验证增强
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    // 创建错误提示
                    const errorTip = document.createElement('div');
                    errorTip.className = 'field-error';
                    errorTip.textContent = '此字段为必填项';
                    field.parentNode.appendChild(errorTip);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // 滚动到第一个错误字段
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // 清除错误状态
        const inputs = this.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorTip = this.parentNode.querySelector('.field-error');
                if (errorTip) {
                    errorTip.remove();
                }
            });
        });
    });
    
    // 下拉菜单功能增强
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
            });
            
            // 点击其他地方关闭下拉菜单
            document.addEventListener('click', function() {
                dropdown.classList.remove('open');
            });
        }
    });
    
    // 模态框功能
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    const modalCloses = document.querySelectorAll('.modal-close, .modal-overlay');
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // 搜索功能增强
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        // 添加清除按钮
        const clearBtn = document.createElement('button');
        clearBtn.className = 'search-clear';
        clearBtn.innerHTML = '<i class="fas fa-times"></i>';
        clearBtn.style.display = 'none';
        input.parentNode.appendChild(clearBtn);
        
        input.addEventListener('input', function() {
            clearBtn.style.display = this.value ? 'block' : 'none';
        });
        
        clearBtn.addEventListener('click', function() {
            input.value = '';
            clearBtn.style.display = 'none';
            input.focus();
        });
    });
    
    // 图片预览功能
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // 查找预览容器
                    const previewContainer = input.closest('.image-upload').querySelector('.image-preview');
                    if (previewContainer) {
                        previewContainer.innerHTML = `<img src="${e.target.result}" alt="预览">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // 数字输入框增强
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        // 添加增加/减少按钮
        const wrapper = document.createElement('div');
        wrapper.className = 'number-input-wrapper';
        
        const decreaseBtn = document.createElement('button');
        decreaseBtn.className = 'number-btn decrease';
        decreaseBtn.innerHTML = '-';
        decreaseBtn.type = 'button';
        
        const increaseBtn = document.createElement('button');
        increaseBtn.className = 'number-btn increase';
        increaseBtn.innerHTML = '+';
        increaseBtn.type = 'button';
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(decreaseBtn);
        wrapper.appendChild(input);
        wrapper.appendChild(increaseBtn);
        
        decreaseBtn.addEventListener('click', function() {
            const min = parseInt(input.min) || 0;
            const step = parseInt(input.step) || 1;
            const currentValue = parseInt(input.value) || 0;
            const newValue = Math.max(currentValue - step, min);
            input.value = newValue;
            input.dispatchEvent(new Event('change'));
        });
        
        increaseBtn.addEventListener('click', function() {
            const max = parseInt(input.max) || 100;
            const step = parseInt(input.step) || 1;
            const currentValue = parseInt(input.value) || 0;
            const newValue = Math.min(currentValue + step, max);
            input.value = newValue;
            input.dispatchEvent(new Event('change'));
        });
    });
});

// 页面加载完成后的初始化
window.addEventListener('load', function() {
    // 移除加载动画
    const loaders = document.querySelectorAll('.page-loader');
    loaders.forEach(loader => loader.remove());
    document.body.style.opacity = '1';
    
    // 初始化工具提示
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'custom-tooltip';
            tooltipEl.textContent = tooltipText;
            document.body.appendChild(tooltipEl);
            
            const rect = this.getBoundingClientRect();
            tooltipEl.style.left = rect.left + (rect.width / 2) - (tooltipEl.offsetWidth / 2) + 'px';
            tooltipEl.style.top = rect.top - tooltipEl.offsetHeight - 10 + 'px';
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = document.querySelector('.custom-tooltip');
            if (tooltipEl) {
                tooltipEl.remove();
            }
        });
    });
});

// 工具函数
function showMessage(message, type = 'info') {
    const messageEl = document.createElement('div');
    messageEl.className = `message-toast ${type}`;
    messageEl.textContent = message;
    
    document.body.appendChild(messageEl);
    
    setTimeout(() => {
        messageEl.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        messageEl.classList.remove('show');
        setTimeout(() => {
            messageEl.remove();
        }, 300);
    }, 3000);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// AJAX请求封装
function ajaxRequest(url, options = {}) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(options.method || 'GET', url);
        
        // 显示全局加载指示器
        showGlobalLoading();
        
        if (options.headers) {
            Object.keys(options.headers).forEach(key => {
                xhr.setRequestHeader(key, options.headers[key]);
            });
        }
        
        xhr.onload = function() {
            // 隐藏全局加载指示器
            hideGlobalLoading();
            
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    resolve(data);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error(`Request failed with status ${xhr.status}`));
            }
        };
        
        xhr.onerror = function() {
            // 隐藏全局加载指示器
            hideGlobalLoading();
            reject(new Error('Network error'));
        };
        
        xhr.send(options.body || null);
    });
}

// 显示全局加载指示器
function showGlobalLoading() {
    if (!document.querySelector('.global-loading')) {
        const loadingEl = document.createElement('div');
        loadingEl.className = 'global-loading';
        loadingEl.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(loadingEl);
    }
}

// 隐藏全局加载指示器
function hideGlobalLoading() {
    const loadingEl = document.querySelector('.global-loading');
    if (loadingEl) {
        loadingEl.remove();
    }
}