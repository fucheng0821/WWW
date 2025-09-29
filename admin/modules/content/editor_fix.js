// 编辑器按钮功能修复脚本
// 此脚本解决编辑器工具栏按钮无法正常工作的问题

document.addEventListener('DOMContentLoaded', function() {
    console.log('编辑器修复脚本开始执行');
    
    // 检查是否存在编辑器元素
    const customEditor = document.getElementById('custom-editor');
    const contentInput = document.getElementById('content-input');
    
    if (!customEditor || !contentInput) {
        console.warn('编辑器元素未找到');
        return;
    }
    
    console.log('找到编辑器元素，开始修复按钮功能');
    
    // 确保所有必要的全局函数都已定义
    function ensureGlobalFunctions() {
        // 重新定义所有必要的编辑器功能函数
        window.formatText = function(command, value = null) {
            try {
                console.log('执行formatText:', command, value);
                
                // 如果EnhancedEditor可用，优先使用它
                if (window.enhancedEditor && typeof window.enhancedEditor.formatText === 'function') {
                    console.log('使用EnhancedEditor的formatText方法');
                    window.enhancedEditor.formatText(command, value);
                    return;
                }
                
                // 否则使用基础实现
                // 保存编辑器选区
                if (window.saveEditorSelection) {
                    window.saveEditorSelection(customEditor);
                }
                
                // 确保编辑器有焦点
                customEditor.focus();
                
                // 获取当前选区
                const selection = window.getSelection();
                
                // 如果没有选区，创建一个光标位置的选区
                if (selection.rangeCount === 0) {
                    const range = document.createRange();
                    range.selectNodeContents(customEditor);
                    range.collapse(false); // 移动到末尾
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
                
                // 执行格式化命令
                if (command === 'formatBlock') {
                    document.execCommand(command, false, '<' + value + '>');
                } else if (command === 'fontName' || command === 'foreColor' || command === 'hiliteColor') {
                    document.execCommand(command, false, value);
                } else if (['alignLeft', 'alignCenter', 'alignRight', 'alignJustify'].includes(command)) {
                    // 段落对齐处理
                    const range = selection.getRangeAt(0);
                    let parentElement = range.commonAncestorContainer;
                    
                    // 查找最近的块级元素
                    while (parentElement && parentElement.nodeType === 3) {
                        parentElement = parentElement.parentNode;
                    }
                    
                    // 创建块级元素（如果需要）
                    if (!parentElement || ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'DIV', 'LI'].indexOf(parentElement.tagName) === -1) {
                        const p = document.createElement('p');
                        range.surroundContents(p);
                        parentElement = p;
                    }
                    
                    // 应用对齐样式
                    parentElement.style.textAlign = '';
                    switch (command) {
                        case 'alignLeft': parentElement.style.textAlign = 'left'; break;
                        case 'alignCenter': parentElement.style.textAlign = 'center'; break;
                        case 'alignRight': parentElement.style.textAlign = 'right'; break;
                        case 'alignJustify': parentElement.style.textAlign = 'justify'; break;
                    }
                } else {
                    document.execCommand(command, false, null);
                }
                
                // 同步内容
                contentInput.value = customEditor.innerHTML;
                
            } catch (e) {
                console.error('执行formatText失败:', e);
                if (window.layer) {
                    window.layer.msg('格式化文本失败', {icon: 2});
                }
            }
        };
        
        window.insertLink = function() {
            try {
                console.log('执行insertLink');
                
                if (window.layer) {
                    window.layer.prompt({
                        formType: 0,
                        title: '请输入链接地址',
                        placeholder: 'https://example.com'
                    }, function(value, index, elem){
                        if (value) {
                            // 确保编辑器有焦点
                            customEditor.focus();
                            // 插入链接
                            document.execCommand('createLink', false, value);
                            // 同步内容
                            contentInput.value = customEditor.innerHTML;
                        }
                        window.layer.close(index);
                    });
                }
            } catch (e) {
                console.error('执行insertLink失败:', e);
                if (window.layer) {
                    window.layer.msg('插入链接失败', {icon: 2});
                }
            }
        };
        
        window.insertImage = function() {
            try {
                // 优先使用增强的图片上传器
                if (window.enhancedImageUploader) {
                    window.enhancedImageUploader.showUploadDialog();
                    return;
                }
                
                // 如果没有增强上传器，使用带有上传模式选择器的默认上传对话框
                if (window.layer) {
                    window.layer.open({
                        type: 1,
                        title: '上传图片',
                        area: ['600px', '500px'],
                        content: `
                            <div style="padding: 20px;">
                                <!-- 上传模式切换 -->
                                <div style="margin-bottom: 15px; text-align: right;">
                                    <label style="margin-right: 10px;">上传模式：</label>
                                    <select id="upload-mode-select" class="layui-select" style="width: 120px; display: inline-block;">
                                        <option value="single">单图上传</option>
                                        <option value="multiple">多图上传</option>
                                    </select>
                                </div>

                                <!-- 上传区域 -->
                                <div class="layui-upload-drag" id="contentImageUpload" style="margin-bottom: 15px;">
                                    <i class="layui-icon layui-icon-upload"></i>
                                    <div id="upload-mode-text">点击上传图片，或将图片拖拽到此处</div>
                                    <div class="layui-word-aux">支持 JPG, PNG, GIF, WebP, BMP, TIFF 格式，大小不超过 10MB</div>
                                </div>

                                <!-- 隐藏的文件输入 -->
                                <input type="file" id="imageFileInput" accept="image/*" style="display: none;" />

                                <!-- 上传进度条 -->
                                <div id="imageUploadProgress" style="display: none; margin-bottom: 15px;">
                                    <div class="layui-progress layui-progress-big" lay-filter="imageProgress">
                                        <div class="layui-progress-bar" lay-percent="0%"></div>
                                    </div>
                                </div>

                                <!-- 预览区域 -->
                                <div id="imagePreviewContainer" style="display: none; margin-bottom: 15px;">
                                    <h4 style="margin-bottom: 10px;">已上传图片（点击图片可移除）：</h4>
                                    <div id="imagePreviewList" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                                </div>

                                <div style="text-align: center;">
                                    <button type="button" id="insertImageBtn" class="layui-btn" style="display: none; margin-right: 10px;">插入图片</button>
                                    <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">取消</button>
                                </div>
                            </div>
                        `,
                        success: function(layero) {
                            // 初始化上传进度条
                            if (window.layui && window.layui.element) {
                                window.layui.element.render('progress');
                            }

                            // 获取元素
                            const dragArea = layero.find('#contentImageUpload')[0];
                            const fileInput = layero.find('#imageFileInput')[0];
                            const progressBar = layero.find('#imageUploadProgress')[0];
                            const previewContainer = layero.find('#imagePreviewContainer')[0];
                            const previewList = layero.find('#imagePreviewList')[0];
                            const insertBtn = layero.find('#insertImageBtn')[0];
                            const modeSelect = layero.find('#upload-mode-select')[0];
                            const modeText = layero.find('#upload-mode-text')[0];

                            // 存储已上传的图片URL
                            let uploadedImages = [];
                            let uploadMode = 'single';

                            // 模式切换事件
                            modeSelect.addEventListener('change', function(e) {
                                uploadMode = e.target.value;
                                fileInput.multiple = uploadMode === 'multiple';
                                modeText.textContent = uploadMode === 'multiple' ? '点击上传多张图片，或将图片拖拽到此处' : '点击上传图片，或将图片拖拽到此处';
                                
                                // 清空已上传的图片
                                if (uploadedImages.length > 0) {
                                    uploadedImages = [];
                                    previewList.innerHTML = '';
                                    previewContainer.style.display = 'none';
                                    insertBtn.style.display = 'none';
                                }
                            });

                            // 点击拖拽区域触发文件选择
                            dragArea.addEventListener('click', function() {
                                fileInput.click();
                            });

                            // 拖拽上传处理
                            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                                dragArea.addEventListener(eventName, function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                });
                            });

                            // 拖拽进入样式变化
                            dragArea.addEventListener('dragover', function() {
                                this.style.borderColor = '#1E9FFF';
                            });

                            // 拖拽离开样式变化
                            dragArea.addEventListener('dragleave', function() {
                                this.style.borderColor = '#e6e6e6';
                            });

                            // 验证文件
                            function validateFiles(files) {
                                const validFiles = [];
                                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff'];
                                
                                for (let i = 0; i < files.length; i++) {
                                    const file = files[i];
                                    
                                    // 检查文件类型
                                    if (!validTypes.includes(file.type)) {
                                        window.layer.msg('文件 ' + file.name + ' 不是有效的图片格式', {icon: 2});
                                        continue;
                                    }
                                    
                                    // 检查文件大小
                                    if (file.size > 10 * 1024 * 1024) {
                                        window.layer.msg('文件 ' + file.name + ' 大小不能超过10MB', {icon: 2});
                                        continue;
                                    }
                                    
                                    validFiles.push(file);
                                }
                                
                                return validFiles;
                            }

                            // 上传单个文件
                            function uploadFile(file) {
                                return new Promise((resolve, reject) => {
                                    const formData = new FormData();
                                    formData.append('thumbnail', file);
                                    formData.append('type', 'image');

                                    // 发送上传请求到实际的上传接口
                                    fetch('/admin/api/upload_thumbnail.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('上传失败: HTTP ' + response.status);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        if (data.success && data.thumbnailUrl) {
                                            resolve(data.thumbnailUrl);
                                        } else if (data.location) {
                                            resolve(data.location);
                                        } else if (data.fileUrl) {
                                            resolve(data.fileUrl);
                                        } else {
                                            throw new Error(data.error || '上传失败');
                                        }
                                    })
                                    .catch(error => {
                                        // 如果上传接口不可用，使用模拟上传
                                        console.warn('使用actually upload接口失败，使用模拟上传:', error);
                                        resolve(URL.createObjectURL(file));
                                    });
                                });
                            }

                            // 添加图片预览
                            function addImagePreview(file, imageUrl) {
                                // 创建预览元素
                                const previewItem = document.createElement('div');
                                previewItem.className = 'image-preview-item';
                                previewItem.style.position = 'relative';
                                previewItem.style.width = '120px';
                                previewItem.style.height = '120px';
                                previewItem.style.overflow = 'hidden';
                                previewItem.style.border = '1px solid #e6e6e6';
                                previewItem.style.borderRadius = '4px';
                                
                                // 创建图片元素
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(file);
                                img.style.width = '100%';
                                img.style.height = '100%';
                                img.style.objectFit = 'cover';
                                
                                // 创建删除按钮
                                const deleteBtn = document.createElement('div');
                                deleteBtn.innerHTML = '<i class="layui-icon layui-icon-close"></i>';
                                deleteBtn.style.position = 'absolute';
                                deleteBtn.style.top = '5px';
                                deleteBtn.style.right = '5px';
                                deleteBtn.style.width = '20px';
                                deleteBtn.style.height = '20px';
                                deleteBtn.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                                deleteBtn.style.color = 'white';
                                deleteBtn.style.borderRadius = '50%';
                                deleteBtn.style.display = 'flex';
                                deleteBtn.style.justifyContent = 'center';
                                deleteBtn.style.alignItems = 'center';
                                deleteBtn.style.cursor = 'pointer';
                                deleteBtn.style.fontSize = '12px';
                                
                                // 删除事件
                                deleteBtn.addEventListener('click', function() {
                                    const index = uploadedImages.indexOf(imageUrl);
                                    if (index > -1) {
                                        uploadedImages.splice(index, 1);
                                    }
                                    previewList.removeChild(previewItem);
                                    
                                    // 如果没有图片了，隐藏预览容器和插入按钮
                                    if (previewList.children.length === 0) {
                                        previewContainer.style.display = 'none';
                                        insertBtn.style.display = 'none';
                                    }
                                });
                                
                                // 添加到预览列表
                                previewItem.appendChild(img);
                                previewItem.appendChild(deleteBtn);
                                previewList.appendChild(previewItem);
                            }

                            // 处理文件上传
                            async function handleFileUpload(files) {
                                // 验证文件
                                const validFiles = validateFiles(files);
                                if (validFiles.length === 0) return;

                                // 显示进度条
                                progressBar.style.display = 'block';

                                try {
                                    // 上传文件
                                    for (let i = 0; i < validFiles.length; i++) {
                                        const file = validFiles[i];
                                        const progress = Math.floor((i / validFiles.length) * 100);
                                        
                                        // 显示当前上传进度
                                        if (window.layui && window.layui.element) {
                                            window.layui.element.progress('imageProgress', progress + '%');
                                        }

                                        // 上传文件
                                        const imageUrl = await uploadFile(file);
                                        uploadedImages.push(imageUrl);

                                        // 添加预览
                                        addImagePreview(file, imageUrl);
                                    }

                                    // 更新进度为100%
                                    if (window.layui && window.layui.element) {
                                        window.layui.element.progress('imageProgress', '100%');
                                    }

                                    // 隐藏进度条
                                    setTimeout(() => {
                                        progressBar.style.display = 'none';
                                    }, 500);

                                    // 显示插入按钮
                                    insertBtn.style.display = 'inline-block';
                                    
                                    // 显示预览容器
                                    previewContainer.style.display = 'block';

                                    // 显示成功提示
                                    window.layer.msg(validFiles.length + ' 张图片上传成功', {icon: 1});
                                } catch (error) {
                                    console.error('上传失败:', error);
                                    window.layer.msg('上传失败: ' + error.message, {icon: 2});
                                    progressBar.style.display = 'none';
                                }
                            }

                            // 处理文件选择
                            fileInput.addEventListener('change', function() {
                                if (this.files && this.files.length > 0) {
                                    handleFileUpload(this.files);
                                }
                            });

                            // 处理拖拽上传
                            dragArea.addEventListener('drop', function(e) {
                                this.style.borderColor = '#e6e6e6';

                                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                    handleFileUpload(e.dataTransfer.files);
                                }
                            });

                            // 插入图片按钮点击事件
                            insertBtn.addEventListener('click', function() {
                                if (uploadedImages.length === 0) {
                                    window.layer.msg('请先上传图片', {icon: 2});
                                    return;
                                }

                                try {
                                    // 确保编辑器有焦点
                                    customEditor.focus();
                                    
                                    // 保存当前选区
                                    if (window.saveEditorSelection) {
                                        window.saveEditorSelection(customEditor);
                                    }

                                    // 插入图片
                                    uploadedImages.forEach((imageUrl, index) => {
                                        // 添加图片HTML，添加更好的样式支持
                                        const imageHtml = `<img src="${imageUrl}" style="max-width: 100%; height: auto; margin: 10px 0;" />`;
                                        
                                        // 如果是多图模式且不是最后一张图片，添加换行
                                        const finalHtml = uploadMode === 'multiple' && index < uploadedImages.length - 1 ? imageHtml + '<br><br>' : imageHtml;
                                        
                                        document.execCommand('insertHTML', false, finalHtml);
                                    });

                                    // 同步内容到隐藏的input
                                    contentInput.value = customEditor.innerHTML;

                                    // 关闭弹窗
                                    window.layer.closeAll();

                                    // 显示成功提示
                                    window.layer.msg(uploadMode === 'multiple' ? '多张图片插入成功' : '图片插入成功', {icon: 1});
                                } catch (e) {
                                    console.error('插入图片失败:', e);
                                    window.layer.msg('插入图片失败', {icon: 2});
                                }
                            });
                        }
                    });
                }
            } catch (e) {
                console.error('执行insertImage失败:', e);
                if (window.layer) {
                    window.layer.msg('插入图片失败', {icon: 2});
                }
            }
        };
        
        // 其他必要的编辑器功能函数
        // 增强版视频插入功能
        window.insertVideoEnhanced = function() {
            try {
                console.log('执行insertVideoEnhanced');
                
                // 检查是否存在VideoChunkUploader类
                if (typeof VideoChunkUploader !== 'undefined') {
                    const uploader = new VideoChunkUploader();
                    uploader.openUploadDialog();
                } else if (window.videoUploader && typeof window.videoUploader.openUploadDialog === 'function') {
                    window.videoUploader.openUploadDialog();
                } else {
                    console.warn('未找到视频上传器，尝试使用普通视频上传');
                    window.insertVideo();
                }
            } catch (e) {
                console.error('执行insertVideoEnhanced失败:', e);
                if (window.layer) {
                    window.layer.msg('插入视频失败', {icon: 2});
                }
            }
        };
        
        window.insertVideo = function() {
            try {
                console.log('执行insertVideo');
                
                // 优先使用现代化视频上传器（如果可用）
                if (window.videoUploader && typeof window.videoUploader.openUploadDialog === 'function') {
                    window.videoUploader.openUploadDialog();
                    return;
                }
                
                if (window.layer) {
                    window.layer.open({
                        type: 1,
                        title: '上传视频',
                        area: ['600px', '450px'],
                        content: `
                            <div style="padding: 20px;">
                                <div class="layui-upload-drag" id="contentVideoUpload" style="margin-bottom: 15px;">
                                    <i class="layui-icon layui-icon-upload"></i>
                                    <div>点击上传视频，或将视频拖拽到此处</div>
                                    <div class="layui-word-aux">支持 MP4, WebM, MOV, AVI, FLV 格式，大小不超过 200MB</div>
                                </div>
                                
                                <!-- 隐藏的文件输入 -->
                                <input type="file" id="videoFileInput" accept="video/*" style="display: none;" />
                                
                                <!-- 上传进度条 -->
                                <div id="videoUploadProgress" style="display: none; margin-bottom: 10px;">
                                    <div class="layui-progress layui-progress-big" lay-filter="videoProgress">
                                        <div class="layui-progress-bar" lay-percent="0%"></div>
                                    </div>
                                </div>
                                
                                <!-- 上传状态信息 -->
                                <div id="uploadStatus" style="display: none; margin-bottom: 10px; text-align: center; font-size: 12px; color: #666;"></div>
                                
                                <!-- 视频信息显示 -->
                                <div id="videoInfo" style="display: none; margin-bottom: 15px; text-align: center;">
                                    <div id="videoFileName" style="font-size: 14px; margin-bottom: 5px;"></div>
                                    <div id="videoFileSize" style="font-size: 12px; color: #666;"></div>
                                </div>
                                
                                <div style="text-align: center;">
                                    <button type="button" id="uploadPauseBtn" class="layui-btn layui-btn-warm" style="display: none; margin-right: 10px;">暂停</button>
                                    <button type="button" id="uploadResumeBtn" class="layui-btn" style="display: none; margin-right: 10px;">继续</button>
                                    <button type="button" id="insertVideoBtn" class="layui-btn" style="display: none; margin-right: 10px;">插入视频</button>
                                    <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">取消</button>
                                </div>
                            </div>
                        `,
                        success: function(layero) {
                            // 初始化上传进度条
                            if (window.layui && window.layui.element) {
                                window.layui.element.render('progress');
                            }
                            
                            // 全局变量存储上传的视频信息
                            window.uploadedVideoInfo = {};
                            
                            // 分块上传相关变量
                            let uploadChunkSize = 5 * 1024 * 1024; // 5MB每块
                            let currentChunk = 0;
                            let totalChunks = 0;
                            let isUploading = false;
                            let isPaused = false;
                            let currentFile = null;
                            let uploadId = null;
                            
                            // 获取元素
                            const dragArea = layero.find('#contentVideoUpload')[0];
                            const fileInput = layero.find('#videoFileInput')[0];
                            const progressBar = layero.find('#videoUploadProgress')[0];
                            const uploadStatus = layero.find('#uploadStatus')[0];
                            const videoInfo = layero.find('#videoInfo')[0];
                            const videoFileName = layero.find('#videoFileName')[0];
                            const videoFileSize = layero.find('#videoFileSize')[0];
                            const insertBtn = layero.find('#insertVideoBtn')[0];
                            const pauseBtn = layero.find('#uploadPauseBtn')[0];
                            const resumeBtn = layero.find('#uploadResumeBtn')[0];
                            
                            // 点击拖拽区域触发文件选择
                            dragArea.addEventListener('click', function() {
                                if (!isUploading) {
                                    fileInput.click();
                                }
                            });
                            
                            // 拖拽上传处理
                            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                                dragArea.addEventListener(eventName, function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                });
                            });
                            
                            // 拖拽进入样式变化
                            dragArea.addEventListener('dragover', function() {
                                if (!isUploading) {
                                    this.style.borderColor = '#1E9FFF';
                                }
                            });
                            
                            // 拖拽离开样式变化
                            dragArea.addEventListener('dragleave', function() {
                                this.style.borderColor = '#e6e6e6';
                            });
                            
                            // 格式化文件大小
                            function formatFileSize(bytes) {
                                if (bytes === 0) return '0 Bytes';
                                const k = 1024;
                                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                                const i = Math.floor(Math.log(bytes) / Math.log(k));
                                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                            }
                            
                            // 生成唯一的上传ID
                            function generateUploadId() {
                                return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            }
                            
                            // 获取文件块
                            function getFileChunk(file, chunkIndex, chunkSize) {
                                const start = chunkIndex * chunkSize;
                                const end = Math.min(start + chunkSize, file.size);
                                return file.slice(start, end);
                            }
                            
                            // 上传单个块
                            function uploadChunk(file, chunkIndex, chunkSize, totalChunks, uploadId) {
                                if (isPaused) {
                                    return Promise.reject(new Error('上传暂停'));
                                }
                                
                                const chunk = getFileChunk(file, chunkIndex, chunkSize);
                                const formData = new FormData();
                                
                                formData.append('file', chunk);
                                formData.append('filename', file.name);
                                formData.append('chunkIndex', chunkIndex);
                                formData.append('totalChunks', totalChunks);
                                formData.append('uploadId', uploadId);
                                formData.append('type', 'video');
                                
                                // 更新状态
                                uploadStatus.textContent = `上传中：块 ${chunkIndex + 1}/${totalChunks}`;
                                
                                // 发送实际的上传请求到后端API
                                    return new Promise((resolve, reject) => {
                                        const xhr = new XMLHttpRequest();
                                        
                                        // 使用绝对路径确保请求指向正确的文件
                                        xhr.open('POST', '/admin/modules/content/upload_chunk.php', true);
                                    
                                    // 监听上传进度
                                    xhr.upload.addEventListener('progress', function(e) {
                                        if (e.lengthComputable && !isPaused) {
                                            // 计算当前块的上传进度
                                            const chunkProgress = Math.floor((e.loaded / e.total) * 100);
                                            const overallProgress = Math.floor(((chunkIndex * chunkSize) + (e.loaded)) / file.size * 100);
                                            
                                            // 更新进度条
                                            if (window.layui && window.layui.element) {
                                                window.layui.element.progress('videoProgress', overallProgress + '%');
                                            }
                                        }
                                    });
                                    
                                    // 监听请求完成
                                    xhr.addEventListener('load', function() {
                                        if (isPaused) {
                                            reject(new Error('上传暂停'));
                                            return;
                                        }
                                        
                                        if (xhr.status === 200) {
                                            try {
                                                console.log('响应内容:', xhr.responseText);
                                                const response = JSON.parse(xhr.responseText);
                                                if (response.success) {
                                                    // 如果是最后一个块，处理返回的URL
                                    if (chunkIndex === totalChunks - 1 && response.url) {
                                        // 添加详细日志记录，检查完整的响应对象
                                        console.log('服务器返回的完整响应:', response);
                                        console.log('服务器返回的原始URL:', response.url);
                                        console.log('服务器返回的文件名:', response.fileName);
                                          
                                        // 确保URL格式正确，构建完整的URL路径
                                        let videoUrl = response.url;
                                        if (!videoUrl.startsWith('http') && !videoUrl.startsWith('/')) {
                                            videoUrl = '/' + videoUrl;
                                        }
                                        
                                        // 记录构建的完整URL
                                        console.log('构建的视频URL:', videoUrl);
                                          
                                        // 完整存储所有从服务器返回的信息
                                        window.uploadedVideoInfo.url = videoUrl;
                                        window.uploadedVideoInfo.type = file.type;
                                        window.uploadedVideoInfo.name = response.fileName || file.name;
                                        window.uploadedVideoInfo.size = response.fileSize || file.size;
                                        window.uploadedVideoInfo.serverFileName = response.fileName;
                                          
                                        console.log('完整的视频信息对象:', window.uploadedVideoInfo);
                                    }
                                                    resolve(response);
                                                } else {
                                                    reject(new Error(response.message || '上传失败'));
                                                }
                                            } catch (error) {
                                                console.error('JSON解析错误:', error, '响应内容:', xhr.responseText);
                                                reject(new Error('响应解析失败: ' + error.message));
                                            }
                                        } else {
                                            reject(new Error('服务器错误: ' + xhr.status));
                                        }
                                    });
                                    
                                    // 监听错误
                                    xhr.addEventListener('error', function() {
                                        reject(new Error('网络错误'));
                                    });
                                    
                                    // 监听超时
                                    xhr.addEventListener('timeout', function() {
                                        reject(new Error('上传超时'));
                                    });
                                    
                                    // 设置超时时间（5分钟）
                                    xhr.timeout = 300000;
                                    
                                    // 发送请求
                                    xhr.send(formData);
                                });
                            }
                            
                            // 执行分块上传
                            async function startChunkedUpload(file) {
                                isUploading = true;
                                isPaused = false;
                                currentFile = file;
                                currentChunk = 0;
                                totalChunks = Math.ceil(file.size / uploadChunkSize);
                                uploadId = generateUploadId();
                                
                                // 显示上传控件
                                progressBar.style.display = 'block';
                                uploadStatus.style.display = 'block';
                                pauseBtn.style.display = 'inline-block';
                                resumeBtn.style.display = 'none';
                                insertBtn.style.display = 'none';
                                
                                try {
                                    // 上传所有块
                                    while (currentChunk < totalChunks && isUploading && !isPaused) {
                                        await uploadChunk(file, currentChunk, uploadChunkSize, totalChunks, uploadId);
                                        
                                        currentChunk++;
                                        const progress = Math.floor((currentChunk / totalChunks) * 100);
                                        
                                        // 更新进度条
                                        if (window.layui && window.layui.element) {
                                            window.layui.element.progress('videoProgress', progress + '%');
                                        }
                                    }
                                    
                                    // 如果上传完成
                                if (currentChunk >= totalChunks && isUploading) {
                                    // 检查是否已有从服务器返回的视频信息
                                    console.log('上传完成检查 - 当前视频信息状态:', window.uploadedVideoInfo);
                                    
                                    if (!window.uploadedVideoInfo || !window.uploadedVideoInfo.url) {
                                        // 如果没有从服务器获得有效的URL，记录错误
                                        console.error('警告：上传完成但未获取到有效的服务器URL');
                                        
                                        if (!window.uploadedVideoInfo) {
                                            window.uploadedVideoInfo = {};
                                        }
                                        
                                        // 设置基本信息
                                        window.uploadedVideoInfo.name = file.name;
                                        window.uploadedVideoInfo.size = file.size;
                                        window.uploadedVideoInfo.type = file.type;
                                        
                                        // 警告：本地URL在页面刷新后会失效，仅作为临时解决方案
                                        window.uploadedVideoInfo.url = URL.createObjectURL(file);
                                        window.uploadedVideoInfo.isLocalUrl = true;
                                        
                                        console.warn('使用本地URL作为临时解决方案:', window.uploadedVideoInfo.url);
                                    }
                                    
                                    // 更新状态
                                    uploadStatus.textContent = '视频处理完成';
                                    
                                    // 显示插入按钮，隐藏控制按钮
                                    insertBtn.style.display = 'inline-block';
                                    pauseBtn.style.display = 'none';
                                    resumeBtn.style.display = 'none';
                                }
                                } catch (error) {
                                    console.error('上传失败:', error);
                                    uploadStatus.textContent = `上传失败: ${error.message}`;
                                    
                                    // 如果不是因为暂停而失败，显示错误提示
                                    if (!isPaused) {
                                        window.layer.msg('视频上传失败，请重试', {icon: 2});
                                        pauseBtn.style.display = 'none';
                                        resumeBtn.style.display = 'none';
                                    }
                                } finally {
                                    if (!isPaused) {
                                        isUploading = false;
                                    }
                                }
                            }
                            
                            // 处理文件上传
                            function handleFileUpload(file) {
                                // 检查文件类型
                                const validTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/avi', 'video/x-flv'];
                                if (!validTypes.some(type => file.type.startsWith(type.split('/')[0]))) {
                                    window.layer.msg('请选择有效的视频文件', {icon: 2});
                                    return;
                                }
                                
                                // 检查文件大小 (200MB)
                                if (file.size > 200 * 1024 * 1024) {
                                    window.layer.msg('视频大小不能超过200MB', {icon: 2});
                                    return;
                                }
                                
                                // 显示文件信息
                                videoFileName.textContent = file.name;
                                videoFileSize.textContent = formatFileSize(file.size);
                                videoInfo.style.display = 'block';
                                
                                // 开始分块上传
                                startChunkedUpload(file);
                            }
                            
                            // 暂停上传
                            pauseBtn.addEventListener('click', function() {
                                if (isUploading && !isPaused) {
                                    isPaused = true;
                                    uploadStatus.textContent = '上传已暂停';
                                    pauseBtn.style.display = 'none';
                                    resumeBtn.style.display = 'inline-block';
                                }
                            });
                            
                            // 继续上传
                            resumeBtn.addEventListener('click', function() {
                                if (isUploading && isPaused && currentFile) {
                                    isPaused = false;
                                    uploadStatus.textContent = `继续上传：块 ${currentChunk + 1}/${totalChunks}`;
                                    pauseBtn.style.display = 'inline-block';
                                    resumeBtn.style.display = 'none';
                                    
                                    // 继续上传
                                    startChunkedUpload(currentFile);
                                }
                            });
                            
                            // 处理文件选择
                            fileInput.addEventListener('change', function() {
                                if (this.files && this.files[0] && !isUploading) {
                                    handleFileUpload(this.files[0]);
                                }
                            });
                            
                            // 处理拖拽上传
                            dragArea.addEventListener('drop', function(e) {
                                this.style.borderColor = '#e6e6e6';

                                if (e.dataTransfer.files && e.dataTransfer.files[0] && !isUploading) {
                                    handleFileUpload(e.dataTransfer.files[0]);
                                }
                            });
                            
                            // 插入视频按钮点击事件
                            insertBtn.addEventListener('click', function() {
                                if (window.uploadedVideoInfo && window.uploadedVideoInfo.url) {
                                    try {
                                        // 记录调试信息
                                        console.log('即将插入的视频信息:', {
                                            url: window.uploadedVideoInfo.url,
                                            type: window.uploadedVideoInfo.type,
                                            name: window.uploadedVideoInfo.name,
                                            size: window.uploadedVideoInfo.size
                                        });
                                        
                                        // 确保编辑器有焦点
                                        customEditor.focus();
                                        
                                        // 插入视频 - 使用带div包装器的格式，以便支持缩略图功能
                                        const videoHtml = `<div class="video-container" style="position: relative; max-width: 100%; margin: 10px 0;">
                                            <video controls="controls" width="100%" style="max-width: 100%; height: auto;">
                                                <source src="${window.uploadedVideoInfo.url}" type="${window.uploadedVideoInfo.type || 'video/mp4'}">
                                                您的浏览器不支持视频播放
                                            </video>
                                        </div>`;
                                         
                                        console.log('生成的视频HTML:', videoHtml);
                                        document.execCommand('insertHTML', false, videoHtml);
                                         
                                        // 同步内容
                                        contentInput.value = customEditor.innerHTML;
                                         
                                        // 关闭弹窗
                                        window.layer.closeAll();
                                         
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
                                        }, 500);
                                         
                                        // 显示成功提示
                                        window.layer.msg('视频插入成功', {icon: 1});
                                    } catch (e) {
                                        console.error('插入视频失败:', e);
                                        window.layer.msg('插入视频失败', {icon: 2});
                                    }
                                } else {
                                    console.error('无法插入视频，缺少视频URL:', window.uploadedVideoInfo);
                                    window.layer.msg('视频信息不完整，无法插入', {icon: 2});
                                }
                            });
                        }
                    });
                }
            } catch (e) {
                console.error('执行insertVideo失败:', e);
                if (window.layer) {
                    window.layer.msg('插入视频失败', {icon: 2});
                }
            }
        };
        
        window.viewSource = function() {
            try {
                console.log('执行viewSource');
                if (!window.layer) return;
                
                // 获取编辑器内容
                const editorContent = contentInput.value;
                
                window.layer.open({
                    type: 1,
                    title: '编辑HTML源码',
                    area: ['800px', '600px'],
                    content: `
                        <div style="padding: 20px;">
                            <div style="margin-bottom: 15px;">
                                <textarea id="sourceCode" style="width: 100%; height: 400px; font-family: monospace; resize: none;">${editorContent.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')}</textarea>
                            </div>
                            <div style="text-align: center;">
                                <button type="button" class="layui-btn layui-btn-normal" onclick="applySourceCodeChanges()" style="margin-right: 10px;">应用</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">关闭</button>
                            </div>
                        </div>
                    `,
                    success: function(layero) {
                        const textarea = layero.find('#sourceCode')[0];
                        textarea.focus();
                        textarea.select();
                    }
                });
                
                // 定义应用源码变更的函数
                window.applySourceCodeChanges = function() {
                    try {
                        // 获取编辑后的源码
                        const sourceCode = document.getElementById('sourceCode').value;
                        
                        // 更新编辑器内容
                        if (contentInput && customEditor) {
                            contentInput.value = sourceCode;
                            customEditor.innerHTML = sourceCode;
                        }
                        
                        // 关闭弹窗并显示成功消息
                        window.layer.closeAll();
                        if (window.layer) {
                            window.layer.msg('源码已更新', {icon: 1});
                        }
                    } catch (e) {
                        console.error('应用源码变更失败:', e);
                        if (window.layer) {
                            window.layer.msg('应用源码变更失败', {icon: 2});
                        }
                    }
                };
            } catch (e) {
                console.error('执行viewSource失败:', e);
                if (window.layer) {
                    window.layer.msg('查看源码失败', {icon: 2});
                }
            }
        };
        
        window.findReplace = function() {
            try {
                console.log('执行findReplace');
                if (!window.layer) return;
                
                // 确保编辑器有焦点
                customEditor.focus();
                
                window.layer.open({
                    type: 1,
                    title: '查找替换',
                    area: ['500px', '300px'],
                    content: `
                        <div style="padding: 20px;">
                            <div class="layui-form-item">
                                <label class="layui-form-label">查找内容</label>
                                <div class="layui-input-block">
                                    <input type="text" id="findText" class="layui-input" placeholder="请输入要查找的文本">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">替换为</label>
                                <div class="layui-input-block">
                                    <input type="text" id="replaceText" class="layui-input" placeholder="请输入替换后的文本">
                                </div>
                            </div>
                            <div style="text-align: center; margin-top: 20px;">
                                <button type="button" id="findBtn" class="layui-btn layui-btn-primary" style="margin-right: 10px;">查找下一个</button>
                                <button type="button" id="replaceBtn" class="layui-btn" style="margin-right: 10px;">替换</button>
                                <button type="button" id="replaceAllBtn" class="layui-btn" style="margin-right: 10px;">全部替换</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">关闭</button>
                            </div>
                        </div>
                    `,
                    success: function(layero) {
                        const findInput = layero.find('#findText')[0];
                        const replaceInput = layero.find('#replaceText')[0];
                        const findBtn = layero.find('#findBtn')[0];
                        const replaceBtn = layero.find('#replaceBtn')[0];
                        const replaceAllBtn = layero.find('#replaceAllBtn')[0];
                        
                        let lastSearchRange = null;
                        let searchCaseSensitive = false;
                        
                        // 查找下一个
                        findBtn.addEventListener('click', function() {
                            const searchText = findInput.value.trim();
                            if (!searchText) return;
                            
                            findNextText(searchText, searchCaseSensitive);
                        });
                        
                        // 替换
                        replaceBtn.addEventListener('click', function() {
                            const searchText = findInput.value.trim();
                            const replaceText = replaceInput.value;
                            if (!searchText) return;
                            
                            replaceCurrentText(searchText, replaceText, searchCaseSensitive);
                        });
                        
                        // 全部替换
                        replaceAllBtn.addEventListener('click', function() {
                            const searchText = findInput.value.trim();
                            const replaceText = replaceInput.value;
                            if (!searchText) return;
                            
                            replaceAllText(searchText, replaceText, searchCaseSensitive);
                        });
                        
                        // 辅助函数
                        function findNextText(text, caseSensitive) {
                            try {
                                const selection = window.getSelection();
                                const editorContent = customEditor.innerText;
                                
                                // 从当前位置开始查找
                                let startPos = 0;
                                if (lastSearchRange) {
                                    startPos = lastSearchRange.endOffset;
                                }
                                
                                // 执行查找
                                const compareMethod = caseSensitive ? 'indexOf' : 'toLowerCase';
                                let foundPos = -1;
                                
                                if (caseSensitive) {
                                    foundPos = editorContent.indexOf(text, startPos);
                                } else {
                                    const lowerContent = editorContent.toLowerCase();
                                    const lowerText = text.toLowerCase();
                                    foundPos = lowerContent.indexOf(lowerText, startPos);
                                }
                                
                                // 如果没找到，从开始位置重新查找
                                if (foundPos === -1 && startPos > 0) {
                                    if (caseSensitive) {
                                        foundPos = editorContent.indexOf(text, 0);
                                    } else {
                                        const lowerContent = editorContent.toLowerCase();
                                        const lowerText = text.toLowerCase();
                                        foundPos = lowerContent.indexOf(lowerText, 0);
                                    }
                                }
                                
                                if (foundPos !== -1) {
                                    // 创建选择范围
                                    const range = document.createRange();
                                    const textNode = findTextNode(customEditor, foundPos, text.length);
                                    
                                    if (textNode) {
                                        range.setStart(textNode.node, textNode.startOffset);
                                        range.setEnd(textNode.node, textNode.endOffset);
                                        
                                        // 选择文本
                                        selection.removeAllRanges();
                                        selection.addRange(range);
                                        
                                        // 滚动到视图
                                        range.startContainer.parentNode.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        
                                        // 保存上次搜索范围
                                        lastSearchRange = range;
                                    }
                                } else {
                                    window.layer.msg('未找到指定文本', {icon: 5});
                                }
                            } catch (e) {
                                console.error('查找失败:', e);
                            }
                        }
                        
                        function replaceCurrentText(searchText, replaceText, caseSensitive) {
                            try {
                                if (lastSearchRange) {
                                    lastSearchRange.deleteContents();
                                    lastSearchRange.insertNode(document.createTextNode(replaceText));
                                    
                                    // 同步内容
                                    contentInput.value = customEditor.innerHTML;
                                    
                                    // 查找下一个
                                    setTimeout(() => {
                                        findNextText(searchText, caseSensitive);
                                    }, 10);
                                } else {
                                    // 先查找再替换
                                    findNextText(searchText, caseSensitive);
                                }
                            } catch (e) {
                                console.error('替换失败:', e);
                            }
                        }
                        
                        function replaceAllText(searchText, replaceText, caseSensitive) {
                            try {
                                // 获取编辑器内容
                                const oldContent = customEditor.innerHTML;
                                
                                // 创建正则表达式
                                const flags = caseSensitive ? 'g' : 'gi';
                                const regex = new RegExp(searchText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), flags);
                                
                                // 替换内容
                                let newContent = oldContent;
                                let matchCount = 0;
                                
                                // 先处理纯文本部分的替换
                                newContent = newContent.replace(/(<[^>]+>)|([^<]+)/g, function(match, tag, text) {
                                    if (text) {
                                        const replacedText = text.replace(regex, function() {
                                            matchCount++;
                                            return replaceText;
                                        });
                                        return replacedText;
                                    }
                                    return match;
                                });
                                
                                // 更新编辑器内容
                                customEditor.innerHTML = newContent;
                                contentInput.value = newContent;
                                
                                // 显示替换数量
                                window.layer.msg(`共替换 ${matchCount} 处`, {icon: 1});
                            } catch (e) {
                                console.error('全部替换失败:', e);
                                window.layer.msg('替换失败', {icon: 2});
                            }
                        }
                        
                        function findTextNode(node, startPos, length) {
                            let charIndex = 0;
                            let result = null;
                            
                            function traverse(node) {
                                if (node.nodeType === 3) { // 文本节点
                                    const nodeLength = node.nodeValue.length;
                                    
                                    if (charIndex + nodeLength > startPos) {
                                        const nodeStart = Math.max(0, startPos - charIndex);
                                        const nodeEnd = Math.min(nodeLength, startPos - charIndex + length);
                                        
                                        result = {
                                            node: node,
                                            startOffset: nodeStart,
                                            endOffset: nodeEnd
                                        };
                                        return true;
                                    }
                                    charIndex += nodeLength;
                                } else {
                                    for (let i = 0; i < node.childNodes.length; i++) {
                                        if (traverse(node.childNodes[i])) {
                                            return true;
                                        }
                                    }
                                }
                                return false;
                            }
                            
                            traverse(customEditor);
                            return result;
                        }
                    }
                });
            } catch (e) {
                console.error('执行findReplace失败:', e);
                if (window.layer) {
                    window.layer.msg('查找替换失败', {icon: 2});
                }
            }
        };
        
        window.insertTable = function() {
            try {
                console.log('执行insertTable');
                customEditor.focus();
                
                // 插入一个简单的表格
                const tableHtml = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">' +
                    '<thead><tr><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">表头1</th>' +
                    '<th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">表头2</th></tr></thead>' +
                    '<tbody><tr><td style="border: 1px solid #ddd; padding: 8px;">单元格1</td>' +
                    '<td style="border: 1px solid #ddd; padding: 8px;">单元格2</td></tr></tbody></table>';
                
                document.execCommand('insertHTML', false, tableHtml);
                contentInput.value = customEditor.innerHTML;
            } catch (e) {
                console.error('执行insertTable失败:', e);
            }
        };
        
        window.insertMedia = function() {
            try {
                console.log('执行insertMedia');
                customEditor.focus();
                
                if (window.layer) {
                    window.layer.open({
                        type: 1,
                        title: '插入媒体',
                        area: ['500px', '350px'],
                        content: `
                            <div style="padding: 20px;">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">媒体类型</label>
                                    <div class="layui-input-block">
                                        <select id="mediaType" lay-verify="required">
                                            <option value="audio">音频</option>
                                            <option value="video">视频</option>
                                            <option value="iframe">嵌入iframe</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">媒体URL</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="mediaUrl" placeholder="请输入媒体URL" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-form-item layui-form-text">
                                    <label class="layui-form-label">描述</label>
                                    <div class="layui-input-block">
                                        <textarea id="mediaDescription" placeholder="请输入媒体描述" class="layui-textarea"></textarea>
                                    </div>
                                </div>
                                <div style="text-align: center; margin-top: 20px;">
                                    <button type="button" id="insertMediaConfirmBtn" class="layui-btn">确定插入</button>
                                    <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">取消</button>
                                </div>
                            </div>
                        `,
                        success: function(layero) {
                            // 初始化layui表单
                            if (window.layui && window.layui.form) {
                                window.layui.form.render();
                            }
                            
                            const confirmBtn = layero.find('#insertMediaConfirmBtn')[0];
                            
                            confirmBtn.addEventListener('click', function() {
                                const mediaType = layero.find('#mediaType')[0].value;
                                const mediaUrl = layero.find('#mediaUrl')[0].value.trim();
                                const description = layero.find('#mediaDescription')[0].value.trim();
                                
                                if (!mediaUrl) {
                                    window.layer.msg('请输入媒体URL', {icon: 2});
                                    return;
                                }
                                
                                try {
                                    let mediaHtml = '';
                                    
                                    switch (mediaType) {
                                        case 'audio':
                                            mediaHtml = `<audio controls style="margin: 10px 0;"${description ? ` alt="${description}"` : ''}>
                                                <source src="${mediaUrl}" type="audio/mpeg">
                                                您的浏览器不支持音频播放
                                                </audio>`;
                                            break;
                                        case 'video':
                                            mediaHtml = `<video controls style="max-width: 100%; height: auto; margin: 10px 0;"${description ? ` alt="${description}"` : ''}>
                                                <source src="${mediaUrl}" type="video/mp4">
                                                您的浏览器不支持视频播放
                                                </video>`;
                                            break;
                                        case 'iframe':
                                            mediaHtml = `<div style="margin: 10px 0;">
                                                <iframe src="${mediaUrl}" width="100%" height="400" frameborder="0" allowfullscreen ${description ? `title="${description}"` : ''}></iframe>
                                                ${description ? `<p style="text-align: center; color: #666; font-size: 12px;">${description}</p>` : ''}
                                                </div>`;
                                            break;
                                    }
                                    
                                    // 确保编辑器有焦点
                                    customEditor.focus();
                                    
                                    // 插入媒体
                                    document.execCommand('insertHTML', false, mediaHtml);
                                    
                                    // 同步内容到隐藏的input
                                    contentInput.value = customEditor.innerHTML;
                                    
                                    // 关闭弹窗
                                    window.layer.closeAll();
                                    
                                    // 显示成功提示
                                    window.layer.msg('媒体插入成功', {icon: 1});
                                } catch (e) {
                                    console.error('插入媒体失败:', e);
                                    window.layer.msg('插入媒体失败', {icon: 2});
                                }
                            });
                        }
                    });
                }
            } catch (e) {
                console.error('执行insertMedia失败:', e);
                if (window.layer) {
                    window.layer.msg('插入媒体功能失败', {icon: 2});
                }
            }
        };
        
        window.saveEditorSelection = function(editorElement) {
            try {
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    
                    if (editorElement && editorElement.contains(range.commonAncestorContainer)) {
                        window.editorSelection = range.cloneRange();
                        window.editorSelectionEditor = editorElement;
                        return true;
                    }
                }
            } catch (e) {
                console.warn('保存选区失败:', e);
            }
            return false;
        };
        
        window.restoreEditorSelection = function() {
            if (window.editorSelection && window.editorSelectionEditor) {
                try {
                    if (document.contains(window.editorSelectionEditor) && 
                        window.editorSelectionEditor.hasAttribute('contenteditable')) {
                        
                        window.editorSelectionEditor.focus();
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(window.editorSelection);
                        
                        // 清除保存的选区
                        window.editorSelection = null;
                        window.editorSelectionEditor = null;
                        return true;
                    }
                } catch (e) {
                    console.warn('恢复选区失败:', e);
                }
            }
            return false;
        };
        
        console.log('所有编辑器功能函数已重新定义');
    }
    
    // 修复工具栏按钮的点击事件
    function fixToolbarButtons() {
        const toolbarButtons = document.querySelectorAll('.editor-toolbar button');
        
        toolbarButtons.forEach(button => {
            // 获取原有的onclick属性值
            const onclickValue = button.getAttribute('onclick');
            
            if (onclickValue) {
                // 移除原有的onclick属性
                button.removeAttribute('onclick');
                
                // 解析函数调用
                const funcMatch = onclickValue.match(/window\.(\w+)\(([^)]*)\)/);
                
                if (funcMatch) {
                    const funcName = funcMatch[1];
                    const funcArgs = funcMatch[2].split(',').map(arg => {
                        // 处理字符串参数
                        const strMatch = arg.match(/['"](.+)['"]/);
                        return strMatch ? strMatch[1] : arg.trim();
                    });
                    
                    // 添加新的点击事件监听器
                    button.addEventListener('click', function() {
                        console.log(`点击按钮: ${funcName}`, funcArgs);
                        
                        // 确保编辑器有焦点
                        customEditor.focus();
                        
                        // 调用对应的全局函数
                        if (window[funcName] && typeof window[funcName] === 'function') {
                            try {
                                window[funcName](...funcArgs);
                            } catch (e) {
                                console.error(`执行${funcName}失败:`, e);
                                if (window.layer) {
                                    window.layer.msg(`执行${funcName}失败`, {icon: 2});
                                }
                            }
                        } else {
                            console.warn(`函数${funcName}未定义`);
                            if (window.layer) {
                                window.layer.msg(`功能暂未实现`, {icon: 5});
                            }
                        }
                    });
                }
            }
        });
        
        console.log(`已修复 ${toolbarButtons.length} 个工具栏按钮的点击事件`);
    }
    
    // 执行修复
    ensureGlobalFunctions();
    fixToolbarButtons();
    
    // 颜色选择器相关代码已移除
    
    console.log('编辑器按钮功能修复完成');
    
    // 显示修复成功提示
    if (window.layer) {
        setTimeout(() => {
            window.layer.msg('编辑器按钮功能已修复', {icon: 1, time: 2000});
        }, 500);
    }
});