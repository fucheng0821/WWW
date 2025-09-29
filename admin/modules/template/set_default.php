<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    // 获取模板信息
    $stmt = $db->prepare("SELECT name, template_type FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    
    if (!$template) {
        header('Location: view.php?id=' . $id . '&error=template_not_found');
        exit();
    }
    
    $db->beginTransaction();
    
    // 取消同类型的其他默认模板
    $stmt = $db->prepare("UPDATE templates SET is_default = 0 WHERE template_type = ?");
    $stmt->execute([$template['template_type']]);
    
    // 设置当前模板为默认
    $stmt = $db->prepare("UPDATE templates SET is_default = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    $db->commit();
    
    $message = "模板「{$template['name']}」已设为默认模板";
    header("Location: view.php?id={$id}&success=" . urlencode($message));
    
} catch (Exception $e) {
    $db->rollBack();
    $error_message = "设置默认模板失败：" . $e->getMessage();
    header("Location: view.php?id={$id}&error=" . urlencode($error_message));
}

exit();
?>