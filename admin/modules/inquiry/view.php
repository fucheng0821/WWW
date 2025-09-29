<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    // 获取询价详情
    $stmt = $db->prepare("
        SELECT i.*, a.username as assigned_admin_name 
        FROM inquiries i 
        LEFT JOIN admins a ON i.assigned_to = a.id 
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $inquiry = $stmt->fetch();
    
    if (!$inquiry) {
        header('Location: index.php?error=inquiry_not_found');
        exit();
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit();
}

// 状态文本映射
$status_map = [
    'pending' => '待处理',
    'processing' => '处理中', 
    'completed' => '已完成',
    'cancelled' => '已取消'
];

$priority_map = [
    'low' => '低',
    'normal' => '普通',
    'high' => '高',
    'urgent' => '紧急'
];

$status_class_map = [
    'pending' => 'status-pending',
    'processing' => 'status-processing',
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled'
];

$priority_class_map = [
    'low' => 'layui-badge',
    'normal' => 'layui-badge layui-bg-blue',
    'high' => 'layui-badge layui-bg-orange',
    'urgent' => 'layui-badge layui-bg-red'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>询价详情 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>询价详情 #<?php echo $inquiry['id']; ?></h2>
                        <div>
                            <a href="edit.php?id=<?php echo $inquiry['id']; ?>" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-edit"></i> 处理询价
                            </a>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="layui-card-body">
                    <div class="layui-row layui-col-space20">
                        <!-- 左侧详细信息 -->
                        <div class="layui-col-md8">
                            <!-- 基本信息 -->
                            <div class="info-section">
                                <h3>基本信息</h3>
                                <table class="layui-table">
                                    <tbody>
                                        <tr>
                                            <td width="120"><strong>客户姓名</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>公司名称</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['company'] ?? '未填写'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>联系电话</strong></td>
                                            <td><a href="tel:<?php echo $inquiry['phone']; ?>"><?php echo htmlspecialchars($inquiry['phone']); ?></a></td>
                                        </tr>
                                        <tr>
                                            <td><strong>邮箱地址</strong></td>
                                            <td>
                                                <?php if ($inquiry['email']): ?>
                                                    <a href="mailto:<?php echo $inquiry['email']; ?>"><?php echo htmlspecialchars($inquiry['email']); ?></a>
                                                <?php else: ?>
                                                    未填写
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>服务类型</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['service_type']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>预算范围</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['budget'] ?? '未填写'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>期望时间</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['timeline'] ?? '未填写'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>询价来源</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['source']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- 项目需求 -->
                            <div class="info-section">
                                <h3>项目需求</h3>
                                <div class="content-box">
                                    <h4>项目描述：</h4>
                                    <p><?php echo nl2br(htmlspecialchars($inquiry['project_description'])); ?></p>
                                    
                                    <?php if ($inquiry['requirements']): ?>
                                        <h4>具体要求：</h4>
                                        <p><?php echo nl2br(htmlspecialchars($inquiry['requirements'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- 处理记录 -->
                            <?php if ($inquiry['notes'] || $inquiry['response_content']): ?>
                            <div class="info-section">
                                <h3>处理记录</h3>
                                
                                <?php if ($inquiry['notes']): ?>
                                    <div class="content-box">
                                        <h4>内部备注：</h4>
                                        <p><?php echo nl2br(htmlspecialchars($inquiry['notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($inquiry['response_content']): ?>
                                    <div class="content-box">
                                        <h4>回复内容：</h4>
                                        <p><?php echo nl2br(htmlspecialchars($inquiry['response_content'])); ?></p>
                                        <?php if ($inquiry['response_at']): ?>
                                            <p class="text-muted">回复时间：<?php echo date('Y-m-d H:i:s', strtotime($inquiry['response_at'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 右侧状态信息 -->
                        <div class="layui-col-md4">
                            <div class="status-panel">
                                <h3>处理状态</h3>
                                
                                <div class="status-item">
                                    <label>当前状态：</label>
                                    <span class="status-badge <?php echo $status_class_map[$inquiry['status']]; ?>">
                                        <?php echo $status_map[$inquiry['status']]; ?>
                                    </span>
                                </div>
                                
                                <div class="status-item">
                                    <label>优先级：</label>
                                    <span class="<?php echo $priority_class_map[$inquiry['priority']]; ?>">
                                        <?php echo $priority_map[$inquiry['priority']]; ?>
                                    </span>
                                </div>
                                
                                <div class="status-item">
                                    <label>负责人：</label>
                                    <span><?php echo htmlspecialchars($inquiry['assigned_admin_name'] ?? '未分配'); ?></span>
                                </div>
                                
                                <div class="status-item">
                                    <label>创建时间：</label>
                                    <span><?php echo date('Y-m-d H:i:s', strtotime($inquiry['created_at'])); ?></span>
                                </div>
                                
                                <div class="status-item">
                                    <label>最后更新：</label>
                                    <span><?php echo date('Y-m-d H:i:s', strtotime($inquiry['updated_at'])); ?></span>
                                </div>
                                
                                <!-- 快速操作 -->
                                <div class="quick-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                    <h4>快速操作</h4>
                                    
                                    <?php if ($inquiry['status'] === 'pending'): ?>
                                        <button class="layui-btn layui-btn-normal layui-btn-sm layui-btn-fluid" 
                                                onclick="updateStatus('processing')" style="margin-bottom: 10px;">
                                            开始处理
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($inquiry['status'] === 'processing'): ?>
                                        <button class="layui-btn layui-btn-normal layui-btn-sm layui-btn-fluid" 
                                                onclick="updateStatus('completed')" style="margin-bottom: 10px;">
                                            标记完成
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($inquiry['status'] !== 'cancelled'): ?>
                                        <button class="layui-btn layui-btn-warm layui-btn-sm layui-btn-fluid" 
                                                onclick="updateStatus('cancelled')" style="margin-bottom: 10px;">
                                            取消询价
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="edit.php?id=<?php echo $inquiry['id']; ?>" 
                                       class="layui-btn layui-btn-primary layui-btn-sm layui-btn-fluid">
                                        详细编辑
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
        
        element.render();
    });
    
    function updateStatus(newStatus) {
        var statusText = {
            'processing': '处理中',
            'completed': '已完成', 
            'cancelled': '已取消'
        }[newStatus];
        
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要将状态更改为"' + statusText + '"吗？', {
                icon: 3,
                title: '状态更改确认'
            }, function(index){
                // 创建表单提交
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'update_status.php';
                
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?php echo $inquiry['id']; ?>';
                form.appendChild(idInput);
                
                var statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                form.appendChild(statusInput);
                
                document.body.appendChild(form);
                form.submit();
                
                layer.close(index);
            });
        });
    }
    </script>
</body>
</html>