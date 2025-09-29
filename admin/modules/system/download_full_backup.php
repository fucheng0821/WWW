<?php
/**
 * 下载全站备份文件
 */

// 设置正确的路径
define('BASE_PATH', dirname(dirname(dirname(dirname(__FILE__)))));

session_start();
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';

check_admin_auth();

// 获取文件名参数
$filename = isset($_GET['file']) ? basename($_GET['file']) : '';

if (empty($filename)) {
    die('无效的文件名');
}

// 检查文件名是否符合备份文件命名规则
if (!preg_match('/^full_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
    die('无效的备份文件名');
}

// 构造文件路径
$filepath = BASE_PATH . '/backup/' . $filename;

// 检查文件是否存在
if (!file_exists($filepath)) {
    die('备份文件不存在');
}

// 检查文件是否为ZIP文件
if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'zip') {
    die('只能下载ZIP格式的备份文件');
}

// 设置下载头信息
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

// 清除缓冲区
ob_clean();
flush();

// 读取并输出文件内容
readfile($filepath);
exit;
?>