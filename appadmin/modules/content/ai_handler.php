<?php
/**
 * AI Handler for Mobile
 * Processes AJAX requests for AI features in mobile admin
 */

header('Content-Type: application/json');
session_start();

// 添加调试信息
error_log("AI Handler accessed at: " . date('Y-m-d H:i:s'));

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/ai_service.php';

// 检查管理员权限
check_admin_auth();

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// 获取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 添加调试信息
error_log("Request data: " . print_r($data, true));

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$action = $data['action'];
$ai_service = new AIService();

// 检查AI服务是否已配置
if (!$ai_service->isConfigured()) {
    $error_msg = 'AI服务未配置，请在配置文件中设置服务类型和API密钥';
    error_log($error_msg);
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit();
}

// 添加调试信息
error_log("AI Service configured, action: " . $action);

switch ($action) {
    case 'generate_content':
        handleGenerateContent($ai_service, $data);
        break;
        
    case 'optimize_content':
        handleOptimizeContent($ai_service, $data);
        break;
        
    case 'generate_seo':
        handleGenerateSEO($ai_service, $data);
        break;
        
    case 'generate_image':
        handleGenerateImage($ai_service, $data);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}

/**
 * Handle content generation request
 */
function handleGenerateContent($ai_service, $data) {
    $prompt = $data['prompt'] ?? '';
    
    if (empty($prompt)) {
        echo json_encode(['success' => false, 'error' => 'Prompt is required']);
        return;
    }
    
    // 添加调试信息
    error_log("Generating content with prompt: " . $prompt);
    
    $result = $ai_service->generateContent($prompt);
    
    // 添加调试信息
    error_log("AI response: " . print_r($result, true));
    
    echo json_encode($result);
}

/**
 * Handle content optimization request
 */
function handleOptimizeContent($ai_service, $data) {
    $content = $data['content'] ?? '';
    $title = $data['title'] ?? '';
    $optimize_type = $data['optimize_type'] ?? '1'; // 默认优化emoji表情
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Content is required']);
        return;
    }
    
    // Clean the content for better AI processing while preserving important formatting
    // Allow more HTML tags that contribute to美感 and structure
    $clean_content = strip_tags($content, '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><ol><ul><li><blockquote><div><span>');
    
    // 根据不同的优化类型生成不同的优化提示，确保每个优化类型独立工作，不影响其他方面
    $optimization_prompt = '';
    
    switch ($optimize_type) {
        case '1':
            // 优化emoji表情 - 只优化表情，不影响其他
            $optimization_prompt = '请只优化内容中的emoji表情使用：1. 根据上下文和语义添加适当的emoji表情；2. 确保emoji使用自然且符合中文表达习惯；3. 不要对段落结构、排版或其他格式进行任何修改。';
            break;
        case '2':
            // 优化段落 - 只优化段落结构，不影响其他
            $optimization_prompt = '请只优化内容的段落结构：1. 确保逻辑清晰，段落划分合理；2. 使用恰当的过渡词；3. 不要添加或修改任何emoji表情；4. 不要对排版或其他格式进行任何修改。';
            break;
        case '3':
            // 优化格式 - 处理#符号、两端对齐和保留数字
            $optimization_prompt = '请按照以下要求优化内容格式：1. 遇到文中有####或###符号就替换成<br>标签；2. 删除所有#符号；3. 全文内容两端对齐；4. 保留阿拉伯数字，不要删除或修改；5. 不要对段落内容本身进行修改。';
            break;
        case '4':
            // 优化排版 - 只优化排版，不影响其他
            $optimization_prompt = '请只优化内容的整体排版：1. 调整标题层级；2. 优化列表使用；3. 确保内容布局清晰；4. 不要添加或修改任何emoji表情；5. 不要对段落结构进行大的调整。';
            break;
        default:
            // 默认优化
            $optimization_prompt = '请优化内容，提升语言质量和表达效果。';
            break;
    }
    
    $result = $ai_service->optimizeContent($clean_content, $title, $optimization_prompt);
    echo json_encode($result);
}

/**
 * Handle SEO metadata generation request
 */
function handleGenerateSEO($ai_service, $data) {
    $title = $data['title'] ?? '';
    $content = $data['content'] ?? '';
    $summary = $data['summary'] ?? '';
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Title is required']);
        return;
    }
    
    // 如果没有提供内容，使用摘要作为内容
    if (empty($content) || $content === '<p>开始编写您的内容...</p>') {
        $content = $summary;
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Content or summary is required']);
        return;
    }
    
    $result = $ai_service->generateSEOMetadata($title, $content);
    echo json_encode($result);
}

/**
 * Handle image generation request
 */
function handleGenerateImage($ai_service, $data) {
    $prompt = $data['prompt'] ?? '';
    
    if (empty($prompt)) {
        echo json_encode(['success' => false, 'error' => 'Prompt is required']);
        return;
    }
    
    $result = $ai_service->generateImage($prompt);
    echo json_encode($result);
}