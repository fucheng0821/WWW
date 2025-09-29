<?php
/**
 * 缓存配置文件
 * 集中管理缓存相关设置
 */

// 缓存设置
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', true); // 是否启用缓存
}
define('CACHE_DEFAULT_TTL', 3600); // 默认缓存时间（秒）

// 不同数据类型的缓存时间（秒）
$cache_ttl_settings = [
    'banners' => 3600, // Banner缓存1小时
    'news' => 3600, // 新闻缓存1小时
    'categories' => 7200, // 栏目缓存2小时
    'content_list' => 3600, // 内容列表缓存1小时
    'featured' => 3600, // 推荐内容缓存1小时
];

/**
 * 获取指定数据类型的缓存时间
 * @param string $type 数据类型
 * @return int 缓存时间（秒）
 */
function get_cache_ttl($type) {
    global $cache_ttl_settings;
    return $cache_ttl_settings[$type] ?? CACHE_DEFAULT_TTL;
}

/**
 * 生成缓存键名
 * @param string $key 基础键名
 * @return string 完整缓存键名
 */
function generate_cache_key($key) {
    return 'cache_' . md5($key);
}

/**
 * 清除所有缓存
 * @return bool 是否成功清除
 */
if (!function_exists('clear_all_cache')) {
    function clear_all_cache() {
        if (!CACHE_ENABLED) {
            return true;
        }
        
        $cache_dir = CACHE_DIR;
        if (!is_dir($cache_dir)) {
            return true;
        }
        
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
}
?>