<?php
// 定义必要的数据库配置常量
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DEBUG_MODE', true);

// 尝试包含数据库文件
try {
    require_once 'includes/database.php';
    echo '数据库文件语法正确，可以成功加载！';
} catch (Exception $e) {
    echo '错误: ' . $e->getMessage();
} catch (Error $e) {
    echo '语法错误: ' . $e->getMessage();
}
?>