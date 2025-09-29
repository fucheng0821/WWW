<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $thumbnail = $_POST['thumbnail'] ?? '';

    // 验证输入
    if (empty($title)) {
        $errors[] = '标题不能为空';
    }
    
    if ($category_id <= 0) {
        $errors[] = '请选择栏目';
    }
    
    if (empty($slug)) {
        $slug = generate_slug($title);
    }
    
    // 如果没有错误，插入数据
    if (empty($errors)) {
        try {
            $published_at = $is_published ? date('Y-m-d H:i:s') : null;
            
            $stmt = $db->prepare("
                INSERT INTO contents 
                (category_id, title, slug, summary, content, tags, sort_order, is_featured, is_published, thumbnail, images, videos, published_at, seo_title, seo_keywords, seo_description, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $category_id, $title, $slug, $summary, $content, $tags, $sort_order, $is_featured, $is_published, $thumbnail, null, null, $published_at, $seo_title, $seo_keywords, $seo_description
            ]);
            
            $success = '内容添加成功！';
            $_POST = [];
        } catch(PDOException $e) {
            $errors[] = '添加失败：' . $e->getMessage();
        }
    }
}

// 获取栏目列表
try {
    $stmt = $db->query("SELECT id, name, parent_id FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加内容（自定义编辑器）- 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .editor-toolbar {
            border: 1px solid #e6e6e6;
            border-bottom: none;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 4px 4px 0 0;
        }
        .editor-content {
            min-height: 400px;
            border: 1px solid #e6e6e6;
            padding: 15px;
            background: #fff;
            font-family: "Microsoft YaHei", "PingFang SC", sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }
        .editor-content:focus {
            outline: none;
        }
        .editor-badge {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .editor-features {
            background: #f1f8e9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
        }
        .editor-container {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>添加内容 <span class="editor-badge">🖋️ 自定义编辑器</span></h2>
                        <div>
                            <a href="index.php" class="layui-btn layui-btn-primary">🔙 返回列表</a>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="layui-alert layui-alert-danger">
                            <ul style="margin: 0; padding-left: 20px;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="editor-features">
                        <h4>🖋️ 自定义编辑器特性</h4>
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-md3">✅ 100%开源免费</div>
                            <div class="layui-col-md3">🚫 无API限制</div>
                            <div class="layui-col-md3">⚡ 轻量级高性能</div>
                            <div class="layui-col-md3">🎨 界面简洁美观</div>
                        </div>
                        <div class="layui-row layui-col-space15" style="margin-top: 10px;">
                            <div class="layui-col-md3">📝 支持富文本编辑</div>
                            <div class="layui-col-md3">🖼️ 图片上传功能</div>
                            <div class="layui-col-md3">🎥 视频上传功能</div>
                            <div class="layui-col-md3">🔗 链接管理</div>
                        </div>
                    </div>
                    
                    <form class="layui-form" method="POST" id="content-form">
                        <div class="layui-tab">
                            <ul class="layui-tab-title">
                                <li class="layui-this">基本信息</li>
                                <li>内容编辑</li>
                                <li>SEO设置</li>
                            </ul>
                            <div class="layui-tab-content">
                                <!-- 基本信息 -->
                                <div class="layui-tab-item layui-show">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">标题 *</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="title" placeholder="请输入内容标题" 
                                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                                   class="layui-input" required>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">所属栏目 *</label>
                                        <div class="layui-input-block">
                                            <select name="category_id" required>
                                                <option value="">请选择栏目</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                            <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">摘要</label>
                                        <div class="layui-input-block">
                                            <textarea name="summary" placeholder="请输入内容摘要" 
                                                      class="layui-textarea" rows="3"><?php echo htmlspecialchars($_POST['summary'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">标签</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="tags" placeholder="多个标签用逗号分隔" 
                                                   value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <input type="checkbox" name="is_featured" value="1" 
                                                   <?php echo ($_POST['is_featured'] ?? 0) ? 'checked' : ''; ?> 
                                                   title="推荐到首页" lay-skin="primary">
                                            <input type="checkbox" name="is_published" value="1" 
                                                   <?php echo (!isset($_POST['is_published']) || $_POST['is_published']) ? 'checked' : ''; ?> 
                                                   title="立即发布" lay-skin="primary">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 内容编辑 -->
                                <div class="layui-tab-item">
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <div class="editor-container">
                                                <!-- 自定义内容编辑器 -->
                                                <div class="custom-editor">
                                                    <div class="editor-toolbar">
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="formatText('bold')"><i class="layui-icon layui-icon-fonts-strong"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="formatText('italic')"><i class="layui-icon layui-icon-fonts-i"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="formatText('underline')"><i class="layui-icon layui-icon-fonts-u"></i></button>
                                                        <div class="layui-inline" style="margin-left: 10px;">
                                                            <select onchange="formatText('formatBlock', this.value)">
                                                                <option value="p">段落</option>
                                                                <option value="h1">标题1</option>
                                                                <option value="h2">标题2</option>
                                                                <option value="h3">标题3</option>
                                                                <option value="h4">标题4</option>
                                                                <option value="h5">标题5</option>
                                                                <option value="h6">标题6</option>
                                                            </select>
                                                        </div>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="insertLink()" style="margin-left: 10px;"><i class="layui-icon layui-icon-link"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="insertImage()" style="margin-left: 5px;"><i class="layui-icon layui-icon-picture"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="insertVideo()" style="margin-left: 5px;"><i class="layui-icon layui-icon-video"></i></button>
                                                    </div>
                                                    <div id="custom-editor" class="editor-content" contenteditable="true">
                                                        <?php echo $_POST['content'] ?? '<p>开始编写您的内容...</p>'; ?>
                                                    </div>
                                                </div>
                                                <textarea name="content" id="content-input" style="display: none;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-alert layui-alert-normal" style="margin-top: 15px;">
                                        <h4>📋 自定义编辑器使用说明</h4>
                                        <ul style="margin: 10px 0; padding-left: 20px;">
                                            <li><strong>基础格式</strong>：选中文字可以设置粗体、斜体、下划线等</li>
                                            <li><strong>标题设置</strong>：使用标题下拉菜单设置H1-H6标题</li>
                                            <li><strong>列表功能</strong>：支持有序列表和无序列表</li>
                                            <li><strong>链接插入</strong>：选中文字后点击链接按钮</li>
                                            <li><strong>图片上传</strong>：点击图片按钮选择图片上传</li>
                                            <li><strong>视频上传</strong>：点击视频按钮上传视频文件</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <!-- SEO设置 -->
                                <div class="layui-tab-item">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO标题</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="seo_title" placeholder="留空使用内容标题" 
                                                   value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO关键词</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="seo_keywords" placeholder="多个关键词用逗号分隔" 
                                                   value="<?php echo htmlspecialchars($_POST['seo_keywords'] ?? ''); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO描述</label>
                                        <div class="layui-input-block">
                                            <textarea name="seo_description" placeholder="留空使用内容摘要" 
                                                      class="layui-textarea" rows="4"><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="layui-form-item">
                                <label class="layui-form-label">缩略图</label>
                                <div class="layui-input-block">
                                    <div class="layui-upload">
                                        <button type="button" class="layui-btn layui-btn-primary" id="upload-thumbnail">
                                            <i class="layui-icon"></i>上传缩略图
                                        </button>
                                        <input type="hidden" name="thumbnail" id="thumbnail-input" value="<?php echo htmlspecialchars($_POST['thumbnail'] ?? ''); ?>">
                                    </div>
                                    <div id="thumbnail-preview" style="margin-top: 10px; display: none;">
                                        <img id="thumbnail-image" src="<?php echo htmlspecialchars($_POST['thumbnail'] ?? ''); ?>" style="max-width: 200px; max-height: 150px; border: 1px solid #eee; padding: 5px;">
                                        <button type="button" class="layui-btn layui-btn-danger layui-btn-xs" id="remove-thumbnail" style="margin-left: 10px;">
                                            删除
                                        </button>
                                    </div>
                                    <p class="layui-word-aux">建议尺寸：1200x675px，最大10MB</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal">💾 保存内容</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="previewContent()">👁️ 预览内容</button>
                                <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    // 自定义编辑器相关变量
    let customEditor = null;
    
    layui.use(['form', 'element', 'layer'], function(){
        var form = layui.form;
        var element = layui.element;
        var layer = layui.layer;
        
        form.render();
        element.render();
        
        // 初始化自定义编辑器
        customEditor = document.getElementById('custom-editor');
        
        // 监听内容变化
        customEditor.addEventListener('input', function() {
            document.getElementById('content-input').value = customEditor.innerHTML;
        });
        
        // 表单提交前同步内容
        document.getElementById('content-form').addEventListener('submit', function() {
            document.getElementById('content-input').value = customEditor.innerHTML;
        });
        
        layer.msg('🖋️ 自定义编辑器加载完成！', {icon: 1, time: 2000});
    });
    
    // 格式化文本
    function formatText(command, value) {
        if (command === 'formatBlock') {
            document.execCommand(command, false, value);
        } else {
            document.execCommand(command, false, null);
        }
        customEditor.focus();
    }
    
    // 插入链接
    function insertLink() {
        layui.layer.prompt({
            formType: 0,
            title: '请输入链接地址',
            placeholder: 'https://example.com'
        }, function(value, index, elem){
            if (value) {
                document.execCommand('createLink', false, value);
            }
            layui.layer.close(index);
            customEditor.focus();
        });
    }
    
    // 缩略图上传处理
    document.getElementById('upload-thumbnail')?.addEventListener('click', function() {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        
        input.onchange = function() {
            const file = input.files[0];
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    layui.layer.msg('图片大小不能超过10MB', {icon: 2});
                    return;
                }
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', 'thumbnail');
                
                // 显示上传进度
                layui.layer.msg('正在上传缩略图...', {icon: 16, time: 0, shade: 0.3});
                
                fetch('upload.php', {method: 'POST', body: formData})
                .then(response => response.json())
                .then(result => {
                    layui.layer.closeAll();
                    
                    if (result.location) {
                        const thumbnailInput = document.getElementById('thumbnail-input');
                        const thumbnailPreview = document.getElementById('thumbnail-preview');
                        const thumbnailImage = document.getElementById('thumbnail-image');
                        
                        thumbnailInput.value = result.location;
                        // Use full URL for image src
                        thumbnailImage.src = '<?php echo UPLOAD_URL; ?>/' + result.location.replace('uploads/', '');
                        thumbnailPreview.style.display = 'block';
                        
                        layui.layer.msg('缩略图上传成功！', {icon: 1, time: 2000});
                    } else {
                        layui.layer.msg('缩略图上传失败：' + (result.message || '未知错误'), {icon: 2});
                    }
                })
                .catch(error => {
                    layui.layer.closeAll();
                    layui.layer.msg('上传失败：' + error.message, {icon: 2});
                });
            }
        };
        
        input.click();
    });
    
    // 删除缩略图
    document.getElementById('remove-thumbnail')?.addEventListener('click', function() {
        const thumbnailInput = document.getElementById('thumbnail-input');
        const thumbnailPreview = document.getElementById('thumbnail-preview');
        
        thumbnailInput.value = '';
        thumbnailPreview.style.display = 'none';
        layui.layer.msg('缩略图已删除', {icon: 0, time: 1000});
    });
    
    // 初始化缩略图预览
    window.addEventListener('load', function() {
        const thumbnailInput = document.getElementById('thumbnail-input');
        const thumbnailPreview = document.getElementById('thumbnail-preview');
        const thumbnailImage = document.getElementById('thumbnail-image');
        
        if (thumbnailInput && thumbnailInput.value) {
            // Use full URL for image src
            thumbnailImage.src = '<?php echo UPLOAD_URL; ?>/' + thumbnailInput.value.replace('uploads/', '');
            thumbnailPreview.style.display = 'block';
        }
    });
    
    // 插入图片
    function insertImage() {
        // 显示图片上传对话框
        layui.layer.open({
            type: 1,
            title: '上传图片',
            area: ['500px', '300px'],
            content: `
                <div style="padding: 20px;">
                    <div class="layui-upload-drag" id="contentImageUpload" style="margin-bottom: 15px;">
                        <i class="layui-icon layui-icon-upload"></i>
                        <div>点击上传图片，或将图片拖拽到此处</div>
                        <div class="layui-word-aux">支持 JPG, PNG, GIF, WebP, BMP, TIFF 格式，大小不超过 10MB</div>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" class="layui-btn layui-btn-normal" id="insertUploadedImage" style="display: none;">插入图片</button>
                        <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>
                    </div>
                    <input type="hidden" id="uploadedImageUrl" value="">
                </div>
            `,
            success: function(layero, index) {
                layui.use(['upload'], function() {
                    var upload = layui.upload;
                    
                    // 初始化图片上传
                    upload.render({
                        elem: '#contentImageUpload',
                        url: 'upload.php',
                        accept: 'images',
                        // 支持更多图片格式
                        exts: 'jpg|jpeg|png|gif|webp|bmp|tiff|tif',
                        size: 10240, // 10MB
                        done: function(res){
                            if(res.success && res.location){
                                // 保存上传的图片URL
                                document.getElementById('uploadedImageUrl').value = res.location;
                                
                                // 显示插入按钮
                                document.getElementById('insertUploadedImage').style.display = 'inline-block';
                                
                                layui.layer.msg('图片上传成功！', {icon: 1});
                            } else {
                                const errorMsg = res.error || res.message || '未知错误';
                                layui.layer.msg('上传失败：' + errorMsg, {icon: 2});
                            }
                        },
                        error: function(){
                            layui.layer.msg('上传失败，请稍后重试', {icon: 2});
                        }
                    });
                    
                    // 插入图片按钮事件
                    document.getElementById('insertUploadedImage').onclick = function() {
                        var imageUrl = document.getElementById('uploadedImageUrl').value;
                        if(imageUrl) {
                            // 插入图片HTML，添加更好的样式支持
                            // 处理可能包含查询参数的URL
                            var imgHtml = '<img src="' + imageUrl + '" style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: block; margin: 10px auto;">';
                            document.execCommand('insertHTML', false, imgHtml);
                            
                            // 关闭对话框
                            layui.layer.closeAll();
                        } else {
                            layui.layer.msg('请先上传图片文件', {icon: 2});
                        }
                    };
                });
            }
        });
    }
    
    // 插入视频
    function insertVideo() {
        // 显示视频上传对话框
        layui.layer.open({
            type: 1,
            title: '上传视频',
            area: ['500px', '300px'],
            content: `
                <div style="padding: 20px;">
                    <div class="layui-upload-drag" id="contentVideoUpload" style="margin-bottom: 15px;">
                        <i class="layui-icon layui-icon-upload"></i>
                        <div>点击上传视频，或将视频拖拽到此处</div>
                        <div class="layui-word-aux">支持 MP4, WebM, OGG, AVI, MOV, WMV, FLV, MKV 格式，大小不超过 100MB</div>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" class="layui-btn layui-btn-normal" id="insertUploadedVideo" style="display: none;">插入视频</button>
                        <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>
                    </div>
                    <input type="hidden" id="uploadedVideoUrl" value="">
                </div>
            `,
            success: function(layero, index) {
                layui.use(['upload'], function() {
                    var upload = layui.upload;
                    
                    // 初始化视频上传
                    upload.render({
                        elem: '#contentVideoUpload',
                        url: 'upload.php',
                        accept: 'video',
                        exts: 'mp4|webm|ogg|avi|mov|wmv|flv|mkv',
                        data: {type: 'video'},
                        size: 102400, // 100MB
                        done: function(res){
                            if(res.success && res.location){
                                // 保存上传的视频URL
                                document.getElementById('uploadedVideoUrl').value = res.location;
                                
                                // 显示插入按钮
                                document.getElementById('insertUploadedVideo').style.display = 'inline-block';
                                
                                layui.layer.msg('视频上传成功！', {icon: 1});
                            } else {
                                layui.layer.msg('上传失败：' + (res.message || res.error || '未知错误'), {icon: 2});
                            }
                        },
                        error: function(){
                            layui.layer.msg('上传失败，请稍后重试', {icon: 2});
                        }
                    });
                    
                    // 插入视频按钮事件
                    document.getElementById('insertUploadedVideo').onclick = function() {
                        var videoUrl = document.getElementById('uploadedVideoUrl').value;
                        if(videoUrl) {
                            // 插入视频播放器HTML，添加支持缩略图功能的div包装器
                            var videoHtml = `
                                <div class="video-container" style="position: relative; max-width: 100%; margin: 10px 0; text-align: center;">
                                    <video controls preload="metadata" style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <source src="${videoUrl}" type="video/mp4">
                                        您的浏览器不支持HTML5视频播放。
                                    </video>
                                </div>
                            `;
                             
                            document.execCommand('insertHTML', false, videoHtml);
                             
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
                             
                            // 关闭对话框
                            layui.layer.closeAll();
                        } else {
                            layui.layer.msg('请先上传视频文件', {icon: 2});
                        }
                    };
                });
            }
        });
    }
    
    // 预览内容
    function previewContent() {
        const content = customEditor.innerHTML;
        if (!content.trim() || content === '<p><br></p>') {
            layui.layer.msg('请先输入内容', {icon: 0});
            return;
        }
        
        layui.use('layer', function(){
            layui.layer.open({
                type: 1,
                title: '📋 内容预览',
                area: ['80%', '70%'],
                content: `
                    <div style="padding: 20px; max-height: 500px; overflow-y: auto; font-family: 'Microsoft YaHei', sans-serif; line-height: 1.6;">
                        ${content}
                    </div>
                `,
                btn: ['关闭预览'],
                yes: function(index) {
                    layui.layer.close(index);
                }
            });
        });
    }
    </script>
</body>
</html>