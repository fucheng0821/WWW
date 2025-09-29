<?php
/**
 * 优化版公共函数库
 * 包含安全过滤、数据验证、工具函数、数据库操作和页面渲染等功能
 */

// 引入缓存配置文件
require_once 'cache_config.php';

// 全局缓存管理器
class CacheManager {
    private static $cache = [];
    private static $enabled = CACHE_ENABLED;
    private static $ttl = CACHE_DEFAULT_TTL; // 使用配置文件中的默认缓存时间
    
    /**
     * 设置缓存
     */
    public static function set($key, $value, $ttl = null) {
        if (!self::$enabled) return;
        $ttl = $ttl ?: self::$ttl;
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }
    
    /**
     * 获取缓存
     */
    public static function get($key) {
        if (!self::$enabled || !isset(self::$cache[$key])) return null;
        
        $item = self::$cache[$key];
        if (time() > $item['expires']) {
            self::delete($key);
            return null;
        }
        
        return $item['value'];
    }
    
    /**
     * 删除缓存
     */
    public static function delete($key) {
        if (isset(self::$cache[$key])) {
            unset(self::$cache[$key]);
        }
    }
    
    /**
     * 清除所有缓存
     */
    public static function clear() {
        self::$cache = [];
    }
    
    /**
     * 启用/禁用缓存
     */
    public static function setEnabled($enabled) {
        self::$enabled = $enabled;
    }
    
    /**
     * 设置默认缓存时间
     */
    public static function setDefaultTTL($ttl) {
        self::$ttl = $ttl;
    }
}

/**
 * 安全过滤输入
 * @param mixed $data 需要过滤的数据
 * @return mixed 过滤后的数据
 */
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * 批量安全过滤
 * @param array $data 需要过滤的数据数组
 * @return array 过滤后的数据数组
 */
function clean_inputs(array $data) {
    return array_map('clean_input', $data);
}

/**
 * 验证邮箱格式
 * @param string $email 邮箱地址
 * @return bool|string 验证通过返回邮箱，否则返回false
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * 验证手机号格式（中国大陆手机号）
 * @param string $phone 手机号
 * @return bool 验证结果
 */
function validate_phone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

/**
 * 验证身份证号格式（中国大陆身份证号）
 * @param string $id_card 身份证号
 * @return bool 验证结果
 */
function validate_id_card($id_card) {
    return preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $id_card);
}

/**
 * 生成随机字符串
 * @param int $length 字符串长度
 * @param string $type 类型：all, numeric, alpha, alphanumeric
 * @return string 随机字符串
 */
function generate_random_string($length = 10, $type = 'all') {
    switch ($type) {
        case 'numeric':
            $characters = '0123456789';
            break;
        case 'alpha':
            $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alphanumeric':
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        default:
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=';
    }
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)]; // 使用更安全的random_int
    }
    return $randomString;
}

/**
 * 生成友好的URL别名
 * @param string $string 原始字符串
 * @param string $separator 分隔符
 * @return string URL别名
 */
function generate_slug($string, $separator = '-') {
    // 转换中文为拼音或使用ID
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9' . preg_quote($separator) . ']/', $separator, $slug);
    $slug = preg_replace('/' . preg_quote($separator) . '+/', $separator, $slug);
    $slug = trim($slug, $separator);
    return $slug ?: generate_random_string(8, 'alphanumeric');
}

/**
 * 格式化日期
 * @param string|int $date 日期字符串或时间戳
 * @param string $format 日期格式
 * @return string 格式化后的日期
 */
function format_date($date, $format = 'Y-m-d H:i:s') {
    if (!$date) return '';
    return date($format, is_numeric($date) ? $date : strtotime($date));
}

/**
 * 格式化日期显示友好时间（如：3分钟前，2小时前，3天前）
 * @param string|int $date 日期字符串或时间戳
 * @return string 友好时间
 */
function format_time_ago($date) {
    if (!$date) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . '秒前';
    } else if ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } else if ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } else if ($diff < 604800) {
        return floor($diff / 86400) . '天前';
    } else if ($diff < 2592000) {
        return floor($diff / 604800) . '周前';
    } else if ($diff < 31536000) {
        return floor($diff / 2592000) . '个月前';
    } else {
        return floor($diff / 31536000) . '年前';
    }
}

/**
 * 截取字符串（支持UTF-8）
 * @param string $string 原始字符串
 * @param int $length 截取长度
 * @param string $suffix 后缀
 * @return string 截取后的字符串
 */
