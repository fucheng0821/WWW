<?php
/**
 * 获取发布日志详情
 */

// 初始化会话和检查登录状态
if (!defined('IN_ADMIN')) {
    define('IN_ADMIN', true);
}
require_once('../../../../../includes/common.inc.php');

// 验证用户权限
if (!isset($manager) || !$manager->isLogin()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

if (!$manager->hasPermission('system_publish_logs')) {
    echo json_encode(['success' => false, 'message' => '无权限操作']);
    exit;
}

// 获取参数
$log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;

if ($log_id <= 0) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

// 引入PlatformManager类
require_once(INCLUDE_PATH . 'class/PlatformManager.php');
$platformManager = new PlatformManager();

// 获取日志详情
try {
    // 从数据库获取日志信息
    $sql = "SELECT cpl.*, pc.platform_name, c.title AS content_title 
            FROM content_publish_logs cpl 
            LEFT JOIN platform_configs pc ON cpl.platform_key = pc.platform_key 
            LEFT JOIN contents c ON cpl.content_id = c.id 
            WHERE cpl.id = ?";
    
    $stmt = $platformManager->db->prepare($sql);
    $stmt->execute([$log_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$log) {
        echo json_encode(['success' => false, 'message' => '日志不存在']);
        exit;
    }
    
    // 返回日志详情
    echo json_encode([
        'success' => true,
        'log' => $log
    ]);
} catch (PDOException $e) {
    // 记录错误
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('获取发布日志详情失败: ' . $e->getMessage());
    }
    
    echo json_encode(['success' => false, 'message' => '获取日志详情失败，请稍后重试']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>