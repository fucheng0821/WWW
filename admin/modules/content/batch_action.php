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
$content_ids = $_POST['content_ids'] ?? [];

if (empty($action) || empty($content_ids) || !is_array($content_ids)) {
    header('Location: index.php?error=invalid_params');
    exit();
}

// 验证ID
$content_ids = array_filter(array_map('intval', $content_ids));
if (empty($content_ids)) {
    header('Location: index.php?error=invalid_ids');
    exit();
}

$placeholders = implode(',', array_fill(0, count($content_ids), '?'));
$success_count = 0;

try {
    $db->beginTransaction();
    
    switch ($action) {
        case 'publish':
            $stmt = $db->prepare("UPDATE contents SET is_published = 1, published_at = NOW(), updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($content_ids);
            $success_count = $stmt->rowCount();
            break;
            
        case 'unpublish':
            $stmt = $db->prepare("UPDATE contents SET is_published = 0, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($content_ids);
            $success_count = $stmt->rowCount();
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM contents WHERE id IN ($placeholders)");
            $stmt->execute($content_ids);
            $success_count = $stmt->rowCount();
            break;
            
        default:
            throw new Exception('无效的操作类型');
    }
    
    $db->commit();
    
    $action_names = [
        'publish' => '发布',
        'unpublish' => '下架',
        'delete' => '删除'
    ];
    
    $message = "成功{$action_names[$action]}了 {$success_count} 个内容";
    header("Location: index.php?success=" . urlencode($message));
    
} catch (Exception $e) {
    $db->rollBack();
    $error_message = "批量操作失败：" . $e->getMessage();
    header("Location: index.php?error=" . urlencode($error_message));
}

exit();
?>