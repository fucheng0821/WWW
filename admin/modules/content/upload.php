<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

// 检查管理员权限 - 为AJAX请求提供不同的处理方式
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    // 对于AJAX请求，返回JSON错误而不是重定向
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => '未登录或会话已过期，请重新登录']);
        exit();
    } else {
        // 对于非AJAX请求，执行正常的重定向
        redirect(ADMIN_URL . '/login.php');
    }
}

// 检查会话超时
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_destroy();
    // 对于AJAX请求，返回JSON错误而不是重定向
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => '会话已过期，请重新登录']);
        exit();
    } else {
        // 对于非AJAX请求，执行正常的重定向
        redirect(ADMIN_URL . '/login.php');
    }
}

$_SESSION['last_activity'] = time();

// 设置JSON响应头
header('Content-Type: application/json');

// 添加缓存控制头，防止浏览器缓存
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// 添加调试信息
error_log("Upload request received at: " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
error_log("Session data: " . print_r($_SESSION, true));

// 检查是否有文件上传
if (!isset($_FILES['file'])) {
    error_log("No file uploaded - FILES array: " . print_r($_FILES, true));
    echo json_encode(['success' => false, 'error' => '没有文件上传']);
    exit();
}

$file = $_FILES['file'];

// 检查上传错误
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => '文件太大，超过了服务器设置的最大值',
        UPLOAD_ERR_FORM_SIZE => '文件太大，超过了表单设置的最大值',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => '文件上传被扩展程序阻止'
    ];
    
    $error_msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : '未知错误';
    error_log("Upload error: " . $error_msg . " (code: " . $file['error'] . ")");
    echo json_encode(['success' => false, 'error' => '文件上传失败：' . $error_msg]);
    exit();
}

// 获取文件类型（默认为图片）
$fileType = $_POST['type'] ?? 'image';
error_log("File type: " . $fileType);

// 根据文件类型设置不同的大小限制
// 使用配置文件中定义的常量
if ($fileType === 'video') {
    $maxSize = MAX_UPLOAD_SIZE; // 200MB 视频文件
} else {
    $maxSize = 10 * 1024 * 1024; // 10MB 图片文件
}

if ($file['size'] > $maxSize) {
    $maxSizeMB = $maxSize / (1024 * 1024);
    error_log("File too large: " . $file['size'] . " bytes, max allowed: " . $maxSize . " bytes");
    echo json_encode(['success' => false, 'error' => '文件大小不能超过 ' . $maxSizeMB . 'MB']);
    exit();
}

// 获取文件信息
$fileName = $file['name'];
$fileTemp = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

error_log("File info - Name: " . $fileName . ", Temp: " . $fileTemp . ", Size: " . $fileSize);

// 获取文件扩展名
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// 定义允许的文件类型
$allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedVideos = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'ogg', 'mkv'];
$allowedDocs = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

$allowedExts = array_merge($allowedImages, $allowedVideos, $allowedDocs);

// 检查文件类型
if (!in_array($fileExt, $allowedExts)) {
    error_log("Unsupported file extension: " . $fileExt);
    echo json_encode(['success' => false, 'error' => '不支持的文件类型']);
    exit();
}

// For video files, check codec support and format validation
if (in_array($fileExt, $allowedVideos)) {
    // 支持的现代视频编码
    $supportedCodecs = [
        'mp4' => ['H.264', 'H.265', 'VP9', 'AV1'], // 最广泛支持的格式
        'webm' => ['VP8', 'VP9', 'AV1'], // 良好的浏览器支持
        'mov' => ['H.264', 'ProRes', 'HEVC'], // Apple格式
        'avi' => ['MPEG-4', 'H.264'], // 较旧但广泛支持
        'mkv' => ['H.264', 'H.265', 'VP8', 'VP9', 'AV1'] // 容器格式，支持多种编码
    ];
    
    // 检查是否为有效的视频文件
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $fileTemp);
    finfo_close($fileInfo);
    
    // 验证MIME类型
    if (strpos($mimeType, 'video') === false && strpos($mimeType, 'application/octet-stream') === false) {
        error_log("Invalid video file detected: " . $mimeType);
        echo json_encode(['success' => false, 'error' => '文件不是有效的视频格式，请检查文件是否损坏']);
        exit();
    }
    
    // 为常见视频格式提供额外的支持信息
    $formatSupportInfo = [
        'mp4' => 'MP4是最广泛支持的视频格式，推荐使用H.264或H.265编码',
        'webm' => 'WebM格式在网页上有很好的兼容性，推荐使用VP9编码',
        'mov' => 'MOV是Apple的视频格式，在苹果设备上有最佳兼容性',
        'avi' => 'AVI是一种较旧的格式，兼容性好但文件通常较大',
        'mkv' => 'MKV是一种容器格式，支持多种编码，但在某些设备上可能需要转换'
    ];
    
    // 在调试模式下添加额外信息
    if (DEBUG_MODE && isset($formatSupportInfo[$fileExt])) {
        error_log("Video format info: " . $formatSupportInfo[$fileExt]);
    }
}

