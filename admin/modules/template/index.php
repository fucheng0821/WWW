<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理搜索和筛选
$search_keyword = $_GET['keyword'] ?? '';
$template_type = $_GET['template_type'] ?? '';
$is_active = $_GET['is_active'] ?? '';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

if (!empty($template_type)) {
    $where_conditions[] = "template_type = ?";
    $params[] = $template_type;
}

if ($is_active !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = intval($is_active);
}

$where_clause = implode(' AND ', $where_conditions);

// 获取模板列表
try {
    // 统计总数
    $count_sql = "SELECT COUNT(*) as total FROM templates WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取模板列表
    $sql = "SELECT * FROM templates WHERE $where_clause
            ORDER BY template_type ASC, sort_order ASC, created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll();
    
    // 计算分页信息
    $total_pages = ceil($total / $per_page);
    
    // 获取统计数据
    $stats_sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN template_type = 'index' THEN 1 ELSE 0 END) as index_count,
                    SUM(CASE WHEN template_type = 'channel' THEN 1 ELSE 0 END) as channel_count,
                    SUM(CASE WHEN template_type = 'list' THEN 1 ELSE 0 END) as list_count,
                    SUM(CASE WHEN template_type = 'content' THEN 1 ELSE 0 END) as content_count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                  FROM templates";
    $stats_stmt = $db->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
} catch(PDOException $e) {
    $templates = [];
    $total = 0;
    $total_pages = 0;
    $stats = ['total_count' => 0, 'index_count' => 0, 'channel_count' => 0, 'list_count' => 0, 'content_count' => 0, 'active_count' => 0];
}

