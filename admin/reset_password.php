<?php
/**
 * 管理员密码重置脚本
 */

// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>管理员密码重置</h2>";

try {
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    require_once '../includes/functions.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        if (empty($username) || empty($new_password)) {
            throw new Exception("用户名和新密码不能为空");
        }
        
        // 检查用户是否存在
        $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("用户不存在");
        }
        
        // 更新密码
        $password_hash = hash_password($new_password);
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->execute([$password_hash, $username]);
        
        echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ 密码重置成功！</h3>";
        echo "<p>用户名: <strong>{$username}</strong></p>";
        echo "<p>新密码: <strong>{$new_password}</strong></p>";
        echo "<p><a href='login.php'>点击这里去登录</a></p>";
        echo "</div>";
        
    } else {
        // 显示现有管理员
        $stmt = $db->query("SELECT username, real_name, role, is_active FROM admins ORDER BY id");
        $admins = $stmt->fetchAll();
        
        echo "<h3>现有管理员账户:</h3>";
        if (!empty($admins)) {
            echo "<table border='1' cellpadding='8' cellspacing='0' style='margin: 20px 0; border-collapse: collapse;'>";
            echo "<tr style='background: #f8f9fa;'><th>用户名</th><th>真实姓名</th><th>角色</th><th>状态</th></tr>";
            foreach ($admins as $admin) {
                $status_color = $admin['is_active'] ? 'green' : 'red';
                $status_text = $admin['is_active'] ? '启用' : '禁用';
                echo "<tr>";
                echo "<td>{$admin['username']}</td>";
                echo "<td>{$admin['real_name']}</td>";
                echo "<td>{$admin['role']}</td>";
                echo "<td style='color: {$status_color};'>{$status_text}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ 没有找到任何管理员账户</p>";
        }
        
        // 显示重置表单
        ?>
        <h3>重置管理员密码:</h3>
        <form method="POST" style="max-width: 400px; margin: 20px 0;">
            <div style="margin-bottom: 15px;">
                <label for="username" style="display: block; margin-bottom: 5px;">用户名:</label>
                <input type="text" id="username" name="username" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="new_password" style="display: block; margin-bottom: 5px;">新密码:</label>
                <input type="text" id="new_password" name="new_password" required 
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                       value="admin123">
                <small style="color: #666;">建议使用默认密码 admin123</small>
            </div>
            
            <button type="submit" 
                    style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                重置密码
            </button>
        </form>
        
        <div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0;">
            <h4>⚠️ 安全提示:</h4>
            <ul>
                <li>请在重置密码后立即删除此文件</li>
                <li>建议登录后台后修改为更安全的密码</li>
                <li>此脚本仅用于紧急情况下的密码重置</li>
            </ul>
        </div>
        <?php
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ 操作失败</h3>";
    echo "<p>错误信息: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>