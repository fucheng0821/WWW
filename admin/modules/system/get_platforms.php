<?php
/**
 * 获取可用的发布平台列表
 * 用于前端发布功能的平台选择
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

// 引入配置文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/PlatformManager.php';

// 检查管理员权限
check_admin_auth();

// 初始化数据库连接
$dbInstance = Database::getInstance();
$conn = $dbInstance->getConnection();

// 初始化平台管理器
$platform_manager = new PlatformManager($conn);

// 获取所有已启用的平台
$platforms = $platform_manager->getEnabledPlatforms();

// 格式化平台数据
$formatted_platforms = [];
foreach ($platforms as $platform) {
    $formatted_platforms[] = [
        'key' => $platform['platform_key'],
        'name' => $platform['platform_name'],
        'checked' => true, // 默认选中所有已启用平台
        'config_complete' => $platform['config_complete'] // 配置是否完整
    ];
}

// 返回平台数据
header('Content-Type: application/json');
echo json_encode([
    'code' => 0,
    'message' => '获取平台数据成功',
    'data' => $formatted_platforms
]);

exit;