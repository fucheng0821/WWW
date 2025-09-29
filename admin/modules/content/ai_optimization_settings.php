<?php
/**
 * AI优化设置页面
 * 用于配置AI智能助手的各项参数
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 会话初始化 - 检查会话状态后只启动一次
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录 - 使用与check_admin_auth函数一致的会话变量
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    header('Location: ../../login.php');
    exit;
}

// 引入配置文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/ai_service.php';

// 检查管理员权限
check_admin_auth();

// 初始化AI服务实例
$ai_service = new AIService();

// 配置项
$config_options = [
    'AI_SERVICE_TYPE' => [
        'label' => 'AI服务类型',
        'type' => 'select',
        'options' => [
            'doubao' => '豆包AI',
            'deepseek' => 'DeepSeek',
            'qwen' => '通义千问'
        ],
        'default' => 'doubao'
    ],
    'AI_API_KEY' => [
        'label' => 'API密钥',
        'type' => 'password',
        'placeholder' => '请输入AI服务的API密钥'
    ],
    'DOUBAO_MODEL' => [
        'label' => '豆包接入点ID',
        'type' => 'text',
        'placeholder' => 'ep-xxxxxxxxxxxx-xxxxxx',
        'condition' => function() { return defined('AI_SERVICE_TYPE') && AI_SERVICE_TYPE === 'doubao'; }
    ],
    'DEEPSEEK_MODEL' => [
        'label' => 'DeepSeek模型',
        'type' => 'select',
        'options' => [
            'deepseek-chat' => 'deepseek-chat',
            'deepseek-llm-7b-chat' => 'deepseek-llm-7b-chat',
            'deepseek-llm-16b-chat' => 'deepseek-llm-16b-chat'
        ],
        'default' => 'deepseek-chat',
        'condition' => function() { return defined('AI_SERVICE_TYPE') && AI_SERVICE_TYPE === 'deepseek'; }
    ],
    'QWEN_MODEL' => [
        'label' => '通义千问模型',
        'type' => 'select',
        'options' => [
            'qwen-turbo' => 'qwen-turbo',
            'qwen-plus' => 'qwen-plus',
            'qwen-max' => 'qwen-max'
        ],
        'default' => 'qwen-turbo',
        'condition' => function() { return defined('AI_SERVICE_TYPE') && AI_SERVICE_TYPE === 'qwen'; }
    ],
    'AI_TEMPERATURE' => [
        'label' => '温度参数',
        'type' => 'range',
        'min' => 0,
        'max' => 2,
        'step' => 0.1,
        'default' => 0.7,
        'description' => '控制生成内容的随机性，值越高内容越随机，值越低内容越确定'
    ],
    'AI_MAX_TOKENS' => [
        'label' => '最大Token数',
        'type' => 'number',
        'min' => 500,
        'max' => 10000,
        'step' => 100,
        'default' => 2000,
        'description' => '控制生成内容的最大长度'
    ],
    'AI_OPTIMIZATION_PROMPT' => [
        'label' => '优化提示词',
        'type' => 'textarea',
        'placeholder' => '请输入自定义的内容优化提示词',
        'rows' => 4,
        'description' => '用于内容优化的基础提示词，留空使用系统默认提示词'
    ]
];

// 初始化消息变量
$success_msg = '';
$error_msg = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = 'CSRF验证失败，请刷新页面重试';
    } else {
        try {
            // 构建新的配置内容
            $new_config = "<?php\n/**\n * 数据库配置文件\n */\n\n";
            
            // 数据库配置部分保持不变
            $new_config .= "// 数据库配置 - 支持移动端和PC端访问\n";
            $new_config .= "// 数据库配置优化 - 2025年优化版本\n";
            $new_config .= "// 检测是否为本地访问\n";
            $new_config .= "\$isLocal = (\$_SERVER['REMOTE_ADDR'] === '127.0.0.1' || \$_SERVER['REMOTE_ADDR'] === '::1' || \n";
            $new_config .= "           strpos(\$_SERVER['REMOTE_ADDR'], '192.168.') === 0 || \n";
            $new_config .= "           strpos(\$_SERVER['REMOTE_ADDR'], '10.') === 0);\n\n";
            $new_config .= "// 根据访问类型设置数据库主机\n";
            $new_config .= "if (\$isLocal) {\n";
            $new_config .= "    define('DB_HOST', 'localhost'); // 本地访问使用localhost\n";
            $new_config .= "} else {\n";
            $new_config .= "    define('DB_HOST', '127.0.0.1'); // 远程访问使用IP地址\n";
            $new_config .= "}\n\n";
            $new_config .= "define('DB_NAME', 'gaoguangshike_cn');\n";
            $new_config .= "define('DB_USER', 'gaoguangshike_cn');\n";
            $new_config .= "define('DB_PASS', 'gaoguangshike_cn');\n";
            $new_config .= "define('DB_CHARSET', 'utf8mb4');\n\n";
            
            // 网站配置保持不变
            $new_config .= "// 网站配置\n";
            $new_config .= "define('SITE_URL', 'http://gaoguangshike.cn');\n";
            $new_config .= "define('BASE_URL', SITE_URL); // 添加BASE_URL定义，与SITE_URL保持一致\n";
            $new_config .= "define('SITE_ROOT', dirname(__FILE__));\n";
            $new_config .= "define('ADMIN_URL', SITE_URL . '/admin');\n";
            $new_config .= "define('TEMPLATE_DIR', SITE_ROOT . '/templates/default');\n";
            $new_config .= "define('UPLOAD_DIR', SITE_ROOT . '/uploads');\n";
            $new_config .= "define('UPLOAD_URL', SITE_URL . '/uploads');\n\n";
            
            // 日志和缓存目录保持不变
            $new_config .= "// 日志和缓存目录\n";
            $new_config .= "define('LOG_DIR', SITE_ROOT . '/logs');\n";
            $new_config .= "define('CACHE_DIR', SITE_ROOT . '/cache');\n\n";
            
            // 安全配置保持不变
            $new_config .= "// 安全配置\n";
            $new_config .= "define('AUTH_KEY', 'gaoguangshike_auth_key_2024');\n";
            $new_config .= "define('SECURE_AUTH_KEY', 'gaoguangshike_secure_auth_key_2024');\n";
            $new_config .= "define('LOGGED_IN_KEY', 'gaoguangshike_logged_in_key_2024');\n";
            $new_config .= "define('NONCE_KEY', 'gaoguangshike_nonce_key_2024');\n\n";
            
            // AI服务配置 - 这部分将被更新
            $new_config .= "// AI服务配置\n";
            $new_config .= "// 可选服务类型: doubao (豆包), deepseek (DeepSeek), qwen (通义千问)\n";
            $new_config .= "define('AI_SERVICE_TYPE', '" . ($_POST['AI_SERVICE_TYPE'] ?? 'doubao') . "'); // 使用豆包AI服务\n";
            $new_config .= "define('AI_API_KEY', '" . ($_POST['AI_API_KEY'] ?? '') . "');      // 您的API密钥\n\n";
            
            // 豆包配置
            $new_config .= "// 豆包(豆包)配置说明:\n";
            $new_config .= "// 1. 访问 https://www.volcengine.com 注册账号\n";
            $new_config .= "// 2. 在控制台创建推理接入点\n";
            $new_config .= "// 3. 获取接入点ID并填入下方\n";
            $new_config .= "// 需要先在火山引擎控制台创建推理接入点，然后填写接入点ID\n";
            $new_config .= "// 接入点ID格式示例: ep-20240701123456-xxxxx\n";
            $new_config .= "// 获取方式:\n";
            $new_config .= "// - 登录火山引擎控制台 (https://console.volcengine.com)\n";
            $new_config .= "// - 进入\"模型推理\"服务\n";
            $new_config .= "// - 创建推理接入点\n";
            $new_config .= "// - 复制生成的接入点ID\n";
            $new_config .= "define('DOUBAO_MODEL', '" . ($_POST['DOUBAO_MODEL'] ?? 'ep-20250912001448-bkk8s') . "');    // 豆包接入点ID\n\n";
            
            // DeepSeek配置
            $new_config .= "// DeepSeek配置说明:\n";
            $new_config .= "// 1. 访问 https://platform.deepseek.com 注册账号\n";
            $new_config .= "// 2. 在API密钥页面获取API密钥\n";
            $new_config .= "// 3. 默认使用deepseek-chat模型\n";
            $new_config .= "define('DEEPSEEK_MODEL', '" . ($_POST['DEEPSEEK_MODEL'] ?? 'deepseek-chat') . "'); // 默认模型\n\n";
            
            // 通义千问配置
            $new_config .= "// 通义千问配置说明:\n";
            $new_config .= "// 1. 访问 https://dashscope.console.aliyun.com 注册账号\n";
            $new_config .= "// 2. 在API密钥页面获取API密钥\n";
            $new_config .= "// 3. 默认使用qwen-turbo模型\n";
            $new_config .= "define('QWEN_MODEL', '" . ($_POST['QWEN_MODEL'] ?? 'qwen-turbo') . "');        // 默认模型\n\n";
            
            // 其他AI参数配置
            $new_config .= "// AI参数配置\n";
            $new_config .= "define('AI_TEMPERATURE', " . ($_POST['AI_TEMPERATURE'] ?? 0.7) . "); // 控制生成内容的随机性\n";
            $new_config .= "define('AI_MAX_TOKENS', " . ($_POST['AI_MAX_TOKENS'] ?? 2000) . "); // 控制生成内容的最大长度\n";
            if (!empty($_POST['AI_OPTIMIZATION_PROMPT'])) {
                $new_config .= "define('AI_OPTIMIZATION_PROMPT', '" . addslashes($_POST['AI_OPTIMIZATION_PROMPT']) . "'); // 自定义内容优化提示词\n\n";
            }
            
            // 会话配置保持不变
            $new_config .= "// 会话配置\n";
            $new_config .= "define('SESSION_TIMEOUT', 7200); // 2小时\n\n";
            
            // 调试模式保持不变
            $new_config .= "// 调试模式\n";
            $new_config .= "define('DEBUG_MODE', true);\n\n";
            
            // 错误报告保持不变
            $new_config .= "// 错误报告\n";
            $new_config .= "if (DEBUG_MODE) {\n";
            $new_config .= "    error_reporting(E_ALL);\n";
            $new_config .= "    ini_set('display_errors', 1);\n";
            $new_config .= "} else {\n";
            $new_config .= "    error_reporting(0);\n";
            $new_config .= "    ini_set('display_errors', 0);\n";
            $new_config .= "}\n\n";
            
            // 时区设置保持不变
            $new_config .= "// 时区设置\n";
            $new_config .= "date_default_timezone_set('Asia/Shanghai');\n\n";
            
            // 文件上传配置保持不变
            $new_config .= "// 文件上传配置\n";
            $new_config .= "define('MAX_UPLOAD_SIZE', 200 * 1024 * 1024); // 200MB\n";
            $new_config .= "// 支持更多图片格式\n";
            $new_config .= "define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif']);\n";
            $new_config .= "define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'ogg', 'mkv']);\n";
            $new_config .= "define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);\n";
            $new_config .= "?>";
            
            // 写入新的配置文件
            file_put_contents(BASE_DIR . '/includes/config.php', $new_config);
            
            // 创建或更新ai_settings表用于存储额外的AI配置
            try {
                // 检查表是否存在
                $stmt = $db->query("SHOW TABLES LIKE 'ai_settings'");
                if ($stmt->rowCount() == 0) {
                    // 创建表
                    $create_table_sql = "\n" .
                    "CREATE TABLE `ai_settings` (\n" .
                    "  `id` int(11) NOT NULL AUTO_INCREMENT,\n" .
                    "  `setting_key` varchar(100) NOT NULL COMMENT '设置键名',\n" .
                    "  `setting_value` text NOT NULL COMMENT '设置值',\n" .
                    "  `created_at` datetime NOT NULL COMMENT '创建时间',\n" .
                    "  `updated_at` datetime NOT NULL COMMENT '更新时间',\n" .
                    "  PRIMARY KEY (`id`),\n" .
                    "  UNIQUE KEY `setting_key` (`setting_key`)\n" .
                    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI设置表';";
                    $db->exec($create_table_sql);
                }
                
                // 保存其他可能的AI设置
                $settings_to_save = [
                    'optimization_prompt' => $_POST['AI_OPTIMIZATION_PROMPT'] ?? '',
                    'temperature' => $_POST['AI_TEMPERATURE'] ?? 0.7,
                    'max_tokens' => $_POST['AI_MAX_TOKENS'] ?? 2000
                ];
                
                foreach ($settings_to_save as $key => $value) {
                    // 检查设置是否已存在
                    $stmt = $db->prepare("SELECT id FROM ai_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $exists = $stmt->fetchColumn() !== false;
                    
                    if ($exists) {
                        // 更新设置
                        $stmt = $db->prepare("UPDATE ai_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                        $stmt->execute([$value, $key]);
                    } else {
                        // 插入新设置
                        $stmt = $db->prepare("INSERT INTO ai_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                        $stmt->execute([$key, $value]);
                    }
                }
            } catch(Exception $e) {
                // 忽略数据库错误，继续执行
                error_log('AI设置存储错误: ' . $e->getMessage());
            }
            
            $success_msg = 'AI设置已成功更新！';
        } catch(Exception $e) {
            $error_msg = '更新设置失败：' . $e->getMessage();
        }
    }
}

