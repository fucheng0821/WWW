<?php
/**
 * 图片相关工具函数
 * 提供图片URL处理、图片存在性检查等功能
 */

// 确保SITE_URL常量已定义
if (!defined('SITE_URL')) {
    // 如果未定义，尝试从配置中获取
    if (file_exists(dirname(__FILE__) . '/config.php')) {
        require_once dirname(__FILE__) . '/config.php';
    }
    // 如果仍然未定义，设置默认值
    if (!defined('SITE_URL')) {
        define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
    }
}

// 确保UPLOAD_DIR常量已定义
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__FILE__) . '/../uploads');
}

/**
 * 检查图片URL是否有效
 * @param string $image_url 图片URL
 * @return bool 是否有效
 */
function is_image_url_valid($image_url) {
    // 检查是否是http/https URL
    if (strpos($image_url, 'http') !== 0 && strpos($image_url, '//') !== 0) {
        // 相对路径，转换为绝对路径进行检查
        $absolute_url = rtrim(SITE_URL, '/') . '/' . ltrim($image_url, '/');
        return is_remote_file_exists($absolute_url);
    } else {
        return is_remote_file_exists($image_url);
    }
}

/**
 * 检查远程文件是否存在
 * @param string $url 远程文件URL
 * @return bool 是否存在
 */
function is_remote_file_exists($url) {
    // 简单检查URL格式
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // 使用快速的HEAD请求来检查文件是否存在
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 忽略SSL证书验证
    curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // 200表示成功，301/302表示重定向但文件存在
    return $status_code == 200 || $status_code == 301 || $status_code == 302;
}

/**
 * 处理内容中的图片URL，替换不存在的图片
 * @param string $content HTML内容
 * @param string $placeholder_image 占位图片URL
 * @return string 处理后的HTML内容
 */
function process_content_images($content, $placeholder_image = null) {
    // 如果没有提供占位图片，使用默认占位图
    if ($placeholder_image === null) {
        $placeholder_image = 'https://picsum.photos/800/450?random=' . rand(1, 1000);
    }
    
    // 使用正则表达式查找所有图片标签
    $pattern = '/<img\s+[^>]*src=("|\')([^"\'<>\s]+)("|\')[^>]*>/i';
    
    // 替换回调函数
    $callback = function($matches) use ($placeholder_image) {
        $img_tag = $matches[0];
        $image_url = $matches[2];
        
        // 检查图片URL是否以http/https开头
        if (strpos($image_url, 'http') !== 0 && strpos($image_url, '//') !== 0) {
            // 相对路径，检查是否是uploads/images目录下的图片
            if (strpos($image_url, 'uploads/images') === 0 || strpos($image_url, '/uploads/images') === 0) {
                // 构建完整的URL进行检查
                $absolute_url = rtrim(SITE_URL, '/') . '/' . ltrim($image_url, '/');
                if (!is_remote_file_exists($absolute_url)) {
                    // 图片不存在，替换为占位图
                    return str_replace($image_url, $placeholder_image, $img_tag);
                }
            }
        } else {
            // 绝对路径，直接检查
            if (!is_remote_file_exists($image_url)) {
                // 图片不存在，替换为占位图
                return str_replace($image_url, $placeholder_image, $img_tag);
            }
        }
        
        // 图片存在，保持原样
        return $img_tag;
    };
    
    // 执行替换
    return preg_replace_callback($pattern, $callback, $content);
}

/**
 * 快速处理内容中的图片URL（不进行实际的HTTP检查，仅基于文件路径判断）
 * @param string $content HTML内容
 * @param string $placeholder_image 占位图片URL
 * @return string 处理后的HTML内容
 */
