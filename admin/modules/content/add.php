<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/ai_service.php';

check_admin_auth();

$ai_service = new AIService();
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
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <link rel="stylesheet" href="../../assets/css/enhanced-editor.css">
    <script src="../../assets/js/admin-utils.js"></script>
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
        .ai-feature {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .ai-feature h4 {
            margin-top: 0;
            color: #e65100;
        }
        .ai-btn {
            background: linear-gradient(45deg, #ff9800, #f57c00);
            border: none;
        }
        .ai-btn:hover {
            background: linear-gradient(45deg, #f57c00, #ef6c00);
        }
        /* Button group styles */
        .layui-btn-group .layui-btn {
            margin-right: 2px;
        }
        .layui-btn-group .layui-btn:last-child {
            margin-right: 0;
        }
        
        /* 视频上传弹窗样式 - 与add.php保持一致 */
        .video-upload-header {
            margin-bottom: 24px;
            padding: 20px;
            background: linear-gradient(135deg, #409EFF 0%, #69b1ff 100%);
            border-radius: 12px;
            color: white;
            box-shadow: 0 4px 16px rgba(64, 158, 255, 0.25);
        }
        
        .upload-dropzone {
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
        }
        
        .upload-icon-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(64, 158, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .upload-btn {
            margin-top: 16px;
            padding: 0 24px;
            height: 40px;
            border-radius: 20px;
            font-size: 14px;
            border: 2px solid #dcdfe6;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .progress-container {
            display: none;
            margin-bottom: 24px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        
        .progress-wrapper {
            width: 100%;
            height: 8px;
            background: #ecf5ff;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #409EFF 0%, #69b1ff 100%);
            border-radius: 4px;
            transition: width 0.6s cubic-bezier(0.65, 0, 0.35, 1);
            position: relative;
        }
        
        .preview-container {
            display: none;
            margin-bottom: 24px;
        }
        
        .preview-list {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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
                        <h2>添加内容 <span class="custom-badge">✍️ 自定义编辑器</span></h2>
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
                        <h4>✍️ 自定义编辑器特性</h4>
                        <div class="layui-row layui-col-space15">
                            <div class="layui-col-md3">✅ 轻量级无依赖</div>
                            <div class="layui-col-md3">🚫 无外部API限制</div>
                            <div class="layui-col-md3">⚡ 快速加载</div>
                            <div class="layui-col-md3">🎨 简洁界面</div>
                        </div>
                        <div class="layui-row layui-col-space15" style="margin-top: 10px;">
                            <div class="layui-col-md3">📝 支持富文本编辑</div>
                            <div class="layui-col-md3">🖼️ 图片上传功能</div>
                            <div class="layui-col-md3">🎥 视频上传功能</div>
                            <div class="layui-col-md3">🔗 链接管理</div>
                        </div>
                    </div>
                    
                    <?php if ($ai_service->isConfigured()): ?>
                    <div class="ai-feature">
                        <h4>🤖 AI智能助手</h4>
                        <p>系统已集成AI功能，可帮助您自动生成内容、优化文章和填充SEO信息。</p>
                        <div class="layui-row layui-col-space10">
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-generate-content">
                                    <i class="layui-icon layui-icon-edit"></i> AI写作助手
                                </button>
                            </div>
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-generate-image">
                                    <i class="layui-icon layui-icon-picture"></i> AI图像生成
                                </button>
                            </div>
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-optimize-content">
                                    <i class="layui-icon layui-icon-rate"></i> AI内容优化
                                </button>
                            </div>
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-generate-seo">
                                    <i class="layui-icon layui-icon-chart"></i> AI SEO填充
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="layui-alert layui-alert-warm">
                        <h4>💡 AI功能提示</h4>
                        <p>系统支持AI功能，但尚未配置AI服务。请在配置文件中添加国内AI服务配置（豆包、DeepSeek或通义千问）以启用AI功能。</p>
                    </div>
                    <?php endif; ?>
                    
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
                                                   class="layui-input" required id="content-title">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">URL别名</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="slug" placeholder="留空自动生成" 
                                                   value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" 
                                                   class="layui-input">
                                            <div class="layui-form-mid layui-word-aux">用于URL链接，只能包含字母、数字和连字符</div>
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
                                                        <?php 
                                                        echo $category['parent_id'] > 0 ? '├─ ' : '';
                                                        echo htmlspecialchars($category['name']); 
                                                        ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">摘要</label>
                                        <div class="layui-input-block">
                                            <textarea name="summary" placeholder="请输入内容摘要" 
                                                      class="layui-textarea" rows="4" id="content-summary"><?php echo htmlspecialchars($_POST['summary'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <!-- 缩略图上传 -->
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">缩略图</label>
                                        <div class="layui-input-block">
                                            <div class="layui-upload">
                                                <button type="button" class="layui-btn" id="upload-thumbnail">
                                                    <i class="layui-icon"></i>上传缩略图
                                                </button>
                                                <div class="layui-upload-list" style="margin-top: 10px;">
                                                    <div id="thumbnail-preview" class="layui-upload-img" style="display: none; max-width: 200px; max-height: 150px;"></div>
                                                    <input type="hidden" name="thumbnail" id="thumbnail-input" value="">
                                                    <p id="thumbnail-text" style="display: none; color: #666;">缩略图已上传</p>
                                                    <button type="button" id="remove-thumbnail" class="layui-btn layui-btn-danger layui-btn-xs" style="display: none; margin-top: 5px;">
                                                        <i class="layui-icon"></i>删除
                                                    </button>
                                                </div>
                                                <p class="layui-word-aux" style="margin-top: 10px;">支持JPG、PNG、GIF格式，建议尺寸：300x200像素</p>
                                            </div>
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
                                        <label class="layui-form-label">排序</label>
                                        <div class="layui-input-inline">
                                            <input type="number" name="sort_order" placeholder="数字越大排序越靠前" 
                                                   value="<?php echo $_POST['sort_order'] ?? 0; ?>" 
                                                   class="layui-input">
                                        </div>
                                        <div class="layui-form-mid layui-word-aux">数字越大排序越靠前</div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <input type="checkbox" name="is_featured" value="1" 
                                                   <?php echo ($_POST['is_featured'] ?? 0) ? 'checked' : ''; ?> 
                                                   title="推荐到首页" lay-skin="primary">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
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
                                                        <!-- 字体选择 - 扩展为更多流行字体 -->
                                                        <div class="layui-inline" style="margin-right: 10px;">
                                                            <div class="layui-btn-group">
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'Microsoft YaHei, 微软雅黑')" title="微软雅黑">雅黑</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'SimSun, 宋体')" title="宋体">宋体</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'SimHei, 黑体')" title="黑体">黑体</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'KaiTi, 楷体')" title="楷体">楷体</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'FangSong, 仿宋')" title="仿宋">仿宋</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'PingFang SC, 苹方')" title="苹方">苹方</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'Arial')" title="Arial">Arial</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'Helvetica')" title="Helvetica">Helvetica</button>
                                                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('fontName', 'Verdana')" title="Verdana">Verdana</button>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- 基础格式 -->
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('bold')" title="粗体"><i class="layui-icon layui-icon-fonts-strong"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('italic')" title="斜体"><i class="layui-icon layui-icon-fonts-i"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('underline')" title="下划线"><i class="layui-icon layui-icon-fonts-u"></i></button>
                                                        
                                                        <!-- 标题选择 - 改为按钮组 -->
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
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('alignJustify')" style="margin-left: 0;" title="两端对齐">两端对齐</button>
                                                        
                                                        <!-- 插入功能 -->
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertLink()" style="margin-left: 10px;" title="插入链接"><i class="layui-icon layui-icon-link"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertImage()" style="margin-left: 5px;" title="插入图片"><i class="layui-icon layui-icon-picture"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertVideoEnhanced()" style="margin-left: 5px;" title="插入视频"><i class="layui-icon layui-icon-video"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.findReplace()" style="margin-left: 10px;" title="查找替换"><i class="layui-icon layui-icon-search"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertTable()" style="margin-left: 5px;" title="插入表格"><i class="layui-icon layui-icon-table"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.insertMedia()" style="margin-left: 5px;" title="插入媒体"><i class="layui-icon layui-icon-share"></i></button>
                                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.viewSource()" style="margin-left: 10px;" title="查看源码">查看源码</button>
                                                    </div>
                                                    <div id="custom-editor" class="editor-content" contenteditable="true" style="min-height: 400px; border: 1px solid #e6e6e6; padding: 15px; background: #fff; font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif; font-size: 14px; line-height: 1.6;">
                                                        <?php echo $_POST['content'] ?? '<p>开始编写您的内容...</p>'; ?>
                                                    </div>
                                                </div>
                                                <textarea name="content" id="content-input" style="display: none;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    

                                    
                                    <div class="layui-alert layui-alert-success">
                                        <h4>📝 自定义编辑器</h4>
                                        <ul style="margin: 10px 0; padding-left: 20px;">
                                            <li><strong>基础格式</strong>：使用工具栏按钮设置粗体、斜体、下划线等</li>
                                            <li><strong>标题设置</strong>：使用标题下拉菜单设置H1-H6标题</li>
                                            <li><strong>链接插入</strong>：点击链接按钮插入超链接</li>
                                            <li><strong>🖼️ 图片上传</strong>：点击图片按钮上传图片（支持 JPG, PNG, GIF, WebP，最大10MB）</li>
                                            <li><strong>🎥 视频上传</strong>：点击视频按钮上传视频文件（支持 MP4, WebM, AVI, MOV，最大100MB）</li>
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
                                                   class="layui-input" id="seo-title">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO关键词</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="seo_keywords" placeholder="多个关键词用逗号分隔" 
                                                   value="<?php echo htmlspecialchars($_POST['seo_keywords'] ?? ''); ?>" 
                                                   class="layui-input" id="seo-keywords">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO描述</label>
                                        <div class="layui-input-block">
                                            <textarea name="seo_description" placeholder="留空使用内容摘要" 
                                                      class="layui-textarea" rows="4" id="seo-description"><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal" lay-submit lay-filter="*">💾 保存内容</button>
                                <button type="button" class="layui-btn layui-btn-warm" onclick="selectVideoThumbnailFromEditor()">🎞️ 选取视频缩略图</button>
                                <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/notifications.js"></script>
    <script src="../../assets/js/enhanced-editor.js"></script>
<script src="../../assets/js/enhanced-image-uploader.js"></script>
    <script src="../../assets/js/chunked_video_upload.js"></script>
    <script>
    // 确认颜色选择 - 已移除颜色功能
    // 全局变量定义

    
    // 全局变量定义
    window.customEditor = null;
    window.contentInput = null;
    window.enhancedEditor = null;
    
    // 确保编辑器功能函数在全局作用域中可用
    window.formatText = window.formatText || function() {};
    window.insertLink = window.insertLink || function() {};
    window.insertImage = window.insertImage || function() {};
    window.insertVideo = window.insertVideo || function() {};
    window.findReplace = window.findReplace || function() {};
    window.insertTable = window.insertTable || function() {};
    window.insertMedia = window.insertMedia || function() {};
    window.importContent = window.importContent || function() {};
    window.exportContent = window.exportContent || function() {};
    
    layui.use(['form', 'element', 'layer', 'laydate', 'upload'], function(){            
        // notification已通过script标签加载并自动初始化
        // 绑定全局变量
        window.form = layui.form;
        window.element = layui.element;
        window.layer = layui.layer;
        window.laydate = layui.laydate;
        window.upload = layui.upload;
        
        window.form.render();
        window.element.render();
        
        // 初始化自定义编辑器
        try {
            window.customEditor = document.getElementById('custom-editor');
            window.contentInput = document.getElementById('content-input');
            
            if (window.customEditor && window.contentInput) {
                // 设置编辑器内容
                if (window.contentInput && window.contentInput.value && window.contentInput.value.trim() !== '') {
                    // 解码HTML实体
                    var decodedContent = window.contentInput.value
                        .replace(/&lt;/g, '<')
                        .replace(/&gt;/g, '>')
                        .replace(/&amp;/g, '&')
                        .replace(/&quot;/g, '"')
                        .replace(/&#039;/g, "'");
                    window.customEditor.innerHTML = decodedContent;
                } else {
                    window.customEditor.innerHTML = '<p>开始编写您的内容...</p>';
                }
                
                // 监听内容变化
                window.customEditor.addEventListener('input', function() {
                    window.contentInput.value = window.customEditor.innerHTML;
                });
                
                // 初始化增强编辑器
                if (typeof EnhancedEditor !== 'undefined') {
                    // 修复：传递DOM元素而不是字符串ID
                    window.enhancedEditor = new EnhancedEditor(window.customEditor, window.contentInput);
                    console.log('增强编辑器初始化成功');
                } else {
                    console.warn('增强编辑器类未定义，使用基础编辑器功能');
                }

                // 初始化增强图片上传器
                if (typeof EnhancedImageUploader !== 'undefined') {
                    window.imageUploader = new EnhancedImageUploader(window.customEditor);
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
        
        // 初始化缩略图上传
        var thumbnailInput = document.getElementById('thumbnail-input');
        var thumbnailPreview = document.getElementById('thumbnail-preview');
        var thumbnailText = document.getElementById('thumbnail-text');
        var removeThumbnail = document.getElementById('remove-thumbnail');
        var uploadThumbnailBtn = document.getElementById('upload-thumbnail');
        
        // 检查是否已有缩略图，如果有则显示预览
        if (thumbnailInput.value) {
            var imageUrl = thumbnailInput.value;
            
            // 更新预览图
            if (thumbnailPreview.querySelector('img')) {
                thumbnailPreview.querySelector('img').src = imageUrl;
            } else {
                var img = document.createElement('img');
                img.src = imageUrl;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                thumbnailPreview.innerHTML = ''; // 清空之前的内容
                thumbnailPreview.appendChild(img);
            }
            
            thumbnailPreview.style.display = 'block';
            thumbnailText.style.display = 'block';
            removeThumbnail.style.display = 'inline-block';
            uploadThumbnailBtn.style.display = 'none';
        }

        // 缩略图上传配置
        window.upload.render({
            elem: '#upload-thumbnail',
            url: 'upload.php',
            accept: 'images',
            acceptMime: 'image/*',
            exts: 'jpg|png|gif',
            size: 5120, // 5MB
            before: function(obj) {
                window.layer.msg('正在上传...', {icon: 16, time: 0});
            },
            done: function(res) {
                window.layer.closeAll();
                if (res.success && (res.location || res.thumbnail)) {
                    // 保存相对路径到表单字段
                    var imagePath = res.thumbnail || res.location;
                    thumbnailInput.value = imagePath;
                    
                    // 使用返回的完整URL用于预览，不再需要拼接
                    var imageUrl = imagePath;
                    
                    // 更新预览图
                    if (thumbnailPreview.querySelector('img')) {
                        thumbnailPreview.querySelector('img').src = imageUrl;
                    } else {
                        var img = document.createElement('img');
                        img.src = imageUrl;
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '100%';
                        thumbnailPreview.innerHTML = ''; // 清空之前的内容
                        thumbnailPreview.appendChild(img);
                    }
                    
                    thumbnailPreview.style.display = 'block';
                    thumbnailText.style.display = 'block';
                    removeThumbnail.style.display = 'inline-block';
                    uploadThumbnailBtn.style.display = 'none';
                    window.layer.msg('缩略图上传成功', {icon: 1});
                } else {
                    window.layer.msg(res.error || '上传失败', {icon: 2});
                }
            },
            error: function() {
                window.layer.msg('上传接口异常', {icon: 2});
            }
        });
        
        // 删除缩略图
        removeThumbnail.addEventListener('click', function() {
            // 清空缩略图路径
            thumbnailInput.value = '';
            
            // 隐藏预览图和相关元素
            thumbnailPreview.style.display = 'none';
            thumbnailText.style.display = 'none';
            removeThumbnail.style.display = 'none';
            uploadThumbnailBtn.style.display = 'inline-block';
            
            window.layer.msg('缩略图已删除', {icon: 1});
        });
    });
    
    // 从编辑器中选择视频并设置缩略图
    function selectVideoThumbnailFromEditor() {
        try {
            // 检查是否有编辑器
            if (!window.customEditor) {
                window.layer.msg('编辑器未初始化，请刷新页面重试', {icon: 2});
                return;
            }
            
            // 查找编辑器中的所有视频
            const videos = window.customEditor.querySelectorAll('video, .video-container video');
            
            if (videos.length === 0) {
                window.layer.msg('编辑器中未找到视频，请先插入视频', {icon: 2});
                return;
            }
            
            // 如果只有一个视频，直接使用它；如果有多个视频，让用户选择
            if (videos.length === 1) {
                // 直接使用唯一的视频
                selectFrameFromVideo(videos[0]);
            } else {
                // 有多个视频，让用户选择
                let videoOptions = '';
                videos.forEach((video, index) => {
                    // 获取视频信息用于显示
                    let videoSrc = video.src || (video.querySelector('source') ? video.querySelector('source').src : '未知视频');
                    let displayName = `视频 ${index + 1}`;
                    
                    // 尝试获取更有意义的名称
                    if (videoSrc) {
                        const urlParts = videoSrc.split('/');
                        const filename = urlParts[urlParts.length - 1];
                        if (filename) {
                            displayName = filename.length > 20 ? filename.substring(0, 20) + '...' : filename;
                        }
                    }
                    
                    videoOptions += `<option value="${index}">${displayName}</option>`;
                });
                
                // 显示选择对话框
                window.layer.open({
                    type: 1,
                    title: '选择要截取缩略图的视频',
                    area: ['400px', 'auto'],
                    content: `
                        <div style="padding: 20px;">
                            <div class="layui-form-item">
                                <label class="layui-form-label">选择视频</label>
                                <div class="layui-input-block">
                                    <select id="selected-video-index" class="layui-select">
                                        ${videoOptions}
                                    </select>
                                </div>
                            </div>
                            <div style="text-align: center; margin-top: 20px;">
                                <button type="button" class="layui-btn layui-btn-normal" onclick="confirmVideoSelection()">确定</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">取消</button>
                            </div>
                        </div>
                    `,
                    success: function(layero, index) {
                        // 初始化LayUI表单
                        layui.use(['form'], function() {
                            const form = layui.form;
                            form.render();
                        });
                    }
                });
            }
        } catch (error) {
            console.error('选择视频缩略图时出错:', error);
            window.layer.msg('选择视频缩略图失败，请重试', {icon: 2});
        }
    }
    
    // 确认选择的视频并截取缩略图
    function confirmVideoSelection() {
        try {
            const selectedIndex = document.getElementById('selected-video-index').value;
            const videos = window.customEditor.querySelectorAll('video, .video-container video');
            
            if (videos[selectedIndex]) {
                window.layer.closeAll();
                selectFrameFromVideo(videos[selectedIndex]);
            } else {
                window.layer.msg('无效的视频选择', {icon: 2});
            }
        } catch (error) {
            console.error('确认视频选择时出错:', error);
            window.layer.msg('操作失败，请重试', {icon: 2});
        }
    }
    
    // 从视频中选择帧作为缩略图
    function selectFrameFromVideo(video) {
        try {
            // 创建一个视频预览对话框
            window.layer.open({
                type: 1,
                title: '选取视频缩略图',
                area: ['600px', '500px'],
                content: `
                    <div style="padding: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <video id="thumbnail-video-preview" controls style="max-width: 100%; max-height: 300px;"></video>
                        </div>
                        <div style="text-align: center;">
                            <button type="button" class="layui-btn layui-btn-normal" onclick="captureVideoFrame()">📸 截取当前帧</button>
                        </div>
                    </div>
                `,
                success: function(layero, index) {
                    // 设置视频源
                    const videoPreview = document.getElementById('thumbnail-video-preview');
                    
                    // 获取原始视频源
                    let videoSrc = video.src || (video.querySelector('source') ? video.querySelector('source').src : '');
                    
                    if (!videoSrc) {
                        window.layer.msg('无法获取视频源', {icon: 2});
                        window.layer.close(index);
                        return;
                    }
                    
                    // 设置预览视频的源
                    videoPreview.src = videoSrc;
                }
            });
        } catch (error) {
            console.error('从视频截取帧时出错:', error);
            window.layer.msg('视频处理失败，请重试', {icon: 2});
        }
    }
    
    // 捕获视频当前帧并上传
    function captureVideoFrame() {
        try {
            const videoPreview = document.getElementById('thumbnail-video-preview');
            
            // 创建canvas元素
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // 设置canvas尺寸为视频尺寸
            canvas.width = videoPreview.videoWidth;
            canvas.height = videoPreview.videoHeight;
            
            // 在canvas上绘制当前视频帧
            ctx.drawImage(videoPreview, 0, 0, canvas.width, canvas.height);
            
            // 显示加载提示
            const loadingIndex = window.layer.msg('正在上传缩略图...', {icon: 16, time: 0});
            
            // 将canvas转换为Blob并上传
            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('thumbnail', blob, 'video_thumbnail_' + Date.now() + '.jpg');
                
                // 获取上传按钮元素
                const uploadThumbnailBtn = document.getElementById('upload-thumbnail');
                
                // 发送到服务器
            fetch('/admin/api/upload_thumbnail.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('网络响应错误: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                window.layer.close(loadingIndex);
                if (data.success && data.thumbnailUrl) {
                    // 更新表单中的缩略图
                    const thumbnailInput = document.getElementById('thumbnail-input');
                    const thumbnailPreview = document.getElementById('thumbnail-preview');
                    const thumbnailText = document.getElementById('thumbnail-text');
                    const removeThumbnail = document.getElementById('remove-thumbnail');
                      
                    // 保存相对路径到表单字段
                    var imagePath = data.thumbnailUrl;
                    thumbnailInput.value = imagePath;
                      
                    // 使用返回的完整URL用于预览
                    var imageUrl = imagePath;
                         
                        // 更新预览图
                        if (thumbnailPreview.querySelector('img')) {
                            thumbnailPreview.querySelector('img').src = imageUrl;
                        } else {
                            const img = document.createElement('img');
                            img.src = imageUrl;
                            img.style.maxWidth = '100%';
                            img.style.maxHeight = '100%';
                            thumbnailPreview.innerHTML = '';
                            thumbnailPreview.appendChild(img);
                        }
                        
                        thumbnailPreview.style.display = 'block';
                        thumbnailText.style.display = 'block';
                        removeThumbnail.style.display = 'inline-block';
                        uploadThumbnailBtn.style.display = 'none';
                        
                        // 关闭对话框
                        window.layer.closeAll();
                        window.layer.msg('缩略图设置成功', {icon: 1});
                    } else {
                        throw new Error(data.error || '上传失败');
                    }
                })
                .catch(error => {
                    window.layer.close(loadingIndex);
                    window.layer.msg('缩略图上传失败: ' + error.message, {icon: 2});
                });
            }, 'image/jpeg', 0.9);
        } catch (error) {
            console.error('捕获视频帧时出错:', error);
            window.layer.msg('捕获视频帧失败，请重试', {icon: 2});
        }
    }
    </script>
    
    <!-- AI智能助手功能 -->
    <script>
    // 等待DOM加载完成后执行AI功能初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 检查AI服务是否已配置
        const aiEnabled = <?php echo isset($ai_service) && $ai_service->isConfigured() ? 'true' : 'false'; ?>;
        
        // 如果AI服务未配置，显示提示信息
        if (!aiEnabled) {
            // 隐藏所有AI按钮
            document.querySelectorAll('#ai-generate-content, #ai-optimize-content, #ai-generate-seo, #ai-generate-image').forEach(btn => {
                btn.style.display = 'none';
            });
            return;
        }
        
        // AI内容生成
        const aiGenerateContentBtn = document.getElementById('ai-generate-content');
        if (aiGenerateContentBtn) {
            aiGenerateContentBtn.addEventListener('click', function() {
                const title = document.querySelector('input[name="title"]').value;
                if (!title) {
                    window.layer.msg('请先输入标题', {icon: 2});
                    return;
                }
                
                window.layer.prompt({
                    formType: 2,
                    title: 'AI写作助手',
                    value: '请根据标题"' + title + '"生成一段详细的文章内容',
                    area: ['500px', '200px']
                }, function(value, index, elem){
                    window.layer.close(index);
                    window.layer.msg('正在生成内容...', {icon: 16, time: 0});
                
                    // 发送AJAX请求到AI处理接口
                    fetch('ai_handler.php', {
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
                    .then(response => response.json())
                    .then(result => {
                        window.layer.closeAll();
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
                            window.layer.msg('内容生成成功！', {icon: 1});
                        } else {
                            window.layer.msg('生成失败：' + result.error, {icon: 2});
                        }
                    })
                    .catch(error => {
                        console.error('AI内容生成请求失败:', error);
                        window.layer.closeAll();
                        window.layer.msg('请求失败，请重试', {icon: 2});
                    });
                });
            });
        }
        
        // AI图像生成
        const aiGenerateImageBtn = document.getElementById('ai-generate-image');
        if (aiGenerateImageBtn) {
            aiGenerateImageBtn.addEventListener('click', function() {
                const title = document.querySelector('input[name="title"]').value;
                if (!title) {
                    window.layer.msg('请先输入标题', {icon: 2});
                    return;
                }
                
                window.layer.prompt({
                    formType: 2,
                    title: 'AI图像生成',
                    value: '与"' + title + '"相关的插图',
                    area: ['500px', '150px']
                }, function(value, index, elem){
                    window.layer.close(index);
                    window.layer.msg('正在生成图像...', {icon: 16, time: 0});
                
                    // 发送AJAX请求到AI处理接口
                    fetch('ai_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'generate_image',
                            prompt: value
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        window.layer.closeAll();
                        if (result.success && window.customEditor) {
                            // 插入生成的图片到编辑器
                            const imgHtml = '<img src="' + result.image_url + '" style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: block; margin: 10px auto;">';
                            document.execCommand('insertHTML', false, imgHtml);
                            window.layer.msg('图像生成成功！', {icon: 1});
                        } else {
                            window.layer.msg('生成失败：' + result.error, {icon: 2});
                        }
                    })
                    .catch(error => {
                        console.error('AI图像生成请求失败:', error);
                        window.layer.closeAll();
                        window.layer.msg('请求失败，请重试', {icon: 2});
                    });
                });
            });
        }
        
        // AI内容优化
        const aiOptimizeContentBtn = document.getElementById('ai-optimize-content');
        if (aiOptimizeContentBtn) {
            aiOptimizeContentBtn.addEventListener('click', function() {
                const title = document.querySelector('input[name="title"]').value;
                let content = '';
                if (window.customEditor) {
                    content = window.customEditor.innerHTML;
                }
                
                if (!content || content === '<p>开始编写您的内容...</p>') {
                    window.layer.msg('请先输入内容', {icon: 2});
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
                window.layer.open({
                    type: 1,
                    title: 'AI内容优化',
                    area: ['500px', '280px'],
                    content: formContent,
                    btn: ['确定优化', '取消'],
                    success: function(layero, index) {
                        // 初始化layui表单组件
                        layui.form.render('select');
                    },
                    yes: function(index, layero) {
                        window.layer.close(index);
                        window.layer.msg('正在优化内容...', {icon: 16, time: 0});
                    
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
                        .then(response => response.json())
                        .then(result => {
                            window.layer.closeAll();
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
                                window.layer.msg('内容优化成功！', {icon: 1});
                            } else {
                                window.layer.msg('优化失败：' + result.error, {icon: 2});
                            }
                        })
                        .catch(error => {
                            console.error('AI内容优化请求失败:', error);
                            window.layer.closeAll();
                            window.layer.msg('请求失败，请重试', {icon: 2});
                        });
                    }
                });
            });
        }
        
        // AI SEO填充
        const aiGenerateSeoBtn = document.getElementById('ai-generate-seo');
        if (aiGenerateSeoBtn) {
            aiGenerateSeoBtn.addEventListener('click', function() {
                const title = document.querySelector('input[name="title"]').value;
                let content = '';
                if (window.customEditor) {
                    content = window.customEditor.innerHTML;
                }
                const summary = document.querySelector('textarea[name="summary"]').value;
                
                if (!title) {
                    window.layer.msg('请先输入标题', {icon: 2});
                    return;
                }
                
                if ((!content || content === '<p>开始编写您的内容...</p>') && !summary) {
                    window.layer.msg('请先输入内容或摘要', {icon: 2});
                    return;
                }
                
                window.layer.confirm('确定要根据内容自动生成SEO信息吗？', {
                    icon: 3,
                    title: 'AI SEO填充'
                }, function(index) {
                    window.layer.close(index);
                    window.layer.msg('正在生成SEO信息...', {icon: 16, time: 0});
                
                    // 发送AJAX请求到AI处理接口
                    fetch('ai_handler.php', {
                        method: 'POST',
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
                    .then(response => response.json())
                    .then(result => {
                        window.layer.closeAll();
                        if (result.success) {
                            if (result.seo_title) document.querySelector('input[name="seo_title"]').value = result.seo_title;
                            if (result.seo_keywords) document.querySelector('input[name="seo_keywords"]').value = result.seo_keywords;
                            if (result.seo_description) document.querySelector('textarea[name="seo_description"]').value = result.seo_description;
                            window.layer.msg('SEO信息生成成功！', {icon: 1});
                        } else {
                            window.layer.msg('生成失败：' + result.error, {icon: 2});
                        }
                    })
                    .catch(error => {
                        console.error('AI SEO生成请求失败:', error);
                        window.layer.closeAll();
                        window.layer.msg('请求失败，请重试', {icon: 2});
                    });
                });
            });
        }
    });
    </script>
</body>
</html>
                                'mov': 'video/quicktime',
                                'mkv': 'video/x-matroska',
                                'flv': 'video/x-flv'
                            };
                            
                            if (mimeTypes[extension]) {
                                mimeType = mimeTypes[extension];
                            }
                            
                            // 获取编辑器元素并设置焦点
                            const customEditor = document.getElementById('custom-editor');
                            if (customEditor) {
                                customEditor.focus();
                            }
                            
                            // 插入视频HTML，添加div包装器以支持缩略图功能
                            const videoHtml = '<div class="video-container" style="position: relative; max-width: 100%; margin: 10px 0;"><video controls style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: block; margin: 0 auto;">' +
                                          '<source src="' + videoUrl + '" type="' + mimeType + '">' +
                                          '您的浏览器不支持视频播放。' +
                                          '</video></div>';
                             
                            // 执行插入操作
                            try {
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
                            } catch (e) {
                                console.error('插入视频失败:', e);
                                layui.layer.msg('插入视频失败，请重试', {icon: 2});
                                return;
                            }
                             
                            // 关闭对话框
                            layui.layer.closeAll();
                        } else {
                            layui.layer.msg('请先上传视频文件', {icon: 2});
                        }
                    });
                    
                    // 上传下一个分块
                    function uploadNextChunk() {
                        if (window.videoUploadState.uploadCanceled || window.videoUploadState.currentChunkIndex >= window.videoUploadState.totalChunks) {
                            return;
                        }
                        
                        // 计算当前分块的起始和结束位置
                        const start = window.videoUploadState.currentChunkIndex * window.videoUploadState.chunkSize;
                        const end = Math.min(start + window.videoUploadState.chunkSize, window.videoUploadState.selectedFile.size);
                        
                        // 读取分块数据
                        const chunk = window.videoUploadState.selectedFile.slice(start, end);
                        
                        // 创建FormData对象
                        const formData = new FormData();
                        formData.append('chunk', chunk);
                        formData.append('chunkIndex', window.videoUploadState.currentChunkIndex);
                        formData.append('totalChunks', window.videoUploadState.totalChunks);
                        formData.append('fileName', window.videoUploadState.selectedFile.name);
                        formData.append('fileHash', window.videoUploadState.fileHash);
                        
                        // 更新状态
                        updateStatus(`正在上传分块 ${window.videoUploadState.currentChunkIndex + 1}/${window.videoUploadState.totalChunks} (${formatBytes(end - start)})`);
                        
                        // 发送分块到服务器
                        fetch('/admin/api/final_chunked_upload_solution.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('网络响应错误: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                window.videoUploadState.currentChunkIndex++;
                                
                                // 更新进度
                                const progress = Math.min(Math.round((window.videoUploadState.currentChunkIndex / window.videoUploadState.totalChunks) * 100), 100);
                                updateProgress(progress);
                                
                                // 检查是否上传完成
                                if (data.partial) {
                                    // 继续上传下一个分块
                                    setTimeout(uploadNextChunk, 50); // 短暂延迟避免请求过于频繁
                                } else {
                                    // 上传完成
                                    uploadedVideoUrl = data.filePath; // 修复：确保变量在函数作用域内正确设置
                                
                                    // 保存上传的视频URL - 修复路径处理逻辑，移除重复域名
                                    let webPath = data.filePath;
                                
                                    // 1. 处理Windows路径格式（大小写不敏感）
                                    webPath = webPath.replace(/^[dD]:[\\/][pP][hH][pP][sS][tT][uU][dD][yY]_[pP][rR][oO][\\/][wW][wW][wW][\\/]/, '/');
                                
                                    // 2. 确保所有路径分隔符统一为正斜杠
                                    webPath = webPath.replace(/\\/g, '/');
                                
                                    // 3. 确保路径以正斜杠开头，符合Web URL标准
                                    if (!webPath.startsWith('/')) {
                                        webPath = '/' + webPath;
                                    }
                                
                                    // 4. 移除可能存在的重复斜杠
                                    webPath = webPath.replace(/\/+/g, '/');
                                
                                    // 5. 关键修复：移除URL中可能存在的重复域名部分
                                    // 例如：将 '/gaoguangshike.cn/uploads/...' 改为 '/uploads/...'
                                    webPath = webPath.replace(/^\/gaoguangshike\.cn\//i, '/');
                                
                                    // 修复：设置全局状态中的视频URL
                                    if (window.videoUploadState) {
                                        window.videoUploadState.uploadedVideoUrl = webPath;
                                    }
                                
                                    // 同时设置隐藏输入字段的值
                                    const urlInput = document.getElementById('uploadedVideoUrl');
                                    if (urlInput) {
                                        urlInput.value = webPath;
                                    }
                                
                                    document.getElementById('uploadedVideoUrl').value = webPath;
                                
                                    // 调试信息
                                    console.log('处理后的视频URL:', webPath);
                                
                                    // 显示插入按钮
                                    insertUploadedVideo.style.display = 'inline-block';
                                
                                    // 重置上传控件状态
                                    resetUploadControls();
                                
                                    // 关闭加载动画
                                    if (window.videoUploadState.layerIndex) {
                                        layui.layer.close(window.videoUploadState.layerIndex);
                                    }
                                }
                            } else {
                                throw new Error(data.error || '上传失败');
                            }
                        })
                        .catch(error => {
                            updateStatus('<span style="color: red;">上传失败: ' + error.message + '</span>');
                            resetUploadControls();
                            
                            // 关闭加载动画
                            if (window.videoUploadState.layerIndex) {
                                layui.layer.close(window.videoUploadState.layerIndex);
                            }
    
    </script>
    <!-- 编辑器功能修复 -->
    <script src="editor_fix.js"></script>
    <!-- 增强图片上传器 -->
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
                console.error('编辑器元素未找到，无法初始化增强图片上传器');
            }
        } catch (error) {
            console.error('增强图片上传器初始化失败:', error);
        }
    } else {
        console.warn('增强图片上传器未加载');
    }
    
    // 确保视频上传器已初始化
    if (typeof ensureVideoUploaderInitialized !== 'undefined') {
        ensureVideoUploaderInitialized();
    }
    
    // 插入增强版视频 - 使用现代化UI和分块上传
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
    
    // 确保视频上传器已初始化
    window.ensureVideoUploaderInitialized = function() {
        // 视频上传状态管理
        if (!window.videoUploadState) {
            window.videoUploadState = {
                selectedFile: null,
                fileHash: '',
                totalChunks: 0,
                chunkSize: 5 * 1024 * 1024, // 5MB
                currentChunkIndex: 0,
                uploadedVideoUrl: '', // 修复：添加uploadedVideoUrl到全局状态管理
                uploadCanceled: false,
                layerIndex: null
            };
        }
    };
    </script>
</body>
</html>