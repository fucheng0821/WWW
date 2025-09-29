<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

check_admin_auth();

// 获取系统统计信息
try {
    $stats = [];
    
    // 栏目总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM categories");
    $stmt->execute();
    $stats['categories'] = $stmt->fetch()['total'];
    
    // 内容总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contents");
    $stmt->execute();
    $stats['contents'] = $stmt->fetch()['total'];
    
    // 已发布内容数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contents WHERE is_published = 1");
    $stmt->execute();
    $stats['published_contents'] = $stmt->fetch()['total'];
    
    // 询价总数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inquiries");
    $stmt->execute();
    $stats['inquiries'] = $stmt->fetch()['total'];
    
    // 待处理询价数
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inquiries WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_inquiries'] = $stmt->fetch()['total'];
    
    // 今日新增内容
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM contents WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['today_contents'] = $stmt->fetch()['total'];
    
    // 今日新增询价
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inquiries WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['today_inquiries'] = $stmt->fetch()['total'];
    
    // 最新内容
    $stmt = $db->prepare("SELECT c.id, c.title, c.created_at, cat.name as category_name 
                          FROM contents c 
                          LEFT JOIN categories cat ON c.category_id = cat.id 
                          ORDER BY c.created_at DESC LIMIT 5");
    $stmt->execute();
    $latest_contents = $stmt->fetchAll();
    
    // 最新询价
    $stmt = $db->prepare("SELECT id, name, service_type, created_at, status FROM inquiries ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $latest_inquiries = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $stats = [];
    $latest_contents = [];
    $latest_inquiries = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="assets/css/admin-optimized.css">
    <script src="assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card">
                <div class="layui-card-header">
                    <h2>管理后台概览</h2>
                </div>
                <div class="layui-card-body">
                    <!-- 统计卡片 -->
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-md3">
                            <div class="admin-card admin-card-blue">
                                <div class="admin-card-icon">
                                    <i class="layui-icon layui-icon-template-1"></i>
                                </div>
                                <div class="admin-card-content">
                                    <div class="admin-card-number"><?php echo $stats['categories'] ?? 0; ?></div>
                                    <div class="admin-card-title">栏目总数</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-col-md3">
                            <div class="admin-card admin-card-green">
                                <div class="admin-card-icon">
                                    <i class="layui-icon layui-icon-file"></i>
                                </div>
                                <div class="admin-card-content">
                                    <div class="admin-card-number"><?php echo $stats['contents'] ?? 0; ?></div>
                                    <div class="admin-card-title">内容总数</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-col-md3">
                            <div class="admin-card admin-card-orange">
                                <div class="admin-card-icon">
                                    <i class="layui-icon layui-icon-survey"></i>
                                </div>
                                <div class="admin-card-content">
                                    <div class="admin-card-number"><?php echo $stats['inquiries'] ?? 0; ?></div>
                                    <div class="admin-card-title">询价总数</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-col-md3">
                            <div class="admin-card admin-card-red">
                                <div class="admin-card-icon">
                                    <i class="layui-icon layui-icon-notice"></i>
                                </div>
                                <div class="admin-card-content">
                                    <div class="admin-card-number"><?php echo $stats['pending_inquiries'] ?? 0; ?></div>
                                    <div class="admin-card-title">待处理询价</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 今日数据 -->
                    <div class="layui-row layui-col-space15" style="margin-top: 20px;">
                        <div class="layui-col-md6">
                            <div class="layui-card">
                                <div class="layui-card-header">今日数据</div>
                                <div class="layui-card-body">
                                    <div class="today-stats">
                                        <div class="today-item">
                                            <span class="today-label">新增内容：</span>
                                            <span class="today-value"><?php echo $stats['today_contents'] ?? 0; ?></span>
                                        </div>
                                        <div class="today-item">
                                            <span class="today-label">新增询价：</span>
                                            <span class="today-value"><?php echo $stats['today_inquiries'] ?? 0; ?></span>
                                        </div>
                                        <div class="today-item">
                                            <span class="today-label">已发布内容：</span>
                                            <span class="today-value"><?php echo $stats['published_contents'] ?? 0; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-col-md6">
                            <div class="layui-card">
                                <div class="layui-card-header">快速操作</div>
                                <div class="layui-card-body">
                                    <div class="quick-actions">
                                        <a href="modules/content/add.php" class="layui-btn layui-btn-normal">
                                            <i class="layui-icon layui-icon-add-1"></i> 添加内容
                                        </a>
                                        <a href="modules/category/add.php" class="layui-btn layui-btn-warm">
                                            <i class="layui-icon layui-icon-template-1"></i> 添加栏目
                                        </a>
                                        <a href="modules/inquiry/" class="layui-btn layui-btn-danger">
                                            <i class="layui-icon layui-icon-survey"></i> 查看询价
                                        </a>
                                        <a href="modules/system/" class="layui-btn">
                                            <i class="layui-icon layui-icon-set"></i> 系统设置
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 最新内容和询价 -->
                    <div class="layui-row layui-col-space15" style="margin-top: 20px;">
                        <div class="layui-col-md6">
                            <div class="layui-card">
                                <div class="layui-card-header">最新内容</div>
                                <div class="layui-card-body">
                                    <?php if (empty($latest_contents)): ?>
                                        <p style="text-align: center; color: #999;">暂无内容</p>
                                    <?php else: ?>
                                        <table class="layui-table" lay-size="sm">
                                            <thead>
                                                <tr>
                                                    <th>标题</th>
                                                    <th>栏目</th>
                                                    <th>创建时间</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($latest_contents as $content): ?>
                                                <tr>
                                                    <td>
                                                        <a href="modules/content/edit.php?id=<?php echo $content['id']; ?>">
                                                            <?php echo truncate_string($content['title'], 30); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo $content['category_name'] ?? '未分类'; ?></td>
                                                    <td><?php echo format_date($content['created_at'], 'm-d H:i'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-col-md6">
                            <div class="layui-card">
                                <div class="layui-card-header">最新询价</div>
                                <div class="layui-card-body">
                                    <?php if (empty($latest_inquiries)): ?>
                                        <p style="text-align: center; color: #999;">暂无询价</p>
                                    <?php else: ?>
                                        <table class="layui-table" lay-size="sm">
                                            <thead>
                                                <tr>
                                                    <th>姓名</th>
                                                    <th>服务类型</th>
                                                    <th>状态</th>
                                                    <th>时间</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($latest_inquiries as $inquiry): ?>
                                                <tr>
                                                    <td>
                                                        <a href="modules/inquiry/view.php?id=<?php echo $inquiry['id']; ?>">
                                                            <?php echo $inquiry['name']; ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo $inquiry['service_type']; ?></td>
                                                    <td>
                                                        <?php 
                                                        $status_map = ['pending' => '待处理', 'processing' => '处理中', 'completed' => '已完成'];
                                                        $status_class = ['pending' => 'layui-bg-orange', 'processing' => 'layui-bg-blue', 'completed' => 'layui-bg-green'];
                                                        echo '<span class="layui-badge ' . ($status_class[$inquiry['status']] ?? '') . '">' . ($status_map[$inquiry['status']] ?? $inquiry['status']) . '</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo format_date($inquiry['created_at'], 'm-d H:i'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
        
        // 初始化导航
        element.render();
        
        // 页面加载完成提示
        layer.msg('管理后台加载完成', {icon: 1, time: 1000});
    });
    </script>
</body>
</html>