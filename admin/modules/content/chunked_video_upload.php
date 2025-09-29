<?php
/**
 * 视频分块上传处理脚本
 * 用于后台内容管理中的视频上传，支持最大200MB文件，带进度条显示和失败原因提示
 */

session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

// 设置UTF-8编码
header('Content-Type: application/json; charset=utf-8');

// 启用错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 验证用户权限
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => '未授权访问']);
    exit;
}

// 创建上传目录
$uploadDir = '../../../uploads/videos/';
$tempDir = $uploadDir . 'temp/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// 检查目录权限
if (!is_writable($uploadDir) || !is_writable($tempDir)) {
    echo json_encode(['success' => false, 'error' => '上传目录没有写入权限']);
    exit;
}

// 配置
$maxChunkSize = 5 * 1024 * 1024; // 5MB每块
$maxFileSize = 200 * 1024 * 1024; // 200MB

// 允许的视频格式
$allowedVideoTypes = [
    'video/mp4', 'video/webm', 'video/ogg', 'video/avi', 
    'video/mov', 'video/wmv', 'video/flv', 'video/mkv'
];

// 允许的文件扩展名
$allowedExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'mkv'];

// 生成唯一的文件ID
function generateFileId($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . $ext;
}

// 日志记录函数
function logUploadEvent($message, $fileId = '') {
    $logPath = '../../../logs/video_upload.log';
    $logDir = dirname($logPath);
    
    // 创建日志目录（如果不存在）
    if (!is_dir($logDir)) {
        if (mkdir($logDir, 0755, true)) {
            // 目录创建成功
        } else {
            // 目录创建失败，尝试直接写入文件
            error_log("无法创建日志目录: $logDir");
        }
    }
    
    // 确保日志文件存在
    if (!file_exists($logPath)) {
        if (touch($logPath)) {
            chmod($logPath, 0644);
        } else {
            // 文件创建失败，使用PHP错误日志
            error_log("无法创建日志文件: $logPath");
            return;
        }
    }
    
    // 尝试写入日志
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [" . ($fileId ? $fileId : 'GENERAL') . "] $message\n";
    
    if (file_put_contents($logPath, $logEntry, FILE_APPEND) === false) {
        // 写入失败，使用PHP错误日志
        error_log($logEntry);
    }
}

// 处理分块上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取上传参数
        $chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
        $totalChunks = isset($_POST['totalChunks']) ? intval($_POST['totalChunks']) : 1;
        $fileId = isset($_POST['fileId']) ? $_POST['fileId'] : '';
        $fileName = isset($_POST['fileName']) ? $_POST['fileName'] : '';
        $fileSize = isset($_POST['fileSize']) ? intval($_POST['fileSize']) : 0;
        
        // 验证文件大小
        if ($fileSize > $maxFileSize) {
            echo json_encode(['success' => false, 'error' => '文件大小超过200MB限制']);
            logUploadEvent("文件大小超过限制: $fileName ($fileSize bytes)");
            exit;
        }
        
        // 如果是第一个分块，生成文件ID
        if ($chunk === 0 && empty($fileId)) {
            $fileId = generateFileId($fileName);
            logUploadEvent("开始上传: $fileName", $fileId);
        }
        
        // 验证文件ID格式
        if (!preg_match('/^\d+_[a-f0-9]+\.\w+$/', $fileId)) {
            echo json_encode(['success' => false, 'error' => '无效的文件ID']);
            exit;
        }
        
        // 检查文件扩展名
        $fileExt = strtolower(pathinfo($fileId, PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExtensions)) {
            echo json_encode(['success' => false, 'error' => '不支持的文件格式']);
            logUploadEvent("不支持的文件格式: $fileExt", $fileId);
            exit;
        }
        
        // 保存分块
        $chunkFilePath = $tempDir . $fileId . '.part' . $chunk;
        
        if (isset($_FILES['chunkFile']) && $_FILES['chunkFile']['error'] === UPLOAD_ERR_OK) {
            if (move_uploaded_file($_FILES['chunkFile']['tmp_name'], $chunkFilePath)) {
                // 检查是否所有分块都已上传
                $allChunksUploaded = true;
                for ($i = 0; $i < $totalChunks; $i++) {
                    if (!file_exists($tempDir . $fileId . '.part' . $i)) {
                        $allChunksUploaded = false;
                        break;
                    }
                }
                
                // 如果所有分块都已上传，合并文件
                if ($allChunksUploaded) {
                    $finalFilePath = $uploadDir . $fileId;
                    
                    // 合并分块
                    if ($out = fopen($finalFilePath, 'wb')) {
                        for ($i = 0; $i < $totalChunks; $i++) {
                            $partFilePath = $tempDir . $fileId . '.part' . $i;
                            if ($in = fopen($partFilePath, 'rb')) {
                                while ($buff = fread($in, 4096)) {
                                    fwrite($out, $buff);
                                }
                                fclose($in);
                                // 删除分块文件
                                unlink($partFilePath);
                            }
                        }
                        fclose($out);
                        
                        // 检查文件类型
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $finalFilePath);
                        finfo_close($finfo);
                        
                        if (!in_array($mimeType, $allowedVideoTypes)) {
                            unlink($finalFilePath);
                            echo json_encode(['success' => false, 'error' => '上传的文件不是有效的视频文件']);
                            logUploadEvent("无效的视频文件类型: $mimeType", $fileId);
                            exit;
                        }
                        
                        // 生成URL
                        $fileUrl = str_replace('../../../', '', $finalFilePath);
                        $fullUrl = SITE_URL . '/' . $fileUrl;
                        
                        // 记录上传信息到数据库
                        try {
                            $stmt = $db->prepare("INSERT INTO uploads (filename, original_name, file_path, file_url, file_type, file_size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                            $stmt->execute([$fileId, $fileName, $finalFilePath, $fileUrl, $fileExt, $fileSize, $_SESSION['admin_id']]);
                        } catch(Exception $e) {
                            // 数据库记录失败不影响文件上传
                            logUploadEvent("数据库记录失败: " . $e->getMessage(), $fileId);
                        }
                        
                        logUploadEvent("上传完成: $fileName -> $fileId", $fileId);
                        
                        // 返回成功响应
                        echo json_encode([
                            'success' => true,
                            'fileId' => $fileId,
                            'location' => $fullUrl,
                            'fileName' => $fileId,
                            'originalName' => $fileName
                        ]);
                    } else {
                        throw new Exception('无法创建目标文件');
                    }
                } else {
                    // 返回部分上传成功
                    echo json_encode([
                        'success' => true,
                        'chunk' => $chunk,
                        'totalChunks' => $totalChunks,
                        'fileId' => $fileId,
                        'message' => '分块上传成功'
                    ]);
                }
            } else {
                throw new Exception('无法保存分块文件');
            }
        } else {
            $errorMsg = '文件上传错误: ' . ($_FILES['chunkFile']['error'] ?? '未知错误');
            throw new Exception($errorMsg);
        }
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        logUploadEvent("上传失败: $errorMsg", isset($fileId) ? $fileId : '');
    }
} else {
    echo json_encode(['success' => false, 'error' => '不支持的请求方法']);
}
?>