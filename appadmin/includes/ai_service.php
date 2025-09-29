<?php
/**
 * AI Service Class
 * Provides integration with various AI services for content generation, image creation, and optimization
 * Supports multiple Chinese AI services including Doubao(豆包), DeepSeek, and Qwen(通义千问)
 */

class AIService {
    private $service_type;
    private $api_key;
    private $api_url;
    private $model;
    
    // Supported Chinese AI services
    const SERVICE_DOUBAO = 'doubao';     // 豆包
    const SERVICE_DEEPSEEK = 'deepseek'; // DeepSeek
    const SERVICE_QWEN = 'qwen';         // 通义千问
    
    public function __construct() {
        // Load service configuration from config
        $this->service_type = defined('AI_SERVICE_TYPE') ? AI_SERVICE_TYPE : '';
        $this->api_key = defined('AI_API_KEY') ? AI_API_KEY : '';
        
        // Set service-specific configurations
        switch ($this->service_type) {
            case self::SERVICE_DOUBAO:
                // For Doubao, the model ID is part of the URL path
                $this->api_url = 'https://ark.cn-beijing.volces.com/api/v3/chat/completions';
                $this->model = defined('DOUBAO_MODEL') ? DOUBAO_MODEL : '';
                break;
            case self::SERVICE_DEEPSEEK:
                $this->api_url = 'https://api.deepseek.com/v1/chat/completions';
                $this->model = defined('DEEPSEEK_MODEL') ? DEEPSEEK_MODEL : 'deepseek-chat';
                break;
            case self::SERVICE_QWEN:
                $this->api_url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';
                $this->model = defined('QWEN_MODEL') ? QWEN_MODEL : 'qwen-turbo';
                break;
            default:
                $this->api_url = '';
                $this->model = '';
        }
    }
    
    /**
     * Check if AI services are configured
     */
    public function isConfigured() {
        // Check basic configuration
        if (empty($this->service_type) || empty($this->api_key) || empty($this->api_url)) {
            return false;
        }
        
        // For Doubao service, also check if model is properly configured
        if ($this->service_type === self::SERVICE_DOUBAO) {
            // Check if DOUBAO_MODEL is set and not the default placeholder
            if (empty($this->model) || $this->model === 'ep-xxxxxx') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get configuration error message
     */
    public function getConfigErrorMessage() {
        if (empty($this->service_type)) {
            return 'AI服务未配置，请在配置文件中设置服务类型';
        }
        
        if (empty($this->api_key)) {
            return 'AI服务API密钥未配置，请在配置文件中设置API密钥';
        }
        
        if (empty($this->api_url)) {
            return 'AI服务URL未配置';
        }
        
        // For Doubao service, check if model is properly configured
        if ($this->service_type === self::SERVICE_DOUBAO) {
            if (empty($this->model) || $this->model === 'ep-xxxxxx') {
                return '豆包AI服务接入点ID未配置，请在配置文件中设置DOUBAO_MODEL';
            }
        }
        
        return 'AI服务配置不完整';
    }
    
    /**
     * Get service name for display
     */
    public function getServiceName() {
        switch ($this->service_type) {
            case self::SERVICE_DOUBAO:
                return '豆包AI';
            case self::SERVICE_DEEPSEEK:
                return 'DeepSeek';
            case self::SERVICE_QWEN:
                return '通义千问';
            default:
                return '未配置AI服务';
        }
    }
    
    /**
     * Get API URL for network testing
     * @return string API URL
     */
    public function getApiUrl() {
        return $this->api_url;
    }
    
    /**
     * Generate content using AI
     * @param string $prompt The prompt for content generation
     * @param float $temperature Temperature parameter (0-2, default: 0.7)
     * @param int $max_tokens Maximum tokens (default: 2000)
     * @return array Result with success status and generated content
     */
    public function generateContent($prompt, $temperature = null, $max_tokens = null) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => $this->getConfigErrorMessage()
            ];
        }
        
        // Use configuration values if parameters are not provided
        if ($temperature === null) {
            $temperature = defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.7;
        }
        
