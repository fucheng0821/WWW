<?php
/**
 * 删除全站备份文件
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
    header('Location: full_backup.php?error=' . urlencode('无效的文件名'));
    exit;
}

// 检查文件名是否符合备份文件命名规则
if (!preg_match('/^full_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
    header('Location: full_backup.php?error=' . urlencode('无效的备份文件名'));
    exit;
}

// 构造文件路径
$filepath = BASE_PATH . '/backup/' . $filename;

// 检查文件是否存在
if (!file_exists($filepath)) {
    header('Location: full_backup.php?error=' . urlencode('备份文件不存在'));
    exit;
}

// 删除文件
if (unlink($filepath)) {
    header('Location: full_backup.php?success=' . urlencode('备份文件已成功删除'));
} else {
    header('Location: full_backup.php?error=' . urlencode('删除备份文件失败'));
}
exit;
?>