function fast_process_content_images($content, $placeholder_image = null) {
    // 如果没有提供占位图片，使用默认占位图
    if ($placeholder_image === null) {
        $placeholder_image = 'https://picsum.photos/800/450?random=' . rand(1, 1000);
    }
    
    // 获取uploads/images目录下所有文件名
    static $uploaded_files = null;
    if ($uploaded_files === null) {
        $upload_dir = UPLOAD_DIR . '/images';
        $uploaded_files = [];
        
        if (is_dir($upload_dir)) {
            // 扫描目录获取所有文件
            $files = scandir($upload_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $uploaded_files[] = $file;
                }
            }
        }
    }
    
    // 使用正则表达式查找所有图片标签
    $pattern = '/<img\s+[^>]*src=("|\')([^"\'<>\s]+)("|\')[^>]*>/i';
    
    // 替换回调函数
    $callback = function($matches) use ($placeholder_image, $uploaded_files) {
        $img_tag = $matches[0];
        $image_url = $matches[2];
        
        // 检查图片URL是否包含uploads/images
        if (strpos($image_url, 'uploads/images') !== false) {
            // 提取文件名
            $path_parts = pathinfo($image_url);
            $filename = $path_parts['basename'];
            
            // 检查文件名是否在已上传文件列表中
            if (!in_array($filename, $uploaded_files)) {
                // 图片不存在，替换为占位图
                return str_replace($image_url, $placeholder_image, $img_tag);
            }
        }
        
        // 图片存在或不是本地图片，保持原样
        return $img_tag;
    };
    
    // 执行替换
    return preg_replace_callback($pattern, $callback, $content);
}

/**
 * 获取图片的绝对URL
 * @param string $image_path 图片路径
 * @return string 绝对URL
 */
function get_image_absolute_url($image_path) {
    if (strpos($image_path, 'http') === 0 || strpos($image_path, '//') === 0) {
        return $image_path;
    }
    return rtrim(SITE_URL, '/') . '/' . ltrim($image_path, '/');
}

/**
 * 修复内容中的相对图片URL为绝对URL
 * @param string $content HTML内容
 * @return string 处理后的HTML内容
 */
function fix_relative_image_urls($content) {
    // 使用正则表达式查找所有图片标签
    $pattern = '/<img\s+[^>]*src=("|\')([^"\'<>\s]+)("|\')[^>]*>/i';
    
    // 替换回调函数
    $callback = function($matches) {
        $img_tag = $matches[0];
        $image_url = $matches[2];
        
        // 如果已经是绝对URL，保持原样
        if (strpos($image_url, 'http') === 0 || strpos($image_url, '//') === 0) {
            return $img_tag;
        }
        
        // 转换为绝对URL
        $absolute_url = get_image_absolute_url($image_url);
        return str_replace($image_url, $absolute_url, $img_tag);
    };
    
    // 执行替换
    return preg_replace_callback($pattern, $callback, $content);
}

/**
 * 根据ID获取内容详情（为模板文件提供缺失的函数）
 * @param int $content_id 内容ID
 * @return array|false 内容详情
 */
function get_content_by_id($content_id) {
    global $db;
    
    // 确保数据库连接可用
    if (!isset($db) || !$db) {
        // 如果未连接数据库，尝试连接
        if (file_exists(dirname(__FILE__) . '/database.php')) {
            require_once dirname(__FILE__) . '/database.php';
        }
        // 如果仍然未连接，返回false
        if (!isset($db) || !$db) {
            return false;
        }
    }
    
    try {
        $stmt = $db->prepare("SELECT c.*, cat.name as category_name, cat.slug as category_slug 
                              FROM contents c 
                              LEFT JOIN categories cat ON c.category_id = cat.id 
                              WHERE c.id = ?");
        $stmt->execute([$content_id]);
        $content = $stmt->fetch();
        
        if ($content) {
            // 解析JSON字段
            $content['images'] = $content['images'] ? json_decode($content['images'], true) : [];
            $content['videos'] = $content['videos'] ? json_decode($content['videos'], true) : [];
            
            // 处理内容中的图片URL，替换不存在的图片
            $content['content'] = fast_process_content_images($content['content']);
        }
        
        return $content;
    } catch(PDOException $e) {
        return false;
    }
}
?>