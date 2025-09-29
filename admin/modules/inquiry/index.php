<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理搜索和筛选
$search_keyword = $_GET['keyword'] ?? '';
$status = $_GET['status'] ?? '';
$service_type = $_GET['service_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "(name LIKE ? OR company LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

if (!empty($status)) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($service_type)) {
    $where_conditions[] = "service_type LIKE ?";
    $params[] = "%$service_type%";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// 获取询价列表
try {
    // 统计总数
    $count_sql = "SELECT COUNT(*) as total FROM inquiries WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取询价列表
    $sql = "SELECT * FROM inquiries WHERE $where_clause
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    // 为列表查询创建新的参数数组，避免影响统计查询
    $list_params = $params;
    $list_params[] = $per_page;
    $list_params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($list_params);
    $inquiries = $stmt->fetchAll();
    
    // 计算分页信息
    $total_pages = ceil($total / $per_page);
    
    // 获取统计数据
    $stats_sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
                  FROM inquiries";
    $stats_stmt = $db->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
} catch(PDOException $e) {
    $inquiries = [];
    $total = 0;
    $total_pages = 0;
    $stats = ['total_count' => 0, 'pending_count' => 0, 'processing_count' => 0, 'completed_count' => 0, 'today_count' => 0];
}

// 处理成功和错误消息
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// 将错误代码转换为友好的中文提示
if ($error_msg) {
    $error_messages = [
        'invalid_id' => '无效的询价ID，请检查链接是否正确',
        'inquiry_not_found' => '询价不存在或已被删除',
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
    <title>询价管理 - 高光视刻</title>
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
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-blue">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-survey"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['total_count']; ?></div>
                            <div class="admin-card-title">总询价数</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-orange">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-time"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['pending_count']; ?></div>
                            <div class="admin-card-title">待处理</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-green">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-ok-circle"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['completed_count']; ?></div>
                            <div class="admin-card-title">已完成</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-red">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-date"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $stats['today_count']; ?></div>
                            <div class="admin-card-title">今日新增</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>询价管理</h2>
                        <div>
                            <a href="export.php" class="layui-btn layui-btn-normal layui-btn-sm">
                                <i class="layui-icon layui-icon-download-circle"></i> 导出数据
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- 搜索筛选 -->
                <div class="layui-card-body">
                    <form class="layui-form" method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                        <div class="layui-row layui-col-space10">
                            <div class="layui-col-md3">
                                <input type="text" name="keyword" placeholder="搜索姓名、公司、电话、邮箱" 
                                       value="<?php echo htmlspecialchars($search_keyword); ?>" 
                                       class="layui-input">
                            </div>
                            <div class="layui-col-md2">
                                <select name="status">
                                    <option value="">全部状态</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待处理</option>
                                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>处理中</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                                </select>
                            </div>
                            <div class="layui-col-md2">
                                <input type="text" name="service_type" placeholder="服务类型" 
                                       value="<?php echo htmlspecialchars($service_type); ?>" 
                                       class="layui-input">
                            </div>
                            <div class="layui-col-md2">
                                <input type="date" name="date_from" placeholder="开始日期" 
                                       value="<?php echo htmlspecialchars($date_from); ?>" 
                                       class="layui-input">
                            </div>
                            <div class="layui-col-md2">
                                <input type="date" name="date_to" placeholder="结束日期" 
                                       value="<?php echo htmlspecialchars($date_to); ?>" 
                                       class="layui-input">
                            </div>
                            <div class="layui-col-md1">
                                <button type="submit" class="layui-btn">搜索</button>
                            </div>
                        </div>
                        <div class="layui-row" style="margin-top: 10px;">
                            <div class="layui-col-md12">
                                <a href="index.php" class="layui-btn layui-btn-primary layui-btn-sm">重置</a>
                                <a href="pending.php" class="layui-btn layui-btn-warm layui-btn-sm">查看待处理</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="layui-row" style="margin-bottom: 15px;">
                        <div class="layui-col-md6">
                            <span>共找到 <strong><?php echo $total; ?></strong> 条询价</span>
                        </div>
                        <div class="layui-col-md6" style="text-align: right;">
                            <button class="layui-btn layui-btn-sm layui-btn-warm" onclick="batchAction('mark_processing')">批量标记处理中</button>
                            <button class="layui-btn layui-btn-sm" onclick="batchAction('mark_completed')">批量标记完成</button>
                            <button class="layui-btn layui-btn-sm layui-btn-danger" onclick="batchAction('delete')">批量删除</button>
                        </div>
                    </div>
                    
                    <?php if (empty($inquiries)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-survey"></i>
                            <h3>暂无询价</h3>
                            <p>还没有收到任何询价信息</p>
                        </div>
                    <?php else: ?>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>ID</th>
                                    <th>客户信息</th>
                                    <th>服务类型</th>
                                    <th>预算</th>
                                    <th>状态</th>
                                    <th>提交时间</th>
                                    <th width="180">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td><input type="checkbox" name="inquiry_ids[]" value="<?php echo $inquiry['id']; ?>"></td>
                                    <td><?php echo $inquiry['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($inquiry['name']); ?></strong>
                                            <?php if ($inquiry['company']): ?>
                                                <br><small style="color: #666;"><?php echo htmlspecialchars($inquiry['company']); ?></small>
                                            <?php endif; ?>
                                            <br><small style="color: #666;">
                                                <?php echo htmlspecialchars($inquiry['phone']); ?>
                                                <?php if ($inquiry['email']): ?>
                                                    | <?php echo htmlspecialchars($inquiry['email']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($inquiry['service_type']); ?></td>
                                    <td><?php echo $inquiry['budget'] ? htmlspecialchars($inquiry['budget']) : '未填写'; ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'status-pending',
                                            'processing' => 'status-processing', 
                                            'completed' => 'status-completed',
                                            'cancelled' => 'layui-bg-gray'
                                        ][$inquiry['status']] ?? 'layui-bg-gray';
                                        
                                        $status_text = [
                                            'pending' => '待处理',
                                            'processing' => '处理中',
                                            'completed' => '已完成', 
                                            'cancelled' => '已取消'
                                        ][$inquiry['status']] ?? '未知';
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $inquiry['id']; ?>" 
                                           class="layui-btn layui-btn-xs">查看</a>
                                        <a href="edit.php?id=<?php echo $inquiry['id']; ?>" 
                                           class="layui-btn layui-btn-xs layui-btn-normal">处理</a>
                                        <a href="javascript:;" 
                                           onclick="deleteInquiry(<?php echo $inquiry['id']; ?>)"
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
            var checkboxes = document.querySelectorAll('input[name="inquiry_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
        });
        
        // 自动刷新提示
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    
    function deleteInquiry(id) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除这条询价吗？', {
                icon: 3,
                title: '删除确认'
            }, function(index){
                window.location.href = 'delete.php?id=' + id;
                layer.close(index);
            });
        });
    }
    
    function batchAction(action) {
        var checked = document.querySelectorAll('input[name="inquiry_ids[]"]:checked');
        if (checked.length === 0) {
            layui.use('layer', function(){
                layui.layer.msg('请先选择要操作的询价');
            });
            return;
        }
        
        var ids = Array.from(checked).map(cb => cb.value);
        var actionText = {'mark_processing': '标记为处理中', 'mark_completed': '标记为完成', 'delete': '删除'}[action];
        
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要批量' + actionText + '选中的询价吗？', {
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
                    input.name = 'inquiry_ids[]';
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