// 生成唯一文件名，添加时间戳防止缓存
$timestamp = time();
$newFileName = uniqid() . '_' . $timestamp . '.' . $fileExt;

// 根据文件类型确定存储目录
$uploadDir = '../../../uploads/';
if (in_array($fileExt, $allowedImages)) {
    $uploadDir .= 'images/';
} elseif (in_array($fileExt, $allowedVideos)) {
    $uploadDir .= 'videos/';
} else {
    $uploadDir .= 'docs/';
}

// 创建目录（如果不存在）
if (!is_dir($uploadDir)) {
    error_log("Creating directory: " . $uploadDir);
    // 使用递归创建目录，并设置权限
    if (!mkdir($uploadDir, 0755, true)) {
        error_log("Failed to create directory: " . $uploadDir);
        echo json_encode(['success' => false, 'error' => '无法创建上传目录，请检查服务器权限']);
        exit();
    }
}

// 检查目录是否可写
if (!is_writable($uploadDir)) {
    error_log("Upload directory not writable: " . $uploadDir);
    echo json_encode(['success' => false, 'error' => '上传目录没有写权限，请检查服务器设置']);
    exit();
}

$uploadPath = $uploadDir . $newFileName;
error_log("Upload path: " . $uploadPath);

// 添加调试信息
if (DEBUG_MODE) {
    error_log("Upload attempt - File: " . $fileName . ", Type: " . $fileExt . ", Size: " . $fileSize . " bytes");
    error_log("Upload path: " . $uploadPath);
    error_log("Is writable: " . (is_writable(dirname($uploadPath)) ? 'Yes' : 'No'));
}

// 检查文件是否已存在，如果存在则删除旧文件
if (file_exists($uploadPath)) {
    error_log("File already exists, removing old file");
    unlink($uploadPath);
}

