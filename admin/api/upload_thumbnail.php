<?php
/**
 * 处理视频缩略图上传请求
 * 接收前端捕获的视频帧并保存为缩略图
 */

// 确保只有POST请求才能访问此页面
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '只允许POST请求']);
    exit;
}

// 检查是否有文件上传
if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '未收到有效的缩略图文件']);
    exit;
}

// 使用绝对路径确保文件保存位置正确
$projectRoot = $_SERVER['DOCUMENT_ROOT'];
$uploadDir = $projectRoot . '/uploads/images/';

// 添加调试信息
//error_log('缩略图上传调试 - 项目根目录: ' . $projectRoot, 0);
//error_log('缩略图上传调试 - 上传目录: ' . $uploadDir, 0);

// 确保目录存在
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '无法创建保存目录: ' . $uploadDir]);
        exit;
    }
}

// 生成唯一的文件名
$timestamp = time();
$randomStr = substr(md5(uniqid()), 0, 8);
$originalName = basename($_FILES['thumbnail']['name']);
$fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
$fileName = 'video_thumbnail_' . $timestamp . '_' . $randomStr . '.' . ($fileExtension ?: 'jpg');
$filePath = $uploadDir . $fileName;

// 移动上传的文件
if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $filePath)) {
    // 确保文件权限正确
    chmod($filePath, 0644);
    
    // 返回成功响应，包含缩略图URL
    $thumbnailUrl = '/uploads/images/' . $fileName;
    //error_log('缩略图上传成功 - 文件路径: ' . $filePath, 0);
    echo json_encode(['success' => true, 'thumbnailUrl' => $thumbnailUrl]);
} else {
    // 移动文件失败，添加详细错误信息
    $errorMsg = '无法保存缩略图文件。检查目录权限和路径是否正确。';
    //error_log('缩略图上传失败 - 目标路径: ' . $filePath, 0);
    
    // 获取系统错误信息
    if (function_exists('error_get_last')) {
        $lastError = error_get_last();
        if ($lastError) {
            $errorMsg .= ' 系统错误: ' . $lastError['message'];
        }
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}
?>