<?php
/**
 * 矩阵发布处理脚本
 * 处理前端发送的内容发布请求，调用PlatformManager执行实际的发布操作
 * 已添加发布状态跟踪功能，实时返回各平台发布结果
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 会话初始化
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    $response = ['code' => 1, 'message' => '未登录，请先登录'];
    echo json_encode($response);
    exit;
}

// 检查是否为AJAX请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['code' => 1, 'message' => '非法请求方式'];
    echo json_encode($response);
    exit;
}

// 引入必要的文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/class/PlatformManager.php';

// 初始化数据库连接
$database = Database::getInstance();
$db = $database->getConnection();

// 初始化平台管理器
$platform_manager = new PlatformManager($db);

try {
    // 获取POST数据
    $content_id = intval($_POST['content_id'] ?? 0);
    $platforms = $_POST['platforms'] ?? [];
    
    // 验证必要参数
    if (empty($content_id)) {
        throw new Exception('内容ID不能为空');
    }
    
    if (empty($platforms) || !is_array($platforms)) {
        throw new Exception('请至少选择一个发布平台');
    }
    
    // 获取启用的平台
    $enabled_platforms = $platform_manager->getEnabledPlatforms();
    $enabled_platforms_map = [];
    foreach ($enabled_platforms as $platform) {
        $enabled_platforms_map[$platform['platform_key']] = $platform;
    }
    
    // 过滤有效的平台
    $valid_platforms = [];
    foreach ($platforms as $platform_key) {
        if (isset($enabled_platforms_map[$platform_key])) {
            $valid_platforms[] = $enabled_platforms_map[$platform_key];
        }
    }
    
    if (empty($valid_platforms)) {
        throw new Exception('所选平台中没有已启用的平台，请先在平台配置中启用相应平台');
    }
    
    // 获取内容信息
    $content_info = $platform_manager->getContentInfo($content_id);
    if (!$content_info) {
        throw new Exception('未找到指定的内容');
    }
    
    // 发布结果数组
    $publish_results = [];
    
    // 逐个发布到平台
    foreach ($valid_platforms as $platform) {
        try {
            // 构造发布参数
            $publish_params = [
                'title' => $content_info['title'],
                'content' => $content_info['content'],
                'summary' => $content_info['summary'] ?? '',
                'thumbnail' => $content_info['thumbnail'] ?? ''
            ];
            
            // 执行发布
            $result = $platform_manager->publishToPlatform($platform['platform_key'], $publish_params);
            
            // 记录成功结果
            $publish_results[] = [
                'platform_key' => $platform['platform_key'],
                'platform_name' => $platform['platform_name'],
                'success' => true,
                'message' => $result['message'] ?? '发布成功',
                'data' => $result['data'] ?? []
            ];
        } catch (Exception $e) {
            // 记录失败结果
            $publish_results[] = [
                'platform_key' => $platform['platform_key'],
                'platform_name' => $platform['platform_name'],
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    // 统计成功和失败的数量
    $success_count = 0;
    $fail_count = 0;
    foreach ($publish_results as $result) {
        if ($result['success']) {
            $success_count++;
        } else {
            $fail_count++;
        }
    }
    
    // 构造响应消息
    if ($fail_count == 0) {
        $message = "全部发布成功！共发布到 {$success_count} 个平台。";
    } else if ($success_count == 0) {
        $message = "发布失败！所有平台均发布失败，请检查平台配置。";
    } else {
        $message = "部分发布成功！成功 {$success_count} 个，失败 {$fail_count} 个。";
    }
    
    // 返回详细结果
    $response = [
        'code' => 0,
        'message' => $message,
        'results' => $publish_results
    ];
    
} catch (Exception $e) {
    // 记录错误
    error_log('矩阵发布错误: ' . $e->getMessage());
    $response = [
        'code' => 1,
        'message' => $e->getMessage()
    ];
} finally {
    // 返回JSON响应
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>