<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取待处理询价
try {
    $sql = "SELECT * FROM inquiries WHERE status = 'pending' ORDER BY created_at DESC";
    $stmt = $db->query($sql);
    $pending_inquiries = $stmt->fetchAll();
    
    $total_pending = count($pending_inquiries);
    
} catch(PDOException $e) {
    $pending_inquiries = [];
    $total_pending = 0;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>待处理询价 - 高光视刻</title>
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
                        <h2>待处理询价 (<?php echo $total_pending; ?>)</h2>
                        <a href="index.php" class="layui-btn layui-btn-primary">
                            <i class="layui-icon layui-icon-return"></i> 返回全部询价
                        </a>
                    </div>
                </div>
                
                <div class="layui-card-body">
                    <?php if (empty($pending_inquiries)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-ok-circle" style="color: #5fb878;"></i>
                            <h3>太棒了！</h3>
                            <p>暂无待处理的询价，所有询价都已处理完毕</p>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 15px;">
                            <button class="layui-btn layui-btn-warm layui-btn-sm" onclick="batchMarkProcessing()">批量标记为处理中</button>
                        </div>
                        
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>客户信息</th>
                                    <th>服务类型</th>
                                    <th>项目描述</th>
                                    <th>预算</th>
                                    <th>提交时间</th>
                                    <th width="150">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_inquiries as $inquiry): ?>
                                <tr>
                                    <td><input type="checkbox" name="inquiry_ids[]" value="<?php echo $inquiry['id']; ?>"></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($inquiry['name']); ?></strong>
                                            <?php if ($inquiry['company']): ?>
                                                <br><small style="color: #666;"><?php echo htmlspecialchars($inquiry['company']); ?></small>
                                            <?php endif; ?>
                                            <br><small style="color: #666;">
                                                📞 <?php echo htmlspecialchars($inquiry['phone']); ?>
                                                <?php if ($inquiry['email']): ?>
                                                    <br>📧 <?php echo htmlspecialchars($inquiry['email']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="layui-badge layui-bg-blue"><?php echo htmlspecialchars($inquiry['service_type']); ?></span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px;">
                                            <?php if ($inquiry['project_description']): ?>
                                                <?php echo nl2br(htmlspecialchars(mb_substr($inquiry['project_description'], 0, 100, 'UTF-8'))); ?>
                                                <?php if (mb_strlen($inquiry['project_description'], 'UTF-8') > 100): ?>
                                                    <small style="color: #999;">...</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #999;">未填写</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($inquiry['budget']): ?>
                                            <span class="layui-badge layui-bg-orange"><?php echo htmlspecialchars($inquiry['budget']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #999;">未填写</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $created_time = strtotime($inquiry['created_at']);
                                        $time_diff = time() - $created_time;
                                        $urgency_class = '';
                                        if ($time_diff > 86400) { // 超过1天
                                            $urgency_class = 'style="color: #ff5722;"';
                                        } elseif ($time_diff > 43200) { // 超过12小时
                                            $urgency_class = 'style="color: #ff9800;"';
                                        }
                                        ?>
                                        <div <?php echo $urgency_class; ?>>
                                            <?php echo date('m-d H:i', $created_time); ?>
                                            <br><small style="color: #999;">
                                                <?php
                                                if ($time_diff < 3600) {
                                                    echo ceil($time_diff / 60) . '分钟前';
                                                } elseif ($time_diff < 86400) {
                                                    echo ceil($time_diff / 3600) . '小时前';
                                                } else {
                                                    echo ceil($time_diff / 86400) . '天前';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $inquiry['id']; ?>" 
                                           class="layui-btn layui-btn-xs">详情</a>
                                        <a href="edit.php?id=<?php echo $inquiry['id']; ?>" 
                                           class="layui-btn layui-btn-xs layui-btn-warm">处理</a>
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
    layui.use(['element', 'layer', 'form'], function(){
        var element = layui.element;
        var layer = layui.layer;
        var form = layui.form;
        
        // 初始化
        element.render();
        form.render();
        
        // 全选功能
        document.getElementById('checkAll').addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('input[name="inquiry_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = this.checked;
            }.bind(this));
        });
    });
    
    function batchMarkProcessing() {
        var checked = document.querySelectorAll('input[name="inquiry_ids[]"]:checked');
        if (checked.length === 0) {
            layui.use('layer', function(){
                layui.layer.msg('请先选择要标记的询价');
            });
            return;
        }
        
        var ids = Array.from(checked).map(cb => cb.value);
        
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要将选中的询价标记为"处理中"吗？', {
                icon: 3,
                title: '批量操作确认'
            }, function(index){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'batch_action.php';
                
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'processing';
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