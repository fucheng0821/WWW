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

$action = $_POST['action'] ?? '';
$template_ids = $_POST['template_ids'] ?? [];

if (empty($action) || empty($template_ids) || !is_array($template_ids)) {
    header('Location: index.php?error=invalid_parameters');
    exit();
}

// 验证ID格式
$template_ids = array_map('intval', $template_ids);
$template_ids = array_filter($template_ids, function($id) { return $id > 0; });

if (empty($template_ids)) {
    header('Location: index.php?error=no_valid_ids');
    exit();
}

$success_count = 0;
$error_count = 0;

try {
    $db->beginTransaction();
    
    switch ($action) {
        case 'delete':
            // 批量删除
            $placeholders = str_repeat('?,', count($template_ids) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM templates WHERE id IN ($placeholders)");
            $stmt->execute($template_ids);
            $success_count = $stmt->rowCount();
            $message = "成功删除 {$success_count} 个模板";
            break;
            
        case 'enable':
            // 批量启用
            $placeholders = str_repeat('?,', count($template_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE templates SET is_active = 1, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($template_ids);
            $success_count = $stmt->rowCount();
            $message = "成功启用 {$success_count} 个模板";
            break;
            
        case 'disable':
            // 批量禁用
            $placeholders = str_repeat('?,', count($template_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE templates SET is_active = 0, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($template_ids);
            $success_count = $stmt->rowCount();
            $message = "成功禁用 {$success_count} 个模板";
            break;
            
        default:
            throw new Exception('无效的操作类型');
    }
    
    $db->commit();
    header("Location: index.php?success=" . urlencode($message));
    
} catch (Exception $e) {
    $db->rollBack();
    $error_message = "批量操作失败：" . $e->getMessage();
    header("Location: index.php?error=" . urlencode($error_message));
}

exit();
?>