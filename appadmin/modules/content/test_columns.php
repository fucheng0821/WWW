<?php
// 简单测试脚本 - 检查add.php的数据库操作
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/config.php';
require_once '../../includes/database.php';

// 测试数据库连接
try {
    echo "数据库连接正常<br>";
    
    // 检查contents表结构
    $stmt = $db->query("SHOW COLUMNS FROM contents");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Contents表字段:<br>";
    foreach ($columns as $column) {
        echo "- $column<br>";
    }
    
    // 检查是否存在tags和keywords字段
    if (in_array('tags', $columns)) {
        echo "<br>tags字段存在<br>";
    } else {
        echo "<br>tags字段不存在<br>";
    }
    
    if (in_array('keywords', $columns)) {
        echo "keywords字段存在<br>";
    } else {
        echo "keywords字段不存在<br>";
    }
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}