<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 检查是否为AJAX请求
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

$errors = [];
$success = '';
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 验证栏目ID
if ($category_id <= 0) {
    if ($is_ajax) {
        json_response(['success' => false, 'message' => '无效的栏目ID'], 400);
    } else {
        header('Location: index.php?error=invalid_id');
        exit;
    }
}

// 获取栏目信息
try {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        if ($is_ajax) {
            json_response(['success' => false, 'message' => '栏目不存在'], 404);
        } else {
            header('Location: index.php?error=category_not_found');
            exit;
        }
    }
} catch(PDOException $e) {
    if ($is_ajax) {
        json_response(['success' => false, 'message' => '数据库错误'], 500);
    } else {
        header('Location: index.php?error=database_error');
        exit;
    }
}

// 检查是否有子栏目
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
    $stmt->execute([$category_id]);
    $sub_count = $stmt->fetch()['count'];
} catch(PDOException $e) {
    $sub_count = 0;
}

// 检查是否有关联内容
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contents WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $content_count = $stmt->fetch()['count'];
} catch(PDOException $e) {
    $content_count = 0;
}

// 处理删除确认
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($sub_count > 0) {
        $errors[] = '不能删除包含子栏目的栏目，请先删除子栏目';
    } elseif ($content_count > 0 && !isset($_POST['force_delete'])) {
        $errors[] = '该栏目包含 ' . $content_count . ' 个内容，请确认是否强制删除';
    } else {
        // 执行删除操作
        try {
            $db->beginTransaction();
            
            // 如果强制删除，先删除关联内容
            if ($content_count > 0 && isset($_POST['force_delete'])) {
                $stmt = $db->prepare("DELETE FROM contents WHERE category_id = ?");
                $stmt->execute([$category_id]);
            }
            
            // 删除栏目
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            
            $db->commit();
            
            // 删除成功，返回JSON响应或重定向
            if ($is_ajax) {
                json_response(['success' => true, 'message' => '栏目删除成功']);
            } else {
                header('Location: index.php?success=deleted');
                exit;
            }
            
        } catch(PDOException $e) {
            $db->rollBack();
            $errors[] = '删除失败：' . $e->getMessage();
            if ($is_ajax) {
                json_response(['success' => false, 'message' => $errors[0]], 500);
            }
        }
    }
    
    // 如果是AJAX请求且有错误，返回错误信息
    if ($is_ajax && !empty($errors)) {
        json_response(['success' => false, 'message' => $errors[0]], 400);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>删除栏目 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .danger-zone {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-info {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .category-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .stat-item {
            display: inline-block;
            background: white;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 5px;
            border: 1px solid #e2e8f0;
        }
    </style>
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
                        <h2>🗑️ 删除栏目确认</h2>
                        <div>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
                            </a>
                            <a href="edit.php?id=<?php echo $category['id']; ?>" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-edit"></i> 编辑栏目
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
                    
                    <!-- 栏目信息 -->
                    <div class="category-info">
                        <h3>📋 栏目信息</h3>
                        <p><strong>栏目名称：</strong><?php echo htmlspecialchars($category['name']); ?></p>
                        <p><strong>栏目别名：</strong><?php echo htmlspecialchars($category['slug']); ?></p>
                        <p><strong>栏目描述：</strong><?php echo htmlspecialchars($category['description'] ?? '无'); ?></p>
                        <p><strong>模板类型：</strong>
                            <?php 
                            switch($category['template_type']) {
                                case 'channel': echo '频道页'; break;
                                case 'list': echo '列表页'; break;
                                case 'content': echo '内容页'; break;
                                default: echo '未设置';
                            }
                            ?>
                        </p>
                        <p><strong>创建时间：</strong><?php echo $category['created_at']; ?></p>
                        
                        <!-- 统计信息 -->
                        <div style="margin-top: 15px;">
                            <span class="stat-item">
                                <i class="layui-icon layui-icon-template-1"></i>
                                子栏目数量：<strong><?php echo $sub_count; ?></strong>
                            </span>
                            <span class="stat-item">
                                <i class="layui-icon layui-icon-file"></i>
                                内容数量：<strong><?php echo $content_count; ?></strong>
                            </span>
                        </div>
                    </div>
                    
                    <!-- 删除影响提示 -->
                    <?php if ($sub_count > 0): ?>
                        <div class="layui-alert layui-alert-danger">
                            <h4>❌ 无法删除</h4>
                            <p>该栏目包含 <strong><?php echo $sub_count; ?></strong> 个子栏目，不能直接删除。</p>
                            <p>请先删除或移动子栏目后再删除此栏目。</p>
                        </div>
                    <?php elseif ($content_count > 0): ?>
                        <div class="warning-info">
                            <h4>⚠️ 删除影响</h4>
                            <p>该栏目包含 <strong><?php echo $content_count; ?></strong> 个内容，删除栏目将会：</p>
                            <ul>
                                <li>永久删除栏目及其所有内容</li>
                                <li>相关的SEO设置和统计数据将丢失</li>
                                <li>前端访问对应页面将返回404错误</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="layui-alert layui-alert-warning">
                            <h4>⚠️ 删除提醒</h4>
                            <p>该栏目为空栏目，删除后无法恢复。</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 删除确认表单 -->
                    <?php if ($sub_count == 0): ?>
                        <div class="danger-zone">
                            <h3>🚨 危险操作区域</h3>
                            <p><strong>删除操作不可逆，请谨慎操作！</strong></p>
                            
                            <form class="layui-form" method="POST" id="deleteForm">
                                <?php if ($content_count > 0): ?>
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <input type="checkbox" name="force_delete" value="1" 
                                                   lay-skin="primary" title="我确认强制删除栏目及其所有内容">
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <input type="hidden" name="confirm_delete" value="1">
                                        <button type="submit" class="layui-btn layui-btn-danger" lay-submit lay-filter="deleteCategory">
                                            <i class="layui-icon layui-icon-delete"></i> 确认删除栏目
                                        </button>
                                        <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; margin-top: 30px;">
                            <a href="index.php" class="layui-btn layui-btn-primary">返回栏目管理</a>
                            <a href="edit.php?id=<?php echo $category['id']; ?>" class="layui-btn layui-btn-normal">编辑栏目</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/admin-utils.js"></script>
    <script>
    layui.use(['form', 'layer'], function(){
        var form = layui.form;
        var layer = layui.layer;
        
        // 监听删除表单提交
        form.on('submit(deleteCategory)', function(data){
            var hasContent = <?php echo $content_count; ?>;
            
            // 如果有内容，检查是否勾选了强制删除
            if (hasContent > 0 && !data.field.force_delete) {
                layer.confirm('该栏目包含 ' + hasContent + ' 个内容，确定要强制删除吗？', {
                    icon: 3,
                    title: '删除确认'
                }, function(index){
                    submitDeleteForm();
                    layer.close(index);
                });
                return false;
            }
            
            submitDeleteForm();
            return false;
        });
        
        // 提交删除表单
        function submitDeleteForm() {
            // 使用AdminUtils的AJAX方法提交
            AdminUtils.ajaxRequest('', {
                method: 'POST',
                data: new FormData(document.getElementById('deleteForm'))
            }).then(function(response) {
                if (response.success) {
                    layer.msg('删除成功', {icon: 1});
                    // 2秒后跳转到列表页
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    layer.msg('删除失败: ' + response.message, {icon: 2});
                }
            }).catch(function(error) {
                layer.msg('删除失败: ' + (error.message || '未知错误'), {icon: 2});
            });
        }
    });
    </script>
</body>
</html>