function truncate_string($string, $length = 100, $suffix = '...') {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
}

/**
 * 文件上传处理
 * @param array $file 文件上传信息
 * @param string $upload_dir 上传目录
 * @param array $allowed_types 允许的文件类型
 * @return string|false 成功返回文件路径，失败返回false
 */
function handle_file_upload($file, $upload_dir = 'images', $allowed_types = null) {
    if (!isset($file['tmp_name']) || !$file['tmp_name']) {
        return false;
    }
    
    $allowed_types = $allowed_types ?: ALLOWED_IMAGE_TYPES;
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    $upload_path = UPLOAD_DIR . '/' . $upload_dir;
    if (!file_exists($upload_path)) {
        if (!mkdir($upload_path, 0755, true)) {
            return false;
        }
    }
    
    $filename = time() . '_' . generate_random_string(8, 'alphanumeric') . '.' . $file_ext;
    $filepath = $upload_path . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $upload_dir . '/' . $filename;
    }
    
    return false;
}

/**
 * 获取文件大小显示（如：10KB，2MB）
 * @param int $bytes 字节数
 * @return string 格式化后的文件大小
 */
function format_file_size($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 数据库查询单个结果
 * @param string $query SQL查询语句
 * @param mixed ...$params 查询参数
 * @return array|false 查询结果
 */
function db_query_one($query, ...$params) {
    global $db;
    
    try {
        // 替换占位符
        $query = str_replace('%d', '?', $query);
        $query = str_replace('%s', '?', $query);
        $query = str_replace('%f', '?', $query);
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch(PDOException $e) {
        // 可以在这里添加日志记录
        return false;
    }
}

/**
 * 数据库查询多个结果
 * @param string $query SQL查询语句
 * @param mixed ...$params 查询参数
 * @return array 查询结果数组
 */
function db_query_all($query, ...$params) {
    global $db;
    
    try {
        // 替换占位符
        $query = str_replace('%d', '?', $query);
        $query = str_replace('%s', '?', $query);
        $query = str_replace('%f', '?', $query);
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        // 可以在这里添加日志记录
        return [];
    }
}

/**
 * 执行数据库修改操作（INSERT, UPDATE, DELETE）
 * @param string $query SQL语句
 * @param mixed ...$params 参数
 * @return bool 执行结果
 */
function db_execute($query, ...$params) {
    global $db;
    
    try {
        // 替换占位符
        $query = str_replace('%d', '?', $query);
        $query = str_replace('%s', '?', $query);
        $query = str_replace('%f', '?', $query);
        
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        // 可以在这里添加日志记录
        return false;
    }
}

/**
 * 获取系统配置
 * @param string $key 配置键名
 * @param mixed $default 默认值
 * @param bool $use_cache 是否使用缓存
 * @return mixed 配置值
 */
function get_config($key, $default = '', $use_cache = true) {
    // 使用缓存
    $cache_key = 'config_' . $key;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $stmt = $db->prepare("SELECT config_value FROM config WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        $value = $result ? $result['config_value'] : $default;
        
        // 存储到缓存
        if ($use_cache) {
            CacheManager::set($cache_key, $value, 3600); // 缓存1小时
        }
        
        return $value;
    } catch(PDOException $e) {
        return $default;
    }
}

/**
 * 获取首页设置
 * @param bool $use_cache 是否使用缓存
 * @return array 首页功能设置
 */
function get_homepage_features($use_cache = true) {
    // 使用缓存
    $cache_key = 'homepage_features';
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute(['homepage_features']);
        $result = $stmt->fetch();
        
        $features = [];
        if ($result && $result['config_value']) {
            $features = json_decode($result['config_value'], true);
        } else {
            // 默认配置
            $features = [
                [
                    'title' => '专业团队',
                    'description' => '资深设计师和制作团队，为您提供专业的创意服务',
                    'image' => 'https://picsum.photos/600/400?random=1'
                ],
                [
                    'title' => '一流设备',
                    'description' => '专业的拍摄设备和制作软件，保证作品的高品质',
                    'image' => 'https://picsum.photos/600/400?random=2'
                ],
                [
                    'title' => '按时交付',
                    'description' => '严格的项目管理流程，确保按时按质完成项目',
                    'image' => 'https://picsum.photos/600/400?random=3'
                ],
                [
                    'title' => '贴心服务',
                    'description' => '全程跟踪服务，及时沟通，让您省心放心',
                    'image' => 'https://picsum.photos/600/400?random=4'
                ]
            ];
        }
        
        // 存储到缓存
        if ($use_cache) {
            CacheManager::set($cache_key, $features, 3600); // 缓存1小时
        }
        
        return $features;
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 设置系统配置
 * @param string $key 配置键名
 * @param mixed $value 配置值
 * @param string $group 配置分组
 * @param string $description 配置描述
 * @return bool 设置结果
 */
function set_config($key, $value, $group = 'general', $description = '') {
    global $db;
    try {
        $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value, config_group, description) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?, config_group = ?, description = ?");
        
        $result = $stmt->execute([$key, $value, $group, $description, $value, $group, $description]);
        
        // 清除缓存
        if ($result) {
            CacheManager::delete('config_' . $key);
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取栏目列表
 * @param int $parent_id 父栏目ID
 * @param bool $enabled_only 是否只获取启用的栏目
 * @param bool $use_cache 是否使用缓存
 * @return array 栏目列表
 */
function get_categories($parent_id = 0, $enabled_only = true, $use_cache = true) {
    // 使用缓存
    $cache_key = 'categories_' . $parent_id . '_' . ($enabled_only ? '1' : '0');
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $sql = "SELECT * FROM categories WHERE parent_id = ?";
        $params = [$parent_id];
        
        if ($enabled_only) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY sort_order ASC, id ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        
        // 存储到缓存
        if ($use_cache) {
            CacheManager::set($cache_key, $result, get_cache_ttl('categories')); // 使用配置文件中的缓存时间
        }
        
        return $result;
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 获取栏目树形结构
 * @param int $parent_id 父栏目ID
 * @param bool $enabled_only 是否只获取启用的栏目
 * @param int $level 当前层级
 * @param bool $use_cache 是否使用缓存
 * @return array 树形结构的栏目列表
 */
function get_categories_tree($parent_id = 0, $enabled_only = true, $level = 0, $use_cache = true) {
    $cache_key = 'categories_tree_' . $parent_id . '_' . ($enabled_only ? '1' : '0');
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    $categories = get_categories($parent_id, $enabled_only, $use_cache);
    $tree = [];
    
    foreach ($categories as $category) {
        $category['level'] = $level;
        $category['children'] = get_categories_tree($category['id'], $enabled_only, $level + 1, $use_cache);
        $tree[] = $category;
    }
    
    // 存储到缓存
    if ($use_cache) {
        CacheManager::set($cache_key, $tree, get_cache_ttl('categories')); // 使用配置文件中的缓存时间
    }
    
    return $tree;
}

/**
 * 根据别名获取栏目信息
 * @param string $slug 栏目别名
 * @param bool $enabled_only 是否只获取启用的栏目
 * @param bool $use_cache 是否使用缓存
 * @return array|false 栏目信息
 */
function get_category_by_slug($slug, $enabled_only = true, $use_cache = true) {
    $cache_key = 'category_slug_' . $slug;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $sql = "SELECT * FROM categories WHERE slug = ?";
        $params = [$slug];
        
        if ($enabled_only) {
            $sql .= " AND is_active = 1";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        // 存储到缓存
        if ($use_cache && $result) {
            CacheManager::set($cache_key, $result, get_cache_ttl('categories')); // 使用配置文件中的缓存时间
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 根据ID获取栏目信息
 * @param int $id 栏目ID
 * @param bool $enabled_only 是否只获取启用的栏目
 * @param bool $use_cache 是否使用缓存
 * @return array|false 栏目信息
 */
function get_category_by_id($id, $enabled_only = true, $use_cache = true) {
    $cache_key = 'category_id_' . $id;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $params = [$id];
        
        if ($enabled_only) {
            $sql .= " AND is_active = 1";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        // 存储到缓存
        if ($use_cache && $result) {
            CacheManager::set($cache_key, $result, get_cache_ttl('categories')); // 使用配置文件中的缓存时间
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取内容列表
 * @param int $category_id 栏目ID，0表示所有栏目
 * @param array $conditions 额外条件
 * @param int $page 页码
 * @param int $per_page 每页条数
 * @param string $order_by 排序方式
 * @param bool $enabled_only 是否只获取启用的内容
 * @param bool $use_cache 是否使用缓存
 * @return array 内容列表和分页信息
 */
function get_contents($category_id = 0, $conditions = [], $page = 1, $per_page = 10, $order_by = 'created_at DESC', $enabled_only = true, $use_cache = true) {
    $cache_key = 'contents_' . $category_id . '_' . md5(json_encode($conditions)) . '_' . $page . '_' . $per_page . '_' . $order_by;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $sql = "SELECT * FROM contents WHERE 1=1";
        $params = [];
        
        if ($category_id > 0) {
            $sql .= " AND category_id = ?";
            $params[] = $category_id;
        }
        
        // 添加额外条件
        foreach ($conditions as $key => $value) {
            $sql .= " AND $key = ?";
            $params[] = $value;
        }
        
        if ($enabled_only) {
            $sql .= " AND is_active = 1";
        }
        
        // 先获取总数
        $count_sql = str_replace("*", "COUNT(*) as count", $sql);
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute($params);
        $count = $count_stmt->fetchColumn();
        
        // 分页
        $offset = ($page - 1) * $per_page;
        $sql .= " ORDER BY " . $order_by . " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // 计算分页信息
        $total_pages = ceil($count / $per_page);
        $pagination = [
            'total' => $count,
            'per_page' => $per_page,
            'current_page' => $page,
            'total_pages' => $total_pages
        ];
        
        $result = [
            'data' => $results,
            'pagination' => $pagination
        ];
        
        // 存储到缓存
        if ($use_cache) {
            CacheManager::set($cache_key, $result, get_cache_ttl('content_list')); // 使用配置文件中的缓存时间
        }
        
        return $result;
    } catch(PDOException $e) {
        return [
            'data' => [],
            'pagination' => [
                'total' => 0,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => 0
            ]
        ];
    }
}

/**
 * 根据别名获取内容详情
 * @param string $slug 内容别名
 * @param bool $enabled_only 是否只获取启用的内容
 * @param bool $use_cache 是否使用缓存
 * @return array|false 内容详情
 */
function get_content_by_slug($slug, $enabled_only = true, $use_cache = true) {
    $cache_key = 'content_slug_' . $slug;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                FROM contents c 
                LEFT JOIN categories cat ON c.category_id = cat.id 
                WHERE c.slug = ?";
        $params = [$slug];
        
        if ($enabled_only) {
            $sql .= " AND c.is_active = 1";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        if ($result) {
            // 解析JSON字段
            $result['images'] = $result['images'] ? json_decode($result['images'], true) : [];
            $result['videos'] = $result['videos'] ? json_decode($result['videos'], true) : [];
            
            // 加载图片处理函数
            if (file_exists(dirname(__FILE__) . '/functions_image.php')) {
                require_once dirname(__FILE__) . '/functions_image.php';
            }
            
            // 处理内容中的图片URL，替换不存在的图片
            if (function_exists('fast_process_content_images')) {
                $result['content'] = fast_process_content_images($result['content']);
            }
            
            // 存储到缓存
            if ($use_cache) {
                CacheManager::set($cache_key, $result, 3600); // 缓存1小时
            }
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 增加内容浏览量
 * @param int $content_id 内容ID
 * @return bool 执行结果
 */
function increment_view_count($content_id) {
    global $db;
    try {
        $stmt = $db->prepare("UPDATE contents SET view_count = view_count + 1 WHERE id = ?");
        $result = $stmt->execute([$content_id]);
        
        // 清除相关缓存
        if ($result) {
            // 获取内容详情
            $stmt = $db->prepare("SELECT slug FROM contents WHERE id = ?");
            $stmt->execute([$content_id]);
            $content = $stmt->fetch();
            if ($content) {
                CacheManager::delete('content_slug_' . $content['slug']);
            }
            
            // 清除栏目内容缓存（这里简化处理，实际可能需要更精确的缓存键）
            CacheManager::delete('featured_contents');
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取推荐内容
 * @param int $limit 限制数量
 * @param int $category_id 栏目ID，0表示所有栏目
 * @param bool $use_cache 是否使用缓存
 * @return array 推荐内容列表
 */
function get_featured_contents($limit = 5, $category_id = 0, $use_cache = true) {
    $cache_key = 'featured_contents_' . $limit . '_' . $category_id;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        // 使用JOIN连接categories表获取分类名称
        $sql = "SELECT c.*, cat.name as category_name FROM contents c
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.is_featured = 1 AND c.is_published = 1";
        $params = [];
        
        if ($category_id > 0) {
            $sql .= " AND c.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // 存储到缓存
        if ($use_cache) {
            CacheManager::set($cache_key, $results, 3600); // 缓存1小时
        }
        
        return $results;
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 获取分页HTML
 * @param int $total 总条数
 * @param int $per_page 每页条数
 * @param int $current_page 当前页码
 * @param string $base_url 基础URL
 * @param array $params GET参数
 * @return string 分页HTML
 */
function get_pagination($total, $per_page, $current_page, $base_url = '', $params = []) {
    if ($total <= $per_page) {
        return '';
    }
    
    $total_pages = ceil($total / $per_page);
    $base_url = $base_url ?: get_current_url();
    
    // 构建URL参数字符串
    $query_string = http_build_query(array_merge($params, ['page' => '']));
    $url_template = $base_url . '?' . $query_string;
    
    $html = '<nav aria-label="分页导航"><ul class="pagination justify-content-center">';
    
    // 上一页
    $prev_page = $current_page > 1 ? $current_page - 1 : 1;
    $prev_active = $current_page > 1 ? '' : ' disabled';
    $html .= '<li class="page-item' . $prev_active . '"><a class="page-link" href="' . str_replace('page=', 'page=' . $prev_page, $url_template) . '">上一页</a></li>';
    
    // 页码
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $start_page + 4);
    
    if ($start_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . str_replace('page=', 'page=1', $url_template) . '">1</a></li>';
        if ($start_page > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . str_replace('page=', 'page=' . $i, $url_template) . '">' . $i . '</a></li>';
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . str_replace('page=', 'page=' . $total_pages, $url_template) . '">' . $total_pages . '</a></li>';
    }
    
    // 下一页
    $next_page = $current_page < $total_pages ? $current_page + 1 : $total_pages;
    $next_active = $current_page < $total_pages ? '' : ' disabled';
    $html .= '<li class="page-item' . $next_active . '"><a class="page-link" href="' . str_replace('page=', 'page=' . $next_page, $url_template) . '">下一页</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * 获取栏目URL
 * @param array $category 栏目信息
 * @param bool $absolute 是否返回绝对URL
 * @return string 栏目URL
 */
function category_url($category, $absolute = false) {
    $base_url = $absolute ? (defined('SITE_URL') ? SITE_URL : '') : '';
    return $base_url . '/category/' . $category['slug'] . '/';
}

/**
 * 获取内容URL
 * @param array $content 内容信息
 * @param bool $absolute 是否返回绝对URL
 * @return string 内容URL
 */
function content_url($content, $absolute = false) {
    $base_url = $absolute ? (defined('SITE_URL') ? SITE_URL : '') : '';
    
    // 尝试获取内容所属栏目
    $category_slug = '';
    if (!empty($content['category_id'])) {
        $category = get_category_by_id($content['category_id']);
        if ($category) {
            $category_slug = $category['slug'] . '/';
        }
    }
    
    // 生成URL，格式为：/栏目slug/内容slug.html
    // 特殊处理：如果是新闻栏目或包含新闻关键词，强制使用/news/前缀
    if ($category && (stripos($category['slug'], 'news') !== false || stripos($category['name'], '新闻') !== false)) {
        $url_path = '/news/' . $content['slug'] . '.html';
    } else {
        $url_path = '/' . $category_slug . $content['slug'] . '.html';
    }
    
    return $base_url . $url_path;
}

/**
 * 生成URL
 * @param string $path 路径
 * @param array $params 参数
 * @param bool $absolute 是否返回绝对URL
 * @return string 生成的URL
 */
function url($path = '', $params = [], $absolute = false) {
    $base_url = $absolute ? (defined('SITE_URL') ? SITE_URL : '') : '';
    $url = $base_url . '/' . ltrim($path, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * 生成站点URL
 * @param string $path 路径
 * @return string 完整URL
 */
/*function url($path = '') {
    $base_url = defined('SITE_URL') ? SITE_URL : '';
    if (empty($path)) {
        return $base_url;
    }
    
    // 确保路径以/开头
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    return $base_url . $path;
}*/

/**
 * 获取当前URL
 * @param bool $include_query 是否包含查询字符串
 * @return string 当前URL
 */
function get_current_url($include_query = true) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    
    $url = $protocol . '://' . $host . $path;
    
    if (!$include_query && strpos($url, '?') !== false) {
        $url = substr($url, 0, strpos($url, '?'));
    }
    
    return $url;
}

/**
 * 包含模板文件
 * @param string $template 模板文件路径
 * @param array $vars 模板变量
 * @param bool $return 是否返回内容而不是输出
 * @return string|null 模板内容或null
 */
function include_template($template, $vars = [], $return = false) {
    extract($vars);
    
    $template_path = TEMPLATE_DIR . '/' . $template . '.php';
    
    if (!file_exists($template_path)) {
        return $return ? '' : false;
    }
    
    if ($return) {
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    include $template_path;
    return null;
}

/**
 * 重定向到指定URL
 * @param string $url 目标URL
 * @param int $status_code HTTP状态码
 * @param bool $safe_check 是否进行安全检查
 */
function redirect($url, $status_code = 302, $safe_check = true) {
    // 安全检查，确保重定向URL是站内地址或可信的外部地址
    if ($safe_check && !is_safe_redirect($url)) {
        $url = defined('SITE_URL') ? SITE_URL . '/' : '/';
    }
    
    // 确保状态码是有效的HTTP重定向状态码
    $valid_codes = [301, 302, 303, 307, 308];
    if (!in_array($status_code, $valid_codes)) {
        $status_code = 302;
    }
    
    // 记录重定向日志
    if (defined('REDIRECT_LOG') && REDIRECT_LOG) {
        error_log("Redirect: " . ($_SERVER['REQUEST_URI'] ?? '') . " to " . $url . " with status " . $status_code);
    }
    
    header('Location: ' . $url, true, $status_code);
    exit();
}

/**
 * 检查重定向URL是否安全
 * @param string $url
 * @return bool
 */
function is_safe_redirect($url) {
    // 允许相对路径
    if (strpos($url, '/') === 0) {
        return true;
    }
    
    // 检查是否为站内绝对路径
    $site_host = $_SERVER['HTTP_HOST'] ?? '';
    $url_host = parse_url($url, PHP_URL_HOST);
    
    if ($url_host && $url_host !== $site_host && !ends_with($url_host, '.' . $site_host)) {
        // 如果是外部链接，检查是否在白名单中
        $whitelist = []; // 可根据需要添加可信的外部域名
        foreach ($whitelist as $allowed) {
            if (ends_with($url_host, $allowed)) {
                return true;
            }
        }
        return false;
    }
    
    return true;
}

/**
 * 检查字符串是否以指定字符串结尾
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function ends_with($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    
    return (substr($haystack, -$length) === $needle);
}

/**
 * 发送JSON响应
 * @param mixed $data 响应数据
 * @param int $status_code HTTP状态码
 */
function json_response($data, $status_code = 200) {
    header('Content-Type: application/json');
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

/**
 * 检查管理员是否已登录
 * @return bool 是否已登录
 */
function is_admin_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_name']);
}

/**
 * 获取当前登录管理员信息
 * @return array|false 管理员信息或false
 */
function get_current_admin() {
    if (!is_admin_logged_in()) {
        return false;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email'] ?? ''
    ];
}

/**
 * 管理员登录
 * @param string $username 用户名
 * @param string $password 密码
 * @return bool 登录结果
 */
function admin_login($username, $password) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            
            return true;
        }
        
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 管理员注销
 */
function admin_logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    
    session_destroy();
}

/**
 * 获取内容模板列表
 * @param bool $use_cache 是否使用缓存
 * @return array 模板列表
 */
function get_content_templates($use_cache = true) {
    $cache_key = 'content_templates';
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    $templates = [];
    $template_dir = TEMPLATE_DIR . '/content_templates';
    
    if (is_dir($template_dir)) {
        $files = scandir($template_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($template_dir . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $template_name = pathinfo($file, PATHINFO_FILENAME);
                $templates[] = [
                    'name' => $template_name,
                    'file' => $file
                ];
            }
        }
    }
    
    // 存储到缓存
    if ($use_cache) {
        CacheManager::set($cache_key, $templates, 3600); // 缓存1小时
    }
    
    return $templates;
}

/**
 * 获取Banner数据
 * @param int $position Banner位置
 * @param int $limit 限制数量
 * @param bool $use_cache 是否使用缓存
 * @return array Banner列表
 */
function get_banners($position = 1, $limit = 3, $use_cache = true) {
    $cache_key = 'banners_' . $position . '_' . $limit;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM banners WHERE position = ? AND is_active = 1 ORDER BY sort_order ASC LIMIT ?");
        $stmt->execute([$position, $limit]);
        $banners = $stmt->fetchAll();
        
        // 存储到缓存
        if ($use_cache) {
            CacheManager::set($cache_key, $banners, get_cache_ttl('banners')); // 使用配置文件中的缓存时间
        }
        
        return $banners;
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 获取新闻动态
 * @param int $limit 限制数量
 * @param bool $use_cache 是否使用缓存
 * @return array 新闻列表
 */
function get_news($limit = 5, $use_cache = true) {
    $cache_key = 'news_' . $limit;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    // 查找新闻栏目ID
    $news_category = get_category_by_slug('news', true, $use_cache);
    if (!$news_category) {
        return [];
    }
    
    $news = get_featured_contents($limit, $news_category['id'], $use_cache);
    
    // 如果没有推荐新闻，获取最新新闻
    if (empty($news)) {
        $result = get_contents($news_category['id'], [], 1, $limit, 'created_at DESC', true, $use_cache);
        $news = $result['data'];
    }
    
    // 存储到缓存
    if ($use_cache) {
        CacheManager::set($cache_key, $news, get_cache_ttl('news')); // 使用配置文件中的缓存时间
    }
    
    return $news;
}

/**
 * 获取栏目路径
 * @param int $category_id 栏目ID
 * @param string $separator 分隔符
 * @param bool $absolute 是否返回绝对URL
 * @param bool $use_cache 是否使用缓存
 * @return array 栏目路径
 */
function get_category_path($category_id, $separator = ' &gt; ', $absolute = false, $use_cache = true) {
    $cache_key = 'category_path_' . $category_id . '_' . $absolute;
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    $path = [];
    $current_id = $category_id;
    
    // 向上追溯所有父栏目
    while ($current_id > 0) {
        $category = get_category_by_id($current_id, true, $use_cache);
        if (!$category) {
            break;
        }
        
        $path[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'url' => category_url($category, $absolute)
        ];
        
        $current_id = $category['parent_id'];
    }
    
    // 反转路径顺序（从根到当前）
    $path = array_reverse($path);
    
    // 存储到缓存
    if ($use_cache) {
        CacheManager::set($cache_key, $path, 3600); // 缓存1小时
    }
    
    return $path;
}

/**
 * 生成面包屑导航数据
 * @param int $category_id 栏目ID
 * @param array $content 当前内容（可选）
 * @param bool $use_cache 是否使用缓存
 * @return array 面包屑导航数据
 */
function generate_breadcrumb($category_id, $content = null, $use_cache = true) {
    $cache_key = 'breadcrumb_' . $category_id . '_' . ($content ? $content['id'] : '0');
    if ($use_cache) {
        $cached_value = CacheManager::get($cache_key);
        if ($cached_value !== null) {
            return $cached_value;
        }
    }
    
    $breadcrumb = [
        [
            'name' => '首页',
            'url' => url()
        ]
    ];
    
    // 添加栏目路径
    if ($category_id > 0) {
        $category_path = get_category_path($category_id, ' &gt; ', false, $use_cache);
        $breadcrumb = array_merge($breadcrumb, $category_path);
    }
    
    // 添加当前内容
    if ($content) {
        $breadcrumb[] = [
            'name' => $content['title'],
            'url' => content_url($content)
        ];
    }
    
    // 存储到缓存
    if ($use_cache) {
        CacheManager::set($cache_key, $breadcrumb, 3600); // 缓存1小时
    }
    
    return $breadcrumb;
}

/**
 * 渲染面包屑导航HTML
 * @param array $breadcrumb 面包屑导航数据
 * @param array $options 配置选项
 * @return string 面包屑导航HTML
 */
function render_breadcrumb($breadcrumb, $options = []) {
    $defaults = [
        'separator' => '<span class="breadcrumb-separator">/</span>',
        'container_class' => 'breadcrumb',
        'item_class' => 'breadcrumb-item',
        'link_class' => 'breadcrumb-link',
        'active_class' => 'breadcrumb-item active',
        'aria_label' => '面包屑导航'
    ];
    
    $options = array_merge($defaults, $options);
    
    // 生成JSON-LD结构化数据
    $json_ld = render_breadcrumb_json_ld($breadcrumb);
    
    $html = $json_ld; // 先添加结构化数据
    $html .= '<nav aria-label="' . $options['aria_label'] . '" itemscope itemtype="https://schema.org/BreadcrumbList">';
    $html .= '<ol class="' . $options['container_class'] . '">';
    
    $total_items = count($breadcrumb);
    foreach ($breadcrumb as $index => $item) {
        $is_active = ($index === $total_items - 1);
        $item_class = $is_active ? $options['active_class'] : $options['item_class'];
        
        // 添加结构化数据属性
        $html .= '<li class="' . $item_class . '" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if ($is_active) {
            $html .= '<span itemprop="name">' . $item['name'] . '</span>';
        } else {
            $html .= '<a class="' . $options['link_class'] . '" href="' . $item['url'] . '" itemprop="item">';
            $html .= '<span itemprop="name">' . $item['name'] . '</span>';
            $html .= '</a>';
        }
        
        // 添加位置信息
        $html .= '<meta itemprop="position" content="' . ($index + 1) . '" />';
        $html .= '</li>';
        
        // 添加分隔符（最后一项不添加）
        if ($index < $total_items - 1) {
            $html .= $options['separator'];
        }
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * 生成面包屑导航的JSON-LD结构化数据
 * @param array $breadcrumb 面包屑导航数据
 * @return string JSON-LD结构化数据
 */
function render_breadcrumb_json_ld($breadcrumb) {
    $items = [];
    foreach ($breadcrumb as $index => $item) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? null
        ];
    }
    
    $json_ld = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    ];
    
    return '<script type="application/ld+json">' . json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * 生成密码哈希
 * @param string $password 原始密码
 * @return string 密码哈希
 */
function generate_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 验证密码哈希
 * @param string $password 原始密码
 * @param string $hash 密码哈希
 * @return bool 验证结果
 */
function verify_password_hash($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * 生成CSRF令牌
 * @return string CSRF令牌
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_random_string(32);
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF令牌
 * @param string $token 待验证的令牌
 * @return bool 验证结果
 */
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 记录日志
 * @param string $message 日志消息
 * @param string $level 日志级别
 * @param string $file 日志文件
 */
function log_message($message, $level = 'info', $file = 'system.log') {
    $log_dir = defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/' . $file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";
    
    error_log($log_entry, 3, $log_file);
}

/**
 * 获取客户端IP地址
 * @return string IP地址
 */
function get_client_ip() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * 检查是否为移动设备
 * @return bool 是否为移动设备
 */
function is_mobile_device() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = [
        'android', 'webos', 'iphone', 'ipad', 'ipod', 'blackberry', 'iemobile', 'opera mini'
    ];
    
    foreach ($mobile_agents as $agent) {
        if (stripos($user_agent, $agent) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * 清除所有缓存
 */
function clear_all_cache() {
    CacheManager::clear();
    
    // 清除文件缓存
    $cache_dir = CACHE_DIR;
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    return true;
}

/**
 * HTML转义
 * @param mixed $data 待转义的数据
 * @return mixed 转义后的数据
 */
function esc($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * HTML解码
 * @param string $data 待解码的数据
 * @return string 解码后的数据
 */
function unesc($data) {
    return htmlspecialchars_decode($data, ENT_QUOTES);
}

/**
 * 获取首页视频配置
 * @param string $key 配置键名
 * @param string $default 默认值
 * @return string 配置值
 */
function get_homepage_video_config($key, $default = '') {
    static $configs = null;
    
    if ($configs === null) {
        global $db;
        try {
            $stmt = $db->prepare("SELECT config_key, config_value FROM system_config WHERE config_key IN (?, ?)");
            $stmt->execute(['homepage_video', 'homepage_video_poster']);
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $configs = [];
        }
    }
    
    return isset($configs[$key]) ? $configs[$key] : $default;
}

/**
 * 获取合作伙伴图片
 * @return array 合作伙伴图片数组
 */
function get_homepage_partners() {
    static $partners = null;
    
    if ($partners === null) {
        global $db;
        try {
            $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
            $stmt->execute(['homepage_partners']);
            $result = $stmt->fetch();
            
            if ($result && $result['config_value']) {
                $partners = json_decode($result['config_value'], true);
            } else {
                // 默认配置
                $partners = [
                    ['image' => 'https://picsum.photos/300/150?random=1'],
                    ['image' => 'https://picsum.photos/300/150?random=2'],
                    ['image' => 'https://picsum.photos/300/150?random=3'],
                    ['image' => 'https://picsum.photos/300/150?random=4'],
                    ['image' => 'https://picsum.photos/300/150?random=5'],
                    ['image' => 'https://picsum.photos/300/150?random=6']
                ];
            }
        } catch (PDOException $e) {
            $partners = [];
        }
    }
    
    return $partners;
}
