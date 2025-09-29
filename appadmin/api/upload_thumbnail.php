<?php
// 缩略图上传处理文件
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit();
}

// 确保上传目录存在
$upload_dir = '../../uploads/images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 初始化文件错误信息
$error_message = '请选择要上传的文件';

// 处理缩略图上传
if (isset($_FILES['thumbnail'])) {
    $file = $_FILES['thumbnail'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // 获取文件信息
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_type = $file['type'];
        
        // 验证文件类型
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['success' => false, 'error' => '不支持的文件类型，请上传JPG、PNG或GIF格式']);
            exit();
        }
        
        // 验证文件大小（限制为5MB）
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file_size > $max_size) {
            echo json_encode(['success' => false, 'error' => '文件大小不能超过5MB']);
            exit();
        }
        
        // 生成唯一文件名
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_id = uniqid();
        $new_filename = $unique_id . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        $relative_path = '/uploads/images/' . $new_filename;
        
        // 移动文件
        if (move_uploaded_file($file_tmp, $upload_path)) {
            // 文件上传成功
            echo json_encode([
                'success' => true,
                'file_path' => $relative_path,
                'file_name' => $new_filename
            ]);
        } else {
            // 文件上传失败
            echo json_encode(['success' => false, 'error' => '文件上传失败，请重试']);
        }
    } else {
        // 上传出错
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = '文件大小超过限制';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = '文件只上传了一部分';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = '没有选择要上传的文件';
                break;
            default:
                $error_message = '上传过程中发生未知错误（错误码：' . $file['error'] . '）';
        }
        echo json_encode(['success' => false, 'error' => $error_message]);
    }
} else {
    // 没有文件上传
    echo json_encode(['success' => false, 'error' => '未收到上传文件']);
}