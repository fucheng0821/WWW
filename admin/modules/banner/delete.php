<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取Banner ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

// 检查Banner是否存在
try {
    $stmt = $db->prepare("SELECT id FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    
    if (!$banner) {
        header('Location: index.php?error=banner_not_found');
        exit();
    }
} catch(PDOException $e) {
    header('Location: index.php?error=database_error');
    exit();
}

// 执行删除操作
try {
    $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    
    // 重定向到列表页并显示成功消息
    header('Location: index.php?success=deleted');
    exit();
} catch(PDOException $e) {
    header('Location: index.php?error=database_error');
    exit();
}