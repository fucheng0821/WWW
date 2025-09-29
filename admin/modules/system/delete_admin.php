<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: admin.php?error=invalid_id');
    exit();
}

// 不能删除自己
if ($id == $_SESSION['admin_id']) {
    header('Location: admin.php?error=cannot_delete_self');
    exit();
}

try {
    // 检查管理员是否存在
    $stmt = $db->prepare("SELECT username FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        header('Location: admin.php?error=admin_not_found');
        exit();
    }
    
    // 删除管理员
    $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    
    $message = "管理员「{$admin['username']}」删除成功";
    header("Location: admin.php?success=" . urlencode($message));
    
} catch (Exception $e) {
    $error_message = "删除失败：" . $e->getMessage();
    header("Location: admin.php?error=" . urlencode($error_message));
}

exit();
?>