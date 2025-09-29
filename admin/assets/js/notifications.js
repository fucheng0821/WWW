/**
 * 消息通知组件
 * 提供统一的消息显示功能
 */

class NotificationManager {
    /**
     * 显示通知消息
     * @param {string} message - 消息内容
     * @param {string} type - 消息类型 (success, error, warning, info)
     * @param {number} duration - 显示时长(毫秒)，默认3000
     */
    static show(message, type = 'info', duration = 3000) {
        // 移除已存在的通知
        this.hide();
        
        // 创建通知元素
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                ${message}
            </div>
            <button class="notification-close" onclick="NotificationManager.hide()">&times;</button>
        `;
        
        // 添加样式
        this.addStyles();
        
        // 添加到页面
        document.body.appendChild(notification);
        
        // 自动隐藏
        if (duration > 0) {
            setTimeout(() => {
                this.hide();
            }, duration);
        }
    }
    
    /**
     * 显示成功消息
     * @param {string} message - 消息内容
     * @param {number} duration - 显示时长(毫秒)
     */
    static success(message, duration = 3000) {
        this.show(message, 'success', duration);
    }
    
    /**
     * 显示错误消息
     * @param {string} message - 消息内容
     * @param {number} duration - 显示时长(毫秒)
     */
    static error(message, duration = 3000) {
        this.show(message, 'error', duration);
    }
    
    /**
     * 显示警告消息
     * @param {string} message - 消息内容
     * @param {number} duration - 显示时长(毫秒)
     */
    static warning(message, duration = 3000) {
        this.show(message, 'warning', duration);
    }
    
    /**
     * 显示信息消息
     * @param {string} message - 消息内容
     * @param {number} duration - 显示时长(毫秒)
     */
    static info(message, duration = 3000) {
        this.show(message, 'info', duration);
    }
    
    /**
     * 隐藏通知
     */
    static hide() {
        const notification = document.querySelector('.notification');
        if (notification) {
            notification.remove();
        }
    }
    
    /**
     * 添加样式
     */
    static addStyles() {
        // 检查是否已添加样式
        if (document.getElementById('notification-styles')) {
            return;
        }
        
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                color: white;
                font-size: 14px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                display: flex;
                align-items: center;
                min-width: 250px;
                max-width: 400px;
                animation: notificationSlideIn 0.3s ease-out;
            }
            
            @keyframes notificationSlideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .notification-content {
                flex: 1;
                margin-right: 10px;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .notification-success {
                background-color: #52c41a;
            }
            
            .notification-error {
                background-color: #ff4d4f;
            }
            
            .notification-warning {
                background-color: #faad14;
            }
            
            .notification-info {
                background-color: #1890ff;
            }
        `;
        
        document.head.appendChild(style);
    }
}

// 如果使用Layui，仍然使用Layui的提示
if (window.layui && layui.layer) {
    const originalShow = NotificationManager.show;
    NotificationManager.show = function(message, type, duration) {
        let icon = 0;
        switch(type) {
            case 'success': icon = 1; break;
            case 'error': icon = 2; break;
            case 'warning': icon = 0; break;
            case 'info': icon = 6; break;
        }
        layui.layer.msg(message, { icon, time: duration });
    };
}