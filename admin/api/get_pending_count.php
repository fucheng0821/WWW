<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// 设置JSON响应头
header('Content-Type: application/json');

// 检查管理员权限
if (!check_admin_auth(false)) {
    echo json_encode(['success' => false, 'message' => '未授权访问']);
    exit();
}

try {
    $response = ['success' => true];
    
    // 获取待处理询价数量
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM inquiries WHERE status = 'pending'");
    $stmt->execute();
    $response['pending_inquiries'] = $stmt->fetch()['count'];
    
    // 获取草稿内容数量
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contents WHERE is_published = 0");
    $stmt->execute();
    $response['draft_content'] = $stmt->fetch()['count'];
    
    // 获取今日新增询价
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM inquiries WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $response['today_inquiries'] = $stmt->fetch()['count'];
    
    // 获取今日新增内容
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contents WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $response['today_contents'] = $stmt->fetch()['count'];
    
    echo json_encode($response);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => '获取数据失败：' . $e->getMessage()
    ]);
}
?>