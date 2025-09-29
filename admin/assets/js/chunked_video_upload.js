/**
 * 视频分块上传JavaScript处理文件 - 现代化UI版本
 * 用于后台内容管理中的视频上传，支持最大200MB文件，带进度条显示和失败原因提示
 */

// 视频分块上传类
class VideoChunkUploader {
    constructor(options = {}) {
        this.options = {
            chunkSize: 5 * 1024 * 1024, // 5MB每块
            maxFileSize: 200 * 1024 * 1024, // 200MB
            allowedExtensions: ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
            uploadUrl: 'chunked_video_upload.php',
            editor: null,
            ...options
        };
        
        // 当前上传任务
        this.currentUpload = null;
        // 是否正在上传
        this.isUploading = false;
        // 是否暂停上传
        this.isPaused = false;
    }
    
    // 设置编辑器元素
    setEditor(editorElement) {
        this.options.editor = editorElement;
    }
    
    // 获取编辑器元素
    getEditor() {
        if (this.options.editor) {
            return this.options.editor;
        }
        // 尝试查找常见的编辑器元素
        const editor = document.getElementById('custom-editor') || 
                      document.querySelector('[contenteditable="true"]') ||
                      document.querySelector('.editor-content') ||
                      document.body;
        return editor;
    }
    
