<?php
/**
 * 平台管理类
 * 用于管理各大平台的配置和发布功能
 */

class PlatformManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * 获取所有平台配置
     */
    public function getAllPlatforms() {
        try {
            $sql = "SELECT * FROM platform_configs ORDER BY sort_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // 记录错误日志
            $this->logError('获取平台配置失败: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取已启用的平台配置
     */
    public function getEnabledPlatforms() {
        try {
            $sql = "SELECT * FROM platform_configs WHERE status = 1 ORDER BY sort_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // 记录错误日志
            $this->logError('获取启用平台配置失败: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取单个平台配置
     */
    public function getPlatform($platform_key) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM platform_configs WHERE platform_key = ?");
            $stmt->execute([$platform_key]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->logError('获取平台配置失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 更新平台配置
     */
    public function updatePlatform($platform_key, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE platform_configs SET api_key = ?, api_secret = ?, access_token = ?, refresh_token = ?, token_expires = ?, status = ?, config = ? WHERE platform_key = ?");
            
            $config_json = !empty($data['config']) ? json_encode($data['config']) : null;
            $token_expires = !empty($data['token_expires']) ? $data['token_expires'] : null;
            
            return $stmt->execute([
                $data['api_key'] ?? null,
                $data['api_secret'] ?? null,
                $data['access_token'] ?? null,
                $data['refresh_token'] ?? null,
                $token_expires,
                isset($data['status']) ? (int)$data['status'] : 0,
                $config_json,
                $platform_key
            ]);
        } catch(PDOException $e) {
            $this->logError('更新平台配置失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 添加发布日志
     */
    public function addPublishLog($content_id, $platform_key, $status = 'pending', $error_message = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO content_publish_logs (content_id, platform_key, status, error_message) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$content_id, $platform_key, $status, $error_message]);
        } catch(PDOException $e) {
            $this->logError('添加发布日志失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 更新发布日志
     */
    public function updatePublishLog($log_id, $status, $response_data = null, $error_message = null) {
        try {
            $stmt = $this->db->prepare("UPDATE content_publish_logs SET status = ?, response_data = ?, error_message = ? WHERE id = ?");
            
            $response_json = !empty($response_data) ? json_encode($response_data) : null;
            
            return $stmt->execute([$status, $response_json, $error_message, $log_id]);
        } catch(PDOException $e) {
            $this->logError('更新发布日志失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取内容的发布日志
     */
    public function getContentPublishLogs($content_id) {
        try {
            $stmt = $this->db->prepare("SELECT cpl.*, pc.platform_name FROM content_publish_logs cpl LEFT JOIN platform_configs pc ON cpl.platform_key = pc.platform_key WHERE cpl.content_id = ? ORDER BY cpl.created_at DESC");
            $stmt->execute([$content_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->logError('获取发布日志失败: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 发布内容到指定平台
     * 注意：这是一个模拟实现，实际使用时需要集成各平台的API
     */
    public function publishToPlatform($content_id, $platform_key, $publish_type = 'auto') {
        // 获取平台配置
        $platform = $this->getPlatform($platform_key);
        if (!$platform || $platform['status'] != 1) {
            $this->logError('平台未启用或不存在: ' . $platform_key);
            return false;
        }
        
        // 检查是否有待处理的相同任务
        if ($this->hasPendingTask($content_id, $platform_key)) {
            $this->logError('有相同的发布任务正在进行: 内容ID ' . $content_id . ' 平台 ' . $platform_key);
            return false;
        }
        
        // 获取内容信息
        $content_info = $this->getContentInfo($content_id);
        if (!$content_info) {
            $this->logError('内容不存在: ' . $content_id);
            return false;
        }
        
        // 验证平台配置是否完整
        $platform_config = json_decode($platform['config'], true);
        if (!isset($platform_config) || !$this->validatePlatformConfig($platform_key, $platform_config)) {
            $this->logError('平台配置不完整: ' . $platform_key);
            return false;
        }
        
        // 创建发布任务
        $log_id = $this->createPublishTask($content_id, $platform_key, $publish_type);
        if (!$log_id) {
            $this->logError('创建发布任务失败: 内容ID ' . $content_id . ' 平台 ' . $platform_key);
            return false;
        }
        
        // 更新任务状态为处理中
        $this->updatePublishLog($log_id, 'processing');
        
        try {
            // 准备发布内容
            $content_data = $this->prepareContentForPublishing($content_id, $platform_key, $publish_type);
            if (!$content_data) {
                throw new Exception('准备发布内容失败');
            }
            
            // 模拟API调用延迟
            usleep(300000); // 300毫秒
            
            // 调用平台API
            $platform_response = $this->callPlatformAPI($platform_key, $platform, $content_data);
            
            // 记录成功响应
            $response_data = [
                'platform' => $platform_key,
                'content_id' => $content_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'success',
                'publish_type' => $publish_type,
                'message' => '发布成功',
                'platform_response' => $platform_response
            ];
            
            // 更新发布日志
            $this->updatePublishLog($log_id, 'success', $response_data);
            
            return $log_id; // 返回日志ID作为成功标识
        } catch(Exception $e) {
            // 记录错误
            $error_msg = '发布到平台失败 (' . $platform_key . '): ' . $e->getMessage();
            $this->logError($error_msg);
            
            // 更新日志为失败状态
            $this->updatePublishLog($log_id, 'failed', null, $error_msg);
            
            return false;
        }
    }
    
    /**
     * 准备发布内容
     */
    private function prepareContentForPublishing($content_id, $platform_key, $publish_type) {
        try {
            // 获取内容信息
            $content = $this->getContentInfo($content_id);
            if (!$content) {
                return false;
            }
            
            // 解析内容配置（如果有）
            $content_config = [];
            if (!empty($content['config'])) {
                $content_config = json_decode($content['config'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $content_config = [];
                }
            }
            
            // 准备基础内容数据
            $content_data = [
                'title' => $content['title'] ?? '',
                'content' => $content['content'] ?? '',
                'cover_image' => $content['cover_image'] ?? '',
                'tags' => !empty($content['tags']) ? explode(',', $content['tags']) : [],
                'publish_type' => $publish_type,
                'content_type' => $content_config['content_type'] ?? 'article', // article, image, video
                'original_url' => BASE_URL . '/content/' . $content_id,
                'publish_time' => date('Y-m-d H:i:s')
            ];
            
            // 根据平台特点调整内容
            switch ($platform_key) {
                case 'douyin':
                    // 抖音平台特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 50);
                    $content_data['content'] = $this->limitStringLength($content_data['content'], 2000);
                    break;
                    
                case 'kuaishou':
                    // 快手平台特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 30);
                    break;
                    
                case 'xiaohongshu':
                    // 小红书平台特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 20);
                    $content_data['content'] = $this->formatForXiaohongshu($content_data['content']);
                    break;
                    
                case 'wechat':
                    // 微信公众号特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 64);
                    $content_data['digest'] = $this->generateDigest($content_data['content'], 120);
                    break;
                    
                case 'toutiao':
                    // 头条号特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 50);
                    break;
                    
                case 'baidu':
                    // 百家号特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 30);
                    break;
                    
                case 'zhihu':
                    // 知乎特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 100);
                    break;
                    
                case 'bilibili':
                    // B站特殊处理
                    $content_data['title'] = $this->limitStringLength($content_data['title'], 50);
                    break;
            }
            
            return $content_data;
        } catch (Exception $e) {
            $this->logError('准备发布内容失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 模拟调用平台API
     */
    private function callPlatformAPI($platform_key, $platform, $content_data) {
        try {
            // 根据平台类型执行不同的API调用
            // 在实际环境中，这里应该是真正的API调用
            
            // 生成模拟响应数据
            $response = [
                'post_id' => 'mock_' . time() . '_' . rand(1000, 9999),
                'url' => 'https://example.com/' . $platform_key . '/post/' . $content_data['content_id'],
                'published_at' => date('Y-m-d H:i:s'),
                'platform' => $platform_key
            ];
            
            // 根据平台添加特殊响应字段
            switch ($platform_key) {
                case 'douyin':
                    $response['douyin_specific'] = ['like_count' => 0, 'comment_count' => 0, 'share_count' => 0];
                    break;
                    
                case 'wechat':
                    $response['wechat_specific'] = ['read_count' => 0, 'like_count' => 0, 'comment_count' => 0];
                    break;
                    
                // 其他平台可以添加更多特定字段
            }
            
            return $response;
        } catch (Exception $e) {
            throw new Exception('调用平台API失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 限制字符串长度
     */
    private function limitStringLength($string, $length) {
        if (mb_strlen($string) > $length) {
            return mb_substr($string, 0, $length - 3) . '...';
        }
        return $string;
    }
    
    /**
     * 为小红书格式化内容
     */
    private function formatForXiaohongshu($content) {
        // 小红书内容格式化逻辑
        $formatted = strip_tags($content); // 移除HTML标签
        $formatted = preg_replace('/\s+/', ' ', $formatted); // 替换多余的空白字符
        return $this->limitStringLength($formatted, 1000); // 限制长度
    }
    
    /**
     * 生成内容摘要
     */
    private function generateDigest($content, $length = 100) {
        $plain_text = strip_tags($content);
        $plain_text = preg_replace('/\s+/', ' ', $plain_text);
        return $this->limitStringLength($plain_text, $length);
    }
    
    /**
     * 一键发布到所有启用的平台
     */
    public function publishToAllEnabledPlatforms($content_id) {
        $results = [];
        $enabled_platforms = $this->getEnabledPlatforms();
        
        foreach ($enabled_platforms as $platform) {
            $result = $this->publishToPlatform($content_id, $platform['platform_key']);
            $results[$platform['platform_key']] = [
                'success' => $result,
                'platform_name' => $platform['platform_name']
            ];
        }
        
        return $results;
    }
    
    /**
     * 获取平台发布状态
     */
    public function getPublishStatus($content_id, $platform_key) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM content_publish_logs WHERE content_id = ? AND platform_key = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$content_id, $platform_key]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->logError('获取发布状态失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 记录错误日志
     */
    private function logError($message) {
        // 在实际环境中，应该记录到日志文件或数据库
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('PlatformManager Error: ' . $message);
        }
    }
    
    /**
     * 检查是否有相同的发布任务正在进行
     */
    public function hasPendingTask($content_id, $platform_key) {
        try {
            // 查找最近30分钟内创建的、状态为pending或processing的任务
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM content_publish_logs WHERE content_id = ? AND platform_key = ? AND status IN ('pending', 'processing') AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $stmt->execute([$content_id, $platform_key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch(PDOException $e) {
            $this->logError('检查待处理任务失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 创建发布任务
     */
    public function createPublishTask($content_id, $platform_key, $publish_type = 'auto') {
        try {
            $stmt = $this->db->prepare("INSERT INTO content_publish_logs (content_id, platform_key, status, publish_type) VALUES (?, ?, 'pending', ?)");
            if ($stmt->execute([$content_id, $platform_key, $publish_type])) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            $this->logError('创建发布任务失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取内容信息
     */
    public function getContentInfo($content_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM contents WHERE id = ?");
            $stmt->execute([$content_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->logError('获取内容信息失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 处理发布任务队列
     * 这个方法可以通过定时任务或后台进程调用
     */
    public function processPublishQueue($limit = 10) {
        try {
            // 获取待处理的任务
            $stmt = $this->db->prepare("SELECT * FROM content_publish_logs WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?");
            $stmt->execute([$limit]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            foreach ($tasks as $task) {
                // 更新任务状态为处理中
                $this->updatePublishLog($task['id'], 'processing');
                
                // 获取发布类型
                $publish_type = !empty($task['publish_type']) ? $task['publish_type'] : 'auto';
                
                // 执行实际的发布操作，传递发布类型参数
                $result = $this->publishToPlatform($task['content_id'], $task['platform_key'], $publish_type);
                
                $results[] = [
                    'task_id' => $task['id'],
                    'content_id' => $task['content_id'],
                    'platform_key' => $task['platform_key'],
                    'publish_type' => $publish_type,
                    'success' => $result
                ];
            }
            
            return $results;
        } catch(PDOException $e) {
            $this->logError('处理发布队列失败: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 验证平台配置是否完整
     */
    public function validatePlatformConfig($platform_key, $config) {
        switch ($platform_key) {
            case 'douyin':
                return !empty($config['api_key']) && !empty($config['access_token']);
            case 'kuaishou':
                return !empty($config['api_key']) && !empty($config['api_secret']);
            case 'xiaohongshu':
                return !empty($config['access_token']);
            case 'wechat':
                return !empty($config['app_id']) && !empty($config['app_secret']);
            case 'toutiao':
                return !empty($config['client_key']) && !empty($config['client_secret']);
            case 'baidu':
                return !empty($config['api_key']) && !empty($config['secret_key']);
            case 'zhihu':
                return !empty($config['client_id']) && !empty($config['client_secret']);
            case 'bilibili':
                return !empty($config['app_key']) && !empty($config['app_secret']);
            default:
                return false;
        }
    }
}
?>