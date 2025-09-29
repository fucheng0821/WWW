<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取内容模板列表
$content_templates = get_content_templates();

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
} catch(PDOException $e) {
    header('Location: index.php?error=database_error');
    exit;
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
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    // 验证输入
    if (empty($name)) {
        $errors[] = '栏目名称不能为空';
    }
    
    if (empty($slug)) {
        $slug = generate_slug($name);
    }
    
    // 检查别名是否重复（排除当前栏目）
    if (!empty($slug)) {
        try {
            $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $category_id]);
            if ($stmt->fetch()) {
                $errors[] = '栏目别名已存在，请使用其他别名';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 检查父级栏目不能是自己或自己的子栏目
    if ($parent_id > 0) {
        if ($parent_id == $category_id) {
            $errors[] = '父级栏目不能是自己';
        } else {
            // 检查是否会形成循环引用
            $check_parent = $parent_id;
            $depth = 0;
            while ($check_parent > 0 && $depth < 10) {
                try {
                    $stmt = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
                    $stmt->execute([$check_parent]);
                    $parent_info = $stmt->fetch();
                    if ($parent_info) {
                        if ($parent_info['parent_id'] == $category_id) {
                            $errors[] = '不能选择自己的子栏目作为父级栏目';
                            break;
                        }
                        $check_parent = $parent_info['parent_id'];
                    } else {
                        break;
                    }
                } catch(PDOException $e) {
                    break;
                }
                $depth++;
            }
        }
    }
    
    // 如果没有错误，更新数据
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE categories 
                SET name = ?, slug = ?, description = ?, template_type = ?, content_template_id = ?,
                    sort_order = ?, is_active = ?, parent_id = ?,
                    meta_title = ?, meta_keywords = ?, meta_description = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $slug, $description, $template_type, $content_template_id,
                $sort_order, $is_active, $parent_id,
                $meta_title, $meta_keywords, $meta_description,
                $category_id
            ]);
            
            $success = '栏目更新成功！';
            
            // 重新获取更新后的数据
            $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();
            
        } catch(PDOException $e) {
            $errors[] = '更新失败：' . $e->getMessage();
        }
    }
}

