<?php
/**
 * 发布内容到外部平台
 * 处理前端发送的内容发布请求，支持发布到多个平台
 */

// 设置响应头
header('Content-Type: application/json');

// 引入必要文件
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/PlatformManager.php';

// 检查管理员权限
check_admin_auth();

// 初始化数据库连接
$db = Database::getInstance();
$conn = $db->getConnection();

// 获取POST数据
$postData = file_get_contents('php://input');
if (empty($postData)) {
    echo json_encode([
        'code' => 1,
        'message' => '请求数据为空'
    ]);
    exit;
}

// 解析JSON数据
$data = json_decode($postData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'code' => 1,
        'message' => '请求数据格式错误'
    ]);
    exit;
}

// 验证必要字段
$requiredFields = ['content_id', 'title', 'content', 'platforms'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            'code' => 1,
            'message' => '缺少必要参数: ' . $field
        ]);
        exit;
    }
}

// 验证平台列表
if (!is_array($data['platforms']) || empty($data['platforms'])) {
    echo json_encode([
        'code' => 1,
        'message' => '请选择至少一个平台'
    ]);
    exit;
}

// 初始化PlatformManager
$platformManager = new PlatformManager($conn);

// 准备发布结果
$results = [];
$successCount = 0;
$failCount = 0;

// 记录发布日志
$publishTime = date('Y-m-d H:i:s');
$scheduledTime = isset($data['scheduled_time']) ? $data['scheduled_time'] : null;
$publishType = isset($data['publish_type']) ? $data['publish_type'] : 'normal';

// 保存发布任务到数据库
$contentId = $data['content_id'];
$title = $conn->real_escape_string($data['title']);
$summary = isset($data['summary']) ? $conn->real_escape_string($data['summary']) : '';
$thumbnail = isset($data['thumbnail']) ? $conn->real_escape_string($data['thumbnail']) : '';
$platformsJson = $conn->real_escape_string(json_encode($data['platforms']));

// 保存发布记录
$insertSql = "INSERT INTO platform_publish_records (content_id, title, summary, thumbnail, platforms, publish_type, scheduled_time, status, created_at, updated_at) 
              VALUES ('$contentId', '$title', '$summary', '$thumbnail', '$platformsJson', '$publishType', '$scheduledTime', 'pending', '$publishTime', '$publishTime')";

if (!$conn->query($insertSql)) {
    echo json_encode([
        'code' => 1,
        'message' => '保存发布记录失败: ' . $conn->error
    ]);
    exit;
}

$publishRecordId = $conn->insert_id;

// 处理发布任务
// 如果是定时发布，则只保存记录，不立即发布
if ($publishType === 'scheduled' && !empty($scheduledTime)) {
    echo json_encode([
        'code' => 0,
        'message' => '定时发布任务已创建',
        'data' => [
            'publish_record_id' => $publishRecordId,
            'scheduled_time' => $scheduledTime
        ]
    ]);
    exit;
}

// 立即发布或保存草稿
foreach ($data['platforms'] as $platformKey) {
    try {
        // 获取平台配置
        $platformConfig = $platformManager->getPlatform($platformKey);
        if (!$platformConfig) {
            throw new Exception('平台配置不存在');
        }
        
        // 验证平台配置是否完整
        if (!$platformManager->isConfigComplete($platformConfig)) {
            throw new Exception('平台配置不完整');
        }
        
        // 构建发布数据
        $publishParams = [
            'title' => $data['title'],
            'content' => $data['content'],
            'summary' => isset($data['summary']) ? $data['summary'] : '',
            'thumbnail' => isset($data['thumbnail']) ? $data['thumbnail'] : '',
            'publish_type' => $publishType
        ];
        
        // 调用平台特定的发布方法
        $platformResult = $platformManager->publishToPlatform($platformKey, $publishParams);
        
        // 记录结果
        $results[$platformKey] = [
            'success' => true,
            'message' => $platformResult['message'] ?? '发布成功',
            'data' => $platformResult['data'] ?? null
        ];
        $successCount++;
        
    } catch (Exception $e) {
        // 记录失败
        $results[$platformKey] = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        $failCount++;
    }
}

// 更新发布记录状态
$status = ($failCount === 0) ? 'success' : ($successCount > 0 ? 'partial' : 'failed');
$updateSql = "UPDATE platform_publish_records SET status = '$status', updated_at = '$publishTime', results = '" . $conn->real_escape_string(json_encode($results)) . "' WHERE id = $publishRecordId";
$conn->query($updateSql);

// 返回结果
if ($failCount === 0) {
    $message = '所有平台发布成功';
} elseif ($successCount === 0) {
    $message = '所有平台发布失败';
} else {
    $message = "部分平台发布成功 ($successCount 成功, $failCount 失败)";
}

echo json_encode([
    'code' => ($failCount === 0) ? 0 : 1,
    'message' => $message,
    'data' => [
        'results' => $results,
        'publish_record_id' => $publishRecordId,
        'success_count' => $successCount,
        'fail_count' => $failCount
    ]
]);

exit;