<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取所有顶级栏目用于父级选择
try {
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC, id DESC");
    $stmt->execute();
    $top_categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $top_categories = [];
    error_log("获取顶级栏目列表失败: " . $e->getMessage());
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id = intval($_POST['parent_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = $_POST['description'] ?? '';
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 验证必填字段
    if (empty($name)) {
        $error_message = '栏目名称为必填项';
    } else {
        try {
            // 插入新栏目
            $stmt = $db->prepare("INSERT INTO categories (parent_id, name, description, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$parent_id, $name, $description, $sort_order, $is_active]);
            
            // 重定向到栏目列表页面
            header("Location: index.php?message=" . urlencode('栏目添加成功'));
            exit();
        } catch(PDOException $e) {
            $error_message = '添加栏目失败: ' . $e->getMessage();
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
    <title>添加栏目 - 移动管理后台</title>
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
                <h1>添加栏目</h1>
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
                    <li class="menu-item active">
                        <a href="../category/">
                            <i class="fas fa-folder"></i>
                            <span>栏目管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
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
                <h1>添加栏目</h1>
                <p>创建新的栏目</p>
            </div>
            
            <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>父级栏目</label>
                        <select name="parent_id" class="form-control">
                            <option value="0">作为顶级栏目</option>
                            <?php foreach ($top_categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>栏目名称 *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>描述</label>
                        <!-- 添加自定义编辑器 -->
                        <div class="custom-editor">
                            <div class="editor-toolbar">
                                <!-- 基础格式 -->
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('bold')" title="粗体"><i class="layui-icon layui-icon-fonts-strong"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('italic')" title="斜体"><i class="layui-icon layui-icon-fonts-i"></i></button>
                                <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('underline')" title="下划线"><i class="layui-icon layui-icon-fonts-u"></i></button>
                                
                                <!-- 标题选择 -->
                                <div class="layui-inline" style="margin-left: 10px;">
                                    <div class="layui-btn-group">
                                        <button type="button" class="layui-btn layui-btn-primary layui-btn-xs" onclick="window.formatText('formatBlock', 'p')" title="段落">P</button>
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
                            </div>
                            <div id="custom-editor" class="editor-content" contenteditable="true" style="min-height: 150px; border: 1px solid #e6e6e6; padding: 15px; background: #fff; font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif; font-size: 14px; line-height: 1.6;">
                                <?php echo $_POST['description'] ?? '<p>开始编写栏目描述...</p>'; ?>
                            </div>
                        </div>
                        <textarea name="description" id="description-input" style="display: none;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo intval($_POST['sort_order'] ?? 0); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?php echo (isset($_POST['is_active']) && $_POST['is_active']) ? 'checked' : 'checked'; ?>>
                            启用栏目
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 保存栏目
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
    </script>
</body>
</html>