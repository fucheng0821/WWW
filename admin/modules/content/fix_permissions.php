<?php
/**
 * 修复视频上传所需的目录和权限
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义需要的目录
$directories = [
    '../../../uploads/videos/',
    '../../../uploads/videos/temp/',
    '../../../logs/'
];

// 检查并创建目录
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "目录创建成功: $dir<br>\n";
        } else {
            echo "目录创建失败: $dir<br>\n";
        }
    } else {
        echo "目录已存在: $dir<br>\n";
    }
    
    // 尝试修改权限
    if (chmod($dir, 0755)) {
        echo "权限设置成功: $dir<br>\n";
    } else {
        echo "权限设置失败: $dir<br>\n";
    }
}

// 创建一个空的日志文件用于测试
$logFile = '../../../logs/video_upload.log';
if (!file_exists($logFile)) {
    if (touch($logFile)) {
        echo "日志文件创建成功<br>\n";
        chmod($logFile, 0644);
    } else {
        echo "日志文件创建失败<br>\n";
    }
} else {
    echo "日志文件已存在<br>\n";
}

// 检查PHP配置参数

function get_php_value($name) {
    $value = ini_get($name);
    $unit = '';
    
    if (preg_match('/^([\d\.]+)([KMG])$/i', $value, $matches)) {
        $value = floatval($matches[1]);
        $unit = strtoupper($matches[2]);
        
        switch ($unit) {
            case 'G': $value *= 1024; // no break
            case 'M': $value *= 1024; // no break
            case 'K': $value *= 1024;
        }
    }
    
    return intval($value);
}

$upload_max_filesize = get_php_value('upload_max_filesize');
$post_max_size = get_php_value('post_max_size');
$memory_limit = get_php_value('memory_limit');
$max_execution_time = ini_get('max_execution_time');
$max_input_time = ini_get('max_input_time');

$chunkSize = 5 * 1024 * 1024; // 5MB

?>