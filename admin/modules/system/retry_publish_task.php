<?php
/**
 * 重试发布任务
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

// 重试发布任务
try {
    // 从数据库获取原始日志信息
    $sql = "SELECT * FROM content_publish_logs WHERE id = ?";
    $stmt = $platformManager->db->prepare($sql);
    $stmt->execute([$log_id]);
    $original_log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$original_log) {
        echo json_encode(['success' => false, 'message' => '日志不存在']);
        exit;
    }
    
    // 检查内容是否存在
    $content_info = $platformManager->getContentInfo($original_log['content_id']);
    if (!$content_info) {
        echo json_encode(['success' => false, 'message' => '对应的内容已不存在']);
        exit;
    }
    
    // 检查平台是否启用
    $platform = $platformManager->getPlatform($original_log['platform_key']);
    if (!$platform || $platform['status'] != 1) {
        echo json_encode(['success' => false, 'message' => '发布平台未启用']);
        exit;
    }
    
    // 创建新的发布任务
    $publish_type = !empty($original_log['publish_type']) ? $original_log['publish_type'] : 'auto';
    $new_log_id = $platformManager->createPublishTask(
        $original_log['content_id'], 
        $original_log['platform_key'],
        $publish_type
    );
    
    if (!$new_log_id) {
        echo json_encode(['success' => false, 'message' => '创建新发布任务失败']);
        exit;
    }
    
    // 记录操作日志
    $manager->log('retry_publish', '重试发布任务，原日志ID: ' . $log_id . '，新日志ID: ' . $new_log_id);
    
    // 返回成功信息
    echo json_encode([
        'success' => true,
        'message' => '发布任务已重新提交，将在后台处理',
        'new_log_id' => $new_log_id
    ]);
} catch (PDOException $e) {
    // 记录错误
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('重试发布任务失败: ' . $e->getMessage());
    }
    
    echo json_encode(['success' => false, 'message' => '操作失败，请稍后重试']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>