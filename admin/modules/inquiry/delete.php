<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    // 检查询价是否存在
    $stmt = $db->prepare("SELECT id, name FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    $inquiry = $stmt->fetch();
    
    if (!$inquiry) {
        header('Location: index.php?error=inquiry_not_found');
        exit();
    }
    
    // 删除询价
    $stmt = $db->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    
    $message = "询价记录「来自{$inquiry['name']}的询价」删除成功";
    header("Location: index.php?success=" . urlencode($message));
    
} catch (Exception $e) {
    $error_message = "删除失败：" . $e->getMessage();
    header("Location: index.php?error=" . urlencode($error_message));
}

exit();
?>