<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

// 获取内容模板列表
$content_templates = get_content_templates();

// 获取父栏目信息
$parent_category = null;
if ($parent_id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$parent_id]);
        $parent_category = $stmt->fetch();
    } catch(PDOException $e) {
        $parent_id = 0;
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $template_type = $_POST['template_type'] ?? 'list';
    $content_template_id = !empty($_POST['content_template_id']) ? (int)$_POST['content_template_id'] : null;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    
    // 验证输入
    if (empty($name)) {
        $errors[] = '栏目名称不能为空';
    }
    
    if (empty($slug)) {
        $slug = generate_slug($name);
    }
    
    // 检查别名是否重复
    if (!empty($slug)) {
        try {
            $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $errors[] = '栏目别名已存在，请使用其他别名';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果没有错误，插入数据
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO categories (name, slug, description, template_type, content_template_id, sort_order, is_active, parent_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$name, $slug, $description, $template_type, $content_template_id, $sort_order, $is_active, $parent_id]);
            
            $success = '栏目添加成功！';
            
            // 清空表单数据
            $_POST = [];
        } catch(PDOException $e) {
            $errors[] = '添加失败：' . $e->getMessage();
        }
    }
}

// 获取所有可作为父级的栏目（顶级和二级栏目）
try {
    $stmt = $db->query("
        SELECT c.id, c.name, c.parent_id,
               (SELECT p.name FROM categories p WHERE p.id = c.parent_id) as parent_name
        FROM categories c 
        WHERE c.parent_id = 0 OR (c.parent_id > 0 AND (
            SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id
        ) < 10) -- 限制层级深度
        ORDER BY c.parent_id ASC, c.sort_order ASC
    ");
    $parent_categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $parent_categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加栏目 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
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
                        <h2>
                            <?php if ($parent_category): ?>
                                添加子栏目 - <?php echo htmlspecialchars($parent_category['name']); ?>
                            <?php else: ?>
                                添加栏目
                            <?php endif; ?>
                        </h2>
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
                    
                    <form class="layui-form" method="POST">
                        <div class="layui-form-item">
                            <label class="layui-form-label">栏目名称 *</label>
                            <div class="layui-input-block">
                                <input type="text" name="name" placeholder="请输入栏目名称" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                       class="layui-input" required>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">栏目别名</label>
                            <div class="layui-input-block">
                                <input type="text" name="slug" placeholder="URL别名，留空自动生成" 
                                       value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" 
                                       class="layui-input">
                                <div class="layui-form-mid layui-word-aux">用于URL链接，只能包含字母、数字和连字符</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">栏目描述</label>
                            <div class="layui-input-block">
                                <textarea name="description" placeholder="请输入栏目描述" 
                                          class="layui-textarea"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">模板类型</label>
                            <div class="layui-input-block">
                                <select name="template_type" lay-filter="templateType">
                                    <option value="channel" <?php echo ($_POST['template_type'] ?? 'list') === 'channel' ? 'selected' : ''; ?>>频道页</option>
                                    <option value="list" <?php echo ($_POST['template_type'] ?? 'list') === 'list' ? 'selected' : ''; ?>>列表页</option>
                                    <option value="content" <?php echo ($_POST['template_type'] ?? 'list') === 'content' ? 'selected' : ''; ?>>内容页</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- 内容模板选择（仅当模板类型为content时显示） -->
                        <div class="layui-form-item" id="contentTemplateSection" style="display: <?php echo (($_POST['template_type'] ?? 'list') === 'content') ? 'block' : 'none'; ?>;">
                            <label class="layui-form-label">内容模板</label>
                            <div class="layui-input-block">
                                <select name="content_template_id">
                                    <option value="">请选择内容模板</option>
                                    <?php foreach ($content_templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>" <?php echo (isset($_POST['content_template_id']) && $_POST['content_template_id'] == $template['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="layui-form-mid layui-word-aux">选择用于此栏目内容页的模板</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">父级栏目</label>
                            <div class="layui-input-block">
                                <select name="parent_id">
                                    <option value="0">顶级栏目</option>
                                    <?php foreach ($parent_categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo ($parent_id == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php 
                                            // 根据是否有父级显示缩进
                                            if ($cat['parent_id'] > 0) {
                                                echo '&nbsp;&nbsp;&nbsp;&nbsp;├─ ' . htmlspecialchars($cat['name']);
                                                if ($cat['parent_name']) {
                                                    echo ' (' . htmlspecialchars($cat['parent_name']) . ')';
                                                }
                                            } else {
                                                echo htmlspecialchars($cat['name']);
                                            }
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="layui-form-mid layui-word-aux">选择父级栏目，最多支持3级层级结构</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">排序</label>
                            <div class="layui-input-block">
                                <input type="number" name="sort_order" placeholder="数字越小排序越靠前" 
                                       value="<?php echo $_POST['sort_order'] ?? 0; ?>" 
                                       class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?> 
                                       title="启用" lay-skin="primary">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal">保存栏目</button>
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
    layui.use(['form', 'element'], function(){
        var form = layui.form;
        var element = layui.element;
        
        // 初始化
        form.render();
        element.render();
        
        // 监听模板类型切换
        form.on('select(templateType)', function(data){
            if(data.value === 'content') {
                document.getElementById('contentTemplateSection').style.display = 'block';
            } else {
                document.getElementById('contentTemplateSection').style.display = 'none';
            }
        });
        
        // 自动生成别名
        var nameInput = document.querySelector('input[name="name"]');
        var slugInput = document.querySelector('input[name="slug"]');
        
        nameInput.addEventListener('blur', function() {
            if (!slugInput.value && this.value) {
                // 简单的拼音转换（这里只是示例，实际项目中可能需要更完善的转换）
                var slug = this.value.toLowerCase()
                    .replace(/[^\w\s-]/g, '') // 移除特殊字符
                    .replace(/[\s_-]+/g, '-') // 替换空格和下划线为连字符
                    .replace(/^-+|-+$/g, ''); // 移除开头和结尾的连字符
                
                slugInput.value = slug;
            }
        });
    });
    </script>
</body>
</html>