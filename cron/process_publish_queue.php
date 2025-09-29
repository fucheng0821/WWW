<?php
/**
 * 处理发布任务队列脚本
 * 该脚本应该通过定时任务（如crontab）定期执行，处理待发布的内容
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(__FILE__)));

// 引入必要的文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/class/PlatformManager.php';

// 初始化数据库连接
$db = new Database();
$pdo = $db->connect();

// 初始化平台管理器
$platform_manager = new PlatformManager($pdo);

// 记录开始时间
$start_time = microtime(true);
$log_file = BASE_DIR . '/logs/publish_queue_' . date('Y-m-d') . '.log';

// 写入日志
function log_message($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // 在调试模式下，也输出到控制台
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo $log_entry;
    }
}

// 创建日志目录（如果不存在）
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// 记录脚本启动
log_message("开始处理发布任务队列", $log_file);

try {
    // 处理发布队列，每次处理最多20个任务
    $results = $platform_manager->processPublishQueue(20);
    
    // 统计结果
    $success_count = 0;
    $failed_count = 0;
    
    foreach ($results as $result) {
        if ($result['success']) {
            $success_count++;
            log_message("成功发布内容 #{$result['content_id']} 到平台 {$result['platform_key']}", $log_file);
        } else {
            $failed_count++;
            log_message("发布内容 #{$result['content_id']} 到平台 {$result['platform_key']} 失败", $log_file);
        }
    }
    
    // 记录完成情况
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);
    log_message("发布队列处理完成: 共处理 {$success_count} 个成功, {$failed_count} 个失败, 耗时 {$execution_time} 秒", $log_file);
    
} catch (Exception $e) {
    // 记录错误
    log_message("处理发布队列时发生错误: " . $e->getMessage(), $log_file);
    
    // 输出错误信息
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "错误: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString();
    }
}

// 关闭数据库连接
$db = null;

// 记录脚本结束
log_message("发布队列处理脚本结束\n", $log_file);
?>