        if ($max_tokens === null) {
            $max_tokens = defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2000;
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => '你是一个专业的中文内容创作者，擅长撰写高质量的文章内容。'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];
        
        $response = $this->makeApiRequest($data);
        
        if ($response['success']) {
            $content = $response['data']['choices'][0]['message']['content'] ?? '';
            return [
                'success' => true,
                'content' => $content
            ];
        }
        
        return $response;
    }
    
    /**
     * Optimize content using AI
     * @param string $content The content to optimize
     * @param string $title The title of the content
     * @param string $optimization_prompt Specific optimization instructions
     * @return array Result with success status and optimized content
     */
    public function optimizeContent($content, $title = '', $optimization_prompt = '') {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => $this->getConfigErrorMessage()
            ];
        }
        
        // 获取配置的温度参数
        $temperature = defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.7;
        $max_tokens = defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : 2500;
        
        // 基础提示词
        $base_prompt = "请优化以下内容，使其更加流畅、专业且易于阅读。保持原意不变，但提升语言质量和表达效果。";
        
        // 首先检查是否有从配置文件中定义的全局优化提示词
        if (empty($optimization_prompt) && defined('AI_OPTIMIZATION_PROMPT')) {
            $optimization_prompt = AI_OPTIMIZATION_PROMPT;
        }
        
        // 如果有特定的优化提示，则添加到基础提示词中
        if (!empty($optimization_prompt)) {
            $base_prompt .= "\n\n特别提示：{$optimization_prompt}";
        } else {
            // 默认的优化要求
            $base_prompt .= "\n\n同时：
1. 优化段落结构，使文章更具美感和可读性
   - 合理划分段落，每个段落聚焦一个核心观点
   - 在应该分段的地方自动分段，避免大段文字
   - 使用过渡词和连接词使段落间逻辑更清晰
   - 保持段落长度适中，避免过长或过短
2. 根据文章风格和内容主题，适当添加相关的emoji表情符号来增强视觉效果
   - 在标题或重点句子前添加合适的emoji
   - 在列举项前添加项目符号emoji
   - 根据情感色彩添加表达情绪的emoji
   - 确保emoji使用自然且符合中文表达习惯
3. 增强文章美感
   - 使用更生动的词汇和表达方式
   - 适当运用修辞手法（比喻、排比等）
   - 保持语言风格一致性和专业性
   - 增强文字的节奏感和韵律感
4. 保持专业性的同时增加文章的生动性";
        }
        
        // 完整的提示词
        $prompt = "{$base_prompt}

标题：{$title}

内容：{$content}";
        
        // 系统提示：强调只根据用户的特定优化提示执行任务
        $system_prompt = '你是一个专业的中文内容编辑，擅长根据用户的具体要求优化文章内容。

