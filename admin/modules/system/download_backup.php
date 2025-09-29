<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    header('Location: backup.php?error=' . urlencode('文件名不能为空'));
    exit();
}

// 验证文件名安全性
if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
    header('Location: backup.php?error=' . urlencode('无效的文件名'));
    exit();
}

$backup_dir = '../../../backup/';
$filepath = $backup_dir . $filename;

// 检查文件是否存在
if (!file_exists($filepath)) {
    header('Location: backup.php?error=' . urlencode('文件不存在'));
    exit();
}

// 设置下载头
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// 输出文件内容
readfile($filepath);
exit();
?>