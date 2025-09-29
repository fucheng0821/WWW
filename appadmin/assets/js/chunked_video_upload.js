/**
 * 分块视频上传器 (移动端版本)
 * 支持大文件分块上传，断点续传等功能
 */
class VideoChunkUploader {
    constructor(options = {}) {
        this.options = {
            chunkSize: 5 * 1024 * 1024, // 5MB per chunk
            maxFileSize: 200 * 1024 * 1024, // 200MB max file size
            uploadUrl: '../content/upload.php',
            editor: null,
            ...options
        };
        this.uploadState = {
            file: null,
            fileHash: '',
            totalChunks: 0,
            currentChunkIndex: 0,
            uploadedVideoUrl: '',
            uploadCanceled: false,
            layerIndex: null
        };
    }

    /**
     * 打开上传对话框
     */
    openUploadDialog() {
        if (!window.layui || !window.layer) {
            console.error('LayUI 未加载');
            return;
        }

        // 重置上传状态
        this.resetUploadState();

        window.layer.open({
            type: 1,
            title: '上传视频',
            area: ['95%', '500px'],
            shade: 0.2,
            shadeClose: false,
            content: `
                <div style="padding: 15px;">
                    <!-- 上传区域 -->
                    <div id="videoUploadArea" style="
                        margin-bottom: 15px;
                        height: 150px;
                        border: 2px dashed #dcdcdc;
                        border-radius: 8px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        background-color: #fafafa;
                    ">
                        <i class="layui-icon layui-icon-upload" style="font-size: 40px; color: #1E9FFF; margin-bottom: 10px;"></i>
                        <div style="font-size: 14px; color: #333; margin-bottom: 6px; font-weight: 500;">点击选择视频文件</div>
                        <div class="layui-word-aux" style="color: #999; font-size: 12px;">支持 MP4, WebM, AVI, MOV, WMV, FLV, MKV 格式，最大200MB</div>
                    </div>

                    <!-- 隐藏的文件输入 -->
                    <input type="file" id="videoFileInput" accept="video/*" style="display: none;" />

                    <!-- 上传进度 -->
                    <div id="videoUploadProgress" style="display: none; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span id="progressFileName" style="color: #333; font-size: 13px; font-weight: 500;"></span>
                            <span id="progressText" style="color: #1E9FFF; font-size: 13px; font-weight: 500;">0%</span>
                        </div>
                        <div class="layui-progress" lay-filter="videoProgress">
                            <div class="layui-progress-bar layui-bg-blue" lay-percent="0%"></div>
                        </div>
                        <div style="margin-top: 8px; font-size: 12px; color: #666;">
                            <span id="uploadedSize">0</span> / <span id="totalSize">0</span> MB
                        </div>
                    </div>

                    <!-- 上传速度和剩余时间 -->
                    <div id="uploadStats" style="display: none; margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 6px; font-size: 12px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>上传速度: <span id="uploadSpeed">0 KB/s</span></span>
                            <span>剩余时间: <span id="remainingTime">--</span></span>
                        </div>
                    </div>

                    <!-- 已上传视频预览 -->
                    <div id="uploadedVideoPreview" style="display: none; margin-bottom: 15px;">
                        <div style="font-size: 14px; font-weight: 500; color: #333; margin-bottom: 10px;">
                            <i class="layui-icon layui-icon-video" style="color: #1E9FFF; margin-right: 6px;"></i>
                            已上传视频
                        </div>
                        <div id="videoPreviewContainer" style="padding: 10px; background-color: #f8f9fa; border-radius: 6px;"></div>
                    </div>

                    <!-- 按钮区域 -->
                    <div style="text-align: center; padding-top: 12px; border-top: 1px solid #e6e6e6;">
                        <button type="button" id="insertUploadedVideo" class="layui-btn layui-btn-normal" style="display: none; margin-right: 10px; padding: 0 20px; height: 32px; font-size: 13px;">
                            <i class="layui-icon layui-icon-video"></i> 插入视频
                        </button>
                        <button type="button" id="cancelUploadBtn" class="layui-btn layui-btn-primary" style="padding: 0 20px; height: 32px; font-size: 13px;">
                            取消
                        </button>
                    </div>
                </div>
            `,
            success: (layero) => {
                this.initUploadDialog(layero);
            }
        });
    }

