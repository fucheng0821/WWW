<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';

// 处理添加管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 验证输入
    if (empty($username)) {
        $errors[] = '用户名不能为空';
    }
    
    if (empty($password)) {
        $errors[] = '密码不能为空';
    } elseif (strlen($password) < 6) {
        $errors[] = '密码长度不能少于6位';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = '两次输入的密码不一致';
    }
    
    // 检查用户名是否重复
    if (!empty($username)) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = '用户名已存在';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 检查邮箱是否重复
    if (!empty($email)) {
        try {
            $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = '邮箱已存在';
            }
        } catch(PDOException $e) {
            $errors[] = '数据库查询错误：' . $e->getMessage();
        }
    }
    
    // 如果没有错误，插入数据
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO admins (username, email, password, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$username, $email, $hashed_password, $is_active]);
            
            $success = '管理员添加成功！';
            $_POST = []; // 清空表单
        } catch(PDOException $e) {
            $errors[] = '添加失败：' . $e->getMessage();
        }
    }
}

// 处理状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    $new_status = intval($_POST['new_status'] ?? 0);
    
    if ($admin_id > 0) {
        try {
            $stmt = $db->prepare("UPDATE admins SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $admin_id]);
            $success = '管理员状态更新成功！';
        } catch(PDOException $e) {
            $errors[] = '状态更新失败：' . $e->getMessage();
        }
    }
}

// 获取管理员列表
try {
    $stmt = $db->query("SELECT id, username, email, is_active, created_at, updated_at FROM admins ORDER BY created_at DESC");
    $admins = $stmt->fetchAll();
} catch(Exception $e) {
    $admins = [];
    $errors[] = '获取管理员列表失败：' . $e->getMessage();
}

// 处理成功和错误消息
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    
    // 将错误代码转换为友好的中文提示
    $error_messages = [
        'invalid_id' => '无效的管理员ID，请检查链接是否正确',
        'cannot_delete_self' => '无法删除当前登录的管理员账户',
        'admin_not_found' => '管理员不存在或已被删除',
        'permission_denied' => '权限不足，无法执行此操作',
        'database_error' => '数据库操作失败，请稍后重试'
    ];
    
    $errors[] = $error_messages[$error_code] ?? $error_code;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员设置 - 高光视刻</title>
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
                        <h2>管理员设置</h2>
                        <div>
                            <button class="layui-btn layui-btn-normal" onclick="showAddForm()">
                                <i class="layui-icon layui-icon-add-1"></i> 添加管理员
                            </button>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回系统设置
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
                    
                    <!-- 添加管理员表单 -->
                    <div id="addForm" style="display: none; margin-bottom: 30px;">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                <h3>添加管理员</h3>
                            </div>
                            <div class="layui-card-body">
                                <form class="layui-form" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <div class="layui-row layui-col-space20">
                                        <div class="layui-col-md6">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">用户名 *</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="username" placeholder="请输入用户名" 
                                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                                           class="layui-input" required>
                                                </div>
                                            </div>
                                            
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">邮箱地址</label>
                                                <div class="layui-input-block">
                                                    <input type="email" name="email" placeholder="请输入邮箱地址" 
                                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                           class="layui-input">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="layui-col-md6">
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">密码 *</label>
                                                <div class="layui-input-block">
                                                    <input type="password" name="password" placeholder="请输入密码（至少6位）" 
                                                           class="layui-input" required>
                                                </div>
                                            </div>
                                            
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">确认密码 *</label>
                                                <div class="layui-input-block">
                                                    <input type="password" name="confirm_password" placeholder="请再次输入密码" 
                                                           class="layui-input" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <input type="checkbox" name="is_active" value="1" checked title="启用账户" lay-skin="primary">
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button type="submit" class="layui-btn layui-btn-normal">添加管理员</button>
                                            <button type="button" class="layui-btn layui-btn-primary" onclick="hideAddForm()">取消</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 管理员列表 -->
                    <table class="layui-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>最后更新</th>
                                <th width="150">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #999;">暂无管理员</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                            <span class="layui-badge layui-bg-blue">当前用户</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['email'] ?? '未设置'); ?></td>
                                    <td>
                                        <?php if ($admin['is_active']): ?>
                                            <span class="status-badge status-completed">启用</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($admin['updated_at'])); ?></td>
                                    <td>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <?php if ($admin['is_active']): ?>
                                                <button class="layui-btn layui-btn-xs layui-btn-warm" 
                                                        onclick="toggleStatus(<?php echo $admin['id']; ?>, 0)">禁用</button>
                                            <?php else: ?>
                                                <button class="layui-btn layui-btn-xs layui-btn-normal" 
                                                        onclick="toggleStatus(<?php echo $admin['id']; ?>, 1)">启用</button>
                                            <?php endif; ?>
                                            <button class="layui-btn layui-btn-xs layui-btn-danger" 
                                                    onclick="deleteAdmin(<?php echo $admin['id']; ?>)">删除</button>
                                        <?php else: ?>
                                            <span class="layui-badge">当前用户</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'element', 'layer'], function(){
        var form = layui.form;
        var element = layui.element;
        var layer = layui.layer;
        
        form.render();
        element.render();
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert-success, .layui-alert-danger');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    
    function showAddForm() {
        document.getElementById('addForm').style.display = 'block';
    }
    
    function hideAddForm() {
        document.getElementById('addForm').style.display = 'none';
    }
    
    function toggleStatus(adminId, newStatus) {
        var statusText = newStatus ? '启用' : '禁用';
        
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要' + statusText + '这个管理员吗？', {
                icon: 3,
                title: '状态更改确认'
            }, function(index){
                var form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                var actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                form.appendChild(actionInput);
                
                var idInput = document.createElement('input');
                idInput.name = 'admin_id';
                idInput.value = adminId;
                form.appendChild(idInput);
                
                var statusInput = document.createElement('input');
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                form.appendChild(statusInput);
                
                document.body.appendChild(form);
                form.submit();
                
                layer.close(index);
            });
        });
    }
    
    function deleteAdmin(id) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除这个管理员吗？', {
                icon: 3,
                title: '删除确认'
            }, function(index){
                // 使用AJAX方式执行删除操作
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_admin.php?id=' + id, true);
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