<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取栏目信息
if ($category_id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        header('Location: index.php?error=category_not_found');
        exit;
    }
    
    // Check if this is a channel template type
    if ($category['template_type'] !== 'channel') {
        header('Location: index.php?error=not_channel_type');
        exit;
    }
} catch(PDOException $e) {
    header('Location: index.php?error=database_error');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    
    // 如果没有错误，更新数据
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE categories 
                SET content = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $content,
                $category_id
            ]);
            
            $success = '频道内容更新成功！';
            
            // 重新获取更新后的数据
            $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();
            
        } catch(PDOException $e) {
            $errors[] = '更新失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理频道内容 - <?php echo htmlspecialchars($category['name']); ?> - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <!-- 引入Quill编辑器CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php 
        $header_path = '../../includes/header.php';
        $sidebar_path = '../../includes/sidebar.php';
        if (file_exists($header_path)) {
            include $header_path;
        }
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        }
        ?>
        
        <div class="layui-body">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>管理频道内容 - <?php echo htmlspecialchars($category['name']); ?></h2>
                        <div>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
                            </a>
                            <a href="<?php echo url($category['slug'] . '/'); ?>" target="_blank" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-release"></i> 预览页面
                            </a>
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
                    
                    <form class="layui-form" method="POST">
                        <div class="layui-form-item">
                            <label class="layui-form-label">栏目名称</label>
                            <div class="layui-input-block">
                                <input type="text" value="<?php echo htmlspecialchars($category['name']); ?>" class="layui-input" readonly>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">栏目别名</label>
                            <div class="layui-input-block">
                                <input type="text" value="<?php echo htmlspecialchars($category['slug']); ?>" class="layui-input" readonly>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">频道内容</label>
                            <div class="layui-input-block">
                                <div id="editor" style="height: 400px;"><?php echo htmlspecialchars($category['content'] ?? ''); ?></div>
                                <textarea name="content" id="content" style="display:none;"><?php echo htmlspecialchars($category['content'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="layui-form-item" style="margin-top: 30px;">
                            <div class="layui-input-block">
                                <button class="layui-btn layui-btn-normal" lay-submit lay-filter="saveContent">
                                    <i class="layui-icon layui-icon-ok"></i> 保存内容
                                </button>
                                <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 引入Quill编辑器JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
        layui.use(['form', 'layer'], function(){
            var form = layui.form;
            var layer = layui.layer;
            
            // 初始化Quill编辑器
            var quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });
            
            // 同步编辑器内容到textarea
            quill.on('text-change', function() {
                document.getElementById('content').value = quill.root.innerHTML;
            });
            
            // 表单提交
            form.on('submit(saveContent)', function(data){
                // 确保内容已同步
                document.getElementById('content').value = quill.root.innerHTML;
                return true;
            });
        });
    </script>
</body>
</html>