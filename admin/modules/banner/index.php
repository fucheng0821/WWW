<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理成功/错误消息
$success = '';
$error = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'deleted':
            $success = 'Banner删除成功！';
            break;
        case 'added':
            $success = 'Banner添加成功！';
            break;
        case 'updated':
            $success = 'Banner更新成功！';
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_id':
            $error = '无效的Banner ID！';
            break;
        case 'banner_not_found':
            $error = 'Banner不存在！';
            break;
        case 'database_error':
            $error = '数据库错误！';
            break;
        default:
            $error = '操作失败！';
    }
}

// 获取Banner列表
try {
    $stmt = $db->query("
        SELECT * FROM banners 
        ORDER BY sort_order ASC, id ASC
    ");
    $banners = $stmt->fetchAll();
} catch(PDOException $e) {
    $banners = [];
    $error = '获取Banner列表失败：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner管理 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
    <style>
        .banner-image {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #eee;
            padding: 5px;
            border-radius: 4px;
        }
        .banner-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .status-active {
            background-color: #f0f9ff;
            color: #1890ff;
            border: 1px solid #91d5ff;
        }
        .status-inactive {
            background-color: #fff2f0;
            color: #ff4d4f;
            border: 1px solid #ffccc7;
        }
        .banner-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            background-color: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
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
                        <h2>Banner管理</h2>
                        <div>
                            <a href="update_banner_types.php" class="layui-btn btn-secondary">
                                <i class="layui-icon layui-icon-setting"></i> 更新Banner类型
                            </a>
                            <a href="add.php" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-add-1"></i> 添加Banner
                            </a>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body">
                    <?php if ($success): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="layui-alert layui-alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($banners)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-carousel"></i>
                            <h3>暂无Banner</h3>
                            <p>点击上方"添加Banner"按钮创建第一个Banner</p>
                        </div>
                    <?php else: ?>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>标题</th>
                                    <th width="150">副标题</th>
                                    <th width="220">图片</th>
                                    <th width="100">类型</th>
                                    <th width="80">状态</th>
                                    <th width="60">排序</th>
                                    <th width="150">创建时间</th>
                                    <th width="150">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $banner): ?>
                                <tr>
                                    <td><?php echo $banner['id']; ?></td>
                                    <td><?php echo htmlspecialchars($banner['title']); ?></td>
                                    <td><?php echo htmlspecialchars($banner['subtitle'] ?? ''); ?></td>
                                    <td>
                                        <?php if (!empty($banner['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="Banner图片" class="banner-image">
                                        <?php else: ?>
                                            <span>无图片</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="banner-type">
                                            <?php echo $banner['banner_type'] === 'home' ? '首页' : '内页'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="banner-status <?php echo $banner['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $banner['is_active'] ? '启用' : '禁用'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $banner['sort_order']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($banner['created_at'])); ?></td>
                                    <td>
                                        <div class="layui-btn-group">
                                            <a href="edit.php?id=<?php echo $banner['id']; ?>" class="layui-btn layui-btn-primary layui-btn-xs">
                                                <i class="layui-icon layui-icon-edit"></i> 编辑
                                            </a>
                                            <a href="delete.php?id=<?php echo $banner['id']; ?>" 
                                               class="layui-btn layui-btn-danger layui-btn-xs" 
                                               onclick="return confirm('确定要删除这个Banner吗？此操作不可恢复！')">
                                                <i class="layui-icon layui-icon-delete"></i> 删除
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
        function deleteBanner(id) {
            layui.use('layer', function(){
                var layer = layui.layer;
                
                layer.confirm('确定要删除这个Banner吗？', {
                    icon: 3,
                    title: '删除确认'
                    }, function(index){
                        // 使用AJAX方式执行删除操作
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', 'delete.php?id=' + id, true);
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) {
                                    try {
                                        var response = JSON.parse(xhr.responseText);
                                        if (response.success) {
                                            layer.msg('删除成功', {icon: 1});
                                            // 重新加载页面以更新列表
                                            setTimeout(function() {
                                                window.location.reload();
                                            }, 1000);
                                        } else {
                                            layer.msg('删除失败: ' + response.message, {icon: 2});
                                        }
                                    } catch (e) {
                                        layer.msg('删除失败: 无法解析服务器响应', {icon: 2});
                                    }
                                } else {
                                    layer.msg('删除失败: HTTP ' + xhr.status, {icon: 2});
                                }
                            }
                        };
                        
                        // 发送删除确认数据
                        xhr.send('confirm_delete=1');
                        
                        layer.close(index);
                    });
                });
            }
    </script>
</body>
</html>