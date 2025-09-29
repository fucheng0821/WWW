/**
 * 增强的图片上传器 - 支持单图和多图上传模式 (移动端版本)
 */
class EnhancedImageUploader {
    constructor(editorElement, contentInput, options = {}) {
        this.editor = editorElement;
        this.contentInput = contentInput;
        this.options = {
            maxSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff'],
            uploadUrl: '../content/upload.php',
            ...options
        };
        this.uploadedImages = [];
        this.uploadMode = 'single'; // 'single' or 'multiple'
    }

    /**
     * 显示图片上传对话框
     */
    showUploadDialog() {
        this.openDialog();
    }
    
    /**
     * 显示图片上传对话框（别名方法，兼容现有调用）
     */
    openDialog() {
        if (!window.layui || !window.layer) {
            console.error('LayUI 未加载');
            return;
        }

        // 清空之前上传的图片
        this.uploadedImages = [];

        window.layer.open({
            type: 1,
            title: '上传图片',
            area: ['95%', '500px'],
            shade: 0.2,
            shadeClose: true,
            content: `
                <div style="padding: 15px;">
                    <!-- 上传模式切换 -->
                    <div style="margin-bottom: 15px; padding: 10px; background-color: #f7f7f7; border-radius: 6px;">
                        <div style="display: flex; align-items: center; flex-wrap: wrap;">
                            <label style="font-weight: 500; margin-right: 10px; color: #333; font-size: 14px;">上传模式：</label>
                            <select id="upload-mode-select" class="layui-select" style="width: 120px; height: 32px; border-radius: 4px; border: 1px solid #dcdcdc; font-size: 13px;">
                                <option value="single">单图上传</option>
                                <option value="multiple">多图上传</option>
                            </select>
                            <div style="margin-left: auto; color: #666; font-size: 12px;">
                                <i class="layui-icon layui-icon-tips" style="color: #1E9FFF;"></i>
                                <span>支持拖拽上传，最多10MB</span>
                            </div>
                        </div>
                    </div>

                    <!-- 上传区域 - 美化设计 -->
                    <div id="contentImageUpload" style="
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
                        <div id="upload-mode-text" style="font-size: 14px; color: #333; margin-bottom: 6px; font-weight: 500; text-align: center;">点击上传图片，或将图片拖拽到此处</div>
                        <div class="layui-word-aux" style="color: #999; font-size: 12px;">支持 JPG, PNG, GIF, WebP, BMP, TIFF 格式</div>
                    </div>

                    <!-- 隐藏的文件输入 -->
                    <input type="file" id="imageFileInput" accept="image/*" style="display: none;" />

                    <!-- 上传进度条 - 美化设计 -->
                    <div id="imageUploadProgress" style="display: none; margin-bottom: 15px; padding: 12px; background-color: #fff; border-radius: 6px; border: 1px solid #e6e6e6;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #666; font-size: 13px;">上传中...</span>
                            <span id="upload-progress-text" style="color: #1E9FFF; font-size: 13px; font-weight: 500;">0%</span>
                        </div>
                        <div class="layui-progress" lay-filter="imageProgress">
                            <div class="layui-progress-bar layui-bg-blue" lay-percent="0%"></div>
                        </div>
                    </div>

                    <!-- 预览区域 - 美化设计 -->
                    <div id="imagePreviewContainer" style="display: none; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h4 style="margin: 0; font-size: 14px; font-weight: 500; color: #333;">
                                <i class="layui-icon layui-icon-picture" style="color: #1E9FFF; margin-right: 6px;"></i>
                                已上传图片
                            </h4>
                            <span id="image-count" style="color: #666; font-size: 12px;">0张</span>
                        </div>
                        <div id="imagePreviewList" style="
                            display: flex;
                            flex-wrap: wrap;
                            gap: 10px;
                            padding: 12px;
                            background-color: #fafafa;
                            border-radius: 6px;
                            min-height: 80px;
                        "></div>
                    </div>

                    <!-- 按钮区域 - 美化设计 -->
                    <div style="text-align: center; padding-top: 12px; border-top: 1px solid #e6e6e6;">
                        <button type="button" id="insertImageBtn" class="layui-btn layui-btn-normal" style="
                            display: none;
                            margin-right: 10px;
                            padding: 0 20px;
                            height: 32px;
                            border-radius: 4px;
                            font-size: 13px;
                        ">
                            <i class="layui-icon layui-icon-file-image"></i> 插入图片
                        </button>
                        <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()" style="
                            padding: 0 20px;
                            height: 32px;
                            border-radius: 4px;
                            font-size: 13px;
                        ">
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
        
        // 初始化上传进度条
        if (layui && layui.element) {
            layui.element.render('progress');
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

        // 模式切换事件
        modeSelect.addEventListener('change', (e) => {
            this.uploadMode = e.target.value;
            fileInput.multiple = this.uploadMode === 'multiple';
            modeText.textContent = this.uploadMode === 'multiple' ? '点击上传多张图片，或将图片拖拽到此处' : '点击上传图片，或将图片拖拽到此处';
            
            // 清空已上传的图片
            if (this.uploadedImages.length > 0) {
                // 添加清空动画效果
                Array.from(previewList.children).forEach((item, index) => {
                    setTimeout(() => {
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.8)';
                        if (index === previewList.children.length - 1) {
                            setTimeout(() => {
                                this.uploadedImages = [];
                                previewList.innerHTML = '';
                                previewContainer.style.display = 'none';
                                insertBtn.style.display = 'none';
                                // 更新图片数量统计
                                const imageCount = document.getElementById('image-count');
                                if (imageCount) {
                                    imageCount.textContent = '0张';
                                }
                            }, 300);
                        }
                    }, index * 50);
                });
            }
        });

        // 点击拖拽区域触发文件选择
        dragArea.addEventListener('click', () => {
            fileInput.click();
        });

        // 拖拽上传处理
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dragArea.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        // 拖拽进入样式变化
        dragArea.addEventListener('dragover', function() {
            this.style.borderColor = '#1E9FFF';
            this.style.backgroundColor = '#e8f4fd';
            this.style.transform = 'scale(1.01)';
        });

        // 拖拽离开样式变化
        dragArea.addEventListener('dragleave', function() {
            this.style.borderColor = '#dcdcdc';
            this.style.backgroundColor = '#fafafa';
            this.style.transform = 'scale(1)';
        });

        // 处理文件选择
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files.length > 0) {
                this.handleFileUpload(fileInput.files, progressBar, previewList, previewContainer, insertBtn, layer, layui);
            }
        });

        // 处理拖拽上传
        dragArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dragArea.style.borderColor = '#dcdcdc';
            dragArea.style.backgroundColor = '#fafafa';

            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                // 添加轻微的动画效果
                dragArea.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    dragArea.style.transform = 'scale(1)';
                    this.handleFileUpload(e.dataTransfer.files, progressBar, previewList, previewContainer, insertBtn, layer, layui);
                }, 150);
            }
        });

        // 插入图片按钮点击事件
        insertBtn.addEventListener('click', () => {
            try {
                this.insertImages();
                layer.closeAll();
                layer.msg(this.uploadMode === 'multiple' ? '多张图片插入成功' : '图片插入成功', {icon: 1});
            } catch (e) {
                console.error('插入图片失败:', e);
                layer.msg('插入图片失败', {icon: 2});
            }
        });
    }

    /**
     * 处理文件上传
     */
    /**
     * 处理文件上传 - 带优化的用户体验
     */
    async handleFileUpload(files, progressBar, previewList, previewContainer, insertBtn, layer, layui) {
        // 验证文件
        const validFiles = this.validateFiles(files);
        if (validFiles.length === 0) return;

        // 显示进度条
        progressBar.style.display = 'block';
        const progressText = document.getElementById('upload-progress-text');
        
        // 初始化进度条
        if (layui && layui.element) {
            layui.element.progress('imageProgress', '0%');
        }
        if (progressText) {
            progressText.textContent = '0% (准备上传...)';
        }

        try {
            // 上传文件
            for (let i = 0; i < validFiles.length; i++) {
                const file = validFiles[i];
                const currentFileIndex = i + 1;
                
                // 更新进度文本为当前正在上传的文件信息
                if (progressText) {
                    progressText.textContent = `0% (${currentFileIndex}/${validFiles.length}) 正在上传: ${file.name}`;
                }

                // 上传文件
                const imageUrl = await this.uploadFile(file);
                this.uploadedImages.push(imageUrl);

                // 更新进度条为当前文件已完成
                const progress = Math.floor(((i + 1) / validFiles.length) * 100);
                if (layui && layui.element) {
                    layui.element.progress('imageProgress', progress + '%');
                }
                if (progressText) {
                    progressText.textContent = `${progress}% (${currentFileIndex}/${validFiles.length}) 已上传: ${file.name}`;
                }

                // 添加预览
                this.addImagePreview(file, imageUrl, previewList, previewContainer, insertBtn);
            }

            // 更新进度为100%并显示完成消息
            if (layui && layui.element) {
                layui.element.progress('imageProgress', '100%');
            }
            if (progressText) {
                progressText.textContent = '100% 全部上传完成!';
            }

            // 延迟隐藏进度条，让用户看到完成状态
            setTimeout(() => {
                progressBar.style.display = 'none';
            }, 800);

            // 显示插入按钮
            insertBtn.style.display = 'inline-block';
            
            // 显示预览容器
            previewContainer.style.display = 'block';

            // 更新图片数量统计
            const imageCount = document.getElementById('image-count');
            if (imageCount) {
                imageCount.textContent = this.uploadedImages.length + '张';
            }

            // 显示成功提示
            layer.msg(validFiles.length + ' 张图片上传成功', {icon: 1});
        } catch (error) {
            console.error('上传失败:', error);
            layer.msg('上传失败: ' + error.message, {icon: 2});
            progressBar.style.display = 'none';
        }
    }

    /**
     * 验证文件
     */
    validateFiles(files) {
        const validFiles = [];
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // 检查文件类型
            if (!this.options.allowedTypes.includes(file.type)) {
                window.layer.msg('文件 ' + file.name + ' 不是有效的图片格式', {icon: 2});
                continue;
            }
            
            // 检查文件大小
            if (file.size > this.options.maxSize) {
                window.layer.msg('文件 ' + file.name + ' 大小不能超过10MB', {icon: 2});
                continue;
            }
            
            validFiles.push(file);
        }
        
        return validFiles;
    }

    /**
     * 上传单个文件
     */
    /**
     * 上传单个文件 - 优化版本，支持真实进度监控
     */
    uploadFile(file) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'image');
            
            // 创建XHR对象以获取真实的上传进度
            const xhr = new XMLHttpRequest();
            
            // 设置上传进度事件
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const progress = Math.min(Math.floor((e.loaded / e.total) * 100), 95); // 保留5%给最后的处理
                    
                    // 更新全局进度状态
                    const progressText = document.getElementById('upload-progress-text');
                    if (progressText) {
                        progressText.textContent = `${progress}% (正在上传: ${file.name})`;
                    }
                }
            });
            
            // 设置完成事件
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success && data.location) {
                            resolve(data.location);
                        } else if (data.fileUrl) {
                            resolve(data.fileUrl);
                        } else {
                            throw new Error(data.error || '上传失败');
                        }
                    } catch (error) {
                        reject(new Error('响应解析错误: ' + error.message));
                    }
                } else {
                    reject(new Error('上传失败: HTTP ' + xhr.status));
                }
            };
            
            // 设置错误事件
            xhr.onerror = () => {
                reject(new Error('网络错误，上传失败'));
            };
            
            // 设置超时事件
            xhr.timeout = 60000; // 60秒超时
            xhr.ontimeout = () => {
                reject(new Error('上传超时，请检查网络连接'));
            };
            
            // 发送请求
            xhr.open('POST', this.options.uploadUrl, true);
            xhr.send(formData);
        });
    }

    /**
     * 添加图片预览
     */
    addImagePreview(file, imageUrl, previewList, previewContainer, insertBtn) {
        // 创建预览元素
        const previewItem = document.createElement('div');
        previewItem.className = 'image-preview-item';
        previewItem.style.position = 'relative';
        previewItem.style.width = '100px';
        previewItem.style.height = '100px';
        previewItem.style.overflow = 'hidden';
        previewItem.style.border = '2px solid #e6e6e6';
        previewItem.style.borderRadius = '6px';
        previewItem.style.boxShadow = '0 2px 6px rgba(0, 0, 0, 0.08)';
        previewItem.style.transition = 'all 0.3s ease';
        previewItem.style.backgroundColor = '#fff';
        
        // 悬停效果
        previewItem.addEventListener('mouseenter', () => {
            previewItem.style.borderColor = '#1E9FFF';
            previewItem.style.transform = 'translateY(-2px)';
            previewItem.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.12)';
        });
        
        previewItem.addEventListener('mouseleave', () => {
            previewItem.style.borderColor = '#e6e6e6';
            previewItem.style.transform = 'translateY(0)';
            previewItem.style.boxShadow = '0 2px 6px rgba(0, 0, 0, 0.08)';
        });
        
        // 创建图片元素
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.transition = 'all 0.3s ease';
        
        // 创建删除按钮
        const deleteBtn = document.createElement('div');
        deleteBtn.innerHTML = '<i class="layui-icon layui-icon-close"></i>';
        deleteBtn.style.position = 'absolute';
        deleteBtn.style.top = '6px';
        deleteBtn.style.right = '6px';
        deleteBtn.style.width = '20px';
        deleteBtn.style.height = '20px';
        deleteBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
        deleteBtn.style.color = '#ff5722';
        deleteBtn.style.borderRadius = '50%';
        deleteBtn.style.display = 'flex';
        deleteBtn.style.justifyContent = 'center';
        deleteBtn.style.alignItems = 'center';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.style.fontSize = '12px';
        deleteBtn.style.boxShadow = '0 1px 4px rgba(0, 0, 0, 0.15)';
        deleteBtn.style.transition = 'all 0.3s ease';
        deleteBtn.style.opacity = '0';
        
        // 悬停时显示删除按钮
        previewItem.addEventListener('mouseenter', () => {
            deleteBtn.style.opacity = '1';
        });
        
        previewItem.addEventListener('mouseleave', () => {
            deleteBtn.style.opacity = '0';
        });
        
        // 删除按钮悬停效果
        deleteBtn.addEventListener('mouseenter', () => {
            deleteBtn.style.backgroundColor = '#ff5722';
            deleteBtn.style.color = 'white';
        });
        
        deleteBtn.addEventListener('mouseleave', () => {
            deleteBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
            deleteBtn.style.color = '#ff5722';
        });
        
        // 删除事件
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // 添加删除动画
            previewItem.style.opacity = '0';
            previewItem.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                const index = this.uploadedImages.indexOf(imageUrl);
                if (index > -1) {
                    this.uploadedImages.splice(index, 1);
                }
                previewList.removeChild(previewItem);
                
                // 更新图片数量统计
                const imageCount = document.getElementById('image-count');
                if (imageCount) {
                    imageCount.textContent = this.uploadedImages.length + '张';
                }
                
                // 如果没有图片了，隐藏预览容器和插入按钮
                if (previewList.children.length === 0) {
                    previewContainer.style.display = 'none';
                    insertBtn.style.display = 'none';
                }
            }, 300);
        });
        
        // 添加文件名显示
        const fileName = document.createElement('div');
        fileName.textContent = file.name.length > 10 ? file.name.substring(0, 10) + '...' : file.name;
        fileName.style.position = 'absolute';
        fileName.style.bottom = '0';
        fileName.style.left = '0';
        fileName.style.right = '0';
        fileName.style.padding = '4px 6px';
        fileName.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
        fileName.style.color = 'white';
        fileName.style.fontSize = '11px';
        fileName.style.whiteSpace = 'nowrap';
        fileName.style.overflow = 'hidden';
        fileName.style.textOverflow = 'ellipsis';
        fileName.style.opacity = '0';
        fileName.style.transition = 'opacity 0.3s ease';
        
        // 悬停时显示文件名
        previewItem.addEventListener('mouseenter', () => {
            fileName.style.opacity = '1';
        });
        
        // 添加到预览列表
        previewItem.appendChild(img);
        previewItem.appendChild(fileName);
        previewItem.appendChild(deleteBtn);
        previewList.appendChild(previewItem);
        
        // 显示预览容器
        previewContainer.style.display = 'block';
        
        // 显示插入按钮
        insertBtn.style.display = 'inline-block';
    }

    /**
     * 插入图片到编辑器 - 修复版本
     */
    insertImages() {
        if (!this.editor || this.uploadedImages.length === 0) {
            console.warn('无法插入图片: 编辑器未初始化或没有上传的图片');
            return;
        }
        
        try {
            // 确保编辑器有焦点
            this.editor.focus();
            
            // 保存当前选区
            if (window.saveEditorSelection) {
                window.saveEditorSelection(this.editor);
            }
            
            // 插入图片
            this.uploadedImages.forEach((imageUrl, index) => {
                // 创建图片元素
                const imgElement = document.createElement('img');
                imgElement.src = imageUrl;
                imgElement.style.maxWidth = '100%';
                imgElement.style.height = 'auto';
                imgElement.style.margin = '10px 0';
                imgElement.style.borderRadius = '4px';
                imgElement.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                imgElement.setAttribute('data-uploaded', 'true'); // 添加标记属性
                
                // 插入图片到编辑器
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(imgElement);
                    
                    // 将光标移动到图片后面
                    range.setStartAfter(imgElement);
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } else {
                    // 如果没有选区，直接添加到编辑器末尾
                    this.editor.appendChild(imgElement);
                    
                    // 添加换行符
                    const br = document.createElement('br');
                    this.editor.appendChild(br);
                }
                
                // 如果是多图模式且不是最后一张图片，添加额外的换行
                if (this.uploadMode === 'multiple' && index < this.uploadedImages.length - 1) {
                    const br = document.createElement('br');
                    this.editor.appendChild(br);
                }
            });
            
            // 同步内容到隐藏的input
            if (this.contentInput) {
                this.contentInput.value = this.editor.innerHTML;
            }
            
            console.log('图片插入成功');
        } catch (error) {
            console.error('插入图片时发生错误:', error);
            throw new Error('插入图片失败: ' + error.message);
        }
    }
}

// 全局函数，供现有代码调用
window.insertImage = function() {
    try {
        if (window.enhancedImageUploader) {
            window.enhancedImageUploader.showUploadDialog();
        } else {
            console.error('EnhancedImageUploader 未初始化');
            if (window.layer) {
                window.layer.msg('图片上传功能初始化失败，请刷新页面重试', {icon: 2});
            }
        }
    } catch (e) {
        console.error('执行insertImage失败:', e);
        if (window.layer) {
            window.layer.msg('插入图片失败', {icon: 2});
        }
    }
};