// 处理成功和错误消息
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// 将错误代码转换为友好的中文提示
if ($error_msg) {
    $error_messages = [
        'invalid_id' => '无效的模板ID，请检查链接是否正确',
        'template_not_found' => '模板不存在或已被删除',
        'cannot_delete_default_template' => '无法删除默认模板，请先设置其他模板为默认',
        'database_error' => '数据库操作失败，请稍后重试',
        'permission_denied' => '权限不足，无法执行此操作'
    ];
    
    $error_msg = $error_messages[$error_msg] ?? $error_msg;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>模板管理 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <?php if ($success_msg): ?>
                <div class="layui-alert layui-alert-success" style="margin: 20px;">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="layui-alert layui-alert-danger" style="margin: 20px;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <!-- 统计卡片 -->
            <div class="layui-row layui-col-space15" style="margin: 20px;">
                <div class="layui-col-md2">
                    <div class="admin-card admin-card-blue">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-template-1"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['total_count']; ?></div>
                            <div class="admin-card-title">总模板数</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md2">
                    <div class="admin-card admin-card-green">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-home"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['index_count']; ?></div>
                            <div class="admin-card-title">首页模板</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md2">
                    <div class="admin-card admin-card-orange">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-website"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['channel_count']; ?></div>
                            <div class="admin-card-title">频道模板</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md2">
                    <div class="admin-card admin-card-red">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-menu-fill"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['list_count']; ?></div>
                            <div class="admin-card-title">列表模板</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md2">
                    <div class="admin-card admin-card-blue">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-file"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['content_count']; ?></div>
                            <div class="admin-card-title">内容模板</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md2">
                    <div class="admin-card admin-card-green">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-ok-circle"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['active_count']; ?></div>
                            <div class="admin-card-title">启用模板</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>模板管理</h2>
                        <div>
                            <a href="add.php" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-add-1"></i> 添加模板
                            </a>
                            <button id="initTemplateBtn" class="layui-btn layui-btn-warm layui-btn-sm">
                                <i class="layui-icon layui-icon-set"></i> 初始化模板表
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 搜索筛选 -->
                <div class="layui-card-body">
                    <form class="layui-form" method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                        <div class="layui-row layui-col-space10">
                            <div class="layui-col-md3">
                                <input type="text" name="keyword" placeholder="搜索模板名称或描述" 
                                       value="<?php echo htmlspecialchars($search_keyword); ?>" 
                                       class="layui-input">
                            </div>
                            <div class="layui-col-md2">
                                <select name="template_type">
                                    <option value="">全部类型</option>
                                    <option value="index" <?php echo $template_type === 'index' ? 'selected' : ''; ?>>首页模板</option>
                                    <option value="channel" <?php echo $template_type === 'channel' ? 'selected' : ''; ?>>频道模板</option>
                                    <option value="list" <?php echo $template_type === 'list' ? 'selected' : ''; ?>>列表模板</option>
                                    <option value="content" <?php echo $template_type === 'content' ? 'selected' : ''; ?>>内容模板</option>
                                </select>
                            </div>
                            <div class="layui-col-md2">
                                <select name="is_active">
                                    <option value="">全部状态</option>
                                    <option value="1" <?php echo $is_active === '1' ? 'selected' : ''; ?>>启用</option>
                                    <option value="0" <?php echo $is_active === '0' ? 'selected' : ''; ?>>禁用</option>
                                </select>
                            </div>
                            <div class="layui-col-md2">
                                <button type="submit" class="layui-btn">搜索</button>
                                <a href="index.php" class="layui-btn layui-btn-primary">重置</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="layui-row" style="margin-bottom: 15px;">
                        <div class="layui-col-md6">
                            <span>共找到 <strong><?php echo $total; ?></strong> 个模板</span>
                        </div>
                        <div class="layui-col-md6" style="text-align: right;">
                            <button class="layui-btn layui-btn-sm" onclick="batchAction('enable')">批量启用</button>
                            <button class="layui-btn layui-btn-sm layui-btn-warm" onclick="batchAction('disable')">批量禁用</button>
                            <button class="layui-btn layui-btn-sm layui-btn-danger" onclick="batchAction('delete')">批量删除</button>
                        </div>
                    </div>
                    
                    <?php if (empty($templates)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-template-1"></i>
                            <h3>暂无模板</h3>
                            <p>点击上方"添加模板"按钮创建第一个模板，或者点击"初始化模板表"创建默认模板</p>
                        </div>
                    <?php else: ?>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>ID</th>
                                    <th>模板名称</th>
                                    <th>类型</th>
                                    <th>文件路径</th>
                                    <th>状态</th>
                                    <th>排序</th>
                                    <th>创建时间</th>
                                    <th width="200">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><input type="checkbox" name="template_ids[]" value="<?php echo $template['id']; ?>"></td>
                                    <td><?php echo $template['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($template['name']); ?></strong>
                                            <?php if ($template['description']): ?>
                                                <div style="color: #999; font-size: 12px; margin-top: 5px;">
                                                    <?php echo htmlspecialchars($template['description']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $type_map = [
                                            'index' => '首页模板',
                                            'channel' => '频道模板',
                                            'list' => '列表模板',
                                            'content' => '内容模板'
                                        ];
                                        $type_class = [
                                            'index' => 'layui-bg-blue',
                                            'channel' => 'layui-bg-orange',
                                            'list' => 'layui-bg-green',
                                            'content' => 'layui-bg-red'
                                        ];
                                        ?>
                                        <span class="layui-badge <?php echo $type_class[$template['template_type']] ?? ''; ?>">
                                            <?php echo $type_map[$template['template_type']] ?? $template['template_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($template['file_path']); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($template['is_active']): ?>
                                            <span class="status-badge status-completed">启用</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $template['sort_order']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($template['created_at'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $template['id']; ?>" 
                                           class="layui-btn layui-btn-xs">编辑</a>
                                        <a href="view.php?id=<?php echo $template['id']; ?>" 
                                           class="layui-btn layui-btn-xs layui-btn-normal">查看</a>
                                        <a href="javascript:;" 
                                           onclick="deleteTemplate(<?php echo $template['id']; ?>)"
                                           class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- 分页 -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-wrapper">
                                <div id="pagination"></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'layer', 'laypage', 'form'], function(){
        var element = layui.element;
        var layer = layui.layer;
        var laypage = layui.laypage;
        var form = layui.form;
        
        // 初始化
        element.render();
        form.render();
        
        // 初始化模板表功能
        document.getElementById('initTemplateBtn').addEventListener('click', function() {
            layer.confirm('确定要初始化模板表吗？这将清空现有模板并恢复默认模板。', {
                icon: 3,
                title: '初始化确认'
            }, function(index){
                layer.close(index);
                
                // 显示加载提示
                var loading = layer.load(1, {shade: [0.1,'#fff']});
                
                // 发送AJAX请求
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'init_template_table.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        layer.close(loading);
                        if (xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                layer.msg(response.message, {icon: 1}, function() {
                                    window.location.reload();
                                });
                            } else {
                                layer.msg(response.message, {icon: 2});
                            }
                        } else {
                            layer.msg('请求失败，请稍后重试', {icon: 2});
                        }
                    }
                };
                xhr.send();
            });
        });
        
        // 分页
        <?php if ($total_pages > 1): ?>
        laypage.render({
            elem: 'pagination',
            count: <?php echo $total; ?>,
            limit: <?php echo $per_page; ?>,
            curr: <?php echo $page; ?>,
            jump: function(obj, first) {
                if (!first) {
                    var url = new URL(window.location);
                    url.searchParams.set('page', obj.curr);
                    window.location.href = url.toString();
                }
            }
        });
        <?php endif; ?>
        
        // 全选功能
        document.getElementById('checkAll').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('input[name="template_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
        });
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    
    function deleteTemplate(id) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除这个模板吗？', {
                icon: 3,
                title: '删除确认'
            }, function(index){
                window.location.href = 'delete.php?id=' + id;
                layer.close(index);
            });
        });
    }
    
    function batchAction(action) {
        var checked = document.querySelectorAll('input[name="template_ids[]"]:checked');
        if (checked.length === 0) {
            layui.use('layer', function(){
                layui.layer.msg('请先选择要操作的模板');
            });
            return;
        }
        
        var ids = Array.from(checked).map(cb => cb.value);
        var actionText = {'enable': '启用', 'disable': '禁用', 'delete': '删除'}[action];
        
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要批量' + actionText + '选中的模板吗？', {
                icon: 3,
                title: '批量操作确认'
            }, function(index){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'batch_action.php';
                
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = action;
                form.appendChild(actionInput);
                
                ids.forEach(function(id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'template_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
                layer.close(index);
            });
        });
    }
    </script>
</body>
</html>