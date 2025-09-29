<?php
// 最小化版编辑页面 - 只包含必要功能
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 会话初始化
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 模拟管理员登录
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'testadmin';

// 检查登录状态
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    echo "ERROR: Not logged in. Redirecting to login page.";
    exit;
}

// 引入必要文件
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

// 检查ID参数
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id']) || intval($_GET['id']) <= 0) {
    echo "ERROR: Invalid ID parameter.";
    exit;
}

$content_id = intval($_GET['id']);
echo "<h2>Minimal Edit Page - ID: $content_id</h2>";

// 检查文件和函数可用性
echo "<h3>File & Function Status</h3>";
echo "config.php: Included<br>";
echo "database.php: Included<br>";
echo "functions.php: Included<br>";
echo "check_admin_auth function: " . (function_exists('check_admin_auth') ? 'Exists' : 'Not Found') . "<br>";
echo "PDO connection: " . (isset($db) && $db instanceof PDO ? 'Established' : 'Not Established') . "<br>";

// 尝试查询内容数据
echo "<h3>Content Query Test</h3>";
if (isset($db) && $db instanceof PDO) {
    try {
        // 简单查询，只获取少量字段
        $stmt = $db->prepare("SELECT id, title FROM content WHERE id = ?");
        $stmt->execute([$content_id]);
        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($content) {
            echo "Content found: " . htmlspecialchars($content['title']) . "<br>";
        } else {
            echo "No content found with ID: $content_id<br>";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "<br>";
    }
}

// 显示请求和服务器信息
echo "<h3>Request Information</h3>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "Server Protocol: " . $_SERVER['SERVER_PROTOCOL'] . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Session ID: " . session_id() . "<br>";

// 内存使用情况
echo "<h3>Memory Usage</h3>";
echo "Current memory usage: " . (memory_get_usage() / 1024 / 1024) . " MB<br>";
echo "Peak memory usage: " . (memory_get_peak_usage() / 1024 / 1024) . " MB<br>";