// 移动文件
error_log("Attempting to move file from " . $fileTemp . " to " . $uploadPath);
if (move_uploaded_file($fileTemp, $uploadPath)) {
    error_log("File moved successfully");
    // 生成访问URL，添加时间戳参数防止缓存
    // 统一使用相对路径，让前端根据UPLOAD_URL拼接完整URL
    $fileUrl = str_replace('../../../', '', $uploadPath) . '?v=' . $timestamp;
    
    // 如果是图片，生成缩略图
    if (in_array($fileExt, $allowedImages)) {
        $thumbnailPath = $uploadDir . 'thumb_' . $newFileName;
        // 调试信息：检查GD库是否启用
        if (extension_loaded('gd')) {
            if (createThumbnail($uploadPath, $thumbnailPath, 300, 300)) {
                $thumbnailUrl = str_replace('../../../', '', $thumbnailPath) . '?v=' . $timestamp;
            } else {
                $thumbnailUrl = $fileUrl; // 如果缩略图创建失败，使用原图
            }
        } else {
            // GD库未启用，使用原图作为缩略图
            $thumbnailUrl = $fileUrl;
        }
    }
    // 如果是视频，尝试生成缩略图（第一帧）
    elseif (in_array($fileExt, $allowedVideos)) {
        $thumbnailPath = $uploadDir . 'thumb_' . pathinfo($newFileName, PATHINFO_FILENAME) . '.jpg';
        if (extension_loaded('gd')) {
            if (createVideoThumbnail($uploadPath, $thumbnailPath, 300, 300)) {
                $thumbnailUrl = str_replace('../../../', '', $thumbnailPath) . '?v=' . $timestamp;
            } else {
                // 如果无法生成视频缩略图，使用默认视频图标
                $thumbnailUrl = SITE_URL . '/admin/assets/images/video-icon.png';
            }
        } else {
            // GD库未启用，使用默认视频图标
            $thumbnailUrl = SITE_URL . '/admin/assets/images/video-icon.png';
        }
    }
    
    // 记录上传信息到数据库（可选）
    try {
        $stmt = $db->prepare("
            INSERT INTO uploads (filename, original_name, file_path, file_url, file_type, file_size, uploaded_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $newFileName,
            $fileName,
            $uploadPath,
            $fileUrl,
            $fileExt,
            $fileSize,
            $_SESSION['admin_id'] ?? 0
        ]);
    } catch(Exception $e) {
        // 数据库记录失败不影响文件上传
        error_log("Database insert failed: " . $e->getMessage());
    }
    
    // 返回成功响应，包含完整URL
    $response = [
        'success' => true,
        'location' => SITE_URL . '/' . $fileUrl
    ];
    
    // 如果是图片或视频，添加缩略图信息
    if (in_array($fileExt, $allowedImages) || in_array($fileExt, $allowedVideos)) {
        $response['thumbnail'] = SITE_URL . '/' . ($thumbnailUrl ?? $fileUrl);
    }
    
    error_log("Upload successful: " . json_encode($response));
    echo json_encode($response);
} else {
    // 记录详细错误信息
    error_log("Failed to move uploaded file from " . $fileTemp . " to " . $uploadPath);
    error_log("PHP Error: " . (error_get_last()['message'] ?? 'No error message'));
    error_log("File temp path exists: " . (file_exists($fileTemp) ? 'Yes' : 'No'));
    error_log("Upload directory exists: " . (is_dir(dirname($uploadPath)) ? 'Yes' : 'No'));
    error_log("Upload directory writable: " . (is_writable(dirname($uploadPath)) ? 'Yes' : 'No'));
    echo json_encode(['success' => false, 'error' => '文件保存失败']);
}

/**
 * 创建缩略图
 */
function createThumbnail($sourcePath, $thumbnailPath, $maxWidth, $maxHeight) {
    // 添加调试信息
    error_log("Attempting to create thumbnail for: " . $sourcePath);
    error_log("Thumbnail path: " . $thumbnailPath);
    error_log("GD extension loaded: " . (extension_loaded('gd') ? 'Yes' : 'No'));
    
    if (!extension_loaded('gd')) {
        error_log("GD extension is not loaded");
        return false;
    }
    
    // 获取图片信息，支持更多图片格式
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        // 尝试使用更宽松的方法检测图片
        $fileExt = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $imageType = null;
        
        switch ($fileExt) {
            case 'jpg':
            case 'jpeg':
                $imageType = IMAGETYPE_JPEG;
                break;
            case 'png':
                $imageType = IMAGETYPE_PNG;
                break;
            case 'gif':
                $imageType = IMAGETYPE_GIF;
                break;
            case 'webp':
                $imageType = IMAGETYPE_WEBP;
                break;
            default:
                error_log("Unsupported image type: " . $fileExt);
                return false;
        }
        
        // 获取图片尺寸
        list($originalWidth, $originalHeight) = @getimagesize($sourcePath);
        if (!$originalWidth || !$originalHeight) {
            error_log("Failed to get image dimensions for: " . $sourcePath);
            return false;
        }
    } else {
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $imageType = $imageInfo[2];
    }
    
    error_log("Image info: width=" . $originalWidth . ", height=" . $originalHeight . ", type=" . $imageType);
    
    // 计算缩略图尺寸
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $thumbnailWidth = round($originalWidth * $ratio);
    $thumbnailHeight = round($originalHeight * $ratio);
    
    error_log("Thumbnail dimensions: " . $thumbnailWidth . "x" . $thumbnailHeight);
    
    // 创建源图像资源，支持更多格式
    $sourceImage = null;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = @imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $sourceImage = @imagecreatefromwebp($sourcePath);
            } else {
                // 如果不支持WebP，尝试转换为PNG
                $sourceImage = @imagecreatefrompng($sourcePath);
                if (!$sourceImage) {
                    $sourceImage = @imagecreatefromjpeg($sourcePath);
                }
            }
            break;
        default:
            error_log("Unsupported image type for creation: " . $imageType);
            return false;
    }
    
    if (!$sourceImage) {
        error_log("Failed to create source image resource");
        return false;
    }
    
    // 创建缩略图画布
    $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
    
    // 保持透明度
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF || $imageType == IMAGETYPE_WEBP) {
        imagealphablending($thumbnailImage, false);
        imagesavealpha($thumbnailImage, true);
        $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
        imagefilledrectangle($thumbnailImage, 0, 0, $thumbnailWidth, $thumbnailHeight, $transparent);
    }
    
    // 复制并调整大小
    imagecopyresampled(
        $thumbnailImage, $sourceImage, 
        0, 0, 0, 0, 
        $thumbnailWidth, $thumbnailHeight, 
        $originalWidth, $originalHeight
    );
    
    // 保存缩略图
    $saved = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($thumbnailImage, $thumbnailPath, 85);
            break;
        case IMAGETYPE_PNG:
            $saved = imagepng($thumbnailImage, $thumbnailPath, 8);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($thumbnailImage, $thumbnailPath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $saved = imagewebp($thumbnailImage, $thumbnailPath, 85);
            } else {
                // 如果不支持WebP，保存为PNG
                $saved = imagepng($thumbnailImage, $thumbnailPath, 8);
            }
            break;
    }
    
    // 释放内存
    imagedestroy($sourceImage);
    imagedestroy($thumbnailImage);
    
    return $saved;
}

