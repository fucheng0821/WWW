<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/ai_service.php';

check_admin_auth();

// 初始化AI服务
$ai_service = new AIService();

// 获取所有栏目用于下拉选择
try {
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
    error_log("获取栏目列表失败: " . $e->getMessage());
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    
    // 生成slug (URL友好的字符串)
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim(preg_replace('/[^\w\d\s]/u', '', $title))));
    $slug = rtrim($slug, '-');
    // 如果生成的slug为空，使用时间戳
    if (empty($slug)) {
        $slug = 'content-' . time();
    }
    
    // 获取SEO相关字段
    $summary = trim($_POST['summary'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    // 获取缩略图路径
    $thumbnail = trim($_POST['thumbnail_path'] ?? '');    
    // 验证必填字段
    if (empty($category_id) || empty($title)) {
        $error_message = '栏目和标题为必填项';
    } else {
        try {
                // 插入新内容 - 仅使用数据库中实际存在的字段
                $stmt = $db->prepare("INSERT INTO contents (category_id, title, content, is_published, sort_order, slug, summary, tags, seo_title, seo_keywords, seo_description, thumbnail, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$category_id, $title, $content, $is_published, $sort_order, $slug, $summary, $tags, $seo_title, $seo_keywords, $seo_description, $thumbnail]);
            
            // 重定向到内容列表页面
            header("Location: index.php?message=" . urlencode('内容添加成功'));
            exit();
        } catch(PDOException $e) {
            $error_message = '添加内容失败: ' . $e->getMessage();
            error_log($error_message);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>添加内容 - 移动管理后台</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/mobile-admin.css">
    <link rel="stylesheet" href="../../assets/css/mobile-modules.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 添加增强编辑器样式 -->
    <link rel="stylesheet" href="../../assets/css/enhanced-editor.css">
</head>
<body>
    <div class="mobile-layout">
        <!-- 顶部导航栏 -->
        <div class="mobile-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="header-title">
                <h1>添加内容</h1>
            </div>
            <div class="header-right">
                <button class="notification-btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationBadge" style="display: none;">0</span>
                </button>
            </div>
        </div>
        
        <!-- 侧边栏菜单 -->
        <div class="mobile-sidebar" id="mobileSidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '管理员'); ?></h3>
                        <p>在线</p>
                    </div>
                </div>
                <button class="close-sidebar" id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li class="menu-item">
                        <a href="../../index.php">
                            <i class="fas fa-home"></i>
                            <span>控制台</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../category/">
                            <i class="fas fa-folder"></i>
                            <span>栏目管理</span>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="../content/">
                            <i class="fas fa-file-alt"></i>
                            <span>内容管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../inquiry/">
                            <i class="fas fa-comment"></i>
                            <span>询价管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../template/">
                            <i class="fas fa-paint-brush"></i>
                            <span>模板管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../system/">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>安全退出</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- 遮罩层 -->
        <div class="overlay" id="overlay"></div>
        
        <!-- 主要内容区域 -->
        <div class="mobile-main">
            <div class="module-header">
                <h1>添加内容</h1>
                <p>创建新的内容项目</p>
            </div>
            
            <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>所属栏目 *</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">请选择栏目</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>标题 *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>URL别名</label>
                        <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" placeholder="自动根据标题生成，用于URL显示">
                    </div>
                    
                    <div class="form-group">
                        <label>摘要</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="简短描述内容，用于列表展示"><?php echo htmlspecialchars($_POST['summary'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>标签</label>
                        <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="用逗号分隔多个标签">
                    </div>
                    
                    <!-- 缩略图上传 -->
                    <div class="form-group">
                        <label>缩略图</label>
                        <div class="thumbnail-upload">
                            <div id="thumbnail-preview" class="thumbnail-preview" style="display: none;">
                                <img id="thumbnail-img" src="" alt="缩略图预览" style="max-width: 200px; max-height: 200px; border: 1px solid #e6e6e6; padding: 5px;">
                                <button type="button" class="layui-btn layui-btn-danger layui-btn-xs" onclick="removeThumbnail()" style="margin-top: 10px;">
                                    <i class="layui-icon layui-icon-delete"></i> 删除缩略图
                                </button>
                            </div>
                            <div id="thumbnail-upload" class="thumbnail-upload-area">
                                <button type="button" class="layui-btn layui-btn-primary" onclick="triggerThumbnailUpload()">
                                    <i class="layui-icon layui-icon-upload"></i> 上传缩略图
                                </button>
                                <input type="file" id="thumbnail-file" name="thumbnail" accept="image/*" style="display: none;">
                                <p class="help-text" style="margin-top: 5px; color: #666; font-size: 12px;">支持JPG、PNG、GIF格式，建议尺寸：200x200px</p>
                            </div>
                            <input type="hidden" id="thumbnail-path" name="thumbnail_path" value="<?php echo htmlspecialchars($_POST['thumbnail_path'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>内容</label>
                        <!-- 添加自定义编辑器 -->
                        <div class="custom-editor">
                            <div class="editor-toolbar">
                                <!-- 字体选择 -->
                                <div class="layui-inline" style="margin-right: 10px;">
                                    <div class="layui-btn-group">
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'Microsoft YaHei, 微软雅黑')" title="微软雅黑">雅黑</button>
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'SimSun, 宋体')" title="宋体">宋体</button>
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'SimHei, 黑体')" title="黑体">黑体</button>
                                    </div>
                                </div>
                                
                                <!-- 基础格式 -->
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('bold')" title="粗体"><i class="layui-icon layui-icon-fonts-strong"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('italic')" title="斜体"><i class="layui-icon layui-icon-fonts-i"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('underline')" title="下划线"><i class="layui-icon layui-icon-fonts-u"></i></button>
                                
                                <!-- 标题选择 -->
                                <div class="layui-inline" style="margin-left: 10px;">
                                    <div class="layui-btn-group">
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('formatBlock', 'p')" title="段落">P</button>
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('formatBlock', 'h1')" title="标题1">H1</button>
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('formatBlock', 'h2')" title="标题2">H2</button>
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('formatBlock', 'h3')" title="标题3">H3</button>
                                    </div>
                                </div>
                                
                                <!-- 对齐方式 -->
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('alignLeft')" style="margin-left: 10px;" title="左对齐"><i class="layui-icon layui-icon-align-left"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('alignCenter')" style="margin-left: 0;" title="居中对齐"><i class="layui-icon layui-icon-align-center"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('alignRight')" style="margin-left: 0;" title="右对齐"><i class="layui-icon layui-icon-align-right"></i></button>
                                
                                <!-- 插入功能 -->
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertLink()" style="margin-left: 10px;" title="插入链接"><i class="layui-icon layui-icon-link"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertImage()" style="margin-left: 5px;" title="插入图片"><i class="layui-icon layui-icon-picture"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertVideoEnhanced()" style="margin-left: 5px;" title="插入视频"><i class="layui-icon layui-icon-video"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertTable()" style="margin-left: 5px;" title="插入表格"><i class="layui-icon layui-icon-table"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.viewSource()" style="margin-left: 10px;" title="查看源码">查看源码</button>
                            </div>
                            <div id="custom-editor" class="editor-content" contenteditable="true" style="min-height: 300px; border: 1px solid #e6e6e6; padding: 15px; background: #fff; font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif; font-size: 14px; line-height: 1.6;">
                                <?php echo $_POST['content'] ?? '<p>开始编写您的内容...</p>'; ?>
                            </div>
                        </div>
                        <textarea name="content" id="content-input" style="display: none;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- AI助手功能 -->
                    <?php if ($ai_service->isConfigured()): ?>
                    <div class="ai-feature" style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 15px 0; border-radius: 4px;">
                        <h4 style="margin-top: 0; color: #e65100;">🤖 AI智能助手</h4>
                        <p>系统已集成AI功能，可帮助您自动生成内容、优化文章和填充SEO信息。</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                            <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" id="ai-generate-content" style="background: linear-gradient(45deg, #ff9800, #f57c00); border: none;">
                                <i class="layui-icon layui-icon-edit"></i> AI写作助手
                            </button>
                            <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" id="ai-optimize-content" style="background: linear-gradient(45deg, #ff9800, #f57c00); border: none;">
                                <i class="layui-icon layui-icon-rate"></i> AI内容优化
                            </button>
                            <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" id="ai-generate-seo" style="background: linear-gradient(45deg, #ff9800, #f57c00); border: none;">
                                <i class="layui-icon layui-icon-chart"></i> AI SEO填充
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="layui-alert layui-alert-warm" style="background-color: #fff3cd; border-color: #ffeaa7; color: #856404; padding: 15px; border-radius: 4px; margin: 15px 0;">
                        <h4>💡 AI功能提示</h4>
                        <p>系统支持AI功能，但尚未配置AI服务。请在配置文件中添加国内AI服务配置（豆包、DeepSeek或通义千问）以启用AI功能。</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- SEO设置 -->
                    <div class="form-group seo-settings" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;">
                        <h3 style="margin-top: 0; margin-bottom: 15px; color: #495057;">SEO设置</h3>
                        
                        <div class="form-group">
                            <label>SEO标题</label>
                            <input type="text" name="seo_title" class="form-control" value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>" placeholder="搜索引擎显示的标题，为空则使用内容标题">
                        </div>
                        
                        <div class="form-group">
                            <label>SEO关键词</label>
                            <input type="text" name="seo_keywords" class="form-control" value="<?php echo htmlspecialchars($_POST['seo_keywords'] ?? ''); ?>" placeholder="用逗号分隔多个关键词">
                        </div>
                        
                        <div class="form-group">
                            <label>SEO描述</label>
                            <textarea name="seo_description" class="form-control" rows="3" placeholder="搜索引擎显示的描述内容"><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo intval($_POST['sort_order'] ?? 0); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_published" value="1" <?php echo (isset($_POST['is_published']) && $_POST['is_published']) ? 'checked' : ''; ?>>
                            发布内容
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 保存内容
                        </button>
                        <a href="index.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/mobile-admin.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <!-- 添加增强编辑器和相关脚本 -->
    <script src="../../assets/js/enhanced-editor.js"></script>
    <script src="../../assets/js/enhanced-image-uploader.js"></script>
    <script src="../../assets/js/chunked_video_upload.js"></script>
    <script>
    // 初始化编辑器
    document.addEventListener('DOMContentLoaded', function() {
        try {
            window.customEditor = document.getElementById('custom-editor');
            window.contentInput = document.getElementById('content-input');
            
            if (window.customEditor && window.contentInput) {
                // 监听内容变化
                window.customEditor.addEventListener('input', function() {
                    window.contentInput.value = window.customEditor.innerHTML;
                });
                
                // 初始化增强编辑器
                if (typeof EnhancedEditor !== 'undefined') {
                    window.enhancedEditor = new EnhancedEditor(window.customEditor, window.contentInput);
                    console.log('增强编辑器初始化成功');
                } else {
                    console.warn('增强编辑器类未定义，使用基础编辑器功能');
                }

                // 初始化增强图片上传器
                if (typeof EnhancedImageUploader !== 'undefined') {
                    window.imageUploader = new EnhancedImageUploader(window.customEditor, window.contentInput);
                    // 修复：确保图片上传器可以通过全局函数访问
                    window.enhancedImageUploader = window.imageUploader;
                    console.log('增强图片上传器初始化成功');
                } else {
                    console.warn('增强图片上传器类未定义');
                }
                
                // 主动初始化编辑器
                window.customEditor.focus();
            } else {
                console.error('编辑器元素未找到');
            }
        } catch (e) {
            console.error('编辑器初始化错误:', e);
        }
    });
    
    // 查看源码功能
    window.viewSource = function() {
        try {
            // 获取编辑器内容
            let content = '';
            if (window.customEditor && window.customEditor.innerHTML) {
                content = window.customEditor.innerHTML;
            } else if (window.contentInput && window.contentInput.value) {
                content = window.contentInput.value;
            } else {
                content = '<p>开始编写您的内容...</p>';
            }
            
            // 转义HTML特殊字符，使其在textarea中正确显示
            const escapedContent = content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            
            // 创建查看源码弹窗
            layui.layer.open({
                type: 1,
                title: '查看源码',
                area: ['90%', '80%'],
                content: '<div style="padding: 15px;"><textarea id="source-code-view" style="width: 100%; height: 100%; min-height: 400px; font-family: monospace; font-size: 14px; line-height: 1.5; padding: 10px; border: 1px solid #e6e6e6;">' + escapedContent + '</textarea></div>',
                success: function(layero) {
                    // 使textarea可以滚动并自动选中
                    const textarea = layero.find('#source-code-view');
                    textarea.focus();
                    textarea.get(0).select();
                }
            });
        } catch (e) {
            console.error('查看源码功能错误:', e);
            layui.layer.msg('查看源码失败: ' + e.message, {icon: 2});
        }
    };
    
    // 缩略图上传相关函数
    function triggerThumbnailUpload() {
        document.getElementById('thumbnail-file').click();
    }
    
    // 处理缩略图文件选择
    document.getElementById('thumbnail-file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // 验证文件类型
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            layui.layer.msg('请上传JPG、PNG或GIF格式的图片', {icon: 2});
            return;
        }
        
        // 显示上传中提示
        layui.layer.msg('正在上传...', {icon: 16, time: 0});
        
        // 创建FormData对象
        const formData = new FormData();
        formData.append('thumbnail', file);
        formData.append('action', 'upload_thumbnail');
        
        // 发送AJAX请求上传图片 - 使用绝对路径
        fetch('/appadmin/api/upload_thumbnail.php', {
            method: 'POST',
            body: formData,
            credentials: 'include' // 确保包含cookie信息用于身份验证
        })
        .then(response => {
            console.log('上传请求响应状态:', response.status);
            
            if (!response.ok) {
                throw new Error(`网络响应错误，状态码: ${response.status}`);
            }
            return response.text().then(text => {
                console.log('原始响应:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('响应格式错误，不是有效的JSON: ' + text);
                }
            });
        })
        .then(result => {
            console.log('上传结果:', result);
            layui.layer.closeAll('loading');
            if (result.success) {
                // 更新预览和隐藏字段
                const thumbnailImg = document.getElementById('thumbnail-img');
                thumbnailImg.src = result.file_path;
                document.getElementById('thumbnail-path').value = result.file_path;
                
                // 显示预览，隐藏上传按钮
                document.getElementById('thumbnail-preview').style.display = 'block';
                document.getElementById('thumbnail-upload').style.display = 'none';
                
                layui.layer.msg('上传成功', {icon: 1});
            } else {
                layui.layer.msg('上传失败：' + (result.error || '未知错误'), {icon: 2});
            }
        })
        .catch(error => {
            console.error('缩略图上传错误:', error);
            layui.layer.closeAll('loading');
            layui.layer.msg('上传失败，请重试: ' + error.message, {icon: 2});
        });
    });
    
    // 删除缩略图
    function removeThumbnail() {
        layui.layer.confirm('确定要删除缩略图吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            layui.layer.close(index);
            
            // 清空预览和隐藏字段
            document.getElementById('thumbnail-img').src = '';
            document.getElementById('thumbnail-path').value = '';
            
            // 隐藏预览，显示上传按钮
            document.getElementById('thumbnail-preview').style.display = 'none';
            document.getElementById('thumbnail-upload').style.display = 'block';
            
            // 重置文件输入
            document.getElementById('thumbnail-file').value = '';
            
            layui.layer.msg('缩略图已删除', {icon: 1});
        });
    }
    
    // slug自动生成功能
    document.querySelector('input[name="title"]').addEventListener('blur', function() {
        if (!document.getElementById('slug').value) {
            const title = this.value;
            const slug = title
                .toLowerCase()
                .replace(/[^a-z0-9\u4e00-\u9fa5]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('slug').value = slug;
        }
    });
    
    // 初始化缩略图状态（如果有已上传的缩略图）
    document.addEventListener('DOMContentLoaded', function() {
        const thumbnailPath = document.getElementById('thumbnail-path').value;
        if (thumbnailPath) {
            // 直接使用返回的路径，不再添加相对路径前缀
            document.getElementById('thumbnail-img').src = thumbnailPath;
            document.getElementById('thumbnail-preview').style.display = 'block';
            document.getElementById('thumbnail-upload').style.display = 'none';
        }
    });
    
    // AI内容生成
    document.addEventListener('click', function(e) {
        if (e.target.id === 'ai-generate-content') {
            const title = document.querySelector('input[name="title"]').value;
            if (!title) {
                layui.layer.msg('请先输入标题', {icon: 2});
                return;
            }
            
            layui.layer.prompt({
                formType: 2,
                title: 'AI写作助手',
                value: '请根据标题"' + title + '"生成一段详细的文章内容',
                area: ['90%', '150px']
            }, function(value, index, elem){
                layui.layer.close(index);
                layui.layer.msg('正在生成内容...', {icon: 16, time: 0});
            
                // 发送AJAX请求到AI处理接口
                fetch('ai_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        action: 'generate_content',
                        prompt: value,
                        title: title
                    })
                })
                .then(response => {
                    // 检查响应状态
                    if (!response.ok) {
                        throw new Error('网络响应错误: ' + response.status);
                    }
                    return response.json();
                })
                .then(result => {
                    layui.layer.closeAll();
                    if (result.success) {
                        if (window.customEditor) {
                            window.customEditor.innerHTML = result.content;
                            if (window.contentInput) {
                                window.contentInput.value = result.content;
                            }
                            // 保存到历史记录，支持撤销操作
                            if (window.enhancedEditor) {
                                window.enhancedEditor.saveHistory();
                            }
                        }
                        layui.layer.msg('内容生成成功！', {icon: 1});
                    } else {
                        layui.layer.msg('生成失败：' + result.error, {icon: 2});
                        // 添加详细的错误日志
                        console.error('AI内容生成失败:', result);
                    }
                })
                .catch(error => {
                    console.error('AI内容生成请求失败:', error);
                    layui.layer.closeAll();
                    layui.layer.msg('请求失败，请重试: ' + error.message, {icon: 2});
                });
            });
        }
        
        // AI内容优化
        if (e.target.id === 'ai-optimize-content') {
            const title = document.querySelector('input[name="title"]').value;
            let content = '';
            if (window.customEditor) {
                content = window.customEditor.innerHTML;
            }
            
            if (!content || content === '<p>开始编写您的内容...</p>') {
                layui.layer.msg('请先输入内容', {icon: 2});
                return;
            }
            
            // 创建包含下拉选择的表单
            const formContent = '<div style="padding: 20px;">' +
                '<div class="layui-form-item">' +
                '<label class="layui-form-label">优化类型</label>' +
                '<div class="layui-input-block">' +
                '<select id="optimize-type" class="layui-select">' +
                '<option value="1">1. 优化emoji表情</option>' +
                '<option value="2">2. 优化排版</option>' +
                '<option value="3">3. 优化格式，遇到####或###换行并替换为<br>，删除#，两端对齐，保留数字</option>' +
                '<option value="4">4. 优化措辞</option>' +
                '</select>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            // 使用layer.open显示表单
            layui.layer.open({
                type: 1,
                title: 'AI内容优化',
                area: ['90%', '280px'],
                content: formContent,
                btn: ['确定优化', '取消'],
                success: function(layero, index) {
                    // 初始化layui表单组件
                    layui.form.render('select');
                },
                yes: function(index, layero) {
                    layui.layer.close(index);
                    layui.layer.msg('正在优化内容...', {icon: 16, time: 0});
                
                    // 获取用户选择的优化类型
                    const optimizeType = document.getElementById('optimize-type').value;
                
                    // 发送AJAX请求到AI处理接口
                    fetch('ai_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'optimize_content',
                            content: content,
                            title: title,
                            optimize_type: optimizeType
                        })
                    })
                    .then(response => {
                        // 检查响应状态
                        if (!response.ok) {
                            throw new Error('网络响应错误: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(result => {
                        layui.layer.closeAll();
                        if (result.success) {
                            // 使用更可靠的方式更新编辑器内容
                            if (window.enhancedEditor) {
                                // 如果EnhancedEditor实例存在，直接更新编辑器内容并同步
                                window.enhancedEditor.editor.innerHTML = result.content;
                                window.enhancedEditor.syncContent();
                                // 保存到历史记录，支持撤销操作
                                window.enhancedEditor.saveHistory();
                            } else if (window.customEditor) {
                                // 备用方案：直接更新编辑器内容
                                window.customEditor.innerHTML = result.content;
                                if (window.contentInput) {
                                    window.contentInput.value = result.content;
                                }
                                // 触发重新渲染
                                const temp = window.customEditor.style.display;
                                window.customEditor.style.display = 'none';
                                window.customEditor.offsetHeight; // 触发重排
                                window.customEditor.style.display = temp;
                            }
                            layui.layer.msg('内容优化成功！', {icon: 1});
                        } else {
                            layui.layer.msg('优化失败：' + result.error, {icon: 2});
                            // 添加详细的错误日志
                            console.error('AI内容优化失败:', result);
                        }
                    })
                    .catch(error => {
                        console.error('AI内容优化请求失败:', error);
                        layui.layer.closeAll();
                        layui.layer.msg('请求失败，请重试: ' + error.message, {icon: 2});
                    });
                }
            });
        }
        
        // AI SEO填充
        if (e.target.id === 'ai-generate-seo') {
            const title = document.querySelector('input[name="title"]').value;
            let content = '';
            if (window.customEditor) {
                content = window.customEditor.innerHTML;
            }
            const summary = document.querySelector('textarea[name="summary"]').value;
            
            if (!title) {
                layui.layer.msg('请先输入标题', {icon: 2});
                return;
            }
            
            if ((!content || content === '<p>开始编写您的内容...</p>') && !summary) {
                layui.layer.msg('请先输入内容或摘要', {icon: 2});
                return;
            }
            
            layui.layer.confirm('确定要根据内容自动生成SEO信息吗？', {
                    icon: 3,
                    title: 'AI SEO填充'
                }, function(index) {
                    layui.layer.close(index);
                    layui.layer.msg('正在生成SEO信息...', {icon: 16, time: 0});
                
                    // 发送AJAX请求到AI处理接口
                    fetch('ai_handler.php', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'generate_seo',
                            title: title,
                            content: content,
                            summary: summary
                        })
                    })
                    .then(response => {
                        // 检查响应状态
                        if (!response.ok) {
                            throw new Error('网络响应错误: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(result => {
                        layui.layer.closeAll();
                        if (result.success) {
                            if (result.seo_title) document.querySelector('input[name="seo_title"]').value = result.seo_title;
                            if (result.seo_keywords) document.querySelector('input[name="seo_keywords"]').value = result.seo_keywords;
                            if (result.seo_description) document.querySelector('textarea[name="seo_description"]').value = result.seo_description;
                            layui.layer.msg('SEO信息生成成功！', {icon: 1});
                        } else {
                            layui.layer.msg('生成失败：' + result.error, {icon: 2});
                            // 添加详细的错误日志
                            console.error('AI SEO生成失败:', result);
                        }
                    })
                    .catch(error => {
                        console.error('AI SEO生成请求失败:', error);
                        layui.layer.closeAll();
                        layui.layer.msg('请求失败，请重试: ' + error.message, {icon: 2});
                    });
                });
        }
    });
    </script>
</body>
</html>