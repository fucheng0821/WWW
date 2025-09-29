<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$id = intval($_GET['id'] ?? 0);
$errors = [];
$success = '';

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    // 获取模板详情
    $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    
    if (!$template) {
        header('Location: index.php?error=template_not_found');
        exit();
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $template_type = $_POST['template_type'] ?? '';
    $file_path = trim($_POST['file_path'] ?? '');
    $template_content = $_POST['template_content'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $variables = trim($_POST['variables'] ?? '');
    
    // 验证输入
    if (empty($name)) {
        $errors[] = '模板名称不能为空';
    }
    
    if (empty($template_type)) {
        $errors[] = '模板类型不能为空';
    }
    
    if (empty($file_path)) {
        $errors[] = '文件路径不能为空';
    }
    
    // 检查文件路径是否重复（排除当前模板）
    if (!empty($file_path)) {
        try {
            $stmt = $db->prepare("SELECT id FROM templates WHERE file_path = ? AND id != ?");
            $stmt->execute([$file_path, $id]);
            if ($stmt->fetch()) {
                $errors[] = '文件路径已存在，请使用其他路径';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果设为默认模板，需要取消同类型的其他默认模板
    if ($is_default && !empty($template_type)) {
        try {
            $stmt = $db->prepare("UPDATE templates SET is_default = 0 WHERE template_type = ? AND id != ?");
            $stmt->execute([$template_type, $id]);
        } catch(PDOException $e) {
            $errors[] = '更新默认模板状态失败：' . $e->getMessage();
        }
    }
    
    // 如果没有错误，更新数据
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE templates SET 
                name = ?, description = ?, template_type = ?, file_path = ?, 
                template_content = ?, is_active = ?, is_default = ?, 
                sort_order = ?, variables = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $template_type, $file_path, $template_content, $is_active, $is_default, $sort_order, $variables, $id]);
            
            $success = '模板更新成功！';
            
            // 重新获取更新后的数据
            $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();
            
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
    <title>编辑模板 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>编辑模板 #<?php echo $template['id']; ?></h2>
                        <div>
                            <a href="view.php?id=<?php echo $template['id']; ?>" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-search"></i> 查看模板
                            </a>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
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
                        <div class="layui-row layui-col-space20">
                            <!-- 左侧基本信息 -->
                            <div class="layui-col-md6">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">模板名称 *</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="name" placeholder="请输入模板名称" 
                                               value="<?php echo htmlspecialchars($template['name']); ?>" 
                                               class="layui-input" required>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">模板类型 *</label>
                                    <div class="layui-input-block">
                                        <select name="template_type" required>
                                            <option value="">请选择模板类型</option>
                                            <option value="index" <?php echo $template['template_type'] === 'index' ? 'selected' : ''; ?>>首页模板</option>
                                            <option value="channel" <?php echo $template['template_type'] === 'channel' ? 'selected' : ''; ?>>频道模板</option>
                                            <option value="list" <?php echo $template['template_type'] === 'list' ? 'selected' : ''; ?>>列表模板</option>
                                            <option value="content" <?php echo $template['template_type'] === 'content' ? 'selected' : ''; ?>>内容模板</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">文件路径 *</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="file_path" placeholder="例如: templates/default/index.php" 
                                               value="<?php echo htmlspecialchars($template['file_path']); ?>" 
                                               class="layui-input" required>
                                        <div class="layui-form-mid layui-word-aux">相对于网站根目录的路径</div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">排序</label>
                                    <div class="layui-input-block">
                                        <input type="number" name="sort_order" placeholder="数字越小排序越靠前" 
                                               value="<?php echo $template['sort_order']; ?>" 
                                               class="layui-input">
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">模板变量</label>
                                    <div class="layui-input-block">
                                        <textarea name="variables" placeholder="模板中使用的变量说明，用逗号分隔" 
                                                  class="layui-textarea" rows="3"><?php echo htmlspecialchars($template['variables'] ?? ''); ?></textarea>
                                        <div class="layui-form-mid layui-word-aux">例如: title, content, author, date</div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php echo $template['is_active'] ? 'checked' : ''; ?> 
                                               title="启用" lay-skin="primary">
                                        <input type="checkbox" name="is_default" value="1" 
                                               <?php echo $template['is_default'] ? 'checked' : ''; ?> 
                                               title="设为默认模板" lay-skin="primary">
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">创建时间</label>
                                    <div class="layui-input-block">
                                        <div class="layui-form-mid"><?php echo $template['created_at']; ?></div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">更新时间</label>
                                    <div class="layui-input-block">
                                        <div class="layui-form-mid"><?php echo $template['updated_at']; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 右侧详细信息 -->
                            <div class="layui-col-md6">
                                <div class="layui-form-item layui-form-text">
                                    <label class="layui-form-label">模板描述</label>
                                    <div class="layui-input-block">
                                        <textarea name="description" placeholder="请输入模板描述" 
                                                  class="layui-textarea" rows="4"><?php echo htmlspecialchars($template['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item layui-form-text">
                                    <label class="layui-form-label">模板内容</label>
                                    <div class="layui-input-block">
                                        <textarea name="template_content" placeholder="模板代码内容" 
                                                  class="layui-textarea" rows="20" style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($template['template_content'] ?? ''); ?></textarea>
                                        <div class="layui-form-mid layui-word-aux">支持HTML、PHP代码</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item" style="margin-top: 30px;">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal">保存更改</button>
                                <a href="view.php?id=<?php echo $template['id']; ?>" class="layui-btn layui-btn-primary">取消</a>
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
        
        form.render();
        element.render();
    });
    </script>
</body>
</html>