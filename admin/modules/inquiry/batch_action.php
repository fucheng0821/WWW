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
$inquiry_ids = $_POST['inquiry_ids'] ?? [];

if (empty($action) || empty($inquiry_ids) || !is_array($inquiry_ids)) {
    header('Location: index.php?error=invalid_parameters');
    exit();
}

// 验证ID格式
$inquiry_ids = array_map('intval', $inquiry_ids);
$inquiry_ids = array_filter($inquiry_ids, function($id) { return $id > 0; });

if (empty($inquiry_ids)) {
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
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM inquiries WHERE id IN ($placeholders)");
            $stmt->execute($inquiry_ids);
            $success_count = $stmt->rowCount();
            $message = "成功删除 {$success_count} 条询价记录";
            break;
            
        case 'mark_processing':
            // 批量标记为处理中
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE inquiries SET status = 'processing', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($inquiry_ids);
            $success_count = $stmt->rowCount();
            $message = "成功标记 {$success_count} 条询价为处理中";
            break;
            
        case 'mark_completed':
            // 批量标记为已完成
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE inquiries SET status = 'completed', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($inquiry_ids);
            $success_count = $stmt->rowCount();
            $message = "成功标记 {$success_count} 条询价为已完成";
            break;
            
        case 'mark_cancelled':
            // 批量标记为已取消
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE inquiries SET status = 'cancelled', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($inquiry_ids);
            $success_count = $stmt->rowCount();
            $message = "成功标记 {$success_count} 条询价为已取消";
            break;
            
        case 'set_priority_high':
            // 批量设置高优先级
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE inquiries SET priority = 'high', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($inquiry_ids);
            $success_count = $stmt->rowCount();
            $message = "成功设置 {$success_count} 条询价为高优先级";
            break;
            
        case 'set_priority_normal':
            // 批量设置普通优先级
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $stmt = $db->prepare("UPDATE inquiries SET priority = 'normal', updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute($inquiry_ids);
            $success_count = $stmt->rowCount();
            $message = "成功设置 {$success_count} 条询价为普通优先级";
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