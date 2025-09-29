<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/ai_service.php';

check_admin_auth();

// 初始化AI服务
$ai_service = new AIService();

// 获取内容ID
$content_id = intval($_GET['id'] ?? 0);

if (empty($content_id)) {
    header("Location: index.php?error=" . urlencode('无效的内容ID'));
    exit();
}

// 获取所有栏目用于下拉选择
try {
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
    error_log("获取栏目列表失败: " . $e->getMessage());
}

// 获取当前内容信息
try {
    $stmt = $db->prepare("SELECT * FROM contents WHERE id = ?");
    $stmt->execute([$content_id]);
    $content = $stmt->fetch();
    
    if (!$content) {
        header("Location: index.php?error=" . urlencode('内容不存在'));
        exit();
    }
} catch(PDOException $e) {
    header("Location: index.php?error=" . urlencode('获取内容信息失败'));
    exit();
}

// 检查并创建上传目录
$upload_dir = '../../uploads/';
$video_dir = $upload_dir . 'videos/';
$thumbnail_dir = $upload_dir . 'images/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if (!is_dir($video_dir)) {
    mkdir($video_dir, 0777, true);
}
if (!is_dir($thumbnail_dir)) {
    mkdir($thumbnail_dir, 0777, true);
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content_text = $_POST['content'] ?? '';
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    // 从POST请求中获取新上传的缩略图路径，而不是使用原始的$content['thumbnail']
    $thumbnail = trim($_POST['thumbnail'] ?? '');
    
    // 如果没有提供slug，则根据标题自动生成
    if (empty($slug) && !empty($title)) {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $slug = trim($slug, '-');
    }
    
    // 验证必填字段
    if (empty($category_id) || empty($title)) {
        $error_message = '栏目和标题为必填项';
    } else {
        try {
            // 更新内容
            $stmt = $db->prepare("UPDATE contents SET category_id = ?, title = ?, content = ?, is_published = ?, sort_order = ?, slug = ?, summary = ?, tags = ?, seo_title = ?, seo_keywords = ?, seo_description = ?, thumbnail = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$category_id, $title, $content_text, $is_published, $sort_order, $slug, $summary, $tags, $seo_title, $seo_keywords, $seo_description, $thumbnail, $content_id]);
            
            // 重定向到内容列表页面
            header("Location: index.php?message=" . urlencode('内容更新成功'));
            exit();
        } catch(PDOException $e) {
            $error_message = '更新内容失败: ' . $e->getMessage();
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
    <title>编辑内容 - 移动管理后台</title>
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
                <h1>编辑内容</h1>
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
                <h1>编辑内容</h1>
                <p>修改内容项目信息</p>
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
                            <option value="<?php echo $category['id']; ?>" <?php echo ($content['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>标题 *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($content['title'] ?? ''); ?>" required>
                    <div class="form-group">
                        <label>URL别名</label>
                        <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($content['slug'] ?? ''); ?>" placeholder="自动根据标题生成，用于URL显示">
                    </div>
                    
                    <div class="form-group">
                        <label>摘要</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="简短描述内容，用于列表展示"><?php echo htmlspecialchars($content['summary'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>缩略图</label>
                        <div class="thumbnail-upload">
                            <?php if (!empty($content['thumbnail'])): ?>
                            <div class="thumbnail-preview" id="thumbnail-preview">
                                <img src="<?php echo htmlspecialchars($content['thumbnail']); ?>" alt="缩略图预览" style="max-width: 150px; max-height: 150px;">
                                <button type="button" class="thumbnail-delete" onclick="deleteThumbnail()">删除</button>
                            </div>
                            <?php endif; ?>
                            <div id="thumbnail-upload-area" style="<?php echo !empty($content['thumbnail']) ? 'display: none;' : ''; ?>">
                                <button type="button" class="layui-btn layui-btn-primary" id="upload-thumbnail">上传缩略图</button>
                            </div>
                            <input type="hidden" name="thumbnail" id="thumbnail-input" value="<?php echo htmlspecialchars($content['thumbnail'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>标签</label>
                        <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($content['tags'] ?? ''); ?>" placeholder="用逗号分隔多个标签">
                    </div>
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
                                <?php echo $content['content'] ?? '<p>开始编写您的内容...</p>'; ?>
                            </div>
                        </div>
                        <textarea name="content" id="content-input" style="display: none;"><?php echo htmlspecialchars($content['content'] ?? ''); ?></textarea>
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
                            <input type="text" name="seo_title" class="form-control" value="<?php echo htmlspecialchars($content['seo_title'] ?? ''); ?>" placeholder="搜索引擎显示的标题，为空则使用内容标题">
                        </div>
                        
                        <div class="form-group">
                            <label>SEO关键词</label>
                            <input type="text" name="seo_keywords" class="form-control" value="<?php echo htmlspecialchars($content['seo_keywords'] ?? ''); ?>" placeholder="用逗号分隔多个关键词">
                        </div>
                        
                        <div class="form-group">
                            <label>SEO描述</label>
                            <textarea name="seo_description" class="form-control" rows="3" placeholder="搜索引擎显示的描述内容"><?php echo htmlspecialchars($content['seo_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo intval($content['sort_order'] ?? 0); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_published" value="1" <?php echo ($content['is_published'] ? 'checked' : ''); ?>>
                            发布内容
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 更新内容
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
    
    // 添加查看源码功能
    window.viewSource = function() {
        // 获取编辑器中的内容
        let content = '';
        if (window.customEditor) {
            content = window.customEditor.innerHTML;
        } else if (window.contentInput) {
            content = window.contentInput.value;
        }
        
        // 转义HTML特殊字符以正确显示源码
        const escapedContent = content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        
        // 创建一个新的layer弹窗显示源码
        layui.layer.open({
            title: '查看源码',
            type: 1,
            area: ['80%', '80%'],
            content: '<div style="padding: 20px;"><pre style="white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 13px; line-height: 1.5;">' + escapedContent + '</pre></div>',
            btn: ['关闭'],
            success: function(layero) {
                // 可以在这里添加额外的处理
            }
        });
    };
    
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
                fetch('../simple_test.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
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
                    fetch('../simple_test.php', {
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
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'generate_seo',
                            title: title,
                            content: content,
                            summary: summary
                        })
                    })
                    .then(response => {
                        // 添加详细日志
                        console.log('Response status:', response.status);
                        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                        
                        if (!response.ok) {
                            throw new Error('网络响应错误: ' + response.status);
                        }
                        
                        return response.text();
                    })
                    .then(text => {
                        console.log('Raw response text:', text);
                        
                        if (!text.trim()) {
                            console.error('Empty response from server');
                            layui.layer.closeAll();
                            layui.layer.msg('空响应，服务器未返回数据', {icon: 2});
                            return;
                        }
                        
                        try {
                            const result = JSON.parse(text);
                            console.log('Parsed response data:', result);
                            
                            layui.layer.closeAll();
                            if (result.success) {
                                if (result.seo_title) document.querySelector('input[name="seo_title"]').value = result.seo_title;
                                if (result.seo_keywords) document.querySelector('input[name="seo_keywords"]').value = result.seo_keywords;
                                if (result.seo_description) document.querySelector('textarea[name="seo_description"]').value = result.seo_description;
                                layui.layer.msg('SEO信息生成成功！', {icon: 1});
                            } else {
                                console.error('Server error:', result.error || 'Unknown error');
                                layui.layer.msg('生成失败：' + (result.error || '未知错误'), {icon: 2});
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            layui.layer.closeAll();
                            layui.layer.msg('响应解析错误: ' + e.message + '\n原始响应: ' + text, {icon: 2});
                        }
                    })
                    .catch(error => {
                        console.error('AI SEO生成请求失败:', error);
                        layui.layer.closeAll();
                        layui.layer.msg('请求失败: ' + error.message, {icon: 2});
                    });
                });
        }
    });
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
    
    // 缩略图上传功能
    layui.use(['upload', 'layer'], function() {
        const upload = layui.upload;
        const layer = layui.layer;
        
        // 缩略图上传配置
        upload.render({
            elem: '#upload-thumbnail',
            url: '../../api/upload_thumbnail.php',
            field: 'thumbnail', // 显式设置上传的文件字段名
            accept: 'images',
            acceptMime: 'image/*',
            size: 5120, // 最大5MB
            before: function(obj) {
                layer.load(); // 上传loading
            },
            done: function(res) {
                layer.closeAll('loading'); // 关闭loading
                if (res.success) {
                    // 上传成功
                    const thumbnailInput = document.getElementById('thumbnail-input');
                    const thumbnailPreview = document.getElementById('thumbnail-preview');
                    const thumbnailUploadArea = document.getElementById('thumbnail-upload-area');
                    
                    // 设置缩略图路径
                    thumbnailInput.value = res.file_path;
                    
                    // 更新预览
                    if (!thumbnailPreview) {
                        const previewDiv = document.createElement('div');
                        previewDiv.id = 'thumbnail-preview';
                        previewDiv.className = 'thumbnail-preview';
                        document.querySelector('.thumbnail-upload').appendChild(previewDiv);
                    }
                    
                    document.getElementById('thumbnail-preview').innerHTML = `
                                <img src="${res.file_path}" alt="缩略图预览" style="max-width: 150px; max-height: 150px;">
                                <button type="button" class="thumbnail-delete" onclick="deleteThumbnail()">删除</button>
                            `;
                    
                    // 隐藏上传按钮
                    thumbnailUploadArea.style.display = 'none';
                    
                    layer.msg('上传成功！', {icon: 1});
                } else {
                    layer.msg(res.error || '上传失败', {icon: 2});
                }
            },
            error: function() {
                layer.closeAll('loading');
                layer.msg('上传异常，请重试', {icon: 2});
            }
        });
    });
    
    // 删除缩略图功能
    window.deleteThumbnail = function() {
        const thumbnailInput = document.getElementById('thumbnail-input');
        const thumbnailPreview = document.getElementById('thumbnail-preview');
        const thumbnailUploadArea = document.getElementById('thumbnail-upload-area');
        
        // 清空缩略图路径
        thumbnailInput.value = '';
        
        // 隐藏预览，显示上传按钮
        if (thumbnailPreview) {
            thumbnailPreview.remove();
        }
        thumbnailUploadArea.style.display = 'block';
    };
    
    // 表单提交前的处理
    document.querySelector('form').addEventListener('submit', function() {
        // 确保编辑器内容同步到隐藏的textarea
        if (window.customEditor && window.contentInput) {
            window.contentInput.value = window.customEditor.innerHTML;
        }
    });
</script>