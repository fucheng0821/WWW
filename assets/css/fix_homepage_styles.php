<?php
/**
 * 修复首页样式丢失问题的脚本
 * 
 * 该脚本会检查并修复首页样式文件引用问题
 */

// 检查所有必要的CSS文件是否存在
$required_css_files = [
    'assets/css/main.css',
    'assets/css/base/reset.css',
    'assets/css/base/variables.css',
    'assets/css/base/typography.css',
    'assets/css/components/buttons.css',
    'assets/css/components/forms.css',
    'assets/css/components/cards.css',
    'assets/css/components/navigation.css',
    'assets/css/layout/grid.css',
    'assets/css/layout/header.css',
    'assets/css/layout/footer.css',
    'assets/css/utils/helpers.css',
    'assets/css/utils/animations.css',
    'assets/css/pages/frontend/home.css',
    'assets/css/responsive.css',
    'templates/default/assets/css/frontend/dynamic-banner.css',
    'templates/default/assets/css/frontend/enhanced-placeholder.css',
    'templates/default/assets/css/frontend/testimonials-section.css',
    'templates/default/assets/css/frontend/inquiry-section.css',
    'templates/default/assets/css/frontend/services-homepage.css'
];

$missing_files = [];

foreach ($required_css_files as $file) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
    if (!file_exists($full_path)) {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    echo "所有CSS文件都存在。\n";
} else {
    echo "缺少以下CSS文件：\n";
    foreach ($missing_files as $file) {
        echo "- " . $file . "\n";
    }
}

// 检查首页文件中的CSS引用
$index_file = $_SERVER['DOCUMENT_ROOT'] . '/index.php';
if (file_exists($index_file)) {
    $content = file_get_contents($index_file);
    
    // 检查是否包含必要的CSS引用
    $required_links = [
        '<link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">',
        '<link rel="stylesheet" href="assets/css/main.css">',
        '<link rel="stylesheet" href="assets/css/pages/frontend/home.css">',
        '<link rel="stylesheet" href="templates/default/assets/css/frontend/dynamic-banner.css">',
        '<link rel="stylesheet" href="templates/default/assets/css/frontend/enhanced-placeholder.css">',
        '<link rel="stylesheet" href="templates/default/assets/css/frontend/testimonials-section.css">',
        '<link rel="stylesheet" href="templates/default/assets/css/frontend/inquiry-section.css">',
        '<link rel="stylesheet" href="templates/default/assets/css/frontend/services-homepage.css">',
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">'
    ];
    
    $missing_links = [];
    foreach ($required_links as $link) {
        if (strpos($content, $link) === false) {
            $missing_links[] = $link;
        }
    }
    
    if (empty($missing_links)) {
        echo "首页文件中的CSS引用完整。\n";
    } else {
        echo "首页文件中缺少以下CSS引用：\n";
        foreach ($missing_links as $link) {
            echo "- " . htmlspecialchars($link) . "\n";
        }
    }
} else {
    echo "首页文件不存在。\n";
}

echo "检查完成。\n";
?>