    // 打开视频上传对话框 - 全新现代化UI设计
    openUploadDialog() {
        const that = this;
        
        // 创建唯一ID用于对话框内元素
        const dialogId = 'video-upload-dialog-' + Date.now();
        
        layui.layer.open({
            type: 1,
            title: '<div style="display: flex; align-items: center;"><i class="layui-icon layui-icon-video" style="margin-right: 8px; color: #409EFF;"></i>上传视频</div>',
            area: ['800px', '650px'],
            shade: 0.3,
            shadeClose: true,
            anim: 2, // 从右侧滑入的动画
            skin: 'layui-layer-molv',
            content: `
                <div style="padding: 24px;">
                    <!-- 上传模式选择栏 - 现代化卡片设计 -->
                    <div class="video-upload-header" style="
                        margin-bottom: 24px;
                        padding: 20px;
                        background: linear-gradient(135deg, #409EFF 0%, #69b1ff 100%);
                        border-radius: 12px;
                        color: white;
                        box-shadow: 0 4px 16px rgba(64, 158, 255, 0.25);
                    ">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="layui-icon layui-icon-video" style="font-size: 24px; margin-right: 12px;"></i>
                                <div>
                                    <div style="font-size: 18px; font-weight: 600;">视频上传</div>
                                    <div style="font-size: 12px; opacity: 0.9; margin-top: 2px;">支持多种视频格式，最大支持200MB</div>
                                </div>
                            </div>
                            <div style="background: rgba(255, 255, 255, 0.2); border-radius: 16px; padding: 4px 12px; font-size: 12px;">
                                <i class="layui-icon layui-icon-tips" style="margin-right: 4px;"></i>
                                拖拽上传
                            </div>
                        </div>
                    </div>

                    <!-- 上传区域 - 全新设计的拖放区域 -->
                    <div id="chunkedVideoUploadArea" class="upload-dropzone" style="
                        margin-bottom: 24px;
                        height: 220px;
                        border: 3px dashed #e0e6ed;
                        border-radius: 16px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        cursor: pointer;
                        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                        background-color: #f8f9fa;
                        position: relative;
                        overflow: hidden;
                    ">
                        <!-- 背景装饰元素 -->
                        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.03;">
                            <div style="position: absolute; top: 20px; left: 20px; font-size: 120px;">▶</div>
                            <div style="position: absolute; bottom: 20px; right: 20px; font-size: 120px;">▶</div>
                        </div>
                        
                        <!-- 上传图标 -->
                        <div class="upload-icon-container" style="
                            width: 80px;
                            height: 80px;
                            border-radius: 50%;
                            background: rgba(64, 158, 255, 0.1);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-bottom: 16px;
                            transition: all 0.3s ease;
                        ">
                            <i class="layui-icon layui-icon-upload" style="font-size: 48px; color: #409EFF;"></i>
                        </div>
                        
                        <!-- 上传文字提示 -->
                        <div style="font-size: 18px; color: #333; font-weight: 600; margin-bottom: 8px; transition: color 0.3s ease;">拖放视频文件到此处，或点击上传</div>
                        <div style="color: #909399; font-size: 14px; text-align: center;">
                            <span>支持 MP4, WebM, OGG, AVI, MOV, WMV, FLV, MKV 格式</span>
                            <br>
                            <span style="margin-top: 4px; display: inline-block;">最大文件大小：200MB</span>
                        </div>
                        
                        <!-- 上传按钮 -->
                        <button type="button" class="layui-btn layui-btn-primary upload-btn" style="
                            margin-top: 16px;
                            padding: 0 24px;
                            height: 40px;
                            border-radius: 20px;
                            font-size: 14px;
                            border: 2px solid #dcdfe6;
                            background-color: white;
                            transition: all 0.3s ease;
                        ">
                            <i class="layui-icon layui-icon-file-video"></i> 选择视频
                        </button>
                    </div>

                    <!-- 隐藏的文件输入 -->
                    <input type="file" id="chunkedVideoFileInput" accept="video/*" style="display: none;">

                    <!-- 上传进度条 - 现代化设计 -->
                    <div id="imageUploadProgress" class="progress-container" style="
                        display: none;
                        margin-bottom: 24px;
                        padding: 20px;
                        background: white;
                        border-radius: 12px;
                        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center;">
                                <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" style="color: #409EFF; margin-right: 8px;"></i>
                                <span style="color: #333; font-size: 16px; font-weight: 500;">上传中</span>
                            </div>
                            <span id="upload-progress-text" class="progress-percent" style="color: #409EFF; font-size: 16px; font-weight: 600;">0%</span>
                        </div>
                        
                        <!-- 自定义进度条 -->
                        <div class="progress-wrapper" style="
                            width: 100%;
                            height: 8px;
                            background: #ecf5ff;
                            border-radius: 4px;
                            overflow: hidden;
                            position: relative;
                        ">
                            <div id="customProgressBar" class="progress-bar" style="
                                width: 0%;
                                height: 100%;
                                background: linear-gradient(90deg, #409EFF 0%, #69b1ff 100%);
                                border-radius: 4px;
                                transition: width 0.6s cubic-bezier(0.65, 0, 0.35, 1);
                                position: relative;
                            ">
                                <div class="progress-shine" style="
                                    position: absolute;
                                    top: 0;
                                    left: -100%;
                                    width: 100%;
                                    height: 100%;
                                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
                                    animation: progressShine 2s infinite;
                                "></div>
                            </div>
                        </div>
                        
                        <!-- 上传信息 -->
                        <div class="upload-info" style="margin-top: 12px; font-size: 12px; color: #909399;">
                            <span id="uploadFileName">准备上传...</span>
                            <span id="uploadFileSize" style="margin-left: 16px;"></span>
                        </div>
                    </div>

                    <!-- 视频信息预览区域 - 精美卡片设计 -->
                    <div id="videoPreviewContainer" class="preview-container" style="
                        display: none;
                        margin-bottom: 24px;
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #333; display: flex; align-items: center;">
                                <i class="layui-icon layui-icon-video" style="color: #409EFF; margin-right: 10px;"></i>
                                视频预览
                            </h4>
                        </div>
                        
                        <!-- 预览卡片 -->
                        <div id="videoPreviewList" class="preview-list" style="
                            background: white;
                            border-radius: 16px;
                            overflow: hidden;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                        "></div>
                    </div>

                    <!-- 按钮区域 - 现代按钮设计 -->
                    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #f0f2f5;">
                        <button type="button" id="insertChunkedUploadedVideo" class="layui-btn" style="
                            display: none;
                            margin-right: 16px;
                            padding: 0 32px;
                            height: 42px;
                            border-radius: 21px;
                            font-size: 16px;
                            background: linear-gradient(135deg, #409EFF 0%, #69b1ff 100%);
                            border: none;
                            box-shadow: 0 4px 16px rgba(64, 158, 255, 0.3);
                            transition: all 0.3s ease;
                        ">
                            <i class="layui-icon layui-icon-file-video" style="margin-right: 8px;"></i> 插入视频
                        </button>
                        <button type="button" id="pauseChunkedUpload" class="layui-btn layui-btn-warm" style="
                            display: none;
                            margin-right: 16px;
                            padding: 0 32px;
                            height: 42px;
                            border-radius: 21px;
                            font-size: 16px;
                            background: linear-gradient(135deg, #e6a23c 0%, #ebb563 100%);
                            border: none;
                            box-shadow: 0 4px 16px rgba(230, 162, 60, 0.3);
                            transition: all 0.3s ease;
                        ">
                            <i class="layui-icon layui-icon-pause" style="margin-right: 8px;"></i> 暂停
                        </button>
                        <button type="button" class="layui-btn layui-btn-primary cancel-btn" onclick="layui.layer.closeAll()" style="
                            padding: 0 32px;
                            height: 42px;
                            border-radius: 21px;
                            font-size: 16px;
                            background: #f5f7fa;
                            color: #606266;
                            border: 1px solid #dcdfe6;
                            transition: all 0.3s ease;
                        ">
                            取消
                        </button>
                    </div>
                </div>
            `,
            success: function(layero, index) {
                const layer = window.layer;
                const layui = window.layui;
                
                const uploadArea = document.getElementById('chunkedVideoUploadArea');
                const fileInput = document.getElementById('chunkedVideoFileInput');
                const progressBar = document.getElementById('imageUploadProgress');
                const pauseBtn = document.getElementById('pauseChunkedUpload');
                const insertBtn = document.getElementById('insertChunkedUploadedVideo');
                const videoPreviewContainer = document.getElementById('videoPreviewContainer');
                const videoPreviewList = document.getElementById('videoPreviewList');
                const uploadBtn = uploadArea.querySelector('.upload-btn');
                
                // 创建隐藏的视频URL存储到实例对象中
                that.uploadedVideoUrl = '';
                
                // 添加CSS动画
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes progressShine {
                        0% { transform: translateX(-100%); }
                        100% { transform: translateX(200%); }
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    @keyframes pulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.05); }
                        100% { transform: scale(1); }
                    }
                `;
                document.head.appendChild(style);
                
                // 点击上传按钮或区域触发文件选择
                uploadArea.addEventListener('click', function() {
                    fileInput.click();
                });
                
                // 监听文件选择
                fileInput.addEventListener('change', function() {
                    if (fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        that.handleFileSelection(file, layero, index, progressBar, videoPreviewContainer, videoPreviewList, insertBtn, pauseBtn, layer, layui);
                    }
                });
                
                // 拖拽上传 - 精美的视觉反馈
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadArea.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });
                
                // 拖拽进入样式变化
                uploadArea.addEventListener('dragover', function() {
                    this.style.borderColor = '#409EFF';
                    this.style.backgroundColor = '#ecf5ff';
                    this.style.transform = 'scale(1.01)';
                    
                    // 改变图标容器样式
                    const iconContainer = this.querySelector('.upload-icon-container');
                    if (iconContainer) {
                        iconContainer.style.transform = 'scale(1.1)';
                        iconContainer.style.backgroundColor = 'rgba(64, 158, 255, 0.2)';
                    }
                    
                    // 改变文字颜色
                    const textElements = this.querySelectorAll('div[style*="color: #333"]');
                    textElements.forEach(el => {
                        el.style.color = '#409EFF';
                    });
                });
                
                // 拖拽离开样式变化
                uploadArea.addEventListener('dragleave', function() {
                    this.style.borderColor = '#e0e6ed';
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.transform = 'scale(1)';
                    
                    // 恢复图标容器样式
                    const iconContainer = this.querySelector('.upload-icon-container');
                    if (iconContainer) {
                        iconContainer.style.transform = 'scale(1)';
                        iconContainer.style.backgroundColor = 'rgba(64, 158, 255, 0.1)';
                    }
                    
                    // 恢复文字颜色
                    const textElements = this.querySelectorAll('div[style*="color: #409EFF"]');
                    textElements.forEach(el => {
                        el.style.color = '#333';
                    });
                });
                
                // 处理拖拽上传
                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    uploadArea.style.borderColor = '#e0e6ed';
                    uploadArea.style.backgroundColor = '#f8f9fa';
                    
                    // 恢复图标容器和文字样式
                    const iconContainer = uploadArea.querySelector('.upload-icon-container');
                    if (iconContainer) {
                        iconContainer.style.transform = 'scale(1)';
                        iconContainer.style.backgroundColor = 'rgba(64, 158, 255, 0.1)';
                    }
                    const textElements = uploadArea.querySelectorAll('div[style*="color: #409EFF"]');
                    textElements.forEach(el => {
                        el.style.color = '#333';
                    });
                    
                    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        // 添加轻微的动画效果
                        uploadArea.style.transform = 'scale(0.98)';
                        setTimeout(() => {
                            uploadArea.style.transform = 'scale(1)';
                            const file = e.dataTransfer.files[0];
                            that.handleFileSelection(file, layero, index, progressBar, videoPreviewContainer, videoPreviewList, insertBtn, pauseBtn, layer, layui);
                        }, 150);
                    }
                });
                
                // 按钮悬停效果
                const buttons = document.querySelectorAll('.layui-btn');
                buttons.forEach(btn => {
                    btn.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                        if (this.classList.contains('layui-btn-primary') && !this.classList.contains('cancel-btn')) {
                            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                        } else if (this.classList.contains('layui-btn')) {
                            this.style.boxShadow = '0 6px 20px rgba(64, 158, 255, 0.4)';
                        } else if (this.classList.contains('layui-btn-warm')) {
                            this.style.boxShadow = '0 6px 20px rgba(230, 162, 60, 0.4)';
                        }
                    });
                    btn.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                        if (this.classList.contains('layui-btn-primary') && !this.classList.contains('cancel-btn')) {
                            this.style.boxShadow = 'none';
                        } else if (this.classList.contains('layui-btn')) {
                            this.style.boxShadow = '0 4px 16px rgba(64, 158, 255, 0.3)';
                        } else if (this.classList.contains('layui-btn-warm')) {
                            this.style.boxShadow = '0 4px 16px rgba(230, 162, 60, 0.3)';
                        }
                    });
                });
                
                // 暂停/继续上传按钮事件
                pauseBtn.addEventListener('click', function() {
                    if (that.isUploading && that.currentUpload) {
                        if (that.isPaused) {
                            // 继续上传
                            that.isPaused = false;
                            this.innerHTML = '<i class="layui-icon layui-icon-pause" style="margin-right: 8px;"></i> 暂停';
                            this.className = 'layui-btn layui-btn-warm';
                            this.style.background = 'linear-gradient(135deg, #e6a23c 0%, #ebb563 100%)';
                            this.style.boxShadow = '0 4px 16px rgba(230, 162, 60, 0.3)';
                            
                            // 更新上传进度文本
                            const progressTextElem = document.getElementById('upload-progress-text');
                            const progressPercent = Math.floor((that.currentUpload.uploadedChunks / that.currentUpload.totalChunks) * 100);
                            progressTextElem.textContent = `${progressPercent}%`;
                            
                            // 显示加载动画
                            const loadingIcon = document.querySelector('.layui-icon-loading');
                            if (loadingIcon) {
                                loadingIcon.classList.add('layui-anim', 'layui-anim-rotate', 'layui-anim-loop');
                            }
                            
                            that.resumeUpload();
                        } else {
                            // 暂停上传
                            that.isPaused = true;
                            this.innerHTML = '<i class="layui-icon layui-icon-play" style="margin-right: 8px;"></i> 继续';
                            this.className = 'layui-btn';
                            this.style.background = 'linear-gradient(135deg, #409EFF 0%, #69b1ff 100%)';
                            this.style.boxShadow = '0 4px 16px rgba(64, 158, 255, 0.3)';
                            
                            // 更新上传进度文本
                            const progressTextElem = document.getElementById('upload-progress-text');
                            progressTextElem.textContent = '已暂停';
                            
                            // 隐藏加载动画
                            const loadingIcon = document.querySelector('.layui-icon-loading');
                            if (loadingIcon) {
                                loadingIcon.classList.remove('layui-anim', 'layui-anim-rotate', 'layui-anim-loop');
                            }
                        }
                    }
                });
                
                // 插入视频按钮事件
                insertBtn.addEventListener('click', function() {
                    if (that.uploadedVideoUrl) {
                        that.insertVideoIntoEditor(that.uploadedVideoUrl);
                        
                        // 关闭对话框
                        layui.layer.closeAll();
                        
                        // 显示成功提示动画
                        layui.layer.msg('视频插入成功', {
                            icon: 1,
                            time: 2000,
                            shade: 0.2
                        });
                    } else {
                        layui.layer.msg('请先上传视频文件', {
                            icon: 2,
                            time: 2000,
                            shade: 0.2
                        });
                    }
                });
                
                // 存储对uploadedVideoUrl的引用到当前上传任务
                that.currentUpload = that.currentUpload || {};
                that.currentUpload.uploadedVideoUrlRef = () => that.uploadedVideoUrl;
                that.currentUpload.setUploadedVideoUrl = (url) => {
                    that.uploadedVideoUrl = url;
                };
            }
        });
    }
    
    // 插入视频到编辑器
    insertVideoIntoEditor(videoUrl) {
        // 获取编辑器元素和隐藏的textarea
        const customEditor = document.getElementById('custom-editor');
        const contentInput = document.getElementById('content-input');
        
        // 确保找到必要的元素
        if (!customEditor || !contentInput) {
            console.error('未找到编辑器元素或隐藏的textarea');
            if (window.layui && window.layui.layer) {
                window.layui.layer.msg('编辑器初始化失败，请刷新页面重试', {
                    icon: 2,
                    time: 2000,
                    shade: 0.2
                });
            }
            return;
        }
        
        // 构建视频HTML - 使用与网站其他功能一致的格式
        const videoHtml = `<div class="video-container" style="position: relative; max-width: 100%; margin: 10px 0;">
            <video controls="controls" width="100%" style="max-width: 100%; height: auto;">
                <source src="${videoUrl}" type="video/mp4">
                您的浏览器不支持视频播放
            </video>
        </div>`;
        
        try {
            console.log('即将插入的视频信息:', {
                url: videoUrl,
                videoHtml: videoHtml
            });
            
            // 确保编辑器有焦点
            customEditor.focus();
            
            // 保存当前选区状态
            if (window.saveEditorSelection) {
                window.saveEditorSelection(customEditor);
            }
            
            // 使用网站统一的document.execCommand方法插入HTML内容
            // 这是网站编辑器功能的核心方法
            if (document.execCommand) {
                document.execCommand('insertHTML', false, videoHtml);
                console.log('使用document.execCommand插入视频HTML成功');
            } else {
                // 备选方案：创建Range对象插入内容
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = videoHtml;
                    const fragment = document.createDocumentFragment();
                    let child;
                    while (child = tempDiv.firstChild) {
                        fragment.appendChild(child);
                    }
                    range.insertNode(fragment);
                    console.log('使用Range对象插入视频HTML成功');
                }
            }
            
            // 立即同步内容到隐藏的textarea - 这是网站编辑器的标准做法
            contentInput.value = customEditor.innerHTML;
            console.log('内容已立即同步到隐藏的textarea');
            console.log('同步的内容长度:', contentInput.value.length);
            
            // 显示成功提示
            if (window.layui && window.layui.layer) {
                window.layui.layer.msg('视频插入成功', {
                    icon: 1,
                    time: 1500
                });
            }
            
        } catch (error) {
            console.error('插入视频失败:', error);
            if (window.layui && window.layui.layer) {
                window.layui.layer.msg('插入视频失败：' + error.message, {
                    icon: 2,
                    time: 2000,
                    shade: 0.2
                });
            }
            return;
        }
        
        // 延迟调用添加缩略图按钮的函数，确保DOM已更新
        setTimeout(() => {
            if (window.addSelectFrameButtonsToExistingVideos && typeof window.addSelectFrameButtonsToExistingVideos === 'function') {
                try {
                    window.addSelectFrameButtonsToExistingVideos();
                    console.log('已调用添加缩略图按钮函数');
                } catch (e) {
                    console.error('调用添加缩略图按钮函数失败:', e);
                }
            }
            
            // 再次同步内容，确保万无一失
            contentInput.value = customEditor.innerHTML;
            console.log('内容再次同步到隐藏的textarea');
        }, 500);
    }
    
    // 处理文件选择
    handleFileSelection(file, layero, index, progressBar, videoPreviewContainer, videoPreviewList, insertBtn, pauseBtn, layer, layui) {
        // 验证文件大小
        if (file.size > this.options.maxFileSize) {
            layer.msg('文件大小超过200MB限制', {
                icon: 2,
                time: 2000,
                shade: 0.2
            });
            return;
        }
        
        // 验证文件类型
        const fileExt = file.name.split('.').pop().toLowerCase();
        if (!this.options.allowedExtensions.includes(fileExt)) {
            layer.msg('不支持的文件格式', {
                icon: 2,
                time: 2000,
                shade: 0.2
            });
            return;
        }
        
        // 显示进度条
        progressBar.style.display = 'block';
        progressBar.style.animation = 'fadeIn 0.5s ease-out';
        
        const progressText = document.getElementById('upload-progress-text');
        const uploadFileName = document.getElementById('uploadFileName');
        const uploadFileSize = document.getElementById('uploadFileSize');
        
        // 格式化文件大小
        const formatFileSize = (bytes) => {
            if (bytes < 1024) return bytes + ' B';
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            else return (bytes / 1048576).toFixed(1) + ' MB';
        };
        
        // 更新文件名和大小
        uploadFileName.textContent = file.name;
        uploadFileSize.textContent = formatFileSize(file.size);
        
        // 初始化进度条
        const customProgressBar = document.getElementById('customProgressBar');
        customProgressBar.style.width = '0%';
        
        if (progressText) {
            progressText.textContent = '0% (准备上传...)';
        }
        
        // 显示暂停按钮
        pauseBtn.style.display = 'inline-block';
        pauseBtn.style.animation = 'fadeIn 0.5s ease-out 0.2s both';
        
        // 隐藏插入按钮
        insertBtn.style.display = 'none';
        
        // 隐藏预览容器
        videoPreviewContainer.style.display = 'none';
        
        // 添加上传区域动画效果
        const uploadArea = document.getElementById('chunkedVideoUploadArea');
        uploadArea.style.borderColor = '#409EFF';
        uploadArea.style.backgroundColor = '#ecf5ff';
        uploadArea.style.transform = 'scale(1.01)';
        
        // 初始化上传任务
        this.currentUpload = {
            file: file,
            fileId: '',
            totalChunks: Math.ceil(file.size / this.options.chunkSize),
            uploadedChunks: 0,
            layero: layero,
            layerIndex: index,
            progressBar: progressBar,
            videoPreviewContainer: videoPreviewContainer,
            videoPreviewList: videoPreviewList,
            insertBtn: insertBtn,
            pauseBtn: pauseBtn,
            layer: layer,
            layui: layui
        };
        
        this.isUploading = true;
        this.isPaused = false;
        
        // 开始上传
        setTimeout(() => this.uploadNextChunk(), 500);
    }
    
    // 上传下一个分块
    async uploadNextChunk() {
        if (!this.isUploading || this.isPaused || !this.currentUpload) {
            return;
        }
        
        const { file, fileId, totalChunks, uploadedChunks } = this.currentUpload;
        
        // 检查是否所有分块都已上传
        if (uploadedChunks >= totalChunks) {
            this.isUploading = false;
            return;
        }
        
        // 计算当前分块的开始和结束位置
        const start = uploadedChunks * this.options.chunkSize;
        const end = Math.min(start + this.options.chunkSize, file.size);
        const chunk = file.slice(start, end);
        
        // 创建FormData
        const formData = new FormData();
        formData.append('chunkFile', chunk);
        formData.append('chunk', uploadedChunks);
        formData.append('totalChunks', totalChunks);
        formData.append('fileName', file.name);
        formData.append('fileSize', file.size);
        
        // 如果已有fileId，添加到FormData
        if (fileId) {
            formData.append('fileId', fileId);
        }
        
        try {
            // 上传分块
            const response = await this.uploadChunk(formData);
            
            // 检查响应
            if (!response.success) {
                throw new Error(response.error || '上传失败');
            }
            
            // 更新fileId（如果是第一个分块）
            if (uploadedChunks === 0 && response.fileId) {
                this.currentUpload.fileId = response.fileId;
            }
            
            // 更新已上传分块数量
            this.currentUpload.uploadedChunks++;
            
            // 更新进度条
            const progressPercent = Math.floor((this.currentUpload.uploadedChunks / totalChunks) * 100);
            const progressTextElem = document.getElementById('upload-progress-text');
            const customProgressBar = document.getElementById('customProgressBar');
            
            if (progressTextElem) {
                progressTextElem.textContent = `${progressPercent}%`;
            }
            
            if (customProgressBar) {
                customProgressBar.style.width = progressPercent + '%';
            }
            
            // 检查是否上传完成
            if (response.location) {
                // 上传完成
                this.isUploading = false;
                
                // 隐藏暂停按钮
                if (this.currentUpload.pauseBtn) {
                    this.currentUpload.pauseBtn.style.display = 'none';
                }
                
                // 更新进度条和文本
                if (progressTextElem) {
                    progressTextElem.textContent = '100%';
                }
                
                if (customProgressBar) {
                    customProgressBar.style.width = '100%';
                }
                
                // 隐藏加载动画
                const loadingIcon = document.querySelector('.layui-icon-loading');
                if (loadingIcon) {
                    loadingIcon.classList.remove('layui-anim', 'layui-anim-rotate', 'layui-anim-loop');
                }
                
                // 添加完成动画
                customProgressBar.style.background = 'linear-gradient(90deg, #67c23a 0%, #85ce61 100%)';
                
                // 延迟隐藏进度条，显示预览区域
                setTimeout(() => {
                    if (this.currentUpload.progressBar) {
                        this.currentUpload.progressBar.style.display = 'none';
                    }
                    
                    // 显示视频预览区域
                    if (this.currentUpload.videoPreviewContainer && this.currentUpload.videoPreviewList) {
                        this.currentUpload.videoPreviewContainer.style.display = 'block';
                        this.currentUpload.videoPreviewContainer.style.animation = 'fadeIn 0.5s ease-out';
                        
                        // 创建视频预览元素
                        const file = this.currentUpload.file;
                        const videoPreviewItem = document.createElement('div');
                        
                        // 格式化文件大小
                        const formatFileSize = (bytes) => {
                            if (bytes < 1024) return bytes + ' B';
                            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                            else return (bytes / 1048576).toFixed(1) + ' MB';
                        };
                        
                        // 构建预览卡片内容
                        videoPreviewItem.innerHTML = `
                            <div style="
                                padding: 20px;
                                display: flex;
                                align-items: flex-start;
                                border-bottom: 1px solid #f0f2f5;
                            ">
                                <!-- 视频图标区域 -->
                                <div style="
                                    width: 120px;
                                    height: 80px;
                                    background: linear-gradient(135deg, #409EFF 0%, #69b1ff 100%);
                                    border-radius: 8px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin-right: 20px;
                                    flex-shrink: 0;
                                    box-shadow: 0 4px 12px rgba(64, 158, 255, 0.2);
                                ">
                                    <i class="layui-icon layui-icon-video" style="font-size: 40px; color: white;"></i>
                                </div>
                                
                                <!-- 视频信息区域 -->
                                <div style="flex: 1; min-width: 0;">
                                    <!-- 视频名称 -->
                                    <div style="
                                        font-size: 16px;
                                        font-weight: 600;
                                        color: #333;
                                        margin-bottom: 8px;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                        white-space: nowrap;
                                    ">${file.name}</div>
                                    
                                    <!-- 视频详细信息 -->
                                    <div style="
                                        display: flex;
                                        flex-wrap: wrap;
                                        gap: 16px;
                                        font-size: 13px;
                                        color: #909399;
                                    ">
                                        <div><i class="layui-icon layui-icon-file-video" style="margin-right: 4px;"></i>${file.type || '视频文件'}</div>
                                        <div><i class="layui-icon layui-icon-file-size" style="margin-right: 4px;"></i>${formatFileSize(file.size)}</div>
                                        <div><i class="layui-icon layui-icon-clock" style="margin-right: 4px;"></i>${new Date().toLocaleString('zh-CN')}</div>
                                    </div>
                                </div>
                                
                                <!-- 状态图标 -->
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 40px;
                                    height: 40px;
                                    background: #f0f9ff;
                                    border-radius: 50%;
                                    margin-left: auto;
                                    flex-shrink: 0;
                                ">
                                    <i class="layui-icon layui-icon-ok" style="font-size: 20px; color: #67c23a;"></i>
                                </div>
                            </div>
                            
                            <!-- 底部操作区域 -->
                            <div style="
                                padding: 16px 20px;
                                background: #fafbfc;
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                            ">
                                <div style="font-size: 12px; color: #909399;">
                                    上传完成，可以插入到编辑器中
                                </div>
                                
                                <div style="display: flex; gap: 12px;">
                                    <button type="button" class="layui-btn layui-btn-sm layui-btn-primary" onclick="previewVideo('${response.location}')" style="
                                        padding: 0 16px;
                                        height: 32px;
                                        border-radius: 16px;
                                        font-size: 12px;
                                    ">
                                        <i class="layui-icon layui-icon-play" style="margin-right: 4px;"></i> 预览
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        // 添加预览项
                        this.currentUpload.videoPreviewList.innerHTML = '';
                        this.currentUpload.videoPreviewList.appendChild(videoPreviewItem);
                    }
                    
                    // 显示插入按钮
                    if (this.currentUpload.insertBtn) {
                        this.currentUpload.insertBtn.style.display = 'inline-block';
                        this.currentUpload.insertBtn.style.animation = 'fadeIn 0.5s ease-out 0.3s both';
                    }
                    
                    // 保存视频URL到实例对象
                    this.uploadedVideoUrl = response.location;
                    
                    // 也通过setter方法设置
                    if (this.currentUpload.setUploadedVideoUrl) {
                        this.currentUpload.setUploadedVideoUrl(response.location);
                    }
                    
                    // 恢复上传区域样式
                    const uploadArea = document.getElementById('chunkedVideoUploadArea');
                    uploadArea.style.borderColor = '#67c23a';
                    uploadArea.style.backgroundColor = '#f0f9ff';
                    uploadArea.style.transform = 'scale(1)';
                    
                    // 改变上传图标颜色
                    const iconContainer = uploadArea.querySelector('.upload-icon-container');
                    const uploadIcon = uploadArea.querySelector('.layui-icon-upload');
                    if (iconContainer) {
                        iconContainer.style.backgroundColor = 'rgba(103, 194, 58, 0.2)';
                    }
                    if (uploadIcon) {
                        uploadIcon.style.color = '#67c23a';
                    }
                    
                    // 改变上传文字颜色
                    const textElements = uploadArea.querySelectorAll('div[style*="color: #333"]');
                    textElements.forEach(el => {
                        el.style.color = '#67c23a';
                    });
                    
                    // 显示成功提示
                    if (this.currentUpload.layer) {
                        this.currentUpload.layer.msg('视频上传成功！', {
                            icon: 1,
                            time: 2000,
                            shade: 0.2
                        });
                    }
                    
                    this.currentUpload = null;
                }, 1000);
            } else {
                // 继续上传下一个分块
                setTimeout(() => this.uploadNextChunk(), 50);
            }
        } catch (error) {
            this.isUploading = false;
            
            // 隐藏暂停按钮
            if (this.currentUpload.pauseBtn) {
                this.currentUpload.pauseBtn.style.display = 'none';
            }
            
            // 更新上传进度文本
            const progressTextElem = document.getElementById('upload-progress-text');
            if (progressTextElem) {
                progressTextElem.textContent = '上传失败';
            }
            
            // 改变进度条颜色表示错误
            const customProgressBar = document.getElementById('customProgressBar');
            if (customProgressBar) {
                customProgressBar.style.background = 'linear-gradient(90deg, #f56c6c 0%, #f78989 100%)';
            }
            
            // 隐藏加载动画
            const loadingIcon = document.querySelector('.layui-icon-loading');
            if (loadingIcon) {
                loadingIcon.classList.remove('layui-anim', 'layui-anim-rotate', 'layui-anim-loop');
            }
            
            // 恢复上传区域样式
            const uploadArea = document.getElementById('chunkedVideoUploadArea');
            if (uploadArea) {
                uploadArea.style.borderColor = '#f56c6c';
                uploadArea.style.backgroundColor = '#fef0f0';
                uploadArea.style.boxShadow = '0 4px 16px rgba(245, 108, 108, 0.15)';
                
                // 改变上传图标颜色
                const iconContainer = uploadArea.querySelector('.upload-icon-container');
                const uploadIcon = uploadArea.querySelector('.layui-icon-upload');
                if (iconContainer) {
                    iconContainer.style.backgroundColor = 'rgba(245, 108, 108, 0.2)';
                }
                if (uploadIcon) {
                    uploadIcon.style.color = '#f56c6c';
                }
                
                // 改变上传文字颜色
                const textElements = uploadArea.querySelectorAll('div[style*="color: #333"]');
                textElements.forEach(el => {
                    el.style.color = '#f56c6c';
                });
            }
            
            // 显示失败提示
            if (this.currentUpload.layer) {
                this.currentUpload.layer.msg('上传失败：' + error.message, {
                    icon: 2,
                    time: 3000,
                    shade: 0.2
                });
            }
        }
    }
    
    // 上传单个分块
    uploadChunk(formData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            
            // 设置超时时间（60秒）
            const timeout = 60000; // 60秒
            
            // 超时处理
            const timeoutId = setTimeout(() => {
                xhr.abort();
                reject(new Error('上传超时，请检查网络连接后重试'));
            }, timeout);
            
            xhr.open('POST', this.options.uploadUrl);
            
            xhr.onload = function() {
                // 清除超时计时器
                clearTimeout(timeoutId);
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (error) {
                    console.error('解析响应失败:', error, '响应内容:', xhr.responseText);
                    reject(new Error('服务器响应格式错误'));
                }
            };
            
            xhr.onerror = function(e) {
                // 清除超时计时器
                clearTimeout(timeoutId);
                
                console.error('网络错误:', e);
                reject(new Error('网络错误，请检查您的网络连接'));
            };
            
            xhr.ontimeout = function() {
                // 清除超时计时器
                clearTimeout(timeoutId);
                
                reject(new Error('请求超时，请稍后重试'));
            };
            
            xhr.onabort = function() {
                // 清除超时计时器
                clearTimeout(timeoutId);
                
                console.log('上传已取消');
            };
            
            xhr.upload.onprogress = function(e) {
                // 这里可以实现更精确的进度显示
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    console.log('分块上传进度:', percent + '%');
                }
            };
            
            xhr.send(formData);
        });
    }
    
    // 继续上传
    resumeUpload() {
        if (!this.isPaused || !this.currentUpload) {
            return;
        }
        
        this.uploadNextChunk();
    }
}

