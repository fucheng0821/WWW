<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$id = intval($_GET['id'] ?? 0);
$errors = [];
$success = '';

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    // 获取询价详情
    $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = ?");
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

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $inquiry['status'];
    $priority = $_POST['priority'] ?? $inquiry['priority'];
    $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $notes = trim($_POST['notes'] ?? '');
    $response_content = trim($_POST['response_content'] ?? '');
    
    try {
        $update_sql = "UPDATE inquiries SET status = ?, priority = ?, assigned_to = ?, notes = ?";
        $params = [$status, $priority, $assigned_to, $notes];
        
        // 如果填写了回复内容，更新回复相关字段
        if (!empty($response_content)) {
            $update_sql .= ", response_content = ?, response_at = NOW()";
            $params[] = $response_content;
        }
        
        $update_sql .= ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;
        
        $stmt = $db->prepare($update_sql);
        $stmt->execute($params);
        
        $success = '询价信息更新成功！';
        
        // 重新获取更新后的数据
        $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = ?");
        $stmt->execute([$id]);
        $inquiry = $stmt->fetch();
        
    } catch (Exception $e) {
        $errors[] = '更新失败：' . $e->getMessage();
    }
}

// 获取管理员列表
try {
    $admin_stmt = $db->query("SELECT id, username FROM admins WHERE is_active = 1 ORDER BY username");
    $admins = $admin_stmt->fetchAll();
} catch (Exception $e) {
    $admins = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>处理询价 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>处理询价 #<?php echo $inquiry['id']; ?></h2>
                        <div>
                            <a href="view.php?id=<?php echo $inquiry['id']; ?>" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-search"></i> 查看详情
                            </a>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
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
                    
                    <?php if ($success): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="layui-row layui-col-space20">
                        <!-- 左侧客户信息（只读） -->
                        <div class="layui-col-md6">
                            <div class="info-section">
                                <h3>客户信息</h3>
                                <table class="layui-table">
                                    <tbody>
                                        <tr>
                                            <td width="100"><strong>姓名</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>公司</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['company'] ?? '未填写'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>电话</strong></td>
                                            <td><a href="tel:<?php echo $inquiry['phone']; ?>"><?php echo htmlspecialchars($inquiry['phone']); ?></a></td>
                                        </tr>
                                        <tr>
                                            <td><strong>邮箱</strong></td>
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
                                            <td><strong>预算</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['budget'] ?? '未填写'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>期望时间</strong></td>
                                            <td><?php echo htmlspecialchars($inquiry['timeline'] ?? '未填写'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="info-section">
                                <h3>需求描述</h3>
                                <div class="content-box">
                                    <p><strong>项目描述：</strong></p>
                                    <p><?php echo nl2br(htmlspecialchars($inquiry['project_description'])); ?></p>
                                    
                                    <?php if ($inquiry['requirements']): ?>
                                        <p><strong>具体要求：</strong></p>
                                        <p><?php echo nl2br(htmlspecialchars($inquiry['requirements'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 右侧处理表单 -->
                        <div class="layui-col-md6">
                            <form class="layui-form" method="POST">
                                <div class="info-section">
                                    <h3>处理信息</h3>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">处理状态</label>
                                        <div class="layui-input-block">
                                            <select name="status">
                                                <option value="pending" <?php echo $inquiry['status'] === 'pending' ? 'selected' : ''; ?>>待处理</option>
                                                <option value="processing" <?php echo $inquiry['status'] === 'processing' ? 'selected' : ''; ?>>处理中</option>
                                                <option value="completed" <?php echo $inquiry['status'] === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                                <option value="cancelled" <?php echo $inquiry['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">优先级</label>
                                        <div class="layui-input-block">
                                            <select name="priority">
                                                <option value="low" <?php echo $inquiry['priority'] === 'low' ? 'selected' : ''; ?>>低</option>
                                                <option value="normal" <?php echo $inquiry['priority'] === 'normal' ? 'selected' : ''; ?>>普通</option>
                                                <option value="high" <?php echo $inquiry['priority'] === 'high' ? 'selected' : ''; ?>>高</option>
                                                <option value="urgent" <?php echo $inquiry['priority'] === 'urgent' ? 'selected' : ''; ?>>紧急</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">分配给</label>
                                        <div class="layui-input-block">
                                            <select name="assigned_to">
                                                <option value="">请选择负责人</option>
                                                <?php foreach ($admins as $admin): ?>
                                                    <option value="<?php echo $admin['id']; ?>" 
                                                            <?php echo $inquiry['assigned_to'] == $admin['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($admin['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item layui-form-text">
                                        <label class="layui-form-label">内部备注</label>
                                        <div class="layui-input-block">
                                            <textarea name="notes" placeholder="内部处理备注，客户不可见" 
                                                      class="layui-textarea" rows="4"><?php echo htmlspecialchars($inquiry['notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h3>客户回复</h3>
                                    
                                    <div class="layui-form-item layui-form-text">
                                        <label class="layui-form-label">回复内容</label>
                                        <div class="layui-input-block">
                                            <textarea name="response_content" placeholder="给客户的回复内容" 
                                                      class="layui-textarea" rows="6"><?php echo htmlspecialchars($inquiry['response_content'] ?? ''); ?></textarea>
                                            <div class="layui-form-mid layui-word-aux">
                                                填写后将记录回复时间，可用于邮件发送等功能
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($inquiry['response_at']): ?>
                                        <div class="layui-form-item">
                                            <label class="layui-form-label">上次回复</label>
                                            <div class="layui-input-block">
                                                <div class="layui-form-mid">
                                                    <?php echo date('Y-m-d H:i:s', strtotime($inquiry['response_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <button type="submit" class="layui-btn layui-btn-normal">保存更改</button>
                                        <a href="view.php?id=<?php echo $inquiry['id']; ?>" class="layui-btn layui-btn-primary">取消</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'element'], function(){
        var form = layui.form;
        var element = layui.element;
        
        form.render();
        element.render();
    });
    </script>
</body>
</html>