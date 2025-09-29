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
    // 检查内容是否存在
    $stmt = $db->prepare("SELECT title FROM contents WHERE id = ?");
    $stmt->execute([$id]);
    $content = $stmt->fetch();
    
    if (!$content) {
        header('Location: index.php?error=content_not_found');
        exit();
    }
    
    // 删除内容
    $stmt = $db->prepare("DELETE FROM contents WHERE id = ?");
    $stmt->execute([$id]);
    
    $message = "内容「{$content['title']}」删除成功";
    header("Location: index.php?success=" . urlencode($message));
    
} catch (Exception $e) {
    $error_message = "删除失败：" . $e->getMessage();
    header("Location: index.php?error=" . urlencode($error_message));
}

exit();
?>