// 预览视频函数
function previewVideo(videoUrl) {
    layui.layer.open({
        type: 1,
        title: '<i class="layui-icon layui-icon-play" style="margin-right: 8px; color: #409EFF;"></i>视频预览',
        area: ['900px', '600px'],
        shade: 0.5,
        content: `
            <div style="padding: 20px; background: #1a1a1a;">
                <video controls="controls" width="100%" height="100%" src="${videoUrl}" style="max-width: 100%; max-height: 500px; display: block; margin: 0 auto;">
                    您的浏览器不支持视频播放
                </video>
            </div>
        `
    });
}

// 全局实例
window.videoUploader = null;

// 初始化视频上传器
function initVideoUploader() {
    console.log('执行initVideoUploader函数');
    if (!window.videoUploader) {
        console.log('创建新的VideoChunkUploader实例');
        window.videoUploader = new VideoChunkUploader();
    } else {
        console.log('videoUploader实例已存在，无需重新创建');
    }
}

// 替换原有的insertVideo函数
function insertVideo() {
    console.log('执行insertVideo函数');
    try {
        initVideoUploader();
        console.log('打开视频上传对话框');
        window.videoUploader.openUploadDialog();
    } catch (error) {
        console.error('打开视频上传对话框失败:', error);
        // 使用全局layer提示错误
        if (window.layui && window.layui.layer) {
            window.layui.layer.msg('打开视频上传对话框失败，请刷新页面重试', {
                icon: 2,
                time: 2000,
                shade: 0.2
            });
        }
    }
}

