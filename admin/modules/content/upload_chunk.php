<?php
/**
 * 视频分块上传处理脚本
 * 此脚本用于处理前端发送的视频文件块，并在所有块上传完成后进行合并
 */

// 确保没有任何额外输出
ob_start();

// 确保中文正常显示
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 设置上传目录
$uploadDir = '../../uploads/videos/';
$tempDir = $uploadDir . 'temp/';

// 创建目录（如果不存在）
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responseError('只支持POST请求');
}

// 获取上传参数
$chunkIndex = isset($_POST['chunkIndex']) ? intval($_POST['chunkIndex']) : -1;
$totalChunks = isset($_POST['totalChunks']) ? intval($_POST['totalChunks']) : -1;
$uploadId = isset($_POST['uploadId']) ? $_POST['uploadId'] : '';
$fileName = isset($_POST['filename']) ? $_POST['filename'] : '';
$fileType = isset($_POST['type']) ? $_POST['type'] : '';

// 记录请求信息用于调试
// file_put_contents('upload_debug.log', print_r($_POST, true), FILE_APPEND);

// 验证参数
if ($chunkIndex < 0 || $totalChunks <= 0 || empty($uploadId) || empty($fileName)) {
    responseError('参数不完整');
}

// 验证文件类型
if ($fileType === 'video') {
    $allowedExtensions = ['mp4', 'webm', 'mov', 'avi', 'flv'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        responseError('不支持的视频文件格式');
    }
}

// 确保临时目录存在
$uploadTempDir = $tempDir . $uploadId . '/';
if (!is_dir($uploadTempDir)) {
    mkdir($uploadTempDir, 0777, true);
}

// 处理文件块
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $chunkFilePath = $uploadTempDir . $chunkIndex;
    
    // 移动上传的文件块到临时目录
    if (move_uploaded_file($_FILES['file']['tmp_name'], $chunkFilePath)) {
        // 检查是否所有块都已上传
        $uploadedChunks = array_diff(scandir($uploadTempDir), ['.', '..']);
        
        // 如果已上传块的数量等于总块数，则合并文件
        if (count($uploadedChunks) == $totalChunks) {
            // 生成唯一的文件名
            $uniqueFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $finalFilePath = $uploadDir . $uniqueFileName;
            
            // 合并文件块
            $finalFile = fopen($finalFilePath, 'wb');
            
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $uploadTempDir . $i;
                
                if (file_exists($chunkPath)) {
                    $chunkFile = fopen($chunkPath, 'rb');
                    stream_copy_to_stream($chunkFile, $finalFile);
                    fclose($chunkFile);
                    
                    // 删除临时块文件
                    unlink($chunkPath);
                }
            }
            
            fclose($finalFile);
            
            // 删除临时目录
            rmdir($uploadTempDir);
            
            // 返回成功响应，包含最终文件的URL
            responseSuccess([
                'success' => true,
                'url' => '/uploads/videos/' . $uniqueFileName,
                'fileName' => $uniqueFileName,
                'fileSize' => filesize($finalFilePath),
                'message' => '文件上传完成'
            ]);
        } else {
            // 还有块未上传，返回当前进度
            responseSuccess([
                'success' => true,
                'chunkIndex' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'uploadedChunks' => count($uploadedChunks),
                'message' => '块上传成功'
            ]);
        }
    } else {
        responseError('文件块保存失败');
    }
} else {
    responseError('文件上传失败: ' . (isset($_FILES['file']['error']) ? getUploadErrorMessage($_FILES['file']['error']) : '未知错误'));
}

/**
 * 获取上传错误消息
 */
function getUploadErrorMessage($errorCode) {
    $messages = [
        UPLOAD_ERR_OK => '没有错误发生，文件上传成功',
        UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
        UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传'
    ];
    
    return isset($messages[$errorCode]) ? $messages[$errorCode] : '未知上传错误';
}

/**
 * 返回错误响应
 */
function responseError($message) {
    // 清空输出缓冲区，确保没有任何额外输出
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

/**
 * 返回成功响应
 */
function responseSuccess($data) {
    // 清空输出缓冲区，确保没有任何额外输出
    ob_clean();
    
    echo json_encode($data);
    exit;
}