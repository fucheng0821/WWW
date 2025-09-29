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
    // 检查模板是否存在
    $stmt = $db->prepare("SELECT name, is_default FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    
    if (!$template) {
        header('Location: index.php?error=template_not_found');
        exit();
    }
    
    // 检查是否为默认模板
    if ($template['is_default']) {
        header('Location: index.php?error=cannot_delete_default_template');
        exit();
    }
    
    // 删除模板
    $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    
    $message = "模板「{$template['name']}」删除成功";
    header("Location: index.php?success=" . urlencode($message));
    
} catch (Exception $e) {
    $error_message = "删除失败：" . $e->getMessage();
    header("Location: index.php?error=" . urlencode($error_message));
}

exit();
?>