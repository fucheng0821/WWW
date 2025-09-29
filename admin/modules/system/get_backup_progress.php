<?php
// 获取备份进度信息

// 设置正确的路径
define('BASE_PATH', dirname(dirname(dirname(dirname(__FILE__)))));
require_once BASE_PATH . '/includes/config.php';

// 设置JSON响应头
header('Content-Type: application/json');

// 检查是否已登录（简化检查）
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    echo json_encode(['progress' => 0, 'current_file' => '未登录']);
    exit;
}

try {
    // 初始化进度信息
    $progress = isset($_SESSION['backup_progress']) ? (int)$_SESSION['backup_progress'] : 0;
    $current_file = isset($_SESSION['backup_current_file']) ? (string)$_SESSION['backup_current_file'] : '';
    
    // 确保进度值在0-100范围内
    $progress = max(0, min(100, $progress));
    
    // 返回进度信息
    echo json_encode([
        'progress' => $progress,
        'current_file' => $current_file
    ]);
} catch (Exception $e) {
    // 发生错误时返回默认值
    echo json_encode([
        'progress' => 0,
        'current_file' => '获取进度时发生错误'
    ]);
}
?>