// 获取所有父级栏目（排除当前栏目及其子栏目）
try {
    $stmt = $db->prepare("SELECT id, name, parent_id FROM categories WHERE id != ? ORDER BY sort_order ASC");
    $stmt->execute([$category_id]);
    $all_categories = $stmt->fetchAll();
    
    // 过滤掉当前栏目的子栏目
    $parent_categories = [];
    foreach ($all_categories as $cat) {
        if ($cat['parent_id'] != $category_id) {
            $parent_categories[] = $cat;
        }
    }
} catch(PDOException $e) {
    $parent_categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑栏目 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
                        <h2>编辑栏目 - <?php echo htmlspecialchars($category['name']); ?></h2>
                        <div>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
                            </a>
                            <?php if ($category['template_type'] === 'channel'): ?>
                                <a href="manage_channel_content.php?id=<?php echo $category['id']; ?>" class="layui-btn layui-btn-normal">
                                    <i class="layui-icon layui-icon-edit"></i> 管理频道内容
                                </a>
                            <?php endif; ?>
                            <a href="add.php?parent_id=<?php echo $category['id']; ?>" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-add-1"></i> 添加子栏目
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
                    
                    <div class="layui-tab layui-tab-brief" lay-filter="categoryTab">
                        <ul class="layui-tab-title">
                            <li class="layui-this">基本信息</li>
                            <li>SEO设置</li>
                        </ul>
                        <div class="layui-tab-content">
                            <form class="layui-form" method="POST">
                                <!-- 基本信息 -->
                                <div class="layui-tab-item layui-show">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">栏目名称 *</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="name" placeholder="请输入栏目名称" 
                                                   value="<?php echo htmlspecialchars($category['name']); ?>" 
                                                   class="layui-input" required>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">栏目别名</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="slug" placeholder="URL别名" 
                                                   value="<?php echo htmlspecialchars($category['slug']); ?>" 
                                                   class="layui-input">
                                            <div class="layui-form-mid layui-word-aux">用于URL链接，只能包含字母、数字和连字符</div>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">栏目描述</label>
                                        <div class="layui-input-block">
                                            <textarea name="description" placeholder="请输入栏目描述" 
                                                      class="layui-textarea" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">模板类型</label>
                                        <div class="layui-input-block">
                                            <select name="template_type" lay-filter="templateType">
                                                <option value="channel" <?php echo $category['template_type'] === 'channel' ? 'selected' : ''; ?>>频道页</option>
                                                <option value="list" <?php echo $category['template_type'] === 'list' ? 'selected' : ''; ?>>列表页</option>
                                                <option value="content" <?php echo $category['template_type'] === 'content' ? 'selected' : ''; ?>>内容页</option>
                                            </select>
                                            <div class="layui-form-mid layui-word-aux">
                                                频道页：展示子栏目和推荐内容 | 列表页：展示内容列表 | 内容页：单页内容
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 内容模板选择（仅当模板类型为content时显示） -->
                                    <div class="layui-form-item" id="contentTemplateSection" style="display: <?php echo ($category['template_type'] === 'content') ? 'block' : 'none'; ?>;">
                                        <label class="layui-form-label">内容模板</label>
                                        <div class="layui-input-block">
                                            <select name="content_template_id">
                                                <option value="">请选择内容模板</option>
                                                <?php foreach ($content_templates as $template): ?>
                                                    <option value="<?php echo $template['id']; ?>" <?php echo ($category['content_template_id'] == $template['id']) ? 'selected' : ''; ?>>
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
                                                <option value="0" <?php echo $category['parent_id'] == 0 ? 'selected' : ''; ?>>顶级栏目</option>
                                                <?php foreach ($parent_categories as $cat): ?>
                                                    <?php if ($cat['parent_id'] == 0): // 只显示顶级栏目作为父级选项 ?>
                                                        <option value="<?php echo $cat['id']; ?>" 
                                                                <?php echo $category['parent_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['name']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">排序</label>
                                        <div class="layui-input-block">
                                            <input type="number" name="sort_order" placeholder="数字越小排序越靠前" 
                                                   value="<?php echo $category['sort_order']; ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">状态</label>
                                        <div class="layui-input-block">
                                            <input type="checkbox" name="is_active" value="1" 
                                                   <?php echo $category['is_active'] ? 'checked' : ''; ?> 
                                                   lay-skin="switch" lay-text="启用|禁用">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SEO设置 -->
                                <div class="layui-tab-item">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO标题</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="meta_title" placeholder="留空使用栏目名称" 
                                                   value="<?php echo htmlspecialchars($category['meta_title'] ?? ''); ?>" 
                                                   class="layui-input">
                                            <div class="layui-form-mid layui-word-aux">建议30-60个字符</div>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO关键词</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="meta_keywords" placeholder="多个关键词用逗号分隔" 
                                                   value="<?php echo htmlspecialchars($category['meta_keywords'] ?? ''); ?>" 
                                                   class="layui-input">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">SEO描述</label>
                                        <div class="layui-input-block">
                                            <textarea name="meta_description" placeholder="留空使用栏目描述" 
                                                      class="layui-textarea" rows="4"><?php echo htmlspecialchars($category['meta_description'] ?? ''); ?></textarea>
                                            <div class="layui-form-mid layui-word-aux">建议120-160个字符</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item" style="margin-top: 30px;">
                                    <div class="layui-input-block">
                                        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="editCategory">
                                            <i class="layui-icon layui-icon-ok"></i> 更新栏目
                                        </button>
                                        <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'element'], function(){
        var form = layui.form;
        var element = layui.element;
        
        // 初始化选项卡
        element.render('tab');
        
        // 监听提交
        form.on('submit(editCategory)', function(data){
            return true; // 允许表单提交
        });
        
        // 监听模板类型切换
        form.on('select(templateType)', function(data){
            if(data.value === 'content') {
                document.getElementById('contentTemplateSection').style.display = 'block';
            } else {
                document.getElementById('contentTemplateSection').style.display = 'none';
            }
        });
        
        // 自动生成别名
        document.querySelector('input[name="name"]').addEventListener('input', function() {
            var name = this.value;
            var slug = name.toLowerCase()
                          .replace(/[^\w\s-]/g, '')
                          .replace(/[\s_-]+/g, '-')
                          .replace(/^-+|-+$/g, '');
            if (slug && !document.querySelector('input[name="slug"]').value) {
                document.querySelector('input[name="slug"]').value = slug;
            }
        });
        
        // 修复选项卡点击事件 - 确保选项卡能正常切换
        element.render('tab', 'categoryTab');
        
        // 手动添加点击事件处理
        var tabTitles = document.querySelectorAll('.layui-tab-title li');
        tabTitles.forEach(function(tab, index) {
            tab.addEventListener('click', function() {
                // 移除所有选项卡的激活状态
                tabTitles.forEach(function(t) {
                    t.classList.remove('layui-this');
                });
                // 激活当前选项卡
                this.classList.add('layui-this');
                
                // 隐藏所有内容区域
                var tabContents = document.querySelectorAll('.layui-tab-content .layui-tab-item');
                tabContents.forEach(function(content) {
                    content.classList.remove('layui-show');
                });
                // 显示对应内容区域
                tabContents[index].classList.add('layui-show');
            });
        });
    });
    </script>
</body>
</html>