<?php
// AI功能处理器

// 开启错误报告以便调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 确保中文正常显示
header('Content-Type: application/json; charset=utf-8');

// 添加调试日志函数
function debug_log($message, $data = null) {
    $log_file = 'd:/phpstudy_pro/WWW/appadmin/ai_handler_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    if ($data !== null) {
        $log_entry .= "数据: " . print_r($data, true) . "\n";
    }
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

debug_log('AI Handler 请求开始', ['$_SERVER' => $_SERVER]);

// 检查是否已登录
session_start();
debug_log('会话状态检查', ['admin_id' => isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : '未登录']);

if (!isset($_SESSION['admin_id'])) {
    debug_log('未登录用户访问');
    echo json_encode(['success' => false, 'error' => '请先登录']);
    exit();
}

// 包含必要的文件
require_once './includes/config.php';
require_once './includes/database.php';
require_once './includes/functions.php';
require_once './includes/ai_service.php';

// 获取AI服务实例
$ai_service = new AIService();

// 检查AI服务是否已配置
if (!$ai_service->isConfigured()) {
    echo json_encode(['success' => false, 'error' => 'AI服务尚未配置，请在系统设置中配置AI服务']);
    exit();
}

// 获取请求数据
try {
    $raw_data = file_get_contents('php://input');
    debug_log('获取原始请求数据', ['raw_data' => $raw_data]);
    
    $data = json_decode($raw_data, true);
    debug_log('解析后的数据', ['data' => $data]);
    
    // 检查JSON解析是否成功
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_msg = '无效的JSON请求数据，错误: ' . json_last_error_msg();
        debug_log($error_msg);
        echo json_encode(['success' => false, 'error' => $error_msg]);
        exit();
    }
    
    // 获取操作类型
    $action = isset($data['action']) ? $data['action'] : '';
    debug_log('处理操作', ['action' => $action]);
    
    // 处理不同的AI操作
    switch ($action) {
        case 'generate_content':
            // 生成内容
            $title = isset($data['title']) ? $data['title'] : '';
            $prompt = isset($data['prompt']) ? $data['prompt'] : '';
            
            if (empty($title) || empty($prompt)) {
                echo json_encode(['success' => false, 'error' => '标题和提示信息不能为空']);
                break;
            }
            
            $result = $ai_service->generateContent($title, $prompt);
            if ($result['success']) {
                echo json_encode(['success' => true, 'content' => $result['content']]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            break;
            
        case 'optimize_content':
            // 优化内容
            $title = isset($data['title']) ? $data['title'] : '';
            $content = isset($data['content']) ? $data['content'] : '';
            $optimize_type = isset($data['optimize_type']) ? $data['optimize_type'] : '1';
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'error' => '优化内容不能为空']);
                break;
            }
            
            $result = $ai_service->optimizeContent($title, $content, $optimize_type);
            if ($result['success']) {
                echo json_encode(['success' => true, 'content' => $result['content']]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            break;
            
        case 'generate_seo':
            // 生成SEO信息
            $title = isset($data['title']) ? $data['title'] : '';
            $content = isset($data['content']) ? $data['content'] : '';
            $summary = isset($data['summary']) ? $data['summary'] : '';
            
            debug_log('生成SEO信息参数', ['title' => $title, 'content_length' => strlen($content), 'summary_length' => strlen($summary)]);
            
            if (empty($title)) {
                $error_msg = '标题不能为空';
                debug_log($error_msg);
                echo json_encode(['success' => false, 'error' => $error_msg]);
                break;
            }
            
            if (empty($content) && empty($summary)) {
                $error_msg = '内容或摘要至少需要提供一项';
                debug_log($error_msg);
                echo json_encode(['success' => false, 'error' => $error_msg]);
                break;
            }
            
            debug_log('调用AI服务生成SEO信息');
            // 如果有摘要，优先使用摘要，否则使用内容
            $analysis_content = !empty($summary) ? $summary : $content;
            $result = $ai_service->generateSEOMetadata($title, $analysis_content);
            debug_log('AI服务返回SEO结果', ['result' => $result]);
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'seo_title' => $result['seo_title'] ?? '',
                    'seo_keywords' => $result['seo_keywords'] ?? '',
                    'seo_description' => $result['seo_description'] ?? ''
                ];
                debug_log('SEO生成成功，返回数据', ['response' => $response]);
                echo json_encode($response);
            } else {
                $error_msg = 'SEO生成失败: ' . ($result['error'] ?? '未知错误');
                debug_log($error_msg);
                echo json_encode(['success' => false, 'error' => $result['error'] ?? '生成SEO信息失败']);
            }
            break;
            
        case 'generate_image':
            // 生成图片
            $prompt = isset($data['prompt']) ? $data['prompt'] : '';
            
            if (empty($prompt)) {
                echo json_encode(['success' => false, 'error' => '图片描述不能为空']);
                break;
            }
            
            $result = $ai_service->generateImage($prompt);
            if ($result['success']) {
                echo json_encode(['success' => true, 'image_url' => $result['image_url']]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '未知的操作类型']);
            break;
    }
    
} catch (Exception $e) {
    // 捕获所有异常并返回错误信息
    $error_msg = '处理请求时发生错误: ' . $e->getMessage() . '\n堆栈跟踪: ' . $e->getTraceAsString();
    debug_log('AI Handler异常', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    // 记录错误日志到文件
    error_log('AI Handler Error: ' . $error_msg);
    // 返回给客户端的错误信息不需要包含完整堆栈
    echo json_encode(['success' => false, 'error' => '处理请求时发生错误: ' . $e->getMessage()]);
}