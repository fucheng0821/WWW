<!-- ������Ƶ����ʵ�� --><!-- 视频上传功能由chunked_video_upload.js提供 -->
<script>
// 格式化字节大�?function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}
</script>

<!-- 增强图片上传�?-->
<script src="../../assets/js/enhanced-image-uploader.js"></script>
<!-- 现代化视频上传器 -->
<script src="../../assets/js/chunked_video_upload.js"></script>
<script>
// 初始化增强图片上传器
if (typeof EnhancedImageUploader !== 'undefined') {
    try {
        // 获取编辑器和内容输入元素
        const editor = document.getElementById('custom-editor');
        const contentInput = document.getElementById('content-input');

        if (editor && contentInput) {
            const uploader = new EnhancedImageUploader(editor, contentInput);
            window.imageUploader = uploader;
            window.enhancedImageUploader = uploader;
            console.log('增强图片上传器初始化成功');
        } else {
            console.error('编辑器元素未找到，无法初始化增强图片上传�?);
        }
    } catch (error) {
        console.error('增强图片上传器初始化失败:', error);
    }
} else {
    console.warn('增强图片上传器未加载');
}

// 确保视频上传器已初始�?if (typeof ensureVideoUploaderInitialized !== 'undefined') {
    ensureVideoUploaderInitialized();
}
</script>
</body>
</html>
