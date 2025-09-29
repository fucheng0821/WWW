/**
 * 后台管理通用工具函数
 * 提供统一的消息提示、表单处理、文件上传等通用功能
 */

// 管理后台工具类
class AdminUtils {
    /**
     * 显示消息提示
     * @param {string} message - 消息内容
     * @param {number} icon - 图标类型 (1:成功, 2:错误, 0:警告)
     * @param {number} time - 显示时间(毫秒)
     */
    static showMessage(message, icon = 0, time = 3000) {
        if (window.layui && layui.layer) {
            layui.layer.msg(message, { icon, time });
        } else {
            // 降级处理
            alert(message);
        }
    }

    /**
     * 显示成功消息
     * @param {string} message - 消息内容
     * @param {number} time - 显示时间(毫秒)
     */
    static showSuccess(message, time = 3000) {
        this.showMessage(message, 1, time);
    }

    /**
     * 显示错误消息
     * @param {string} message - 消息内容
     * @param {number} time - 显示时间(毫秒)
     */
    static showError(message, time = 3000) {
        this.showMessage(message, 2, time);
    }

    /**
     * 显示警告消息
     * @param {string} message - 消息内容
     * @param {number} time - 显示时间(毫秒)
     */
    static showWarning(message, time = 3000) {
        this.showMessage(message, 0, time);
    }

    /**
     * 发起AJAX请求
     * @param {string} url - 请求地址
     * @param {object} options - 请求选项
     * @returns {Promise}
     */
    static ajaxRequest(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        };
        
        const finalOptions = Object.assign({}, defaultOptions, options);
        
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(finalOptions.method, url, true);
            
            // 设置请求头
            for (let header in finalOptions.headers) {
                xhr.setRequestHeader(header, finalOptions.headers[header]);
            }
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            reject(response);
                        } catch (e) {
                            reject(new Error('请求失败'));
                        }
                    }
                }
            };
            
            // 发送数据
            if (finalOptions.data) {
                if (typeof finalOptions.data === 'object' && !(finalOptions.data instanceof FormData)) {
                    // 转换为URL编码格式
                    const params = new URLSearchParams();
                    for (let key in finalOptions.data) {
                        params.append(key, finalOptions.data[key]);
                    }
                    xhr.send(params.toString());
                } else {
                    xhr.send(finalOptions.data);
                }
            } else {
                xhr.send();
            }
        });
    }

    /**
     * 显示确认对话框
     * @param {string} message - 确认消息
     * @param {function} confirmCallback - 确认回调函数
     * @param {function} cancelCallback - 取消回调函数
     */
    static showConfirm(message, confirmCallback, cancelCallback) {
        if (window.layui && layui.layer) {
            layui.layer.confirm(message, {
                icon: 3,
                title: '确认操作'
            }, function(index) {
                if (confirmCallback) confirmCallback();
                layui.layer.close(index);
            }, function() {
                if (cancelCallback) cancelCallback();
            });
        } else {
            // 降级处理
            if (confirm(message)) {
                if (confirmCallback) confirmCallback();
            } else {
                if (cancelCallback) cancelCallback();
            }
        }
    }

    /**
     * 初始化文件上传功能
     * @param {string} elem - 上传按钮选择器
     * @param {string} url - 上传接口地址
     * @param {object} options - 上传配置选项
     */
    static initUploader(elem, url, options = {}) {
        if (!window.layui || !layui.upload) {
            // 在生产环境中静默失败，在开发环境中显示警告
            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.warn('LayUI upload module not available');
            }
            return;
        }

        const defaultOptions = {
            url: url,
            accept: 'images',
            exts: 'jpg|jpeg|png|gif|webp',
            field: 'file',
            size: 10240, // 10MB
            ...options
        };

        return layui.upload.render({
            elem: elem,
            ...defaultOptions,
            before: function(obj) {
                if (options.before) options.before(obj);
            },
            done: function(res, index, upload) {
                if (res.success) {
                    if (options.done) options.done(res, index, upload);
                    AdminUtils.showSuccess('上传成功');
                } else {
                    if (options.error) options.error(res);
                    AdminUtils.showError('上传失败：' + (res.error || '未知错误'));
                }
            },
            error: function() {
                if (options.error) options.error();
                AdminUtils.showError('上传请求异常');
            }
        });
    }

    /**
     * 初始化表单
     * @param {object} options - 表单配置选项
     */
    static initForm(options = {}) {
        if (!window.layui || !layui.form) {
            // 在生产环境中静默失败，在开发环境中显示警告
            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.warn('LayUI form module not available');
            }
            return;
        }

        layui.form.render();

        // 表单验证规则
        if (options.verify) {
            layui.form.verify(options.verify);
        }

        // 表单提交处理
        if (options.submit) {
            layui.form.on('submit(' + (options.filter || 'form') + ')', function(data) {
                return options.submit(data);
            });
        }
    }

    /**
     * 自动隐藏提示消息
     * @param {number} delay - 延迟时间(毫秒)
     */
    static autoHideAlerts(delay = 5000) {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, delay);
    }

    /**
     * 获取URL查询参数
     * @param {string} name - 参数名
     * @returns {string|null} 参数值
     */
    static getUrlParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    /**
     * 设置URL查询参数
     * @param {string} name - 参数名
     * @param {string} value - 参数值
     */
    static setUrlParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.replaceState({}, '', url);
    }

    /**
     * 删除URL查询参数
     * @param {string} name - 参数名
     */
    static removeUrlParam(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.replaceState({}, '', url);
    }
}

// 全局初始化函数
function initAdminPage() {
    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 自动隐藏提示消息
        AdminUtils.autoHideAlerts();
        
        // 初始化LayUI组件
        if (window.layui) {
            layui.use(['element', 'form'], function() {
                const element = layui.element;
                const form = layui.form;
                
                if (element) element.render();
                if (form) form.render();
            });
        }
    });
}

// 执行全局初始化
initAdminPage();

// 导出为全局变量
window.AdminUtils = AdminUtils;