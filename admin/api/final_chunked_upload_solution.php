<?php
/**
 * 视频分块上传处理脚本
 * 接收分块数据，保存并在所有分块上传完成后合并文件
 */

// 设置响应头
header('Content-Type: application/json');

// 确保请求是POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '只允许POST请求']);
    exit;
}

// 检查必要的参数
if (!isset($_FILES['chunk']) || !isset($_POST['chunkIndex']) || !isset($_POST['totalChunks']) || !isset($_POST['fileName']) || !isset($_POST['fileHash'])) {
    echo json_encode(['success' => false, 'error' => '缺少必要的上传参数']);
    exit;
}

// 获取上传参数
$chunkIndex = intval($_POST['chunkIndex']);
$totalChunks = intval($_POST['totalChunks']);
$fileName = basename($_POST['fileName']); // 安全处理文件名
$fileHash = $_POST['fileHash'];

// 设置上传目录
$uploadDir = __DIR__ . '/../../uploads/videos/';
$tempDir = $uploadDir . 'temp/' . $fileHash . '/';

// 创建必要的目录
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// 保存分块文件
$chunkFilePath = $tempDir . $chunkIndex;
if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkFilePath)) {
    echo json_encode(['success' => false, 'error' => '分块保存失败']);
    exit;
}

// 检查是否所有分块都已上传
$allChunksUploaded = true;
for ($i = 0; $i < $totalChunks; $i++) {
    if (!file_exists($tempDir . $i)) {
        $allChunksUploaded = false;
        break;
    }
}

// 如果所有分块都已上传，则合并文件
if ($allChunksUploaded) {
    // 生成唯一的文件名
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $uniqueFileName = $fileHash . '.' . $extension;
    $finalFilePath = $uploadDir . $uniqueFileName;
    
    // 合并分块
    $finalFile = fopen($finalFilePath, 'wb');
    if ($finalFile === false) {
        echo json_encode(['success' => false, 'error' => '无法创建最终文件']);
        exit;
    }
    
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunk = fopen($tempDir . $i, 'rb');
        if ($chunk === false) {
            fclose($finalFile);
            echo json_encode(['success' => false, 'error' => '无法打开分块文件']);
            exit;
        }
        
        stream_copy_to_stream($chunk, $finalFile);
        fclose($chunk);
        unlink($tempDir . $i); // 删除临时分块文件
    }
    
    fclose($finalFile);
    rmdir($tempDir); // 删除临时目录
    
    // 生成可访问的URL路径
    $webPath = '/uploads/videos/' . $uniqueFileName;
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'partial' => false,
        'filePath' => $webPath
    ]);
} else {
    // 返回部分上传成功的响应
    echo json_encode([
        'success' => true,
        'partial' => true,
        'message' => "分块 $chunkIndex 上传成功"
    ]);
}

?>