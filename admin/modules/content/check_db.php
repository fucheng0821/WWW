<?php
// 数据库表和内容检查脚本
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 引入数据库配置
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';

// 检查数据库连接
if (!isset($db) || !($db instanceof PDO)) {
    die("ERROR: Database connection not established");
}

// 检查内容ID参数
$content_id = isset($_GET['id']) ? intval($_GET['id']) : 314;

echo "<h2>Database Table and Content Checker</h2>";

// 检查数据库中的表
echo "<h3>Checking for Content Tables</h3>";
$tables_to_check = ['content', 'contents'];
$found_tables = [];

foreach ($tables_to_check as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $result = $stmt->fetch(PDO::FETCH_NUM);
        
        if ($result) {
            echo "✓ Table '$table' exists<br>";
            $found_tables[] = $table;
            
            // 获取表结构
            echo "<h4>Structure of '$table':</h4>";
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        } else {
            echo "✗ Table '$table' does not exist<br>";
        }
    } catch (PDOException $e) {
        echo "ERROR checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// 检查内容ID
echo "<h3>Checking for Content with ID: $content_id</h3>";

if (empty($found_tables)) {
    echo "No content tables found in database.<br>";
} else {
    foreach ($found_tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE id = ?");
            $stmt->execute([$content_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo "✓ Found content with ID $content_id in table '$table'<br>";
                
                // 获取内容详情
                $stmt = $db->prepare("SELECT id, title, created_at FROM $table WHERE id = ?");
                $stmt->execute([$content_id]);
                $content = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "Content details:<br>";
                echo "ID: {$content['id']}<br>";
                echo "Title: {$content['title']}<br>";
                echo "Created: {$content['created_at']}<br><br>";
            } else {
                echo "✗ No content found with ID $content_id in table '$table'<br>";
            }
        } catch (PDOException $e) {
            echo "ERROR checking content in table '$table': " . $e->getMessage() . "<br>";
        }
    }
}

// 显示数据库信息
echo "<h3>Database Information</h3>";
echo "Connected to database: " . DB_NAME . "<br>";
echo "Server: " . DB_HOST . "<br>";
echo "Username: " . DB_USER . "<br>";