重要原则：严格按照用户提供的具体优化提示进行操作，只执行提示中要求的优化任务，不进行任何额外的优化或修改。保持原文的核心意思不变，只在用户指定的方面进行优化。';

        $messages = [
            [
                'role' => 'system',
                'content' => $system_prompt
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature, // Slightly higher temperature for more creative emoji usage
            'max_tokens' => $max_tokens // Increased token limit to accommodate emoji additions
        ];
        
        $response = $this->makeApiRequest($data);
        
        if ($response['success']) {
            $optimized_content = $response['data']['choices'][0]['message']['content'] ?? '';
            return [
                'success' => true,
                'content' => $optimized_content
            ];
        }
        
        return $response;
    }
    
    /**
     * Generate SEO metadata using AI
     * @param string $title The title of the content
     * @param string $content The content to analyze
     * @return array Result with success status and SEO metadata
     */
    public function generateSEOMetadata($title, $content) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => $this->getConfigErrorMessage()
            ];
        }
        
        // 获取配置的温度参数
        $temperature = defined('AI_TEMPERATURE') ? AI_TEMPERATURE : 0.3;
        $max_tokens = defined('AI_MAX_TOKENS') ? min(AI_MAX_TOKENS, 500) : 500; // 限制最大tokens为500，因为SEO元数据不需要太长
        
        $prompt = "基于以下标题和内容，生成SEO优化所需的元数据：\n\n标题：{$title}\n\n内容：{$content}\n\n请提供：\n1. SEO标题（不超过60个字符）\n2. SEO关键词（3-5个，用逗号分隔）\n3. SEO描述（不超过160个字符）\n\n请以JSON格式返回结果，格式如下：\n{\n  \"seo_title\": \"...\",\n  \"seo_keywords\": \"...\",\n  \"seo_description\": \"...\"\n}";
        
        $messages = [
            [
                'role' => 'system',
                'content' => '你是一个专业的SEO专家，擅长根据内容生成优化的SEO元数据。'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
            'response_format' => ['type' => 'json_object']
        ];
        
        $response = $this->makeApiRequest($data);
        
        if ($response['success']) {
            $result = $response['data']['choices'][0]['message']['content'] ?? '';
            $seo_data = json_decode($result, true);
            
            if ($seo_data) {
                return [
                    'success' => true,
                    'seo_title' => $seo_data['seo_title'] ?? $title,
                    'seo_keywords' => $seo_data['seo_keywords'] ?? '',
                    'seo_description' => $seo_data['seo_description'] ?? ''
                ];
            }
        }
        
        return $response;
    }
    
    /**
     * Generate image using AI (DALL-E)
     * Note: Most Chinese AI services don't have native image generation capabilities
     * This function will return a message indicating the limitation
     * @param string $prompt The prompt for image generation
     * @return array Result with success status and message
     */
    public function generateImage($prompt) {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => $this->getConfigErrorMessage()
            ];
        }
        
        // Most Chinese AI services don't have native image generation capabilities
        // Return a helpful message instead
        return [
            'success' => false,
            'error' => '当前使用的AI服务(' . $this->getServiceName() . ')暂不支持图像生成功能。建议使用支持图像生成的AI服务，或使用系统自带的图片上传功能。'
        ];
    }
    
    /**
     * Make API request to the selected AI service
     * @param array $data Request data
     * @return array Response data
     */
    private function makeApiRequest($data) {
        $headers = $this->getAuthHeaders();
        
        // 确保cURL扩展已加载
        if (!function_exists('curl_init')) {
            $this->logCriticalError('服务器未安装cURL扩展，无法连接AI服务');
            return [
                'success' => false,
                'error' => '服务器未安装cURL扩展，无法连接AI服务'
            ];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 增加超时时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 连接超时时间
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // For development only
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // 优先使用IPv4
        
        // 为豆包服务添加特殊处理
        if ($this->service_type === self::SERVICE_DOUBAO) {
            // 豆包服务可能需要额外的头部或参数
            // 如果需要可以在这里添加
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $error_no = curl_errno($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);
        
        // 记录调试信息（仅在调试模式下）
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->logDebugInfo([
                'service_type' => $this->service_type,
                'api_url' => $this->api_url,
                'request_data' => $data,
                'headers' => $headers,
                'http_code' => $http_code,
                'curl_error' => $error,
                'curl_error_no' => $error_no,
                'curl_info' => $curl_info,
                'response' => substr($response, 0, 500) // 只记录部分响应以避免日志过大
            ]);
        }
        
        if ($error) {
            // 增强的网络错误处理
            $error_message = $this->getNetworkErrorMessage($error_no, $error);
            $this->logCriticalError($error_message . ' - 服务类型: ' . $this->service_type . ', API URL: ' . $this->api_url);
            return [
                'success' => false,
                'error' => $error_message,
                'error_no' => $error_no
            ];
        }
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if ($result) {
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                // 尝试使用更宽松的JSON解析选项
                $result = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
                if ($result) {
                    return [
                        'success' => true,
                        'data' => $result
                    ];
                }
                return [
                    'success' => false,
                    'error' => '响应解析失败: ' . json_last_error_msg()
                ];
            }
        } else {
            // 根据不同服务类型处理错误响应
            $error_result = json_decode($response, true);
            
            // 服务特定的错误处理
            switch ($this->service_type) {
                case self::SERVICE_DOUBAO:
                    $error_message = $error_result['error']['message'] ?? $error_result['message'] ?? '豆包服务请求失败';
                    break;
                case self::SERVICE_DEEPSEEK:
                    $error_message = $error_result['error']['message'] ?? 'DeepSeek服务请求失败';
                    break;
                case self::SERVICE_QWEN:
                    $error_message = $error_result['message'] ?? '通义千问服务请求失败';
                    break;
                default:
                    $error_message = 'API请求失败，HTTP状态码: ' . $http_code;
            }
            
            // 处理常见错误情况
            if ($http_code === 401) {
                $error_message = 'API密钥无效或已过期，请检查配置';
            } elseif ($http_code === 429) {
                $error_message = '请求过于频繁，请稍后再试';
            } elseif ($http_code >= 500) {
                $error_message = 'AI服务暂时不可用，请稍后再试';
            }
            
            return [
                'success' => false,
                'error' => $error_message,
                'http_code' => $http_code
            ];
        }
    }
    
    /**
     * 记录调试信息到日志文件
     * @param array $info 要记录的信息
     */
    private function logDebugInfo($info) {
        if (!defined('LOG_DIR')) {
            return;
        }
        
        $log_file = LOG_DIR . '/ai_service_debug.log';
        $log_message = "[" . date('Y-m-d H:i:s') . "] " . json_encode($info) . "\n";
        
        // 尝试写入日志文件
        @file_put_contents($log_file, $log_message, FILE_APPEND);
    }
    
    /**
     * 记录关键错误信息（无论是否在调试模式下）
     * @param string $error_message 错误信息
     */
    private function logCriticalError($error_message) {
        // 获取日志目录（如果未定义LOG_DIR，则使用默认路径）
        $log_dir = defined('LOG_DIR') ? LOG_DIR : dirname(dirname(__FILE__)) . '/logs';
        
        // 确保日志目录存在
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . '/ai_service_error.log';
        $log_message = "[" . date('Y-m-d H:i:s') . "] " . $error_message . "\n";
        
        // 尝试写入日志文件
        @file_put_contents($log_file, $log_message, FILE_APPEND);
    }
    
    /**
     * 根据cURL错误码获取更友好的网络错误消息
     * @param int $error_no cURL错误码
     * @param string $error cURL错误信息
     * @return string 友好的错误消息
     */
    private function getNetworkErrorMessage($error_no, $error) {
        $error_messages = [
            CURLE_COULDNT_RESOLVE_HOST => '无法解析主机名，请检查网络连接或API地址是否正确',
            CURLE_COULDNT_CONNECT => '无法连接到AI服务，请检查网络连接和防火墙设置',
            CURLE_OPERATION_TIMEOUTED => 'AI服务请求超时，请检查网络连接或稍后重试',
            CURLE_SSL_CONNECT_ERROR => 'SSL连接错误，请检查SSL证书配置',
            CURLE_REMOTE_ACCESS_DENIED => '远程访问被拒绝，请检查API密钥和权限设置',
            CURLE_GOT_NOTHING => '未收到AI服务的响应，请检查网络连接和服务器状态'
        ];
        
        if (isset($error_messages[$error_no])) {
            return $error_messages[$error_no];
        }
        
        // 通用错误消息
        return '网络错误，请稍后重试（错误代码: ' . $error_no . '）';
    }
    
    /**
     * Get authentication headers based on service type
     * @return array Headers for API request
     */
    private function getAuthHeaders() {
        switch ($this->service_type) {
            case self::SERVICE_DOUBAO:
                // 豆包 uses Authorization header with Bearer token
                return [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key
                ];
            case self::SERVICE_DEEPSEEK:
                // DeepSeek uses Authorization header with Bearer token (OpenAI compatible)
                return [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key
                ];
            case self::SERVICE_QWEN:
                // 通义千问 uses Alibaba Cloud API key in X-DashScope-Authorization header
                return [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key,
                    'X-DashScope-SSE: enable' // Enable server-sent events if needed
                ];
            default:
                return [
                    'Content-Type: application/json'
                ];
        }
    }
}