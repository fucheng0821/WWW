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
            $success = '栏目删除成功！';
            break;
        case 'added':
            $success = '栏目添加成功！';
            break;
        case 'updated':
            $success = '栏目更新成功！';
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_id':
            $error = '无效的栏目 ID！';
            break;
        case 'category_not_found':
            $error = '栏目不存在！';
            break;
        case 'database_error':
            $error = '数据库错误！';
            break;
        default:
            $error = '操作失败！';
    }
}

// 获取栏目列表
try {
    $stmt = $db->query("
        SELECT c.*, 
               COUNT(sub.id) as sub_count,
               (SELECT COUNT(*) FROM contents WHERE category_id = c.id) as content_count
        FROM categories c 
        LEFT JOIN categories sub ON sub.parent_id = c.id 
        GROUP BY c.id 
        ORDER BY c.parent_id ASC, c.sort_order ASC, c.id ASC
    ");
    $all_categories = $stmt->fetchAll();
    
    // 构建层级化的栏目列表
    function buildCategoryTree($categories, $parent_id = 0, $level = 0) {
        $result = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parent_id) {
                $category['level'] = $level;
                $result[] = $category;
                // 递归获取子栏目
                $children = buildCategoryTree($categories, $category['id'], $level + 1);
                $result = array_merge($result, $children);
            }
        }
        return $result;
    }
    
    $categories = buildCategoryTree($all_categories);
} catch(PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>栏目管理 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
    <script src="../../assets/js/notifications.js"></script>
    <style>
        .category-tree {
            font-family: 'Microsoft YaHei', sans-serif;
        }
        .category-tree tr[data-level="0"] {
            border-top: 2px solid #e2e8f0;
        }
        .category-tree tr[data-level="0"] td {
            font-weight: 600;
        }
        .category-tree tr[data-level="1"] {
            background: #fafbfc !important;
        }
        .category-tree tr[data-level="1"] td {
            font-size: 13px;
            color: #555;
        }
        .category-tree .tree-indent {
            color: #999;
            font-family: monospace;
        }
        .parent-category {
            color: #1890ff;
            font-weight: 600;
        }
        .sub-category {
            color: #666;
        }
        /* 折叠相关样式 */
        .collapse-toggle {
            cursor: pointer;
            user-select: none;
            margin-right: 5px;
        }
        .collapse-toggle::before {
            content: "▼";
            font-size: 12px;
            margin-right: 5px;
            transition: transform 0.2s;
        }
        .collapse-toggle.collapsed::before {
            transform: rotate(-90deg);
        }
        .child-row {
            display: table-row;
        }
        .child-row.collapsed {
            display: none;
        }
    </style>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php 
        // 调整 header 和 sidebar 的路径
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
                        <h2>栏目管理</h2>
                        <a href="add.php" class="layui-btn layui-btn-normal">
                            <i class="layui-icon layui-icon-add-1"></i> 添加栏目
                        </a>
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
                    
                    <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-template-1"></i>
                            <h3>暂无栏目</h3>
                            <p>点击上方"添加栏目"按钮创建第一个栏目</p>
                        </div>
                    <?php else: ?>
                        <table class="layui-table category-tree">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>栏目名称</th>
                                    <th width="120">别名</th>
                                    <th width="80">类型</th>
                                    <th width="60">状态</th>
                                    <th width="60">子栏目</th>
                                    <th width="60">内容数</th>
                                    <th width="60">排序</th>
                                    <th width="120">创建时间</th>
                                    <th width="200">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // 重新组织栏目数据，按父级分组
                                $grouped_categories = [];
                                foreach ($categories as $category) {
                                    if ($category['parent_id'] == 0) {
                                        $grouped_categories[$category['id']] = $category;
                                        $grouped_categories[$category['id']]['children'] = [];
                                    }
                                }
                                
                                foreach ($categories as $category) {
                                    if ($category['parent_id'] != 0) {
                                        if (isset($grouped_categories[$category['parent_id']])) {
                                            $grouped_categories[$category['parent_id']]['children'][] = $category;
                                        }
                                    }
                                }
                                
                                foreach ($grouped_categories as $parent_category):
                                    $has_children = !empty($parent_category['children']);
                                ?>
                                <tr data-level="0" data-id="<?php echo $parent_category['id']; ?>">
                                    <td><?php echo $parent_category['id']; ?></td>
                                    <td>
                                        <?php if ($has_children): ?>
                                            <span class="collapse-toggle" data-target="children-<?php echo $parent_category['id']; ?>"></span>
                                        <?php else: ?>
                                            <span style="display: inline-block; width: 16px;"></span>
                                        <?php endif; ?>
                                        <span class="parent-category"><?php echo htmlspecialchars($parent_category['name']); ?></span>
                                        <?php if ($parent_category['sub_count'] > 0): ?>
                                            <span class="layui-badge layui-badge-rim" style="margin-left: 5px;"><?php echo $parent_category['sub_count']; ?>个子栏目</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($parent_category['slug']); ?></td>
                                    <td>
                                        <span class="layui-badge layui-bg-blue">
                                            <?php 
                                            switch($parent_category['template_type']) {
                                                case 'channel': echo '频道页'; break;
                                                case 'list': echo '列表页'; break;
                                                case 'content': echo '内容页'; break;
                                                default: echo '未设置';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($parent_category['is_active']): ?>
                                            <span class="status-badge status-completed">启用</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $parent_category['sub_count']; ?></td>
                                    <td><?php echo $parent_category['content_count']; ?></td>
                                    <td><?php echo $parent_category['sort_order']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($parent_category['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $parent_category['id']; ?>" 
                                           class="layui-btn layui-btn-xs">编辑</a>
                                        <?php if ($parent_category['template_type'] === 'channel'): ?>
                                            <a href="manage_channel_content.php?id=<?php echo $parent_category['id']; ?>" 
                                               class="layui-btn layui-btn-xs layui-btn-normal">管理内容</a>
                                        <?php endif; ?>
                                        <a href="add.php?parent_id=<?php echo $parent_category['id']; ?>" 
                                           class="layui-btn layui-btn-xs layui-btn-normal">添加子栏目</a>
                                        <a href="javascript:;" 
                                           onclick="deleteCategory(<?php echo $parent_category['id']; ?>)"
                                           class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                                    </td>
                                </tr>
                                
                                <?php foreach ($parent_category['children'] as $child_category): ?>
                                <tr class="child-row" data-level="1" data-parent="children-<?php echo $parent_category['id']; ?>">
                                    <td><?php echo $child_category['id']; ?></td>
                                    <td>
                                        <span class="tree-indent">&nbsp;&nbsp;&nbsp;&nbsp;├─ </span>
                                        <span class="sub-category"><?php echo htmlspecialchars($child_category['name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($child_category['slug']); ?></td>
                                    <td>
                                        <span class="layui-badge layui-bg-blue">
                                            <?php 
                                            switch($child_category['template_type']) {
                                                case 'channel': echo '频道页'; break;
                                                case 'list': echo '列表页'; break;
                                                case 'content': echo '内容页'; break;
                                                default: echo '未设置';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($child_category['is_active']): ?>
                                            <span class="status-badge status-completed">启用</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $child_category['sub_count']; ?></td>
                                    <td><?php echo $child_category['content_count']; ?></td>
                                    <td><?php echo $child_category['sort_order']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($child_category['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $child_category['id']; ?>" 
                                           class="layui-btn layui-btn-xs">编辑</a>
                                        <?php if ($child_category['template_type'] === 'channel'): ?>
                                            <a href="manage_channel_content.php?id=<?php echo $child_category['id']; ?>" 
                                               class="layui-btn layui-btn-xs layui-btn-normal">管理内容</a>
                                        <?php endif; ?>
                                        <a href="javascript:;" 
                                           onclick="deleteCategory(<?php echo $child_category['id']; ?>)"
                                           class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
        
        // 初始化导航
        element.render();
        
        // 折叠/展开功能
        document.querySelectorAll('.collapse-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var childRows = document.querySelectorAll('[data-parent="' + targetId + '"]');
                var isCollapsed = this.classList.contains('collapsed');
                
                if (isCollapsed) {
                    // 展开
                    this.classList.remove('collapsed');
                    childRows.forEach(function(row) {
                        row.classList.remove('collapsed');
                    });
                } else {
                    // 折叠
                    this.classList.add('collapsed');
                    childRows.forEach(function(row) {
                        row.classList.add('collapsed');
                    });
                }
            });
        });
    });
    
    function deleteCategory(id) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除这个栏目吗？', {
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