    /**
     * 初始化上传对话框
     */
    initUploadDialog(layero) {
        const layer = window.layer;
        const layui = window.layui;

        // 获取元素
        const uploadArea = layero.find('#videoUploadArea')[0];
        const fileInput = layero.find('#videoFileInput')[0];
        const progressContainer = layero.find('#videoUploadProgress')[0];
        const insertBtn = layero.find('#insertUploadedVideo')[0];
        const cancelBtn = layero.find('#cancelUploadBtn')[0];

        // 点击上传区域触发文件选择
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // 文件选择事件
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                const file = e.target.files[0];
                this.handleFileSelection(file, progressContainer, insertBtn, layer, layui);
            }
        });

        // 插入视频按钮事件
        insertBtn.addEventListener('click', () => {
            this.insertUploadedVideo();
            layer.closeAll();
        });

        // 取消按钮事件
        cancelBtn.addEventListener('click', () => {
            this.cancelUpload();
            layer.closeAll();
        });
    }

    /**
     * 处理文件选择
     */
    async handleFileSelection(file, progressContainer, insertBtn, layer, layui) {
        // 验证文件
        if (!this.validateFile(file)) {
            return;
        }

        // 设置文件
        this.uploadState.file = file;
        
        // 显示进度条
        progressContainer.style.display = 'block';
        document.getElementById('progressFileName').textContent = file.name;
        document.getElementById('totalSize').textContent = (file.size / (1024 * 1024)).toFixed(2);
        
        // 初始化上传进度条
        if (layui && layui.element) {
            layui.element.render('progress');
        }

        // 计算文件哈希（简化版本）
        this.uploadState.fileHash = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        this.uploadState.totalChunks = Math.ceil(file.size / this.options.chunkSize);
        this.uploadState.currentChunkIndex = 0;

        // 开始上传
        this.startUpload(layer, layui);
    }

    /**
     * 验证文件
     */
    validateFile(file) {
        // 检查文件类型
        const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-flv', 'video/x-matroska'];
        if (!allowedTypes.includes(file.type)) {
            window.layer.msg('不支持的视频格式，请选择 MP4, WebM, AVI, MOV, WMV, FLV, MKV 格式的文件', {icon: 2});
            return false;
        }

        // 检查文件大小
        if (file.size > this.options.maxFileSize) {
            window.layer.msg('文件大小不能超过200MB', {icon: 2});
            return false;
        }

        return true;
    }

    /**
     * 开始上传
     */
    async startUpload(layer, layui) {
        const file = this.uploadState.file;
        const totalChunks = this.uploadState.totalChunks;
        
        // 更新UI
        document.getElementById('progressText').textContent = '0%';
        if (layui && layui.element) {
            layui.element.progress('videoProgress', '0%');
        }

        // 显示上传统计信息
        document.getElementById('uploadStats').style.display = 'block';

        // 记录开始时间
        const startTime = Date.now();
        let uploadedBytes = 0;

        // 上传每个分块
        for (let i = 0; i < totalChunks; i++) {
            if (this.uploadState.uploadCanceled) {
                break;
            }

            // 计算分块
            const start = i * this.options.chunkSize;
            const end = Math.min(start + this.options.chunkSize, file.size);
            const chunk = file.slice(start, end);

            try {
                // 上传分块
                const result = await this.uploadChunk(chunk, i, totalChunks);
                
                // 更新已上传字节数
                uploadedBytes += chunk.size;
                
                // 更新进度
                this.uploadState.currentChunkIndex = i + 1;
                const progress = Math.min(Math.floor((this.uploadState.currentChunkIndex / totalChunks) * 100), 100);
                
                // 更新UI
                document.getElementById('progressText').textContent = `${progress}%`;
                document.getElementById('uploadedSize').textContent = (uploadedBytes / (1024 * 1024)).toFixed(2);
                
                if (layui && layui.element) {
                    layui.element.progress('videoProgress', progress + '%');
                }

                // 更新上传速度和剩余时间
                const elapsedTime = (Date.now() - startTime) / 1000; // 秒
                const uploadSpeed = uploadedBytes / elapsedTime; // bytes per second
                const remainingBytes = file.size - uploadedBytes;
                const remainingTime = remainingBytes / uploadSpeed; // seconds

                document.getElementById('uploadSpeed').textContent = this.formatSpeed(uploadSpeed);
                document.getElementById('remainingTime').textContent = this.formatTime(remainingTime);

                // 如果上传完成
                if (result.location || result.fileUrl) {
                    // 使用location字段（优先）或fileUrl字段
                    this.uploadState.uploadedVideoUrl = result.location || result.fileUrl;
                    document.getElementById('insertUploadedVideo').style.display = 'inline-block';
                    
                    // 显示预览
                    this.showVideoPreview(this.uploadState.uploadedVideoUrl);
                    break;
                }
            } catch (error) {
                window.layer.msg('上传失败: ' + error.message, {icon: 2});
                break;
            }
        }
    }

    /**
     * 上传分块
     */
    uploadChunk(chunk, chunkIndex, totalChunks) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('chunkIndex', chunkIndex);
            formData.append('totalChunks', totalChunks);
            formData.append('fileName', this.uploadState.file.name);
            formData.append('fileHash', this.uploadState.fileHash);
            formData.append('type', 'video');

            const xhr = new XMLHttpRequest();

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            resolve(data);
                        } else {
                            reject(new Error(data.error || '上传失败'));
                        }
                    } catch (error) {
                        reject(new Error('响应解析错误: ' + error.message));
                    }
                } else {
                    reject(new Error('上传失败: HTTP ' + xhr.status));
                }
            };

            xhr.onerror = () => {
                reject(new Error('网络错误，上传失败'));
            };

            xhr.timeout = 120000; // 2分钟超时
            xhr.ontimeout = () => {
                reject(new Error('上传超时，请检查网络连接'));
            };

            xhr.open('POST', this.options.uploadUrl, true);
            xhr.send(formData);
        });
    }

    /**
     * 显示视频预览 - 改进版本
     */
    showVideoPreview(videoUrl) {
        const previewContainer = document.getElementById('videoPreviewContainer');
        const previewHtml = `
            <div style="position: relative; max-width: 100%;">
                <video controls style="max-width: 100%; height: auto; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" preload="metadata">
                    <source src="${videoUrl}" type="video/mp4">
                    您的浏览器不支持视频播放。
                </video>
            </div>
        `;
        previewContainer.innerHTML = previewHtml;
        document.getElementById('uploadedVideoPreview').style.display = 'block';
    }

    /**
     * 插入已上传的视频 - 改进版本
     */
    insertUploadedVideo() {
        if (!this.options.editor || !this.uploadState.uploadedVideoUrl) {
            console.warn('无法插入视频: 编辑器未初始化或没有上传的视频');
            return;
        }

        try {
            // 确保编辑器有焦点
            this.options.editor.focus();

            // 创建视频元素
            const videoContainer = document.createElement('div');
            videoContainer.className = 'video-container';
            videoContainer.style.position = 'relative';
            videoContainer.style.maxWidth = '100%';
            videoContainer.style.margin = '10px 0';
            
            const videoElement = document.createElement('video');
            videoElement.controls = true;
            videoElement.style.maxWidth = '100%';
            videoElement.style.height = 'auto';
            videoElement.style.borderRadius = '6px';
            videoElement.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            videoElement.preload = 'metadata';
            
            const sourceElement = document.createElement('source');
            sourceElement.src = this.uploadState.uploadedVideoUrl;
            sourceElement.type = 'video/mp4';
            
            videoElement.appendChild(sourceElement);
            videoElement.appendChild(document.createTextNode('您的浏览器不支持视频播放。'));
            videoContainer.appendChild(videoElement);
            
            // 插入视频到编辑器
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                range.deleteContents();
                range.insertNode(videoContainer);
                
                // 将光标移动到视频后面
                range.setStartAfter(videoContainer);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            } else {
                // 如果没有选区，直接添加到编辑器末尾
                this.options.editor.appendChild(videoContainer);
            }
            
            // 同步内容到隐藏的input（如果存在）
            const contentInput = document.getElementById('content-input');
            if (contentInput) {
                contentInput.value = this.options.editor.innerHTML;
            }
            
            console.log('视频插入成功');
        } catch (error) {
            console.error('插入视频时发生错误:', error);
            window.layer.msg('插入视频失败', {icon: 2});
        }
    }

    /**
     * 取消上传
     */
    cancelUpload() {
        this.uploadState.uploadCanceled = true;
        window.layer.msg('上传已取消', {icon: 1});
    }

    /**
     * 重置上传状态
     */
    resetUploadState() {
        this.uploadState = {
            file: null,
            fileHash: '',
            totalChunks: 0,
            currentChunkIndex: 0,
            uploadedVideoUrl: '',
            uploadCanceled: false,
            layerIndex: null
        };
    }

    /**
     * 格式化上传速度
     */
    formatSpeed(bytesPerSecond) {
        if (bytesPerSecond < 1024) {
            return bytesPerSecond.toFixed(2) + ' B/s';
        } else if (bytesPerSecond < 1024 * 1024) {
            return (bytesPerSecond / 1024).toFixed(2) + ' KB/s';
        } else {
            return (bytesPerSecond / (1024 * 1024)).toFixed(2) + ' MB/s';
        }
    }

    /**
     * 格式化时间
     */
    formatTime(seconds) {
        if (seconds < 60) {
            return Math.floor(seconds) + '秒';
        } else if (seconds < 3600) {
            return Math.floor(seconds / 60) + '分钟';
        } else {
            return Math.floor(seconds / 3600) + '小时' + Math.floor((seconds % 3600) / 60) + '分钟';
        }
    }
}

// 全局函数：插入增强版视频
window.insertVideoEnhanced = function() {
    try {
        // 检查是否已定义视频上传类
        if (typeof VideoChunkUploader !== 'undefined') {
            // 创建上传器实例
            const uploader = new VideoChunkUploader({
                editor: window.customEditor
            });
            
            // 打开上传对话框
            uploader.openUploadDialog();
        } else {
            console.error('视频上传类未定义');
            layui.layer.msg('视频上传功能加载失败，请刷新页面重试', {icon: 2});
        }
    } catch (e) {
        console.error('视频上传功能初始化失败:', e);
        layui.layer.msg('视频上传功能初始化失败，请刷新页面重试', {icon: 2});
    }
};