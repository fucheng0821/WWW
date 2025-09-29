// 移动端登录页面JavaScript功能

document.addEventListener('DOMContentLoaded', function() {
    // 获取表单元素
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');
    const loginBtn = document.querySelector('.login-btn');
    
    // 表单验证
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 获取输入值
            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();
            
            // 验证输入
            if (!username) {
                showMessage('请输入用户名', 'error');
                usernameInput.focus();
                // 添加输入框震动效果
                usernameInput.classList.add('shake');
                setTimeout(() => {
                    usernameInput.classList.remove('shake');
                }, 500);
                return;
            }
            
            if (!password) {
                showMessage('请输入密码', 'error');
                passwordInput.focus();
                // 添加输入框震动效果
                passwordInput.classList.add('shake');
                setTimeout(() => {
                    passwordInput.classList.remove('shake');
                }, 500);
                return;
            }
            
            // 显示加载状态
            loginBtn.disabled = true;
            const originalText = loginBtn.innerHTML;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 登录中...';
            
            // 添加按钮点击效果
            loginBtn.classList.add('loading');
            
            // 提交表单
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('登录成功，正在跳转...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = originalText;
                    loginBtn.classList.remove('loading');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('登录请求失败，请稍后重试', 'error');
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalText;
                loginBtn.classList.remove('loading');
            });
        });
    }
    
    // 输入框焦点效果
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.classList.remove('focused');
        });
    });
    
    // 记住用户名功能
    if (usernameInput) {
        // 从本地存储获取记住的用户名
        const rememberedUsername = localStorage.getItem('admin_username');
        if (rememberedUsername) {
            usernameInput.value = rememberedUsername;
            passwordInput.focus();
        }
        
        // 记住用户名
        loginForm.addEventListener('submit', function() {
            if (usernameInput.value) {
                localStorage.setItem('admin_username', usernameInput.value);
            }
        });
    }
    
    // 密码可见性切换
    if (passwordInput) {
        // 创建切换按钮
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        
        // 插入到密码输入框旁边
        const passwordWrapper = passwordInput.parentNode;
        passwordWrapper.style.position = 'relative';
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '15px';
        toggleBtn.style.top = '50%';
        toggleBtn.style.transform = 'translateY(-50%)';
        toggleBtn.style.background = 'none';
        toggleBtn.style.border = 'none';
        toggleBtn.style.color = '#9e9e9e';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.zIndex = '2';
        
        passwordWrapper.appendChild(toggleBtn);
        
        // 切换密码可见性
        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    }
    
    // 页面加载动画
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.3s ease';
        document.body.style.opacity = '1';
    }, 100);
});

// 显示消息提示
function showMessage(message, type = 'info') {
    // 移除已存在的消息
    const existingMessage = document.querySelector('.message-toast');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // 创建消息元素
    const messageEl = document.createElement('div');
    messageEl.className = `message-toast ${type}`;
    messageEl.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // 添加样式
    messageEl.style.position = 'fixed';
    messageEl.style.top = '20px';
    messageEl.style.left = '50%';
    messageEl.style.transform = 'translateX(-50%)';
    messageEl.style.padding = '12px 20px';
    messageEl.style.borderRadius = '8px';
    messageEl.style.color = 'white';
    messageEl.style.fontWeight = '500';
    messageEl.style.zIndex = '9999';
    messageEl.style.display = 'flex';
    messageEl.style.alignItems = 'center';
    messageEl.style.gap = '8px';
    messageEl.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    
    // 根据类型设置背景色
    switch (type) {
        case 'success':
            messageEl.style.background = '#4caf50';
            break;
        case 'error':
            messageEl.style.background = '#f44336';
            break;
        default:
            messageEl.style.background = '#2196f3';
    }
    
    // 添加到页面
    document.body.appendChild(messageEl);
    
    // 3秒后自动移除
    setTimeout(() => {
        messageEl.style.transition = 'all 0.3s ease';
        messageEl.style.opacity = '0';
        messageEl.style.transform = 'translate(-50%, -20px)';
        setTimeout(() => {
            messageEl.remove();
        }, 300);
    }, 3000);
}

// 表单输入验证
function validateInput(input) {
    const value = input.value.trim();
    const name = input.name;
    
    // 移除之前的错误状态
    input.classList.remove('error');
    
    // 根据字段名进行验证
    switch (name) {
        case 'username':
            if (!value) {
                showError(input, '请输入用户名');
                return false;
            }
            break;
        case 'password':
            if (!value) {
                showError(input, '请输入密码');
                return false;
            }
            if (value.length < 6) {
                showError(input, '密码长度不能少于6位');
                return false;
            }
            break;
    }
    
    return true;
}

// 显示错误信息
function showError(input, message) {
    input.classList.add('error');
    showMessage(message, 'error');
}