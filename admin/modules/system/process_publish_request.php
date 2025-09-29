<?php
/**
 * 处理内容发布请求的后端接口
 * 接收前端发送的内容ID、选择的平台和发布类型，将任务添加到发布队列
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 会话初始化
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 401,
        'message' => '请先登录'
    ]);
    exit;
}

// 检查是否为AJAX请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 405,
        'message' => '仅支持POST请求'
    ]);
    exit;
}

// 引入配置文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/class/PlatformManager.php';

// 检查管理员权限
check_admin_auth();

// 获取请求数据
$post_data = json_decode(file_get_contents('php://input'), true);

// 验证请求数据
if (!isset($post_data['content_id']) || !isset($post_data['platforms']) || !isset($post_data['publish_type'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 400,
        'message' => '参数不完整'
    ]);
    exit;
}

// 初始化平台管理器
$platform_manager = new PlatformManager($db);

// 获取内容信息
$content_id = $post_data['content_id'];
$content_info = $platform_manager->getContentInfo($content_id);

if (!$content_info) {
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 404,
        'message' => '内容不存在'
    ]);
    exit;
}

// 获取选择的平台和发布类型
$platforms = $post_data['platforms'];
$publish_type = $post_data['publish_type'];

// 验证平台是否存在
$all_platforms = $platform_manager->getAllPlatforms();
$valid_platform_keys = array_column($all_platforms, 'platform_key');

$invalid_platforms = array_diff($platforms, $valid_platform_keys);
if (!empty($invalid_platforms)) {
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 400,
        'message' => '存在无效的平台: ' . implode(', ', $invalid_platforms)
    ]);
    exit;
}

// 验证发布类型是否合法
$valid_publish_types = ['auto', 'article', 'video'];
if (!in_array($publish_type, $valid_publish_types)) {
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 400,
        'message' => '无效的发布类型'
    ]);
    exit;
}

// 批量发布时的处理
if (isset($post_data['batch']) && $post_data['batch'] === true && is_array($content_id)) {
    $success_count = 0;
    $fail_count = 0;
    $fail_reasons = [];
    
    foreach ($content_id as $id) {
        $content_info = $platform_manager->getContentInfo($id);
        
        if (!$content_info) {
            $fail_count++;
            $fail_reasons[] = "内容ID $id 不存在";
            continue;
        }
        
        // 为每个内容创建发布任务
        $task_created = false;
        foreach ($platforms as $platform_key) {
            $result = $platform_manager->createPublishTask($id, $platform_key, $publish_type);
            if ($result) {
                $task_created = true;
            }
        }
        
        if ($task_created) {
            $success_count++;
        } else {
            $fail_count++;
            $fail_reasons[] = "内容ID $id 创建发布任务失败";
        }
    }
    
    // 返回批量处理结果
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 0,
        'message' => "批量发布任务提交成功，共提交 $success_count 条内容，失败 $fail_count 条",
        'data' => [
            'success_count' => $success_count,
            'fail_count' => $fail_count,
            'fail_reasons' => $fail_reasons
        ]
    ]);
} else {
    // 单个内容发布处理
    $task_ids = [];
    
    foreach ($platforms as $platform_key) {
        $task_id = $platform_manager->createPublishTask($content_id, $platform_key, $publish_type);
        if ($task_id) {
            $task_ids[] = $task_id;
        }
    }
    
    if (!empty($task_ids)) {
        // 返回成功响应
        header('Content-Type: application/json');
        echo json_encode([
            'code' => 0,
            'message' => '发布任务已成功提交到队列，共 ' . count($task_ids) . ' 个平台',
            'data' => [
                'content_id' => $content_id,
                'task_ids' => $task_ids,
                'platforms_count' => count($platforms)
            ]
        ]);
    } else {
        // 返回失败响应
        header('Content-Type: application/json');
        echo json_encode([
            'code' => 500,
            'message' => '发布任务创建失败'
        ]);
    }
}

exit;