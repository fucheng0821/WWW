<?php
/**
 * 公共函数库
 */

/**
 * 确保数据库连接有效
 */
function ensure_db_connection() {
    global $db;
    
    // 检查数据库连接是否有效
    if (!$db || !($db instanceof PDO)) {
        // 尝试重新连接
        if (!class_exists('Database')) {
            require_once 'database.php';
        }
        
        if (class_exists('Database')) {
            $database = Database::getInstance();
            $db = $database->getConnection();
        }
    }
    
    return $db && ($db instanceof PDO);
}

/**
 * 安全过滤输入
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
 * 验证邮箱格式
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * 验证手机号格式
 */
function validate_phone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

/**
 * 生成随机字符串
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * 生成友好的URL别名
 */
function generate_slug($string) {
    // 转换中文为拼音或使用ID
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: generate_random_string(8);
}

/**
 * 格式化日期
 */
function format_date($date, $format = 'Y-m-d H:i:s') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * 截取字符串
 */
function truncate_string($string, $length = 100, $suffix = '...') {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
}

/**
 * 文件上传处理
 */
function handle_file_upload($file, $upload_dir = 'images', $allowed_types = null) {
    if (!isset($file['tmp_name']) || !$file['tmp_name']) {
        return false;
    }
    
    $allowed_types = $allowed_types ?: ALLOWED_IMAGE_TYPES;
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // 支持更多图片格式
    if (!in_array($file_ext, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    $upload_path = UPLOAD_DIR . '/' . $upload_dir;
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $filename = time() . '_' . generate_random_string(8) . '.' . $file_ext;
    $filepath = $upload_path . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $upload_dir . '/' . $filename;
    }
    
    return false;
}

/**
 * 获取系统配置
 */
function get_config($key, $default = '') {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return $default;
    }
    
    try {
        $stmt = $db->prepare("SELECT config_value FROM config WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['config_value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

/**
 * 获取首页设置
 */
function get_homepage_features() {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute(['homepage_features']);
        $result = $stmt->fetch();
        
        if ($result && $result['config_value']) {
            return json_decode($result['config_value'], true);
        }
        
        // 默认配置
        return [
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
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 获取合作伙伴图片
 */
function get_homepage_partners() {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute(['homepage_partners']);
        $result = $stmt->fetch();
        
        if ($result && $result['config_value']) {
            return json_decode($result['config_value'], true);
        }
        
        // 默认配置
        return [
            ['image' => 'https://picsum.photos/300/150?random=1'],
            ['image' => 'https://picsum.photos/300/150?random=2'],
            ['image' => 'https://picsum.photos/300/150?random=3'],
            ['image' => 'https://picsum.photos/300/150?random=4'],
            ['image' => 'https://picsum.photos/300/150?random=5'],
            ['image' => 'https://picsum.photos/300/150?random=6']
        ];
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 设置系统配置
 */
function set_config($key, $value, $group = 'general', $description = '') {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value, config_group, description) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?, config_group = ?, description = ?");
        return $stmt->execute([$key, $value, $group, $description, $value, $group, $description]);
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取栏目列表
 */
function get_categories($parent_id = 0, $enabled_only = true) {
    // 添加静态缓存优化性能
    static $cache = [];
    $cache_key = $parent_id . '_' . ($enabled_only ? '1' : '0');
    
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return [];
    }
    
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
        $cache[$cache_key] = $result;
        return $result;
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 根据slug获取栏目信息
 */
function get_category_by_slug($slug) {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 根据ID获取栏目信息
 */
function get_category_by_id($id) {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取内容列表
 */
function get_contents($category_id = null, $limit = 10, $offset = 0, $published_only = true) {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return [];
    }
    
    try {
        $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                FROM contents c 
                LEFT JOIN categories cat ON c.category_id = cat.id 
                WHERE 1=1";
        $params = [];
        
        if ($category_id) {
            $sql .= " AND c.category_id = ?";
            $params[] = $category_id;
        }
        
        if ($published_only) {
            $sql .= " AND c.is_published = 1";
        }
        
        $sql .= " ORDER BY c.sort_order DESC, c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 根据slug获取内容详情
 */
function get_content_by_slug($slug) {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                              FROM contents c 
                              LEFT JOIN categories cat ON c.category_id = cat.id 
                              WHERE c.slug = ? AND c.is_published = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 增加浏览量
 */
function increment_view_count($content_id) {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("UPDATE contents SET view_count = view_count + 1 WHERE id = ?");
        return $stmt->execute([$content_id]);
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取推荐内容
 */
function get_featured_contents($limit = 5) {
    global $db;
    
    // 确保数据库连接有效
    if (!ensure_db_connection()) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                              FROM contents c 
                              LEFT JOIN categories cat ON c.category_id = cat.id 
                              WHERE c.is_featured = 1 AND c.is_published = 1 
                              ORDER BY c.sort_order DESC, c.created_at DESC 
                              LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 分页处理
 */
function get_pagination($total, $current_page, $per_page, $url_pattern) {
    $total_pages = ceil($total / $per_page);
    $pagination = [];
    
    if ($total_pages <= 1) {
        return $pagination;
    }
    
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    // 上一页
    if ($current_page > 1) {
        $pagination[] = [
            'text' => '上一页',
            'url' => str_replace('{page}', $current_page - 1, $url_pattern),
            'current' => false
        ];
    }
    
    // 页码
    for ($i = $start; $i <= $end; $i++) {
        $pagination[] = [
            'text' => $i,
            'url' => str_replace('{page}', $i, $url_pattern),
            'current' => $i == $current_page
        ];
    }
    
    // 下一页
    if ($current_page < $total_pages) {
        $pagination[] = [
            'text' => '下一页',
            'url' => str_replace('{page}', $current_page + 1, $url_pattern),
            'current' => false
        ];
    }
    
    return $pagination;
}

/**
 * URL生成
 */
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * 内容URL生成 - 统一的内容页URL生成函数
 */
function content_url($content) {
    if (!is_array($content)) {
        return url();
    }
    
    // 获取内容所属栏目
    $category = get_category_by_id($content['category_id']);
    if (!$category) {
        return url();
    }
    
    // 构建URL路径
    $path = '';
    
    // 检查是否有父栏目
    if ($category['parent_id'] > 0) {
        $parent_category = get_category_by_id($category['parent_id']);
        if ($parent_category) {
            $path .= $parent_category['slug'] . '/' . $category['slug'] . '/';
        } else {
            $path .= $category['slug'] . '/';
        }
    } else {
        $path .= $category['slug'] . '/';
    }
    
    // 添加内容slug和扩展名
    $path .= $content['slug'] . '.html';
    
    return url($path);
}

/**
 * 模板包含
 */
function template_include($template, $vars = []) {
    extract($vars);
    $template_file = TEMPLATE_DIR . '/' . $template . '.php';
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        echo "模板文件不存在: " . $template;
    }
}

/**
 * 重定向
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
 * JSON响应
 */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 简单的身份验证检查（用于后台）
 */
function check_admin_auth() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // 检查是否为AJAX请求
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
        // 保存用户原本要访问的页面
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        if ($is_ajax) {
            // AJAX请求返回JSON响应
            json_response(['success' => false, 'message' => '请先登录', 'redirect' => ADMIN_URL . '/login.php'], 401);
        } else {
            // 普通请求重定向到登录页面
            redirect(ADMIN_URL . '/login.php');
        }
    }
    
    // 检查会话超时
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        // 保存用户原本要访问的页面
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        if ($is_ajax) {
            // AJAX请求返回JSON响应
            json_response(['success' => false, 'message' => '会话已超时，请重新登录', 'redirect' => ADMIN_URL . '/login.php'], 401);
        } else {
            // 普通请求重定向到登录页面
            redirect(ADMIN_URL . '/login.php');
        }
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * 获取启用的内容模板列表
 */
function get_content_templates() {
    global $db;
    try {
        $stmt = $db->prepare("SELECT id, name, template_type, file_path FROM templates WHERE template_type = 'content' AND is_active = 1 ORDER BY sort_order ASC, id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 根据模板ID获取模板信息
 */
function get_template_by_id($id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * 获取Banner列表
 */
function get_banners($type = 'home', $limit = 5) {
    global $db;
    try {
        $sql = "SELECT * FROM banners WHERE is_active = 1";
        $params = [];
        
        // 根据类型筛选Banner
        if ($type !== 'all') {
            // 修复banner_type值不匹配的问题
            if ($type === 'home') {
                $sql .= " AND banner_type = ?";
                $params[] = 'home';
            } elseif ($type === 'inner') {
                $sql .= " AND banner_type = ?";
                $params[] = 'inner';
            } else {
                $sql .= " AND banner_type = ?";
                $params[] = $type;
            }
        }
        
        $sql .= " ORDER BY sort_order ASC, id DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 获取新闻动态内容
 */
function get_news_updates($limit = 3) {
    global $db;
    try {
        // 尝试通过栏目名称或slug获取新闻动态的ID
        $news_category_id = null;
        
        // 先尝试通过名称获取
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND is_active = 1");
        $stmt->execute(['新闻动态']);
        $category = $stmt->fetch();
        
        if ($category) {
            $news_category_id = $category['id'];
        } else {
            // 再尝试通过slug获取
            $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND is_active = 1");
            $stmt->execute(['news']);
            $category = $stmt->fetch();
            
            if ($category) {
                $news_category_id = $category['id'];
            }
        }
        
        if ($news_category_id) {
            // 如果找到新闻动态栏目，获取该栏目的内容
            return get_contents($news_category_id, $limit);
        } else {
            // 如果没有找到专门的新闻动态栏目，获取最新的内容
            $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                    FROM contents c 
                    LEFT JOIN categories cat ON c.category_id = cat.id 
                    WHERE c.is_published = 1 
                    ORDER BY c.created_at DESC 
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        return [];
    }
}

/**
 * 获取首页Banner列表
 */
function get_homepage_banners($limit = 5) {
    return get_banners('home', $limit);
}

/**
 * 获取内页Banner列表
 */
function get_innerpage_banners($limit = 5) {
    return get_banners('inner', $limit);
}

/**
 * 密码哈希
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 验证密码
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * 栏目URL生成
 */
function category_url($category) {
    if (!is_array($category)) {
        return url();
    }
    
    // 检查必要的键是否存在
    if (!isset($category['slug'])) {
        return url();
    }
    
    // 构建URL路径
    $path = '';
    
    // 检查是否有父栏目
    if (!empty($category['parent_id']) && $category['parent_id'] != 0) {
        $path .= get_category_path($category['parent_id']) . '/';
    }
    
    // 添加当前栏目slug
    $path .= $category['slug'] . '/';
    
    return url($path);
}

/**
 * 获取栏目路径（递归获取父栏目路径）
 */
function get_category_path($category_id) {
    global $db;
    
    if (empty($category_id)) {
        return '';
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch();
        
        if (!$category) {
            return '';
        }
        
        // 检查必要的键是否存在
        if (!isset($category['slug'])) {
            return '';
        }
        
        $path = '';
        if (!empty($category['parent_id']) && $category['parent_id'] != 0) {
            $path = get_category_path($category['parent_id']) . '/';
        }
        
        return $path . $category['slug'];
    } catch(PDOException $e) {
        return '';
    }
}

/**
 * 数据库查询单个结果
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
        return false;
    }
}

/**
 * 生成面包屑导航数据
 */
function generate_breadcrumb($category = null, $content = null) {
    $breadcrumbs = [];
    
    // 添加首页
    $breadcrumbs[] = [
        'name' => '首页',
        'url' => url()
    ];
    
    // 添加分类路径
    if (!empty($category)) {
        // 处理分类路径
        $categoryPath = [];
        $currentCat = $category;
        
        while ($currentCat) {
            // 检查必要的键是否存在
            if (!isset($currentCat['name']) || !isset($currentCat['slug'])) {
                break;
            }
            
            $categoryPath[] = [
                'name' => $currentCat['name'],
                'url' => category_url($currentCat)
            ];
            
            // 如果有父分类，继续向上查找
            if (!empty($currentCat['parent_id']) && $currentCat['parent_id'] != 0) {
                $parentCat = db_query_one("SELECT * FROM categories WHERE id = %d", $currentCat['parent_id']);
                $currentCat = $parentCat;
            } else {
                break;
            }
        }
        
        // 反转分类路径，使层级从浅到深
        $categoryPath = array_reverse($categoryPath);
        $breadcrumbs = array_merge($breadcrumbs, $categoryPath);
    }
    
    // 添加内容标题
    if (!empty($content)) {
        $breadcrumbs[] = [
            'name' => isset($content['title']) ? $content['title'] : '未知内容',
            'url' => ''
        ];
    }
    
    return $breadcrumbs;
}

/**
 * 渲染面包屑导航HTML
 */
function render_breadcrumb($category = null, $content = null) {
    // 使用优化版本的面包屑生成功能
    if (function_exists('generate_breadcrumb') && function_exists('render_breadcrumb_html')) {
        // 如果有$category但是没有ID，尝试获取ID
        $category_id = 0;
        if (!empty($category) && is_array($category)) {
            $category_id = isset($category['id']) ? $category['id'] : 0;
        }
        
        $breadcrumb_data = generate_breadcrumb_data($category_id, $content);
        return render_breadcrumb_html($breadcrumb_data);
    }
    
    // 原有实现作为后备
    $breadcrumbs = generate_breadcrumb($category, $content);
    
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $html .= '<div class="breadcrumb-item">';
        
        if (!empty($crumb['url']) && $index < count($breadcrumbs) - 1) {
            $html .= '<a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['name']) . '</a>';
        } else {
            $html .= '<span>' . htmlspecialchars($crumb['name']) . '</span>';
        }
        
        // 添加分隔符
        if ($index < count($breadcrumbs) - 1) {
            $html .= '<span class="breadcrumb-separator">›</span>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * 生成面包屑导航数据（兼容旧版本）
 */
function generate_breadcrumb_data($category_id = 0, $content = null) {
    $breadcrumb = [
        [
            'name' => '首页',
            'url' => url()
        ]
    ];
    
    // 添加栏目路径
    if ($category_id > 0) {
        $category_path = get_category_path_for_breadcrumb($category_id);
        $breadcrumb = array_merge($breadcrumb, $category_path);
    }
    
    // 添加当前内容
    if ($content) {
        $breadcrumb[] = [
            'name' => isset($content['title']) ? $content['title'] : '未知内容',
            'url' => ''
        ];
    }
    
    return $breadcrumb;
}

/**
 * 获取栏目路径用于面包屑导航
 */
function get_category_path_for_breadcrumb($category_id) {
    global $db;
    
    if (empty($category_id)) {
        return [];
    }
    
    $path = [];
    $current_id = $category_id;
    
    // 向上追溯所有父栏目
    while ($current_id > 0) {
        try {
            $stmt = $db->prepare("SELECT id, name, slug, parent_id FROM categories WHERE id = ?");
            $stmt->execute([$current_id]);
            $category = $stmt->fetch();
            
            if (!$category) {
                break;
            }
            
            // 检查必要的键是否存在
            if (!isset($category['name']) || !isset($category['slug'])) {
                break;
            }
            
            $path[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'url' => category_url($category)
            ];
            
            $current_id = $category['parent_id'];
        } catch(PDOException $e) {
            break;
        }
    }
    
    // 反转路径顺序（从根到当前）
    $path = array_reverse($path);
    
    return $path;
}

/**
 * 渲染面包屑导航HTML（兼容旧版本）
 */
function render_breadcrumb_html($breadcrumb) {
    if (empty($breadcrumb)) {
        return '';
    }
    
    // 生成JSON-LD结构化数据
    $json_ld = render_breadcrumb_json_ld($breadcrumb);
    
    $html = $json_ld; // 先添加结构化数据
    $html .= '<nav class="breadcrumb" aria-label="面包屑导航" itemscope itemtype="https://schema.org/BreadcrumbList">';
    
    foreach ($breadcrumb as $index => $item) {
        // 添加结构化数据属性
        $html .= '<div class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        
        if (empty($item['url']) || $index === count($breadcrumb) - 1) {
            $html .= '<span itemprop="name">' . htmlspecialchars($item['name']) . '</span>';
        } else {
            $html .= '<a class="breadcrumb-link" href="' . htmlspecialchars($item['url']) . '" itemprop="item">';
            $html .= '<span itemprop="name">' . htmlspecialchars($item['name']) . '</span>';
            $html .= '</a>';
        }
        
        // 添加位置信息
        $html .= '<meta itemprop="position" content="' . ($index + 1) . '" />';
        $html .= '</div>';
        
        // 添加分隔符（最后一项不添加）
        if ($index < count($breadcrumb) - 1) {
            $html .= '<span class="breadcrumb-separator">›</span>';
        }
    }
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * 生成面包屑导航的JSON-LD结构化数据
 */
function render_breadcrumb_json_ld($breadcrumb) {
    $items = [];
    foreach ($breadcrumb as $index => $item) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => !empty($item['url']) ? $item['url'] : null
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
 * 生成CSRF令牌
 */
function generate_token($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    } else {
        // 后备方案，使用time()和mt_rand()
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= chr(mt_rand(32, 126));
        }
        return md5($token . time());
    }
}

?>