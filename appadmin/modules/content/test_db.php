<?php
// 简单的数据库测试脚本
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

try {
    // 测试数据库连接
    echo "数据库连接成功<br>";
    
    // 测试获取表结构
    $stmt = $db->query("DESCRIBE contents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "contents表结构:<br>";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>