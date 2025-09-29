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

try {
    // 删除文件
    if (unlink($filepath)) {
        $message = "备份文件「{$filename}」删除成功";
        header("Location: backup.php?success=" . urlencode($message));
    } else {
        header('Location: backup.php?error=' . urlencode('文件删除失败'));
    }
} catch (Exception $e) {
    $error_message = "删除失败：" . $e->getMessage();
    header("Location: backup.php?error=" . urlencode($error_message));
}

exit();
?>