// 当DOM加载完成后初始化
function ensureVideoUploaderInitialized() {
    console.log('执行ensureVideoUploaderInitialized函数');
    try {
        if (!window.videoUploader || typeof window.videoUploader !== 'object') {
            console.log('videoUploader不存在或无效，创建新实例');
            window.videoUploader = new VideoChunkUploader();
        } else {
            console.log('videoUploader实例已经有效');
        }
    } catch (error) {
        console.error('初始化videoUploader失败:', error);
    }
}

// 为与网站的insertVideoEnhanced函数兼容，创建全局的VideoChunkUploader类的单例实例
window.createVideoUploaderInstance = function() {
    console.log('执行createVideoUploaderInstance函数 - 为insertVideoEnhanced提供兼容支持');
    ensureVideoUploaderInitialized();
    return window.videoUploader;
};

if (typeof document !== 'undefined' && document.readyState === 'loading') {
    console.log('页面加载中，等待DOMContentLoaded事件');
    document.addEventListener('DOMContentLoaded', function() {
        initVideoUploader();
        // 添加全局方法，以便其他脚本可以手动确保初始化
        window.ensureVideoUploaderInitialized = ensureVideoUploaderInitialized;
        console.log('DOM加载完成，已注册全局方法');
    });
} else {
    console.log('页面已加载完成，直接初始化');
    initVideoUploader();
    // 添加全局方法，以便其他脚本可以手动确保初始化
    window.ensureVideoUploaderInitialized = ensureVideoUploaderInitialized;
    console.log('已注册全局方法');
}