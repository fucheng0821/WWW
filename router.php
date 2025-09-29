<?php
// 路由优化 - 2025年性能优化版本
// 确保设置正确的工作目录
$root_path = __DIR__;
chdir($root_path);

// 检查是否需要重定向到正确协议的www版本
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// 检查是否需要重定向到www版本（可选）
// 如果访问的是不带www的域名，重定向到带www的版本
/*
if (strpos($host, 'www.') !== 0) {
    $redirect_url = $protocol . '://www.' . $host . $request_uri;
    redirect($redirect_url, 301);
}
*/

// 检查是否需要强制HTTPS（可选）
/*
if ($protocol !== 'https') {
    $redirect_url = 'https://'.$host.$request_uri;
    redirect($redirect_url, 301);
}
*/

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// 解析URL路径
$request_uri = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
$segments = array_filter(explode('/', $path));

// 如果是admin或appadmin目录，直接放行，不进行路由处理
if (!empty($segments) && ($segments[0] === 'admin' || $segments[0] === 'appadmin')) {
    return;
}

// 默认处理首页
if (empty($segments)) {
    include $root_path . '/index.php';
    exit;
}

// 特殊处理新闻URL格式: /news/content_slug.html
if (!empty($segments) && $segments[0] === 'news') {
    // 处理多种可能的新闻URL格式
    if (count($segments) === 2 && substr($segments[1], -5) === '.html') {
        // 格式: /news/content_slug.html
        $content_slug = substr($segments[1], 0, -5);
    } else if (count($segments) === 1 && substr($segments[0], -5) === '.html') {
        // 格式: /news.html (虽然不太可能，但为了兼容性保留)
        $content_slug = substr($segments[0], 0, -5);
    } else {
        $content_slug = '';
    }
    
    if (!empty($content_slug)) {
        // 尝试获取内容
        $content = get_content_by_slug($content_slug);
        
        if ($content) {
            // 增加浏览量
            increment_view_count($content['id']);
            include $root_path . '/templates/default/detail.php';
            exit;
        }
    }
    
    // 如果找不到特定内容，尝试加载新闻栏目列表页
    $news_category = get_category_by_slug('news');
    if ($news_category) {
        $category = $news_category;
        if ($category['template_type'] === 'channel') {
            include $root_path . '/templates/default/channel.php';
        } else {
            include $root_path . '/templates/default/list.php';
        }
        exit;
    }
}

// 获取栏目信息
$category_slug = $segments[0];

// 特殊处理联系我们页面
if ($category_slug === 'contact') {
    // 获取联系我们的栏目信息，用于面包屑导航
    $category = get_category_by_slug($category_slug);
    include $root_path . '/templates/default/contact.php';
    exit;
}

$category = get_category_by_slug($category_slug);

if (!$category) {
    // 如果找不到栏目，尝试直接查找内容（作为备选方案）
    if (count($segments) === 1 && substr($segments[0], -5) === '.html') {
        $content_slug = substr($segments[0], 0, -5);
        $content = get_content_by_slug($content_slug);
        
        if ($content) {
            // 找到内容，获取对应的栏目信息
            $category = get_category_by_id($content['category_id']);
            if ($category) {
                // 增加浏览量
                increment_view_count($content['id']);
                // 传递$category和$content变量到模板
                include $root_path . '/templates/default/detail.php';
                exit;
            }
        }
    }
    
    // 仍然找不到，返回404页面
    http_response_code(404);
    include $root_path . '/templates/default/404.php';
    exit;
}

// 根据栏目层级和类型处理
if (count($segments) === 1) {
    // 一级栏目页面
    if ($category['template_type'] === 'channel') {
        include $root_path . '/templates/default/channel.php';
    } elseif ($category['template_type'] === 'content') {
        // For content template type, we need to check if there's actual content
        // If no content, fall back to channel template
        $contents = get_contents($category['id'], 1, 0, true);
        if (!empty($contents)) {
            // Use the first content item
            $content = $contents[0];
            // 增加浏览量
            increment_view_count($content['id']);
            // 传递$category和$content变量到模板
            include $root_path . '/templates/default/content.php';
        } else {
            // No content found, fall back to channel template
            include $root_path . '/templates/default/channel.php';
        }
    } else {
        include $root_path . '/templates/default/list.php';
    }
} elseif (count($segments) === 2) {
    // 二级栏目页面或内容页面
    $second_segment = $segments[1];
    
    // 检查是否为内容页面（以.html结尾）
    if (substr($second_segment, -5) === '.html') {
        $content_slug = substr($second_segment, 0, -5);
        $content = get_content_by_slug($content_slug);
        
        if ($content && $content['category_id'] == $category['id']) {
            // 增加浏览量
            increment_view_count($content['id']);
            // 传递$category和$content变量到模板
            include $root_path . '/templates/default/detail.php';
        } else {
            http_response_code(404);
            include $root_path . '/templates/default/404.php';
        }
    } else {
        // 二级栏目页面
        $sub_category = get_category_by_slug($second_segment);
        
        if ($sub_category && $sub_category['parent_id'] == $category['id']) {
            $category = $sub_category; // 使用二级栏目信息
            include $root_path . '/templates/default/list.php';
        } else {
            http_response_code(404);
            include $root_path . '/templates/default/404.php';
        }
    }
} elseif (count($segments) === 3) {
    // 三级内容页面 (parent/category/content.html)
    $second_segment = $segments[1];
    $third_segment = $segments[2];
    
    if (substr($third_segment, -5) === '.html') {
        $content_slug = substr($third_segment, 0, -5);
        $sub_category = get_category_by_slug($second_segment);
        
        if ($sub_category && $sub_category['parent_id'] == $category['id']) {
            $content = get_content_by_slug($content_slug);
            
            if ($content && $content['category_id'] == $sub_category['id']) {
                $category = $sub_category; // 使用二级栏目信息
                increment_view_count($content['id']);
                // 传递$category和$content变量到模板
                include $root_path . '/templates/default/detail.php';
            } else {
                http_response_code(404);
                include $root_path . '/templates/default/404.php';
            }
        } else {
            http_response_code(404);
            include $root_path . '/templates/default/404.php';
        }
    } else {
        http_response_code(404);
        include $root_path . '/templates/default/404.php';
    }
} else {
    // 其他情况返回404
    http_response_code(404);
    include $root_path . '/templates/default/404.php';
}
?>