/**
 * 创建视频缩略图
 * @param string $videoPath 视频文件路径
 * @param string $thumbnailPath 缩略图保存路径
 * @param int $maxWidth 最大宽度
 * @param int $maxHeight 最大高度
 * @param int $frameNumber 要截取的帧号，默认为1（第一帧）
 * @return bool 是否成功创建缩略图
 */
function createVideoThumbnail($videoPath, $thumbnailPath, $maxWidth, $maxHeight, $frameNumber = 1) {
    // 注意：当前系统未安装FFmpeg，无法真正截取视频帧
    // 在生产环境中，您应该安装FFmpeg并使用以下命令：
    // exec("ffmpeg -i $videoPath -ss 00:00:0{$frameNumber}.000 -vframes 1 $thumbnailPath 2>&1", $output, $returnCode);
    
    // 当前创建的是占位图，显示指定的帧号

    if (!extension_loaded('gd')) {
        return false;
    }
    
    // Create a simple placeholder image
    $thumbnailWidth = $maxWidth;
    $thumbnailHeight = $maxHeight;
    
    // Create thumbnail image
    $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
    
    // Set background color (dark gray)
    $bgColor = imagecolorallocate($thumbnailImage, 50, 50, 50);
    imagefill($thumbnailImage, 0, 0, $bgColor);
    
    // Set text color (white)
    $textColor = imagecolorallocate($thumbnailImage, 255, 255, 255);
    
    // Draw a play button icon
    $centerX = $thumbnailWidth / 2;
    $centerY = $thumbnailHeight / 2;
    $buttonSize = min($thumbnailWidth, $thumbnailHeight) * 0.3;
    
    // Draw triangle (play button)
    $points = [
        $centerX - $buttonSize/2, $centerY - $buttonSize/2,  // top left
        $centerX + $buttonSize/2, $centerY,                  // right middle
        $centerX - $buttonSize/2, $centerY + $buttonSize/2   // bottom left
    ];
    imagefilledpolygon($thumbnailImage, $points, 3, $textColor);
    
    // 添加说明文字
    $font = 4; // 使用更大的字体
    $text1 = "视频占位图";
    $text2 = "(预设第{$frameNumber}帧)";
    
    // 计算文字宽度
    $text1Width = imagefontwidth($font) * strlen($text1);
    $text2Width = imagefontwidth($font) * strlen($text2);
    
    // 计算文字位置
    $text1X = ($thumbnailWidth - $text1Width) / 2;
    $text2X = ($thumbnailWidth - $text2Width) / 2;
    $textY = $centerY + $buttonSize + 10;
    $text2Y = $textY + 20;
    
    // 绘制文字
    imagestring($thumbnailImage, $font, $text1X, $textY, $text1, $textColor);
    imagestring($thumbnailImage, $font, $text2X, $text2Y, $text2, $textColor);
    
    // Save thumbnail
    $saved = imagejpeg($thumbnailImage, $thumbnailPath, 85);
    
    // Free memory
    imagedestroy($thumbnailImage);
    
    return $saved;
}
?>