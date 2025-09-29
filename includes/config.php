<?php
/**
 * 数据库配置文件
 */

// 数据库配置 - 支持移动端和PC端访问
// 数据库配置优化 - 2025年优化版本
// 检测是否为本地访问
$isLocal = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1' || 
           strpos($_SERVER['REMOTE_ADDR'], '192.168.') === 0 || 
           strpos($_SERVER['REMOTE_ADDR'], '10.') === 0);

// 根据访问类型设置数据库主机
if ($isLocal) {
    define('DB_HOST', 'localhost'); // 本地访问使用localhost
} else {
    define('DB_HOST', '127.0.0.1'); // 远程访问使用IP地址
}

define('DB_NAME', 'gaoguangshike_cn');
define('DB_USER', 'gaoguangshike_cn');
define('DB_PASS', 'gaoguangshike_cn');
define('DB_CHARSET', 'utf8mb4');


// 重定向日志记录开关
define('REDIRECT_LOG', true);

// 站点URL（如果尚未定义）
if (!defined('SITE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $protocol . '://' . $host);
}

// 管理后台URL（如果尚未定义）
if (!defined('ADMIN_URL')) {
    // 根据当前请求路径自动判断是admin还是appadmin
    $is_appadmin = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/appadmin/') !== false;
    define('ADMIN_URL', SITE_URL . ($is_appadmin ? '/appadmin' : '/admin'));
}

// 模板目录
define('TEMPLATE_DIR', __DIR__ . '/../templates/default');

// 默认每页显示数量
define('DEFAULT_PER_PAGE', 10);

// 缓存相关配置
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache');

// 安全配置
define('AUTH_KEY', 'gaoguangshike_auth_key_2024');
define('SECURE_AUTH_KEY', 'gaoguangshike_secure_auth_key_2024');
define('LOGGED_IN_KEY', 'gaoguangshike_logged_in_key_2024');
define('NONCE_KEY', 'gaoguangshike_nonce_key_2024');

// AI服务配置
// 可选服务类型: doubao (豆包), deepseek (DeepSeek), qwen (通义千问)
define('AI_SERVICE_TYPE', 'doubao'); // 使用豆包AI服务
define('AI_API_KEY', 'd51560cf-9647-4615-854b-a539859f46be');      // 您的API密钥

// 豆包(豆包)配置说明:
// 1. 访问 https://www.volcengine.com 注册账号
// 2. 在控制台创建推理接入点
// 3. 获取接入点ID并填入下方
// 需要先在火山引擎控制台创建推理接入点，然后填写接入点ID
// 接入点ID格式示例: ep-20240701123456-xxxxx
// 获取方式:
// - 登录火山引擎控制台 (https://console.volcengine.com)
// - 进入"模型推理"服务
// - 创建推理接入点
// - 复制生成的接入点ID
define('DOUBAO_MODEL', 'ep-20250912001448-bkk8s');    // 豆包接入点ID

// DeepSeek配置说明:
// 1. 访问 https://platform.deepseek.com 注册账号
// 2. 在API密钥页面获取API密钥
// 3. 默认使用deepseek-chat模型
define('DEEPSEEK_MODEL', 'deepseek-chat'); // 默认模型

// 通义千问配置说明:
// 1. 访问 https://dashscope.console.aliyun.com 注册账号
// 2. 在API密钥页面获取API密钥
// 3. 默认使用qwen-turbo模型
define('QWEN_MODEL', 'qwen-turbo');        // 默认模型

// 会话配置
define('SESSION_TIMEOUT', 7200); // 2小时

// 调试模式
define('DEBUG_MODE', true);

// 错误报告
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 文件上传配置
define('MAX_UPLOAD_SIZE', 200 * 1024 * 1024); // 200MB
// 支持更多图片格式
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'ogg', 'mkv']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
?>