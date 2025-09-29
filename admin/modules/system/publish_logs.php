<?php
/**
 * 内容发布日志管理页面
 * 用于查看和管理各平台的内容发布记录
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 会话初始化
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    header('Location: ../../login.php');
    exit;
}

// 引入配置文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/class/PlatformManager.php';

// 检查管理员权限
check_admin_auth();

// 初始化平台管理器
$platform_manager = new PlatformManager($db);

// 获取所有平台配置
$platforms = $platform_manager->getAllPlatforms();

// 初始化过滤器
$filter = [
    'content_id' => $_GET['content_id'] ?? '',
    'platform_key' => $_GET['platform_key'] ?? '',
    'status' => $_GET['status'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'page' => (int)($_GET['page'] ?? 1),
    'limit' => 20
];

// 获取发布日志
$logs = $platform_manager->getPublishLogs($filter);
$total = $platform_manager->getPublishLogsCount($filter);
$total_pages = ceil($total / $filter['limit']);

// 状态映射
$status_map = [
    'pending' => '待发布',
    'publishing' => '发布中',
    'success' => '发布成功',
    'failed' => '发布失败'
];

// 平台名称映射
$platform_name_map = [];
foreach ($platforms as $platform) {
    $platform_name_map[$platform['platform_key']] = $platform['platform_name'];
}
?> 
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>内容发布日志 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <style>
        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-col {
            flex: 1;
            min-width: 150px;
        }
        .status-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-publishing {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .log-actions {
            display: flex;
            gap: 5px;
        }
        .log-actions button {
            padding: 0 10px;
            font-size: 12px;
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
                    <h3>内容发布日志</h3>
                    <p class="text-muted">查看和管理各平台的内容发布记录和状态</p>
                </div>
                <div class="layui-card-body">
                    
                    <!-- 筛选表单 -->
                    <form class="filter-form layui-form" method="get" action="publish_logs.php">
                        <div class="filter-row">
                            <div class="filter-col">
                                <label class="layui-form-label">内容ID</label>
                                <div class="layui-input-block">
                                    <input type="text" name="content_id" value="<?php echo htmlspecialchars($filter['content_id']); ?>" 
                                           class="layui-input" placeholder="请输入内容ID">
                                </div>
                            </div>
                            
                            <div class="filter-col">
                                <label class="layui-form-label">发布平台</label>
                                <div class="layui-input-block">
                                    <select name="platform_key" class="layui-select">
                                        <option value="">全部平台</option>
                                        <?php foreach ($platforms as $platform): ?>
                                            <option value="<?php echo htmlspecialchars($platform['platform_key']); ?>" 
                                                    <?php echo ($filter['platform_key'] === $platform['platform_key']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($platform['platform_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="filter-col">
                                <label class="layui-form-label">发布状态</label>
                                <div class="layui-input-block">
                                    <select name="status" class="layui-select">
                                        <option value="">全部状态</option>
                                        <?php foreach ($status_map as $key => $value): ?>
                                            <option value="<?php echo htmlspecialchars($key); ?>" 
                                                    <?php echo ($filter['status'] === $key) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($value); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="filter-row" style="margin-top: 10px;">
                            <div class="filter-col">
                                <label class="layui-form-label">开始日期</label>
                                <div class="layui-input-block">
                                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($filter['start_date']); ?>" 
                                           class="layui-input">
                                </div>
                            </div>
                            
                            <div class="filter-col">
                                <label class="layui-form-label">结束日期</label>
                                <div class="layui-input-block">
                                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($filter['end_date']); ?>" 
                                           class="layui-input">
                                </div>
                            </div>
                            
                            <div class="filter-col" style="display: flex; align-items: flex-end; gap: 10px;">
                                <button type="submit" class="layui-btn layui-btn-normal">查询</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="clearFilters()">清空</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- 日志列表 -->
                    <table class="layui-table" lay-size="sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>内容ID</th>
                                <th>发布平台</th>
                                <th>发布状态</th>
                                <th>发布时间</th>
                                <th>完成时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                                        <td>
                                            <a href="../../modules/content/edit.php?id=<?php echo htmlspecialchars($log['content_id']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($log['content_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($platform_name_map[$log['platform_key']] ?? $log['platform_key']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($log['status']); ?>">
                                                <?php echo htmlspecialchars($status_map[$log['status']] ?? $log['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($log['completed_at']); ?></td>
                                        <td>
                                            <div class="log-actions">
                                                <button class="layui-btn layui-btn-xs" onclick="viewLogDetail(<?php echo htmlspecialchars($log['id']); ?>)">
                                                    查看详情
                                                </button>
                                                <?php if ($log['status'] === 'failed'): ?>
                                                    <button class="layui-btn layui-btn-xs layui-btn-warm" onclick="retryPublish(<?php echo htmlspecialchars($log['id']); ?>, <?php echo htmlspecialchars($log['content_id']); ?>, '<?php echo htmlspecialchars($log['platform_key']); ?>')">
                                                        重试发布
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px;">暂无发布日志</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- 分页 -->
                    <?php if ($total > $filter['limit']): ?>
                        <div class="layui-box layui-laypage layui-laypage-default">
                            <div id="page"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (file_exists(BASE_DIR . '/admin/includes/footer.php')): ?>
            <?php include BASE_DIR . '/admin/includes/footer.php'; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
        layui.use(['form', 'layer', 'laypage'], function() {
            var form = layui.form;
            var layer = layui.layer;
            var laypage = layui.laypage;
            
            // 渲染分页
            <?php if ($total > $filter['limit']): ?>
                laypage.render({
                    elem: 'page',
                    count: <?php echo htmlspecialchars($total); ?>,
                    limit: <?php echo htmlspecialchars($filter['limit']); ?>,
                    curr: <?php echo htmlspecialchars($filter['page']); ?>,
                    layout: ['prev', 'page', 'next', 'skip', 'count'],
                    jump: function(obj, first) {
                        if (!first) {
                            var url = window.location.pathname + '?page=' + obj.curr;
                            
                            // 添加其他过滤器参数
                            <?php if (!empty($filter['content_id'])): ?>
                                url += '&content_id=<?php echo htmlspecialchars($filter['content_id']); ?>';
                            <?php endif; ?>
                            
                            <?php if (!empty($filter['platform_key'])): ?>
                                url += '&platform_key=<?php echo htmlspecialchars($filter['platform_key']); ?>';
                            <?php endif; ?>
                            
                            <?php if (!empty($filter['status'])): ?>
                                url += '&status=<?php echo htmlspecialchars($filter['status']); ?>';
                            <?php endif; ?>
                            
                            <?php if (!empty($filter['start_date'])): ?>
                                url += '&start_date=<?php echo htmlspecialchars($filter['start_date']); ?>';
                            <?php endif; ?>
                            
                            <?php if (!empty($filter['end_date'])): ?>
                                url += '&end_date=<?php echo htmlspecialchars($filter['end_date']); ?>';
                            <?php endif; ?>
                            
                            window.location.href = url;
                        }
                    }
                });
            <?php endif; ?>
        });
        
        // 清空过滤器
        function clearFilters() {
            window.location.href = 'publish_logs.php';
        }
        
        // 查看日志详情
        function viewLogDetail(logId) {
            // 显示加载状态
            var loadingIndex = layer.load(2, {
                shade: [0.3, '#000']
            });
            
            // 发送AJAX请求获取日志详情
            $.ajax({
                url: '/admin/modules/system/get_publish_log_detail.php',
                type: 'POST',
                data: { log_id: logId },
                dataType: 'json',
                success: function(data) {
                    // 关闭加载动画
                    layer.close(loadingIndex);
                    
                    if (data.success && data.log) {
                        const log = data.log;
                        
                        // 构建详情HTML
                        let html = `
                            <div class="detail-container">
                                <div class="detail-item">
                                    <label>内容标题:</label>
                                    <span>${log.content_title || '-'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>内容ID:</label>
                                    <span>${log.content_id}</span>
                                </div>
                                <div class="detail-item">
                                    <label>发布平台:</label>
                                    <span>${log.platform_name || log.platform_key}</span>
                                </div>
                                <div class="detail-item">
                                    <label>发布类型:</label>
                                    <span>${log.publish_type || 'auto'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>发布时间:</label>
                                    <span>${log.created_at}</span>
                                </div>
                                <div class="detail-item">
                                    <label>最后更新:</label>
                                    <span>${log.updated_at}</span>
                                </div>
                                <div class="detail-item">
                                    <label>发布状态:</label>
                                    <span class="status-${log.status}">${log.status === 'success' ? '成功' : log.status === 'failed' ? '失败' : log.status === 'pending' ? '待处理' : '处理中'}</span>
                                </div>
                                <div class="detail-item">
                                    <label>响应信息:</label>
                                    <span>${log.error_message || '无'}</span>
                                </div>
                            `;
                            
                            // 如果有平台响应数据，添加到详情中
                            if (log.response_data) {
                                try {
                                    const responseData = JSON.parse(log.response_data);
                                    html += `
                                        <div class="detail-section">
                                            <h4>平台响应详情:</h4>
                                            <pre>${JSON.stringify(responseData, null, 2)}</pre>
                                        </div>
                                    `;
                                } catch (e) {
                                    html += `
                                        <div class="detail-section">
                                            <h4>平台响应详情:</h4>
                                            <pre>${log.response_data}</pre>
                                        </div>
                                    `;
                                }
                            }
                            
                            html += '</div>';
                            
                            // 显示详情弹窗
                            layer.open({
                                type: 1,
                                title: '发布详情',
                                area: ['800px', '500px'],
                                content: html,
                                btn: ['关闭'],
                                btnAlign: 'c'
                            });
                    } else {
                        layer.msg(data.message || '获取日志详情失败', {icon: 2});
                    }
                },
                error: function() {
                    layer.close(loadingIndex);
                    layer.msg('网络请求失败，请重试', {icon: 2});
                }
            });
        }
        
        // 重试发布
        function retryPublish(logId, contentId, platformKey) {
            layer.confirm('确定要重试发布此内容到"' + getPlatformName(platformKey) + '"吗？', {
                title: '确认重试',
                icon: 3
            }, function(index) {
                layer.close(index);
                
                // 显示加载中
                var loadingIndex = layer.load(2, {
                    shade: [0.3, '#000']
                });
                
                // 发送AJAX请求重试发布
                $.ajax({
                    url: '/admin/modules/system/retry_publish_task.php',
                    type: 'POST',
                    data: { log_id: logId },
                    dataType: 'json',
                    success: function(data) {
                        // 关闭加载动画
                        layer.close(loadingIndex);
                        
                        if (data.success) {
                            layer.alert(data.message || '发布任务已提交，请稍后刷新页面查看状态', {
                                icon: 6,
                                title: '操作成功'
                            }, function() {
                                // 刷新页面
                                location.reload();
                            });
                        } else {
                            layer.msg(data.message || '重试发布失败', {icon: 2});
                        }
                    },
                    error: function() {
                        layer.close(loadingIndex);
                        layer.msg('网络请求失败，请重试', {icon: 2});
                    }
                });
            });
        }
        
        // 获取平台名称
        function getPlatformName(platformKey) {
            var platformMap = <?php echo json_encode($platform_name_map); ?>;
            return platformMap[platformKey] || platformKey;
        }
    </script>
</body>
</html>