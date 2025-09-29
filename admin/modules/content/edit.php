<?php
/**
 * 内容编辑页面
 * 用于编辑和更新网站内容
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 启用详细错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 会话初始化 - 检查会话状态后只启动一次
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录 - 使用与check_admin_auth函数一致的会话变量
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    header('Location: ../../login.php');
    exit;
}

// 引入配置文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/ai_service.php';

// 检查管理员权限
check_admin_auth();

// 创建AI服务实例
$ai_service = new AIService();

// 检查并创建uploads表（如果不存在）
try {
    $stmt = $db->query("SHOW TABLES LIKE 'uploads'");
    if ($stmt->rowCount() == 0) {
        $create_uploads_sql = "
        CREATE TABLE `uploads` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `filename` varchar(255) NOT NULL COMMENT '文件名',
          `original_name` varchar(255) NOT NULL COMMENT '原始文件名',
          `file_path` varchar(500) NOT NULL COMMENT '文件路径',
          `file_url` varchar(500) NOT NULL COMMENT '访问URL',
          `file_type` varchar(50) NOT NULL COMMENT '文件类型',
          `file_size` int(11) NOT NULL COMMENT '文件大小',
          `uploaded_by` int(11) NOT NULL COMMENT '上传者ID',
          `created_at` datetime NOT NULL COMMENT '上传时间',
          PRIMARY KEY (`id`),
          KEY `uploaded_by` (`uploaded_by`),
          KEY `file_type` (`file_type`),
          KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件上传记录表'";
        
        $db->exec($create_uploads_sql);
    }
} catch(Exception $e) {
    // 忽略表创建错误
}

$success = '';
$errors = [];
$categories = [];
$content_id = 0;

// 检查是否提供了内容ID
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id']) || intval($_GET['id']) <= 0) {
    header('Location: index.php');
    exit;
}

$content_id = intval($_GET['id']);

// 处理URL中的成功参数
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = '内容更新成功！';
}

// 获取栏目列表
try {
    $stmt = $db->prepare("SELECT id, name, parent_id FROM categories ORDER BY parent_id, sort_order");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errors[] = '获取栏目列表失败：' . $e->getMessage();
}

// 初始化表单数据
$title = $slug = $category_id = $summary = $content = $tags = '';
$sort_order = 0;
$is_featured = $is_published = 0;
$published_at = date('Y-m-d H:i:s');
$seo_title = $seo_keywords = $seo_description = $thumbnail = '';

// 获取现有内容数据
try {
    $stmt = $db->prepare("SELECT * FROM contents WHERE id = ?");
    $stmt->execute([$content_id]);
    $content_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$content_data) {
        $errors[] = '未找到指定的内容';
        header('Location: index.php');
        exit;
    }
    
    // 填充表单数据
    $title = $content_data['title'];
    $slug = $content_data['slug'];
    $category_id = $content_data['category_id'];
    $summary = $content_data['summary'];
    $content = $content_data['content'];
    $tags = $content_data['tags'];
    $sort_order = $content_data['sort_order'];
    $is_featured = $content_data['is_featured'];
    $is_published = $content_data['is_published'];
    $published_at = $content_data['published_at'];
    $seo_title = $content_data['seo_title'];
    $seo_keywords = $content_data['seo_keywords'];
    $seo_description = $content_data['seo_description'];
    $thumbnail = $content_data['thumbnail'];
} catch(PDOException $e) {
    $errors[] = '获取内容数据失败：' . $e->getMessage();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $content = $_POST['content'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $sort_order = $_POST['sort_order'] ?? 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $published_at = $_POST['published_at'] ?? date('Y-m-d H:i:s');
    $seo_title = $_POST['seo_title'] ?? '';
    $seo_keywords = $_POST['seo_keywords'] ?? '';
    $seo_description = $_POST['seo_description'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';

    // 验证必填项
    if (empty($title)) {
        $errors[] = '标题不能为空';
    }
    
    if (empty($category_id)) {
        $errors[] = '请选择所属栏目';
    }

    // 如果没有错误，则处理表单
    if (empty($errors)) {
        try {
            // 处理视频缩略图
            // 检查是否是视频缩略图标记
            if (strpos($thumbnail, '__VIDEO_THUMBNAIL__:') === 0) {
                // 提取视频URL
                $videoUrl = substr($thumbnail, strlen('__VIDEO_THUMBNAIL__:'));
                
                // 清理视频URL，确保它是正确的文件路径
                // 移除可能的查询参数
                $videoPath = parse_url($videoUrl, PHP_URL_PATH);
                
                // 如果是相对路径，转换为绝对路径
                if ($videoPath && substr($videoPath, 0, 1) === '/') {
                    $absoluteVideoPath = BASE_DIR . $videoPath;
                    
                    // 检查文件是否存在
                    if (file_exists($absoluteVideoPath)) {
                        // 尝试生成视频第10帧的缩略图
                        $thumbnailPath = '';
                        
                        // 检查GD库是否可用
                        if (extension_loaded('gd')) {
                            // 生成唯一的缩略图文件名
                            $thumbnailDir = BASE_DIR . '/uploads/images/';
                            $thumbnailFilename = 'video_thumb_' . time() . '_' . rand(1000, 9999) . '.jpg';
                            $thumbnailPath = $thumbnailDir . $thumbnailFilename;
                            
                            // 创建目录（如果不存在）
                            if (!is_dir($thumbnailDir)) {
                                mkdir($thumbnailDir, 0777, true);
                            }
                            
                            // 调用函数创建视频缩略图（第10帧）
                            if (function_exists('createVideoThumbnail')) {
                            if (createVideoThumbnail($absoluteVideoPath, $thumbnailPath, 300, 300, 10)) {
                                    // 更新缩略图路径为相对路径
                                    $thumbnail = '/uploads/images/' . $thumbnailFilename;
                                }
                            } else {
                                // 如果函数不存在，创建一个简单的视频占位符
                                $thumbnailImage = imagecreatetruecolor(300, 300);
                                $bgColor = imagecolorallocate($thumbnailImage, 50, 50, 50);
                                imagefill($thumbnailImage, 0, 0, $bgColor);
                                
                                // 设置文字颜色
                                $textColor = imagecolorallocate($thumbnailImage, 255, 255, 255);
                                
                                // 绘制播放按钮图标
                                $centerX = 150;
                                $centerY = 150;
                                $buttonSize = 60;
                                $points = [
                                    $centerX - $buttonSize/2, $centerY - $buttonSize/2,
                                    $centerX + $buttonSize/2, $centerY,
                                    $centerX - $buttonSize/2, $centerY + $buttonSize/2
                                ];
                                imagefilledpolygon($thumbnailImage, $points, 3, $textColor);
                                
                                // 添加文字
                                $text = "视频内容";
                                $fontSize = 4;
                                $textWidth = imagefontwidth($fontSize) * strlen($text);
                                $textX = (300 - $textWidth) / 2;
                                $textY = $centerY + $buttonSize + 20;
                                imagestring($thumbnailImage, $fontSize, $textX, $textY, $text, $textColor);
                                
                                // 保存缩略图
                                imagejpeg($thumbnailImage, $thumbnailPath, 85);
                                imagedestroy($thumbnailImage);
                                
                                // 更新缩略图路径
                                $thumbnail = '/uploads/images/' . $thumbnailFilename;
                            }
                        }
                    }
                }
            }
            
            // 开始事务
            $db->beginTransaction();
            
            // 准备更新语句
            $stmt = $db->prepare("UPDATE contents SET category_id = ?, title = ?, slug = ?, summary = ?, content = ?, tags = ?, sort_order = ?, is_featured = ?, is_published = ?, published_at = ?, seo_title = ?, seo_keywords = ?, seo_description = ?, thumbnail = ?, updated_at = NOW() WHERE id = ?");
            
            // 执行更新
            $stmt->execute([
                $category_id, $title, $slug, $summary, $content, $tags, 
                $sort_order, $is_featured, $is_published, $published_at,
                $seo_title, $seo_keywords, $seo_description, $thumbnail,
                $content_id
            ]);
            
            // 检查是否有行被更新
            if ($stmt->rowCount() === 0) {
                throw new Exception('没有内容被更新，请检查ID是否存在');
            }
            
            // 提交事务
            $db->commit();
            
            // 保存成功后重定向到当前页面，确保显示最新数据
            header('Location: edit.php?id=' . $content_id . '&success=1');
            exit;
        } catch(PDOException $e) {
            // 回滚事务
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = '更新失败：' . $e->getMessage();
            // 在调试模式下显示更多错误信息
            if (DEBUG_MODE) {
                $errors[] = '错误代码：' . $e->getCode();
                $errors[] = 'SQLSTATE：' . $e->errorInfo[0];
            }
        } catch(Exception $e) {
            // 回滚事务
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = '更新失败：' . $e->getMessage();
        }
    }
}?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑内容 - 高光视刻</title>
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
                        <h2>编辑内容 <span class="editor-badge">📝 自定义编辑器</span></h2>
                        <a href="index.php" class="layui-btn layui-btn-primary">
                            <i class="layui-icon layui-icon-return"></i> 返回列表
                        </a>
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
                    
                    <?php if ($ai_service->isConfigured()): ?>
                    <div class="ai-feature">
                        <h4>🤖 AI智能助手</h4>
                        <p>系统已集成AI功能，可帮助您优化内容和填充SEO信息。</p>
                        <div class="layui-row layui-col-space10">
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-generate-content">
                                    <i class="layui-icon layui-icon-edit"></i> AI写作助手
                                </button>
                            </div>
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-optimize-content">
                                    <i class="layui-icon layui-icon-rate"></i> AI内容优化✨
                                </button>
                            </div>
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-generate-seo">
                                    <i class="layui-icon layui-icon-chart"></i> AI SEO填充
                                </button>
                            </div>
                            <div class="layui-col-md3">
                                <button type="button" class="layui-btn ai-btn" id="ai-generate-image">
                                    <i class="layui-icon layui-icon-picture"></i> AI图像生成
                                </button>
                            </div>
                        </div>
                        <div class="layui-alert layui-alert-info" style="margin-top: 15px;">
                            <p><strong>💡 AI内容优化功能说明：</strong></p>
                            <ul style="margin: 5px 0; padding-left: 20px;">
                                <li>优化段落结构，提升文章美感和可读性</li>
                                <li>根据文章风格自动添加合适的emoji表情</li>
                                <li>增强语言表现力，使内容更生动有趣</li>
                                <li>保持专业性的同时增加文章的感染力</li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="layui-alert layui-alert-warm">
                        <h4>💡 AI功能提示</h4>
                        <p>系统支持AI功能，但尚未配置AI服务。请在配置文件中添加国内AI服务配置（豆包、DeepSeek或通义千问）以启用AI功能。</p>
                    </div>
                    <?php endif; ?>
                    
                    <form class="layui-form" method="POST">
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
                                                   value="<?php echo htmlspecialchars($title); ?>" 
                                                   class="layui-input" required>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">URL别名</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="slug" placeholder="留空自动生成" 
                                                   value="<?php echo htmlspecialchars($slug); ?>" 
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
                                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
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
                                                      class="layui-textarea" rows="4"><?php echo htmlspecialchars($summary); ?></textarea>
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
                                                    <input type="hidden" name="thumbnail" id="thumbnail-input" value="<?php echo htmlspecialchars($thumbnail); ?>">
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
                                                   value="<?php echo htmlspecialchars($tags); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">排序</label>
                                        <div class="layui-input-inline">
                                            <input type="number" name="sort_order" placeholder="数字越大排序越靠前" 
                                                   value="<?php echo $sort_order; ?>" 
                                                   class="layui-input">
                                        </div>
                                        <div class="layui-form-mid layui-word-aux">数字越大排序越靠前</div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">发布时间</label>
                                        <div class="layui-input-inline">
                                            <input type="text" name="published_at" id="published_at" 
                                                   value="<?php echo htmlspecialchars($published_at); ?>" 
                                                   class="layui-input" placeholder="yyyy-MM-dd HH:mm:ss">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <input type="checkbox" name="is_featured" value="1" 
                                                   <?php echo $is_featured ? 'checked' : ''; ?> 
                                                   title="推荐到首页" lay-skin="primary">
                                            <input type="checkbox" name="is_published" value="1" 
                                                   <?php echo $is_published ? 'checked' : ''; ?> 
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
                                                        <?php echo $content ?: '<p>开始编写您的内容...</p>'; ?>
                                                    </div>
                                                </div>
                                                <textarea name="content" id="content-input" style="display: none;"><?php echo htmlspecialchars($content); ?></textarea>
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
                                                   value="<?php echo htmlspecialchars($seo_title); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO关键词</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="seo_keywords" placeholder="多个关键词用逗号分隔" 
                                                   value="<?php echo htmlspecialchars($seo_keywords); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO描述</label>
                                        <div class="layui-input-block">
                                            <textarea name="seo_description" placeholder="留空使用内容摘要" 
                                                      class="layui-textarea" rows="4"><?php echo htmlspecialchars($seo_description); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script type="text/javascript">
                                // 颜色选择器相关代码已移除
                            </script>
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
    <script src="test-video-uploader.js"></script>
    <script>
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
            
            // 日期时间选择器
            window.laydate.render({
                elem: '#published_at',
                type: 'datetime'
            });
            
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
                        window.layer.msg('上传失败：' + (res.message || res.error || '未知错误'), {icon: 2});
                    }
                },
                error: function() {
                    window.layer.closeAll();
                    window.layer.msg('上传失败，请重试', {icon: 2});
                }
            });

            // 删除缩略图
            removeThumbnail.onclick = function() {
                thumbnailInput.value = '';
                thumbnailPreview.style.display = 'none';
                thumbnailText.style.display = 'none';
                removeThumbnail.style.display = 'none';
                uploadThumbnailBtn.style.display = 'inline-block';
            };
            
            // 表单提交前同步内容
            document.querySelector('form').addEventListener('submit', function(e) {
                // 强制同步编辑器内容到隐藏的textarea
                if (window.contentInput && window.customEditor) {
                    var content = window.customEditor.innerHTML;
                    // 确保内容不为空且不是默认内容
                    if (content && content !== '<p>开始编写您的内容...</p>') {
                        window.contentInput.value = content;
                    } else {
                        // 内容为空时也设置为空值
                        window.contentInput.value = '';
                    }
                }
                
                // 这里不阻止默认行为，因为LayUI的处理已经阻止了
            });
            
            // LayUI 表单提交事件监听
            window.form.on('submit(*)', function(data) {
                // 强制同步编辑器内容
                if (window.customEditor && window.contentInput) {
                    var content = window.customEditor.innerHTML;
                    // 确保内容不为空且不是默认内容
                    if (content && content !== '<p>开始编写您的内容...</p>') {
                        data.field.content = content;
                        window.contentInput.value = content;
                    } else {
                        // 内容为空时也设置为空值
                        data.field.content = '';
                        window.contentInput.value = '';
                    }
                } else {
                    console.warn('编辑器元素未找到，无法同步内容');
                }
                
                // 验证必填项
                if (!data.field.title || data.field.title.trim() === '') {
                    window.layer.msg('请输入标题', {icon: 2});
                    return false;
                }
                
                if (!data.field.category_id || data.field.category_id === '') {
                    window.layer.msg('请选择所属栏目', {icon: 2});
                    return false;
                }
                
                // 如果通过验证，显示加载提示
                window.layer.msg('正在保存...', {icon: 16, time: 0});
                
                // 返回true让表单正常提交
                return true;
            });
            
            // 加强内容同步，定时自动同步
            var syncInterval = setInterval(function() {
                try {
                    if (window.customEditor && window.contentInput) {
                        var content = window.customEditor.innerHTML;
                        if (content && content !== '<p>开始编写您的内容...</p>') {
                            window.contentInput.value = content;
                        }
                    }
                } catch (e) {
                    console.error('自动同步内容失败:', e);
                    clearInterval(syncInterval); // 出错时停止自动同步
                }
            }, 2000); // 每2秒自动同步一次
            
            // 自动生成别名
            var titleInput = document.querySelector('input[name="title"]');
            var slugInput = document.querySelector('input[name="slug"]');
            
            if (titleInput && slugInput) {
                titleInput.addEventListener('blur', function() {
                    if (!slugInput.value && this.value) {
                        var slug = this.value.toLowerCase()
                            .replace(/[^\w\s-]/g, '') 
                            .replace(/[\s_-]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                        
                        slugInput.value = slug;
                    }
                });
            }
            
            // 编辑器加载完成提示
            window.layer.msg('编辑器加载完成！', {icon: 1, time: 2000});
            
            // 确保所有编辑器功能函数都已正确绑定
            setTimeout(function() {
                const requiredFunctions = [
                    'formatText', 'insertLink', 'insertImage', 'insertVideo',
                    'findReplace', 'insertTable', 'insertMedia', 'importContent', 'exportContent'
                ];
                
                const missingFunctions = [];
                requiredFunctions.forEach(funcName => {
                    if (typeof window[funcName] !== 'function') {
                        missingFunctions.push(funcName);
                    }
                });
                
                if (missingFunctions.length === 0) {
                    console.log('所有编辑器功能函数已正确绑定');
                } else {
                    console.warn('部分编辑器功能函数未正确绑定:', missingFunctions);
                }
            }, 100);
        });
        
        // 编辑器功能函数 - 绑定到window对象以确保全局访问
        window.formatText = function(command, value) {
            try {
                // 如果增强编辑器可用，使用它的功能
                if (window.enhancedEditor && typeof window.enhancedEditor.formatText === 'function') {
                    window.enhancedEditor.formatText(command, value);
                    return;
                }
                
                // 否则使用基础功能
                if (!window.customEditor) {
                    console.error('编辑器未初始化');
                    if (window.layer) {
                        window.layer.msg('编辑器未初始化，请刷新页面重试', {icon: 2});
                    }
                    return;
                }
                
                if (command === 'formatBlock') {
                    document.execCommand(command, false, '<' + value + '>');
                } else {
                    document.execCommand(command, false, null);
                }
                window.customEditor.focus();
            } catch (e) {
                console.error('格式化文本时出错:', e);
                if (window.layer) {
                    window.layer.msg('格式化文本失败', {icon: 2});
                }
            }
        };
        
        window.insertLink = function() {
            try {
                // 如果增强编辑器可用，使用它的功能
                if (window.enhancedEditor && typeof window.enhancedEditor.insertLink === 'function') {
                    window.enhancedEditor.insertLink();
                    return;
                }
                
                // 否则使用基础功能
                if (!window.customEditor) {
                    console.error('编辑器未初始化');
                    if (window.layer) {
                        window.layer.msg('编辑器未初始化，请刷新页面重试', {icon: 2});
                    }
                    return;
                }
                
                window.layer.prompt({
                    formType: 0,
                    title: '请输入链接地址',
                    placeholder: 'https://example.com'
                }, function(value, index, elem){
                    if (value && window.customEditor) {
                        document.execCommand('createLink', false, value);
                    }
                    window.layer.close(index);
                    if (window.customEditor) {
                        window.customEditor.focus();
                    }
                });
            } catch (e) {
                console.error('插入链接时出错:', e);
                if (window.layer) {
                    window.layer.msg('插入链接失败', {icon: 2});
                }
            }
        };
        
        window.insertImage = function() {
            try {
                // 如果增强图片上传器可用，使用它的功能
                if (window.enhancedImageUploader && typeof window.enhancedImageUploader.showUploadDialog === 'function') {
                    window.enhancedImageUploader.showUploadDialog();
                    return;
                }
                // 如果旧版图片上传器可用，使用它的功能
                else if (window.imageUploader && typeof window.imageUploader.openDialog === 'function') {
                    window.imageUploader.openDialog();
                    return;
                }
                // 否则使用增强编辑器的功能
                else if (window.enhancedEditor && typeof window.enhancedEditor.insertImage === 'function') {
                    window.enhancedEditor.insertImage();
                    return;
                }
                
                // 否则使用基础功能
                if (!window.customEditor) {
                    console.error('编辑器未初始化');
                    if (window.layer) {
                        window.layer.msg('编辑器未初始化，请刷新页面重试', {icon: 2});
                    }
                    return;
                }
                
                // 显示图片上传对话框，包含上传模式选择器
                window.layer.open({
                    type: 1,
                    title: '上传图片',
                    area: ['600px', '450px'],
                    content: `
                        <div style="padding: 20px;">
                            <!-- 上传模式选择 -->
                            <div style="margin-bottom: 15px;">
                                <label for="upload-mode-select" style="margin-right: 10px;">上传模式：</label>
                                <select id="upload-mode-select" style="padding: 5px 10px; border-radius: 4px; border: 1px solid #e6e6e6;">
                                    <option value="single">单张上传</option>
                                    <option value="multiple">多张上传</option>
                                </select>
                            </div>
                            
                            <!-- 上传区域 -->
                            <div class="layui-upload-drag" id="contentImageUpload" style="margin-bottom: 15px;">
                                <i class="layui-icon layui-icon-upload"></i>
                                <div id="upload-mode-text">点击上传图片，或将图片拖拽到此处</div>
                                <div class="layui-word-aux">支持 JPG, PNG, GIF, WebP, BMP, TIFF 格式，大小不超过 10MB</div>
                            </div>
                            
                            <!-- 预览区域 -->
                            <div id="imagePreviewContainer" style="margin-bottom: 15px; display: none;">
                                <h4 style="margin-bottom: 10px;">上传预览：</h4>
                                <div id="imagePreviewList" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                            </div>
                            
                            <!-- 操作按钮 -->
                            <div style="text-align: center;">
                                <button type="button" class="layui-btn layui-btn-normal" id="insertUploadedImage" style="display: none;">插入图片</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="window.layer.closeAll()">取消</button>
                            </div>
                            
                            <!-- 隐藏元素 -->
                            <input type="file" id="imageFileInput" multiple="false" accept="image/*" style="display: none;">
                        </div>
                    `,
                    success: function(layero) {
                        // 获取元素
                        const dragArea = layero.find('#contentImageUpload')[0];
                        const fileInput = layero.find('#imageFileInput')[0];
                        const modeSelect = layero.find('#upload-mode-select')[0];
                        const modeText = layero.find('#upload-mode-text')[0];
                        const insertBtn = layero.find('#insertUploadedImage')[0];
                        const previewContainer = layero.find('#imagePreviewContainer')[0];
                        const previewList = layero.find('#imagePreviewList')[0];
                        const uploadedImages = [];
                        
                        // 模式切换事件
                        modeSelect.addEventListener('change', function(e) {
                            const uploadMode = e.target.value;
                            fileInput.multiple = uploadMode === 'multiple';
                            modeText.textContent = uploadMode === 'multiple' ? '点击上传多张图片，或将图片拖拽到此处' : '点击上传图片，或将图片拖拽到此处';
                            
                            // 清空预览
                            previewList.innerHTML = '';
                            uploadedImages.length = 0;
                            previewContainer.style.display = 'none';
                            insertBtn.style.display = 'none';
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
                        
                        // 处理拖拽上传
                        dragArea.addEventListener('drop', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            dragArea.style.borderColor = '#e6e6e6';
                            
                            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                handleFiles(e.dataTransfer.files);
                            }
                        });
                        
                        // 文件选择事件
                        fileInput.addEventListener('change', function(e) {
                            if (e.target.files.length > 0) {
                                handleFiles(e.target.files);
                            }
                        });
                        
                        // 处理文件上传
                        function handleFiles(files) {
                            const uploadMode = modeSelect.value;
                            const filesToUpload = uploadMode === 'multiple' ? [...files] : [files[0]];
                            
                            // 清空预览
                            previewList.innerHTML = '';
                            uploadedImages.length = 0;
                            previewContainer.style.display = 'none';
                            insertBtn.style.display = 'none';
                            
                            // 上传文件
                            filesToUpload.forEach(function(file, index) {
                                // 简单的文件验证
                                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff'];
                                const validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
                                const fileExtension = file.name.split('.').pop().toLowerCase();
                                
                                if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
                                    window.layer.msg('文件 ' + file.name + ' 不是有效的图片格式', {icon: 2});
                                    return;
                                }
                                
                                // 检查文件大小（10MB）
                                const maxSize = 10 * 1024 * 1024;
                                if (file.size > maxSize) {
                                    window.layer.msg('图片文件 ' + file.name + ' 大小不能超过 10MB', {icon: 2});
                                    return;
                                }
                                
                                // 显示上传中提示
                                const loadingIndex = window.layer.msg('正在上传图片 ' + (index + 1) + '/' + filesToUpload.length + '...', {icon: 16, time: 0});
                                
                                // 创建FormData并上传
                                const formData = new FormData();
                                formData.append('file', file);
                                formData.append('type', 'image');
                                
                                // 使用正确的上传路径
                                const uploadUrl = '/admin/modules/content/upload.php';
                                
                                // 使用fetch上传文件
                                fetch(uploadUrl, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(result => {
                                    window.layer.close(loadingIndex);
                                    if (result.success && result.location) {
                                        // 保存上传的图片URL
                                        const imageUrl = result.location;
                                        uploadedImages.push(imageUrl);
                                        
                                        // 添加预览
                                        addImagePreview(file, imageUrl);
                                        
                                        // 显示插入按钮和预览容器
                                        previewContainer.style.display = 'block';
                                        insertBtn.style.display = 'inline-block';
                                        
                                        if (index === filesToUpload.length - 1) {
                                            window.layer.msg('图片上传成功！', {icon: 1});
                                        }
                                    } else if (result.fileUrl) {
                                        // 兼容其他格式的响应
                                        const imageUrl = result.fileUrl;
                                        uploadedImages.push(imageUrl);
                                        
                                        // 添加预览
                                        addImagePreview(file, imageUrl);
                                        
                                        // 显示插入按钮和预览容器
                                        previewContainer.style.display = 'block';
                                        insertBtn.style.display = 'inline-block';
                                        
                                        if (index === filesToUpload.length - 1) {
                                            window.layer.msg('图片上传成功！', {icon: 1});
                                        }
                                    } else {
                                        const errorMsg = result.error || result.message || '未知错误';
                                        window.layer.msg('上传失败：' + errorMsg, {icon: 2});
                                    }
                                })
                                .catch(error => {
                                    window.layer.close(loadingIndex);
                                    window.layer.msg('上传失败，请稍后重试', {icon: 2});
                                    console.error('图片上传错误:', error);
                                });
                            });
                        }
                        
                        // 添加图片预览
                        function addImagePreview(file, imageUrl) {
                            // 创建预览元素
                            const previewItem = document.createElement('div');
                            previewItem.className = 'image-preview-item';
                            previewItem.style.position = 'relative';
                            previewItem.style.width = '100px';
                            previewItem.style.height = '100px';
                            previewItem.style.overflow = 'hidden';
                            previewItem.style.border = '1px solid #e6e6e6';
                            previewItem.style.borderRadius = '4px';
                            
                            // 创建图片元素
                            const img = document.createElement('img');
                            img.src = URL.createObjectURL(file);
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'cover';
                            
                            // 添加到预览列表
                            previewItem.appendChild(img);
                            previewList.appendChild(previewItem);
                        }
                        
                        // 插入图片按钮点击事件
                        insertBtn.addEventListener('click', function() {
                            if (uploadedImages.length > 0) {
                                // 确保编辑器有焦点
                                window.customEditor.focus();
                                
                                // 插入图片
                                uploadedImages.forEach((imageUrl, index) => {
                                    // 插入图片HTML
                                    const imgHtml = '<img src="' + imageUrl + '" style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: block; margin: 10px auto;">' + 
                                                  (modeSelect.value === 'multiple' && index < uploadedImages.length - 1 ? '<br><br>' : '');
                                    
                                    // 尝试使用execCommand插入
                                    if (document.execCommand) {
                                        document.execCommand('insertHTML', false, imgHtml);
                                    }
                                });
                                
                                window.layer.closeAll();
                                window.layer.msg(modeSelect.value === 'multiple' ? '多张图片插入成功' : '图片插入成功', {icon: 1});
                            } else {
                                window.layer.msg('请先上传图片', {icon: 2});
                            }
                        });

                    }
                });
            } catch (e) {
                console.error('插入图片时出错:', e);
                if (window.layer) {
                    window.layer.msg('插入图片失败', {icon: 2});
                }
            }
        };
        
        // 插入视频 - 优先使用现代化视频上传器
        window.insertVideoEnhanced = function() {
            try {
                // 优先使用现代化视频上传器（如果可用）
                if (window.videoUploader && typeof window.videoUploader.openUploadDialog === 'function') {
                    window.videoUploader.openUploadDialog();
                    return;
                }
                
                // 如果没有现代化上传器，使用基础实现
                // 创建一个临时的全局对象来跟踪上传状态
                window.videoUploadState = {
                    selectedFile: null,
                    chunkSize: 2 * 1024 * 1024, // 2MB分块大小
                    totalChunks: 0,
                    currentChunkIndex: 0,
                    uploadCanceled: false,
                    fileHash: '',
                    dialogIndex: null,
                    layerIndex: null,
                    uploadedVideoUrl: ''
                };
                
                // 打开上传对话框 - 使用现代化UI设计
                window.videoUploadState.dialogIndex = layui.layer.open({
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
                            <div id="contentVideoUpload" class="upload-dropzone" style="
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
                            <input type="file" id="fileInput" accept="video/*" style="display: none;">

                            <!-- 上传进度条 - 现代化设计 -->
                            <div id="videoUploadProgress" class="progress-container" style="
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
                                <button type="button" id="insertUploadedVideo" class="layui-btn" style="
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
                                <button type="button" id="pauseUpload" class="layui-btn layui-btn-warm" style="
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
                        const fileInput = document.getElementById('fileInput');
                        const contentVideoUpload = document.getElementById('contentVideoUpload');
                        const progressBar = document.getElementById('videoUploadProgress');
                        const pauseBtn = document.getElementById('pauseUpload');
                        const insertBtn = document.getElementById('insertUploadedVideo');
                        const videoPreviewContainer = document.getElementById('videoPreviewContainer');
                        const videoPreviewList = document.getElementById('videoPreviewList');
                        const uploadBtn = contentVideoUpload.querySelector('.upload-btn');
                        
                        // 创建隐藏的视频URL存储
                        let uploadedVideoUrl = '';
                        
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
                        
                        // 文件选择事件
                        contentVideoUpload.addEventListener('click', function() {
                            fileInput.click();
                        });
                        
                        // 文件拖拽事件
                        contentVideoUpload.addEventListener('dragover', function(e) {
                            e.preventDefault();
                            this.classList.add('layui-upload-drag-hover');
                        });
                        
                        contentVideoUpload.addEventListener('dragleave', function() {
                            this.classList.remove('layui-upload-drag-hover');
                        });
                        
                        contentVideoUpload.addEventListener('drop', function(e) {
                            e.preventDefault();
                            this.classList.remove('layui-upload-drag-hover');
                            
                            const files = e.dataTransfer.files;
                            if (files.length > 0) {
                                handleFileSelection(files[0]);
                            }
                        });
                        
                        // 处理文件选择
                        fileInput.addEventListener('change', function() {
                            if (this.files.length > 0) {
                                handleFileSelection(this.files[0]);
                            }
                        });
                        
                        // 处理文件选择
                        function handleFileSelection(file) {
                            // 检查文件类型
                            const validTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/quicktime', 'video/x-matroska', 'video/x-flv'];
                            const validExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
                            const fileExtension = file.name.split('.').pop().toLowerCase();
                            
                            if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
                                layui.layer.msg('请选择有效的视频文件，支持 MP4, WebM, OGG, AVI, MOV, WMV, FLV, MKV 格式', {icon: 2});
                                return;
                            }
                            
                            // 检查文件大小（200MB）
                            const maxSize = 200 * 1024 * 1024;
                            if (file.size > maxSize) {
                                layui.layer.msg('视频文件大小不能超过 200MB', {icon: 2});
                                return;
                            }
                            
                            // 更新上传状态
                            window.videoUploadState.selectedFile = file;
                            window.videoUploadState.totalChunks = Math.ceil(file.size / window.videoUploadState.chunkSize);
                            window.videoUploadState.currentChunkIndex = 0;
                            window.videoUploadState.uploadCanceled = false;
                            window.videoUploadState.fileHash = file.name + '_' + file.size + '_' + file.lastModified;
                            
                            // 更新UI
                            document.getElementById('uploadFileName').textContent = file.name;
                            document.getElementById('uploadFileSize').textContent = formatBytes(file.size);
                            
                            // 显示进度条和预览容器
                            progressBar.style.display = 'block';
                            videoPreviewContainer.style.display = 'block';
                            
                            // 创建预览
                            videoPreviewList.innerHTML = `
                                <div style="padding: 20px;">
                                    <div style="display: flex; align-items: center;">
                                        <div style="
                                            width: 80px; 
                                            height: 60px; 
                                            background: linear-gradient(135deg, #409EFF 0%, #69b1ff 100%);
                                            border-radius: 8px;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            margin-right: 16px;
                                        ">
                                            <i class="layui-icon layui-icon-video" style="font-size: 32px; color: white;"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 4px;">${file.name}</div>
                                            <div style="font-size: 12px; color: #909399;">${formatBytes(file.size)}</div>
                                        </div>
                                        <div style="
                                            background: #ecf5ff;
                                            color: #409EFF;
                                            padding: 4px 12px;
                                            border-radius: 12px;
                                            font-size: 12px;
                                            font-weight: 500;
                                        ">
                                            准备上传
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            insertBtn.style.display = 'inline-block';
                            pauseBtn.style.display = 'inline-block';
                        }
                        
                        // 开始上传按钮事件
                        insertBtn.addEventListener('click', function() {
                            if (!window.videoUploadState.selectedFile) return;
                            
                            // 更新UI状态
                            insertBtn.disabled = true;
                            pauseBtn.disabled = false;
                            updateProgress(0);
                            
                            // 更新预览状态
                            const statusElement = videoPreviewList.querySelector('div > div > div:last-child');
                            if (statusElement) {
                                statusElement.textContent = '上传中...';
                                statusElement.style.background = '#fff1eb';
                                statusElement.style.color = '#e6743d';
                            }
                            
                            // 显示加载中动画
                            window.videoUploadState.layerIndex = layui.layer.msg('视频上传中，请稍候...', {icon: 16, time: 0});
                            
                            // 开始分块上传
                            uploadNextChunk();
                        });
                        
                        // 暂停上传按钮事件
                        pauseBtn.addEventListener('click', function() {
                            window.videoUploadState.uploadCanceled = true;
                            
                            // 更新预览状态
                            const statusElement = videoPreviewList.querySelector('div > div > div:last-child');
                            if (statusElement) {
                                statusElement.textContent = '已暂停';
                                statusElement.style.background = '#f4f4f5';
                                statusElement.style.color = '#909399';
                            }
                            
                            if (window.videoUploadState.layerIndex) {
                                layui.layer.close(window.videoUploadState.layerIndex);
                            }
                        });
                        
                        // 插入视频按钮事件
                        insertBtn.addEventListener('click', function() {
                            const videoUrl = uploadedVideoUrl;
                            if (videoUrl) {
                                // 获取文件扩展名并设置正确的MIME类型
                                const extension = videoUrl.split('.').pop().toLowerCase();
                                let mimeType = 'video/mp4'; // 默认MP4格式
                                
                                // 根据扩展名设置正确的MIME类型
                                const mimeTypes = {
                                    'mp4': 'video/mp4',
                                    'webm': 'video/webm',
                                    'ogg': 'video/ogg',
                                    'avi': 'video/x-msvideo',
                                    'mov': 'video/quicktime',
                                    'wmv': 'video/x-ms-wmv',
                                    'flv': 'video/x-flv',
                                    'mkv': 'video/x-matroska'
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
                            const progressText = document.getElementById('upload-progress-text');
                            if (progressText) {
                                progressText.textContent = Math.round((window.videoUploadState.currentChunkIndex / window.videoUploadState.totalChunks) * 100) + '%';
                            }
                            
                            // 更新预览状态
                            const statusElement = videoPreviewList.querySelector('div > div > div:last-child');
                            if (statusElement) {
                                statusElement.textContent = `上传中... ${Math.round((window.videoUploadState.currentChunkIndex / window.videoUploadState.totalChunks) * 100)}%`;
                            }
                            
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
                                        uploadedVideoUrl = data.filePath;
                                        
                                        // 更新预览状态
                                        const statusElement = videoPreviewList.querySelector('div > div > div:last-child');
                                        if (statusElement) {
                                            statusElement.textContent = '上传完成';
                                            statusElement.style.background = '#f0f9eb';
                                            statusElement.style.color = '#67c23a';
                                        }
                                        
                                        // 显示插入按钮
                                        insertBtn.style.display = 'inline-block';
                                        insertBtn.disabled = false;
                                        pauseBtn.style.display = 'none';
                                        
                                        // 关闭加载动画
                                        if (window.videoUploadState.layerIndex) {
                                            layui.layer.close(window.videoUploadState.layerIndex);
                                        }
                                        
                                        layui.layer.msg('视频上传成功！', {icon: 1});
                                    }
                                } else {
                                    throw new Error(data.error || '上传失败');
                                }
                            })
                            .catch(error => {
                                // 更新预览状态
                                const statusElement = videoPreviewList.querySelector('div > div > div:last-child');
                                if (statusElement) {
                                    statusElement.textContent = '上传失败';
                                    statusElement.style.background = '#fef0f0';
                                    statusElement.style.color = '#f56c6c';
                                }
                                
                                // 关闭加载动画
                                if (window.videoUploadState.layerIndex) {
                                    layui.layer.close(window.videoUploadState.layerIndex);
                                }
                                
                                layui.layer.msg('上传失败: ' + error.message, {icon: 2});
                            });
                        }
                        
                        // 更新进度条
                        function updateProgress(percent) {
                            const progressBar = document.getElementById('customProgressBar');
                            const progressText = document.getElementById('upload-progress-text');
                            
                            if (progressBar) {
                                progressBar.style.width = percent + '%';
                            }
                            
                            if (progressText) {
                                progressText.textContent = percent + '%';
                            }
                        }
                    }
                });
            } catch (e) {
                console.error('视频上传功能初始化失败:', e);
                layui.layer.msg('视频上传功能初始化失败，请刷新页面重试', {icon: 2});
            }
        }
        
        // 格式化字节数
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            
            // 初始化字体和标题选择器事件
            const fontSelect = document.getElementById('font-select');
            const headingSelect = document.getElementById('heading-select');
            
            if (fontSelect) {
                fontSelect.addEventListener('mousedown', function() {
                    window.saveEditorSelection();
                });
                
                fontSelect.addEventListener('change', function() {
                    // 使用setTimeout确保选区在change事件之前被保存
                    setTimeout(() => {
                        window.formatText('fontName', this.value);
                        // 重置选择器到默认状态
                        if (this.value !== '') {
                            setTimeout(() => {
                                this.selectedIndex = 0;
                            }, 10);
                        }
                    }, 10);
                });
            }
            
            if (headingSelect) {
                headingSelect.addEventListener('mousedown', function() {
                    window.saveEditorSelection();
                });
                
                headingSelect.addEventListener('change', function() {
                    // 使用setTimeout确保选区在change事件之前被保存
                    setTimeout(() => {
                        window.formatText('formatBlock', this.value);
                        // 重置选择器到默认状态
                        if (this.value !== 'p') {
                            setTimeout(() => {
                                this.selectedIndex = 0;
                            }, 10);
                        }
                    }, 10);
                });
            }
            
            // 初始化视频上传功能
            // 视频上传功能现在直接在insertVideoEnhanced函数中初始化
            
            // 为所有已存在的视频创建容器，但不添加任何操作按钮
            function createVideoContainers() {
                try {
                    // 查找编辑器中的所有视频
                    const videos = document.querySelectorAll('video');
                    
                    videos.forEach(video => {
                        try {
                            // 检查视频是否已经有合适的容器
                            let container = video.closest('.video-container');
                            
                            // 如果没有合适的容器，创建或调整
                            if (!container) {
                                // 保存视频的原始父元素和下一个兄弟元素
                                const parent = video.parentNode;
                                const nextSibling = video.nextSibling;
                                
                                // 检查视频是否已经在某个div中
                                let existingWrapper = video.closest('div');
                                if (existingWrapper && existingWrapper !== parent && 
                                    !existingWrapper.classList.contains('video-container')) {
                                    // 改造现有包装器
                                    existingWrapper.classList.add('video-container');
                                    existingWrapper.style.position = 'relative';
                                    existingWrapper.style.maxWidth = '100%';
                                    existingWrapper.style.margin = '10px 0';
                                } else {
                                    // 创建新容器
                                    container = document.createElement('div');
                                    container.className = 'video-container';
                                    container.style.position = 'relative';
                                    container.style.maxWidth = '100%';
                                    container.style.margin = '10px 0';
                                    
                                    // 将视频移动到新容器
                                    container.appendChild(video);
                                    
                                    // 将容器插入到原始位置
                                    if (nextSibling) {
                                        parent.insertBefore(container, nextSibling);
                                    } else {
                                        parent.appendChild(container);
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('处理视频元素时出错:', error);
                        }
                    });
                } catch (error) {
                    console.error('创建视频容器失败:', error);
                }
            }
            
            // 执行创建容器的函数
            createVideoContainers();
            
            // 确保增强编辑器在DOM加载后正确初始化
            setTimeout(function() {
                if (window.customEditor && window.contentInput && typeof EnhancedEditor !== 'undefined') {
                    try {
                        window.enhancedEditor = new EnhancedEditor(window.customEditor, window.contentInput);
                        console.log('增强编辑器在DOM加载后初始化成功');
                    } catch (e) {
                        console.error('增强编辑器初始化失败:', e);
                    }
                }
            }, 200);
        });
    </script>
    
    <!-- 视频缩略图选择功能 -->
    <script>
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
                            const uploadThumbnailBtn = document.getElementById('upload-thumbnail');
                            
                            // 设置缩略图路径
                            thumbnailInput.value = data.thumbnailUrl;
                            
                            // 更新预览图
                            if (thumbnailPreview.querySelector('img')) {
                                thumbnailPreview.querySelector('img').src = data.thumbnailUrl;
                            } else {
                                const img = document.createElement('img');
                                img.src = data.thumbnailUrl;
                                img.style.maxWidth = '100%';
                                img.style.maxHeight = '100%';
                                thumbnailPreview.innerHTML = '';
                                thumbnailPreview.appendChild(img);
                            }
                            
                            // 显示/隐藏相关元素
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
    
    <!-- 编辑器按钮功能修复脚本 -->
    <script src="editor_fix.js"></script>
    
    <!-- 增强图片上传器 -->
    <script src="../../assets/js/enhanced-image-uploader.js"></script>
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
                console.warn('无法找到编辑器元素，增强图片上传器未初始化');
            }
        } catch (e) {
            console.error('增强图片上传器初始化失败:', e);
        }
    } else {
        console.warn('增强图片上传器类未定义');
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