// 生成CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 获取当前配置值
function get_current_config_value($key, $config_options) {
    if (defined($key)) {
        return constant($key);
    }
    return $config_options[$key]['default'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI优化设置 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
    <style>
        .config-section {
            background: #fff;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .config-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 16px;
            font-weight: 600;
        }
        .config-item {
            margin-bottom: 20px;
        }
        .config-item .layui-form-label {
            width: 150px;
        }
        .config-item .layui-input-block {
            margin-left: 180px;
        }
        .config-description {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        .test-result {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #009688;
            border-radius: 0 4px 4px 0;
            display: none;
        }
        .test-success {
            border-left-color: #009688;
        }
        .test-error {
            border-left-color: #ff5722;
        }
        .range-value {
            display: inline-block;
            min-width: 60px;
            text-align: center;
            margin-left: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-container">
                <div class="layui-row">
                    <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                <h2>AI优化设置</h2>
                            </div>
                            <div class="layui-card-body">
                                
                                <?php if ($success_msg): ?>
                                    <div class="layui-alert layui-alert-success" style="margin: 20px;">
                                        <?php echo htmlspecialchars($success_msg); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($error_msg): ?>
                                    <div class="layui-alert layui-alert-danger" style="margin: 20px;">
                                        <?php echo htmlspecialchars($error_msg); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="config-section">
                                    <h3>基本信息</h3>
                                    <div class="config-item">
                                        <label class="layui-form-label">当前AI服务</label>
                                        <div class="layui-input-block">
                                            <div class="layui-form-mid layui-word-aux">
                                                <?php echo $ai_service->getServiceName(); ?>
                                                <?php if ($ai_service->isConfigured()): ?>
                                                    <span class="layui-badge layui-bg-green">已配置</span>
                                                <?php else: ?>
                                                    <span class="layui-badge layui-bg-red">未配置</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!$ai_service->isConfigured()): ?>
                                        <div class="config-item">
                                            <label class="layui-form-label">配置状态</label>
                                            <div class="layui-input-block">
                                                <div class="layui-form-mid layui-word-aux">
                                                    <span class="layui-text-danger"><?php echo $ai_service->getConfigErrorMessage(); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <form class="layui-form" method="POST" action="ai_optimization_settings.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    
                                    <div class="config-section">
                                        <h3>服务配置</h3>
                                        
                                        <!-- AI服务类型 -->
                                        <div class="config-item">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['AI_SERVICE_TYPE']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <select name="AI_SERVICE_TYPE" id="ai-service-type" lay-verify="required">
                                                    <?php foreach ($config_options['AI_SERVICE_TYPE']['options'] as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" 
                                                            <?php echo get_current_config_value('AI_SERVICE_TYPE', $config_options) === $value ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <!-- API密钥 -->
                                        <div class="config-item">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['AI_API_KEY']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <input type="password" 
                                                       name="AI_API_KEY" 
                                                       placeholder="<?php echo $config_options['AI_API_KEY']['placeholder']; ?>" 
                                                       value="<?php echo get_current_config_value('AI_API_KEY', $config_options); ?>" 
                                                       class="layui-input">
                                                <div class="config-description">
                                                    请输入您的AI服务API密钥，该密钥将用于所有AI相关功能的认证
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 豆包接入点ID -->
                                        <div class="config-item" id="doubao-config" 
                                            style="display: <?php echo (get_current_config_value('AI_SERVICE_TYPE', $config_options) === 'doubao') ? 'block' : 'none'; ?>">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['DOUBAO_MODEL']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <input type="text" 
                                                       name="DOUBAO_MODEL" 
                                                       placeholder="<?php echo $config_options['DOUBAO_MODEL']['placeholder']; ?>" 
                                                       value="<?php echo get_current_config_value('DOUBAO_MODEL', $config_options); ?>" 
                                                       class="layui-input">
                                                <div class="config-description">
                                                    请输入您在火山引擎创建的豆包推理接入点ID
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- DeepSeek模型 -->
                                        <div class="config-item" id="deepseek-config" 
                                            style="display: <?php echo (get_current_config_value('AI_SERVICE_TYPE', $config_options) === 'deepseek') ? 'block' : 'none'; ?>">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['DEEPSEEK_MODEL']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <select name="DEEPSEEK_MODEL">
                                                    <?php foreach ($config_options['DEEPSEEK_MODEL']['options'] as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" 
                                                            <?php echo get_current_config_value('DEEPSEEK_MODEL', $config_options) === $value ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="config-description">
                                                    选择您要使用的DeepSeek模型
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 通义千问模型 -->
                                        <div class="config-item" id="qwen-config" 
                                            style="display: <?php echo (get_current_config_value('AI_SERVICE_TYPE', $config_options) === 'qwen') ? 'block' : 'none'; ?>">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['QWEN_MODEL']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <select name="QWEN_MODEL">
                                                    <?php foreach ($config_options['QWEN_MODEL']['options'] as $value => $label): ?>
                                                        <option value="<?php echo $value; ?>" 
                                                            <?php echo get_current_config_value('QWEN_MODEL', $config_options) === $value ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="config-description">
                                                    选择您要使用的通义千问模型
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="config-section">
                                        <h3>高级参数</h3>
                                        
                                        <!-- 温度参数 -->
                                        <div class="config-item">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['AI_TEMPERATURE']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <input type="range" 
                                                       name="AI_TEMPERATURE" 
                                                       min="<?php echo $config_options['AI_TEMPERATURE']['min']; ?>" 
                                                       max="<?php echo $config_options['AI_TEMPERATURE']['max']; ?>" 
                                                       step="<?php echo $config_options['AI_TEMPERATURE']['step']; ?>" 
                                                       value="<?php echo defined('AI_TEMPERATURE') ? AI_TEMPERATURE : $config_options['AI_TEMPERATURE']['default']; ?>" 
                                                       lay-filter="temperature-range"
                                                       class="layui-input">
                                                <div class="range-value" id="temperature-value">
                                                    <?php echo defined('AI_TEMPERATURE') ? AI_TEMPERATURE : $config_options['AI_TEMPERATURE']['default']; ?>
                                                </div>
                                                <div class="config-description">
                                                    <?php echo $config_options['AI_TEMPERATURE']['description']; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 最大Token数 -->
                                        <div class="config-item">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['AI_MAX_TOKENS']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <input type="number" 
                                                       name="AI_MAX_TOKENS" 
                                                       min="<?php echo $config_options['AI_MAX_TOKENS']['min']; ?>" 
                                                       max="<?php echo $config_options['AI_MAX_TOKENS']['max']; ?>" 
                                                       step="<?php echo $config_options['AI_MAX_TOKENS']['step']; ?>" 
                                                       value="<?php echo defined('AI_MAX_TOKENS') ? AI_MAX_TOKENS : $config_options['AI_MAX_TOKENS']['default']; ?>" 
                                                       class="layui-input">
                                                <div class="config-description">
                                                    <?php echo $config_options['AI_MAX_TOKENS']['description']; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 优化提示词 -->
                                        <div class="config-item">
                                            <label class="layui-form-label">
                                                <?php echo $config_options['AI_OPTIMIZATION_PROMPT']['label']; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <textarea name="AI_OPTIMIZATION_PROMPT" 
                                                          placeholder="<?php echo $config_options['AI_OPTIMIZATION_PROMPT']['placeholder']; ?>" 
                                                          rows="<?php echo $config_options['AI_OPTIMIZATION_PROMPT']['rows']; ?>" 
                                                          class="layui-textarea">
<?php echo defined('AI_OPTIMIZATION_PROMPT') ? htmlspecialchars(constant('AI_OPTIMIZATION_PROMPT')) : ''; ?></textarea>
                                                <div class="config-description">
                                                    <?php echo $config_options['AI_OPTIMIZATION_PROMPT']['description']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="config-section">
                                        <h3>测试与预览</h3>
                                        <div class="config-item">
                                            <label class="layui-form-label">AI服务测试</label>
                                            <div class="layui-input-block">
                                                <button type="button" class="layui-btn" id="test-ai-service">
                                                    <i class="layui-icon layui-icon-test"></i> 测试AI服务连接
                                                </button>
                                                <div class="test-result" id="test-result"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="config-section">
                                        <div class="layui-input-block" style="margin-left: 180px;">
                                            <button type="submit" class="layui-btn layui-btn-normal">
                                                <i class="layui-icon layui-icon-save"></i> 保存设置
                                            </button>
                                            <a href="ai_optimization_settings.php" class="layui-btn layui-btn-primary">
                                                <i class="layui-icon layui-icon-refresh"></i> 重置
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include '../../includes/footer.php'; ?>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
        layui.use(['form', 'layer'], function() {
            const form = layui.form;
            const layer = layui.layer;
            
            // 表单渲染
            form.render();
            
            // 监听温度滑块变化
            form.on('input(temperature-range)', function(data) {
                document.getElementById('temperature-value').innerText = data.value;
            });
            
            // 监听AI服务类型变化，显示对应的配置项
            form.on('select(ai-service-type)', function(data) {
                // 隐藏所有服务特定配置
                document.getElementById('doubao-config').style.display = 'none';
                document.getElementById('deepseek-config').style.display = 'none';
                document.getElementById('qwen-config').style.display = 'none';
                
                // 显示选中的服务配置
                if (data.value === 'doubao') {
                    document.getElementById('doubao-config').style.display = 'block';
                } else if (data.value === 'deepseek') {
                    document.getElementById('deepseek-config').style.display = 'block';
                } else if (data.value === 'qwen') {
                    document.getElementById('qwen-config').style.display = 'block';
                }
                
                // 重新渲染表单
                form.render();
            });
            
            // 测试AI服务连接
            document.getElementById('test-ai-service').addEventListener('click', function() {
                const btn = this;
                const originalText = btn.innerHTML;
                const testResult = document.getElementById('test-result');
                
                // 显示加载状态
                btn.innerHTML = '<i class="layui-icon layui-icon-loading layui-icon-spin"></i> 测试中...';
                btn.disabled = true;
                testResult.style.display = 'none';
                
                // 发送测试请求
                fetch('../../api/test_upload.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        test_type: 'ai_service'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // 恢复按钮状态
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    // 显示测试结果
                    testResult.style.display = 'block';
                    if (data.success) {
                        testResult.className = 'test-result test-success';
                        testResult.innerHTML = '<p><i class="layui-icon layui-icon-ok-circle layui-text-green"></i> ' + data.message + '</p>';
                    } else {
                        testResult.className = 'test-result test-error';
                        testResult.innerHTML = '<p><i class="layui-icon layui-icon-error-circle layui-text-danger"></i> ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    // 恢复按钮状态
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    // 显示错误信息
                    testResult.style.display = 'block';
                    testResult.className = 'test-result test-error';
                    testResult.innerHTML = '<p><i class="layui-icon layui-icon-error-circle layui-text-danger"></i> 测试失败：网络错误，请稍后重试</p>';
                });
            });
        });
    </script>
</body>
</html>