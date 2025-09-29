<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 设置响应头
header('Content-Type: application/json');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '只允许POST请求']);
    exit();
}

try {
    // 检查是否有文件上传
    if (!isset($_FILES['file']) && !isset($_FILES['chunk'])) {
        echo json_encode(['success' => false, 'error' => '没有上传文件']);
        exit();
    }

    // 获取上传类型
    $type = $_POST['type'] ?? 'image';
    
    // 处理分块上传
    if (isset($_FILES['chunk'])) {
        handleChunkedUpload($type);
    } else {
        // 处理普通文件上传
        handleRegularUpload($type);
    }
} catch (Exception $e) {
    error_log("上传错误: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '上传失败: ' . $e->getMessage()]);
}

/**
 * 处理普通文件上传
 */
function handleRegularUpload($type) {
    $file = $_FILES['file'];
    
    // 验证文件
    $validation = validateFile($file, $type);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit();
    }
    
    // 生成文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // 确定存储路径
    $uploadDir = '../../../uploads/' . $type . 's/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $filename;
    
    // 移动文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // 生成访问URL - 使用配置文件中定义的SITE_URL常量
        $fileUrl = SITE_URL . '/uploads/' . $type . 's/' . $filename;
        
        echo json_encode([
            'success' => true,
            'location' => $fileUrl,
            'fileUrl' => $fileUrl
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => '文件保存失败']);
    }
}

/**
 * 处理分块上传
 */
function handleChunkedUpload($type) {
    $chunk = $_FILES['chunk'];
    $chunkIndex = intval($_POST['chunkIndex'] ?? 0);
    $totalChunks = intval($_POST['totalChunks'] ?? 1);
    $fileName = $_POST['fileName'] ?? '';
    $fileHash = $_POST['fileHash'] ?? '';
    
    // 验证分块
    if ($chunk['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => '分块上传失败']);
        exit();
    }
    
    // 创建临时目录
    $tempDir = '../../../uploads/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // 保存分块
    $chunkPath = $tempDir . $fileHash . '_chunk_' . $chunkIndex;
    if (!move_uploaded_file($chunk['tmp_name'], $chunkPath)) {
        echo json_encode(['success' => false, 'error' => '分块保存失败']);
        exit();
    }
    
    // 检查是否所有分块都已上传
    $allChunksUploaded = true;
    for ($i = 0; $i < $totalChunks; $i++) {
        if (!file_exists($tempDir . $fileHash . '_chunk_' . $i)) {
            $allChunksUploaded = false;
            break;
        }
    }
    
    // 如果所有分块都已上传，合并文件
    if ($allChunksUploaded) {
        // 生成文件名
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $finalFilename = $fileHash . '.' . $extension;
        
        // 确定存储路径
        $uploadDir = '../../../uploads/' . $type . 's/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $finalPath = $uploadDir . $finalFilename;
        
        // 合并分块
        $finalFile = fopen($finalPath, 'wb');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . $fileHash . '_chunk_' . $i;
            $chunkFile = fopen($chunkPath, 'rb');
            stream_copy_to_stream($chunkFile, $finalFile);
            fclose($chunkFile);
            unlink($chunkPath); // 删除分块
        }
        fclose($finalFile);
        
        // 生成访问URL - 使用配置文件中定义的SITE_URL常量
        $fileUrl = SITE_URL . '/uploads/' . $type . 's/' . $finalFilename;
        
        echo json_encode([
            'success' => true,
            'location' => $fileUrl,  // 统一使用location字段
            'fileUrl' => $fileUrl    // 保留fileUrl字段以兼容旧代码
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => '分块上传成功'
        ]);
    }
}

/**
 * 验证文件
 */
function validateFile($file, $type) {
    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => '文件上传失败'];
    }
    
    // 检查文件大小
    $maxSize = $type === 'video' ? 200 * 1024 * 1024 : 10 * 1024 * 1024; // 视频200MB，图片10MB
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => '文件大小超出限制'];
    }
    
    // 检查文件类型
    $allowedTypes = [];
    if ($type === 'image') {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff'];
    } elseif ($type === 'video') {
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-flv', 'video/x-matroska'];
    }
    
    if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'error' => '不支持的文件类型'];
    }
    
    return ['valid' => true];
}
?>