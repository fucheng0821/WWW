<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理搜索和筛选
$search_keyword = $_GET['keyword'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$parent_category_id = $_GET['parent_category_id'] ?? ''; // 新增一级栏目筛选
$is_published = $_GET['is_published'] ?? '';
$is_featured = $_GET['is_featured'] ?? '';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "(c.title LIKE ? OR c.summary LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

// 根据是否选择了一级栏目来构建查询条件
if (!empty($parent_category_id)) {
    // 如果选择了一级栏目，则筛选该一级栏目及其子栏目
    if (!empty($category_id)) {
        // 如果同时选择了二级栏目，则只筛选该二级栏目
        $where_conditions[] = "c.category_id = ?";
        $params[] = $category_id;
    } else {
        // 如果只选择了一级栏目，则筛选该一级栏目及其所有子栏目
        $where_conditions[] = "(cat.id = ? OR cat.parent_id = ?)";
        $params[] = $parent_category_id;
        $params[] = $parent_category_id;
    }
} else if (!empty($category_id)) {
    // 如果没有选择一级栏目但选择了具体栏目，则直接筛选该栏目
    $where_conditions[] = "c.category_id = ?";
    $params[] = $category_id;
}

if ($is_published !== '') {
    $where_conditions[] = "c.is_published = ?";
    $params[] = intval($is_published);
}

if ($is_featured !== '') {
    $where_conditions[] = "c.is_featured = ?";
    $params[] = intval($is_featured);
}

$where_clause = implode(' AND ', $where_conditions);

// 获取内容列表
try {
    // 统计总数
    $count_sql = "SELECT COUNT(*) as total FROM contents c 
                  LEFT JOIN categories cat ON c.category_id = cat.id 
                  WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取内容列表
    $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug, cat.parent_id as parent_category_id
            FROM contents c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            WHERE $where_clause
            ORDER BY c.sort_order DESC, c.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $contents = $stmt->fetchAll();
    
    // 计算分页信息
    $total_pages = ceil($total / $per_page);
    
} catch(PDOException $e) {
    $contents = [];
    $total = 0;
    $total_pages = 0;
}

// 获取栏目列表 - 区分一级栏目和二级栏目
try {
    // 获取所有一级栏目 (parent_id = 0)
    $parent_categories_stmt = $db->query("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC");
    $parent_categories = $parent_categories_stmt->fetchAll();
    
    // 获取所有二级栏目
    $sub_categories_stmt = $db->query("SELECT id, name, parent_id FROM categories WHERE parent_id > 0 ORDER BY parent_id, sort_order ASC");
    $sub_categories = $sub_categories_stmt->fetchAll();
    
    // 将二级栏目按父栏目ID分组
    $sub_categories_by_parent = [];
    foreach ($sub_categories as $sub_cat) {
        if (!isset($sub_categories_by_parent[$sub_cat['parent_id']])) {
            $sub_categories_by_parent[$sub_cat['parent_id']] = [];
        }
        $sub_categories_by_parent[$sub_cat['parent_id']][] = $sub_cat;
    }
    
    // 获取所有栏目（用于原有功能兼容）
    $all_categories_stmt = $db->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id ASC, sort_order ASC");
    $all_categories = $all_categories_stmt->fetchAll();
    
} catch(PDOException $e) {
    $parent_categories = [];
    $sub_categories = [];
    $sub_categories_by_parent = [];
    $all_categories = [];
}

// 处理成功和错误消息
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// 将错误代码转换为友好的中文提示
if ($error_msg) {
    $error_messages = [
        'invalid_id' => '无效的内容ID，请检查链接是否正确',
        'invalid_ids' => '无效的内容ID列表，请选择有效的内容',
        'content_not_found' => '内容不存在或已被删除',
        'permission_denied' => '权限不足，无法执行此操作',
        'database_error' => '数据库操作失败，请稍后重试'
    ];
    
    $error_msg = $error_messages[$error_msg] ?? $error_msg;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>内容管理 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
    <style>
        .editor-group {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .editor-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }
        #editor-panel {
            transition: all 0.3s ease;
        }
        .editor-group h5 {
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .editor-group p {
            margin: 0 0 10px 0;
            line-height: 1.4;
        }
        .layui-btn-xs {
            font-size: 11px;
            padding: 2px 6px;
        }
    </style>
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
            
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>内容管理</h2>
                        <div>
                            <button class="layui-btn layui-btn-normal" id="add-content-btn">
                                <i class="layui-icon layui-icon-add-1"></i> 添加内容
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 搜索筛选 -->
                <div class="layui-card-body">
                    <form class="layui-form" method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                        <div class="layui-row layui-col-space10">
                            <div class="layui-col-md3">
                                <input type="text" name="keyword" placeholder="搜索标题或摘要" 
                                       value="<?php echo htmlspecialchars($search_keyword); ?>" 
                                       class="layui-input">
                            </div>
                            <div class="layui-col-md2">
                                <!-- 一级栏目下拉框 -->
                                <select name="parent_category_id" id="parent_category_id">
                                    <option value="">全部一级栏目</option>
                                    <?php foreach ($parent_categories as $parent_cat): ?>
                                        <option value="<?php echo $parent_cat['id']; ?>" 
                                                <?php echo $parent_category_id == $parent_cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($parent_cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="layui-col-md2">
                                <!-- 二级栏目下拉框 -->
                                <select name="category_id" id="category_id">
                                    <option value="">全部二级栏目</option>
                                    <?php if (!empty($parent_category_id)): ?>
                                        <?php if (isset($sub_categories_by_parent[$parent_category_id])): ?>
                                            <?php foreach ($sub_categories_by_parent[$parent_category_id] as $sub_cat): ?>
                                                <option value="<?php echo $sub_cat['id']; ?>" 
                                                        <?php echo $category_id == $sub_cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sub_cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- 如果没有选择一级栏目，显示所有二级栏目 -->
                                        <?php foreach ($sub_categories as $sub_cat): ?>
                                            <option value="<?php echo $sub_cat['id']; ?>" 
                                                    <?php echo $category_id == $sub_cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sub_cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="layui-col-md2">
                                <select name="is_published">
                                    <option value="">全部状态</option>
                                    <option value="1" <?php echo $is_published === '1' ? 'selected' : ''; ?>>已发布</option>
                                    <option value="0" <?php echo $is_published === '0' ? 'selected' : ''; ?>>未发布</option>
                                </select>
                            </div>
                            <div class="layui-col-md2">
                                <select name="is_featured">
                                    <option value="">全部类型</option>
                                    <option value="1" <?php echo $is_featured === '1' ? 'selected' : ''; ?>>推荐</option>
                                    <option value="0" <?php echo $is_featured === '0' ? 'selected' : ''; ?>>普通</option>
                                </select>
                            </div>
                            <div class="layui-col-md3">
                                <button type="submit" class="layui-btn">搜索</button>
                                <a href="index.php" class="layui-btn layui-btn-primary">重置</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="layui-row" style="margin-bottom: 15px;">
                        <div class="layui-col-md6">
                            <span>共找到 <strong><?php echo $total; ?></strong> 条内容</span>
                        </div>
                        <div class="layui-col-md6" style="text-align: right;">
                                <button class="layui-btn layui-btn-sm" onclick="batchAction('publish')">批量发布</button>
                                <button class="layui-btn layui-btn-sm layui-btn-warm" onclick="batchAction('unpublish')">批量下架</button>
                                <button class="layui-btn layui-btn-sm layui-btn-primary" onclick="batchMatrixPublish()">批量发布矩阵</button>
                                <button class="layui-btn layui-btn-sm layui-btn-danger" onclick="batchAction('delete')">批量删除</button>
                            </div>
                    </div>
                    
                    <?php if (empty($contents)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-file"></i>
                            <h3>暂无内容</h3>
                            <p>点击上方"添加内容"按钮创建第一个内容</p>
                        </div>
                    <?php else: ?>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>ID</th>
                                    <th>标题</th>
                                    <th>栏目</th>
                                    <th width="70">浏览量</th>
                                    <th width="70">状态</th>
                                    <th width="70">推荐</th>
                                    <th width="70">排序</th>
                                    <th>发布时间</th>
                                    <th width="260">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contents as $content): ?>
                                <tr>
                                    <td><input type="checkbox" name="content_ids[]" value="<?php echo $content['id']; ?>"></td>
                                    <td><?php echo $content['id']; ?></td>
                                    <td>
                                        <div style="max-width: 200px;">
                                            <strong><?php echo htmlspecialchars($content['title']); ?></strong>
                                            <?php if ($content['summary']): ?>
                                                <div style="color: #999; font-size: 12px; margin-top: 5px;">
                                                    <?php echo htmlspecialchars(mb_substr($content['summary'], 0, 50, 'UTF-8')) . '...'; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($content['category_name'] ?? '未分类'); ?></td>
                                    <td><?php echo $content['view_count']; ?></td>
                                    <td>
                                        <?php if ($content['is_published']): ?>
                                            <span class="status-badge status-completed">已发布</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">未发布</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($content['is_featured']): ?>
                                            <span class="layui-badge layui-bg-orange">推荐</span>
                                        <?php else: ?>
                                            <span class="layui-badge">普通</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $content['sort_order']; ?></td>
                                    <td><?php echo $content['published_at'] ? date('Y-m-d', strtotime($content['published_at'])) : '未发布'; ?></td>
                                    <td>
                                        <div style="display: flex; flex-wrap: nowrap; gap: 5px;">
                                            <a href="edit.php?id=<?php echo $content['id']; ?>" 
                                               class="layui-btn layui-btn-xs">编辑</a>
                                            <?php 
                                                // 生成正确的预览URL
                                                $preview_url = '../../../';
                                                if (!empty($content['category_slug'])) {
                                                    $preview_url .= $content['category_slug'] . '/' . $content['slug'] . '.html';
                                                } else {
                                                    $preview_url .= $content['slug'] . '.html';
                                                }
                                            ?>
                                            <a href="<?php echo $preview_url; ?>" target="_blank"
                                               class="layui-btn layui-btn-xs layui-btn-normal">预览</a>
                                            <a href="javascript:;" 
                                               onclick="matrixPublish(<?php echo $content['id']; ?>)"
                                               class="layui-btn layui-btn-xs layui-btn-warm">一键发布</a>
                                            <a href="javascript:;" 
                                               onclick="deleteContent(<?php echo $content['id']; ?>)"
                                               class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                                        </div>
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
        
        // 一级栏目选择变化时，更新二级栏目下拉框
        document.getElementById('parent_category_id').addEventListener('change', function() {
            var parentId = this.value;
            var subCategorySelect = document.getElementById('category_id');
            
            // 清空二级栏目选项
            subCategorySelect.innerHTML = '<option value="">全部二级栏目</option>';
            
            // 如果选择了一级栏目，则加载对应的二级栏目
            if (parentId) {
                // 这里需要通过AJAX获取二级栏目数据
                // 为了简化，我们使用页面已有的数据
                var subCategoriesByParent = <?php echo json_encode($sub_categories_by_parent); ?>;
                
                if (subCategoriesByParent[parentId]) {
                    subCategoriesByParent[parentId].forEach(function(subCat) {
                        var option = document.createElement('option');
                        option.value = subCat.id;
                        option.textContent = subCat.name;
                        subCategorySelect.appendChild(option);
                    });
                }
            } else {
                // 如果没有选择一级栏目，显示所有二级栏目
                var allSubCategories = <?php echo json_encode($sub_categories); ?>;
                allSubCategories.forEach(function(subCat) {
                    var option = document.createElement('option');
                    option.value = subCat.id;
                    option.textContent = subCat.name;
                    subCategorySelect.appendChild(option);
                });
            }
            
            // 重新渲染layui表单
            form.render('select');
        });
        
        // 添加内容按钮 - 使用自定义编辑器
        document.getElementById('add-content-btn').addEventListener('click', function() {
            window.location.href = 'add_custom.php';
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
            var checkboxes = document.querySelectorAll('input[name="content_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
        });
    });
    
    function deleteContent(id) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除这个内容吗？', {
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
    
    function batchAction(action) {
        var checked = document.querySelectorAll('input[name="content_ids[]"]:checked');
        if (checked.length === 0) {
            layui.use('layer', function(){layui.layer.msg('请先选择要操作的内容');});
            return;
        }
        
        var ids = Array.from(checked).map(cb => cb.value);
        var actionText = {'publish': '发布', 'unpublish': '下架', 'delete': '删除'}[action];
        
        layui.use('layer', function(){var layer = layui.layer;
            layer.confirm('确定要批量' + actionText + '选中的内容吗？', {icon: 3, title: '批量操作确认'}, function(index){
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
                    input.name = 'content_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
                layer.close(index);
            });
        });
    }
    
    // 一键发布到矩阵
    function matrixPublish(contentId) {
        layui.use(['layer', 'form', 'jquery'], function(){var layer = layui.layer; var form = layui.form; var $ = layui.jquery;
            
            // 从后端获取可用平台数据
            $.ajax({
                url: '../system/get_platforms.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var platforms = data.code === 0 ? data.data : [];
                    
                    // 检查是否有可用平台
                    if (platforms.length === 0) {
                        layer.alert('暂无可用的发布平台，请先在"平台配置管理"中配置并启用平台。', {
                            icon: 2,
                            title: '提示'
                        });
                        return;
                    }
                    
                    // 构建平台选择HTML
                    var platformHtml = '';
                    platforms.forEach(function(platform) {
                        var statusText = platform.config_complete ? '' : ' (配置未完成)';
                        var disabled = platform.config_complete ? '' : 'disabled';
                        platformHtml += '<div class="layui-form-item" style="margin-bottom: 10px;">' +
                                       '    <input type="checkbox" name="platforms[]" value="' + platform.key + '" ' + (platform.checked ? 'checked' : '') +
                                       '           lay-skin="primary" lay-text="' + platform.name + statusText + '" ' + disabled + '>' +
                                       '</div>';
                    });
                    
                    // 打开平台选择弹窗
                    layer.open({
                        type: 1,
                        title: '选择发布平台',
                        area: ['400px', '450px'],
                        content: '<div style="padding: 20px;">' +
                                 '    <form class="layui-form" id="publish-form">' +
                                 '        <div class="layui-form-item">' +
                                 '            <label class="layui-form-label">发布内容ID</label>' +
                                 '            <div class="layui-input-block">' +
                                 '                <input type="text" value="' + contentId + '" disabled class="layui-input">' +
                                 '            </div>' +
                                 '        </div>' +
                                 '        <div class="layui-form-item">' +
                                 '            <label class="layui-form-label">发布平台</label>' +
                                 '            <div class="layui-input-block">' +
                                 platformHtml +
                                 '            </div>' +
                                 '        </div>' +
                                 '        <div class="layui-form-item">' +
                                 '            <div class="layui-input-block">' +
                                 '                <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>' +
                                 '                <button type="button" class="layui-btn layui-btn-normal" id="publish-btn">确认发布</button>' +
                                 '            </div>' +
                                 '        </div>' +
                                 '    </form>' +
                                 '</div>',
                        success: function(layero, index) {
                            form.render();
                            
                            // 绑定发布按钮事件
                            $('#publish-btn').on('click', function() {
                                var selectedPlatforms = [];
                                $('input[name="platforms[]"]:checked').each(function() {
                                    selectedPlatforms.push($(this).val());
                                });
                                
                                if (selectedPlatforms.length === 0) {
                                    layer.msg('请至少选择一个发布平台', {icon: 2});
                                    return;
                                }
                                
                                // 发布到选中的平台
                                $.ajax({
                                    url: '../content/matrix_publish_handler.php',
                                    type: 'POST',
                                    data: {
                                        content_id: contentId,
                                        platforms: selectedPlatforms
                                    },
                                    dataType: 'json',
                                    beforeSend: function() {
                                        layer.load(2, {shade: [0.3, '#000']});
                                    },
                                    complete: function() {
                                        layer.closeAll('loading');
                                    },
                                    success: function(res) {
                                        if (res.code === 0) {
                                            // 构建详细的发布结果HTML
                                            var resultHtml = '<div style="padding: 10px; max-height: 300px; overflow-y: auto;">';
                                            resultHtml += '<p>' + res.message + '</p>';
                                            resultHtml += '<table class="layui-table" style="margin-top: 10px;">';
                                            resultHtml += '<thead><tr><th>平台</th><th>状态</th><th>消息</th></tr></thead><tbody>';
                                            
                                            res.results.forEach(function(result) {
                                                var statusText = result.success ? '<span style="color: green;">成功</span>' : '<span style="color: red;">失败</span>';
                                                var message = result.message || (result.success ? '发布成功' : '发布失败');
                                                resultHtml += '<tr>';
                                                resultHtml += '<td>' + result.platform_name + '</td>';
                                                resultHtml += '<td>' + statusText + '</td>';
                                                resultHtml += '<td>' + message + '</td>';
                                                resultHtml += '</tr>';
                                            });
                                            
                                            resultHtml += '</tbody></table></div>';
                                            
                                            layer.alert(resultHtml, {
                                                icon: 1,
                                                title: '发布完成',
                                                area: ['500px', '400px']
                                            }, function() {
                                                layer.closeAll();
                                            });
                                        } else {
                                            layer.alert(res.message, {
                                                icon: 2,
                                                title: '发布失败'
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('发布请求失败:', xhr, status, error);
                                        var errorMessage = '网络错误，请稍后重试';
                                        if (xhr.responseText) {
                                            try {
                                                var response = JSON.parse(xhr.responseText);
                                                if (response.message) {
                                                    errorMessage = response.message;
                                                }
                                            } catch (e) {
                                                errorMessage = xhr.responseText;
                                            }
                                        }
                                        layer.alert(errorMessage, {
                                            icon: 2,
                                            title: '发布失败'
                                        });
                                    }
                                });
                            });
                        }
                    });
                },
                error: function() {
                    layer.alert('获取平台数据失败，请稍后重试', {
                        icon: 2,
                        title: '错误'
                    });
                }
            });
        });
    }
    
    // 批量发布矩阵
    function batchMatrixPublish() {
        var checked = document.querySelectorAll('input[name="content_ids[]"]:checked');
        if (checked.length === 0) {
            layui.use('layer', function(){layui.layer.msg('请先选择要操作的内容');});
            return;
        }
        
        var ids = Array.from(checked).map(cb => cb.value);
        
        layui.use(['layer', 'form', 'jquery'], function(){var layer = layui.layer; var form = layui.form; var $ = layui.jquery;
            
            // 从后端获取可用平台数据
            $.ajax({
                url: '../system/get_platforms.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var platforms = data.code === 0 ? data.data : [
                        {key: 'douyin', name: '抖音', checked: true},
                        {key: 'kuaishou', name: '快手', checked: true},
                        {key: 'xiaohongshu', name: '小红书', checked: true},
                        {key: 'wechat', name: '微信公众号', checked: true},
                        {key: 'toutiao', name: '头条号', checked: true},
                        {key: 'baidu', name: '百家号', checked: true},
                        {key: 'zhihu', name: '知乎', checked: true},
                        {key: 'bilibili', name: 'B站', checked: true}
                    ];
                    
                    // 构建平台选择HTML
                    var platformHtml = '';
                    platforms.forEach(function(platform) {
                        platformHtml += '<div class="layui-form-item" style="margin-bottom: 10px;">' +
                                       '    <input type="checkbox" name="platforms[]" value="' + platform.key + '" ' + (platform.checked ? 'checked' : '') +
                                       '           lay-skin="primary" lay-text="' + platform.name + '">' +
                                       '</div>';
                    });
                    
                    // 打开平台选择弹窗
                    layer.open({
                        type: 1,
                        title: '批量发布到矩阵',
                        area: ['400px', '450px'],
                        content: '<div style="padding: 20px;">' +
                                 '    <form class="layui-form" id="batch-publish-form">' +
                                 '        <div class="layui-form-item">' +
                                 '            <label class="layui-form-label">已选择</label>' +
                                 '            <div class="layui-input-block">' +
                                 '                <input type="text" value="' + checked.length + '条内容" disabled class="layui-input">' +
                                 '            </div>' +
                                 '        </div>' +
                                 '        <div class="layui-form-item">' +
                                 '            <label class="layui-form-label">发布类型</label>' +
                                 '            <div class="layui-input-block">' +
                                 '                <select name="publish_type">' +
                                 '                    <option value="auto">自动（根据内容类型）</option>' +
                                 '                    <option value="article">文章</option>' +
                                 '                    <option value="video">视频</option>' +
                                 '                </select>' +
                                 '            </div>' +
                                 '        </div>' +
                                 '        <div class="layui-form-item">' +
                                 '            <label class="layui-form-label">选择平台</label>' +
                                 '            <div class="layui-input-block">' + platformHtml + '</div>' +
                                 '        </div>' +
                                 '    </form>' +
                                 '</div>',
                        btn: ['立即发布', '取消'],
                        btnAlign: 'c',
                        success: function(layero) {
                            // 渲染表单
                            form.render();
                        },
                        yes: function(index, layero) {
                            // 获取选择的平台
                            var selectedPlatforms = [];
                            layero.find('input[name="platforms[]"]:checked').each(function() {
                                selectedPlatforms.push($(this).val());
                            });
                            
                            if (selectedPlatforms.length === 0) {
                                layer.msg('请至少选择一个发布平台');
                                return;
                            }
                            
                            // 获取发布类型
                            var publish_type = layero.find('select[name="publish_type"]').val();
                            
                            // 显示加载中
                            var loadingIndex = layer.load(2, {shade: [0.3, '#000']});
                            
                            // 实际批量发布请求
                            $.ajax({
                                url: '../system/process_publish_request.php',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({
                                    content_id: ids,
                                    platforms: selectedPlatforms,
                                    publish_type: publish_type,
                                    batch: true
                                }),
                                success: function(response) {
                                    layer.close(loadingIndex);
                                    layer.close(index);
                                    
                                    if (response.code === 0) {
                                        // 显示发布成功消息
                                        layer.alert(response.message, {
                                            icon: 6,
                                            title: '发布任务已提交'
                                        }, function() {
                                            // 跳转查看发布日志
                                            window.location.href = '../system/publish_logs.php';
                                        });
                                    } else {
                                        layer.alert(response.message || '批量发布失败，请稍后重试', {
                                            icon: 5,
                                            title: '发布失败'
                                        });
                                    }
                                },
                                error: function() {
                                    layer.close(loadingIndex);
                                    layer.alert('网络错误，请稍后重试', {
                                        icon: 5,
                                        title: '发布失败'
                                    });
                                }
                            });
                        }
                    });
                },
                error: function() {
                    layer.msg('获取平台数据失败，使用默认平台列表');
                    // 这里可以添加使用默认平台列表的逻辑
                }
            });
        });
    }
    </script>
</body>
</html>