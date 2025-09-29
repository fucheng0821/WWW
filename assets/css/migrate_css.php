<?php
/**
 * CSS文件迁移脚本
 * 将现有的CSS文件内容迁移到新的结构中
 */

// 定义源目录和目标目录
$source_dir = '../templates/default/assets/css';
$target_dir = '.';

// 创建必要的目录
$directories = [
    'base',
    'components',
    'layout',
    'pages/frontend',
    'pages/admin',
    'utils'
];

foreach ($directories as $dir) {
    $path = $target_dir . '/' . $dir;
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

// 定义文件映射关系
$file_mappings = [
    // 基础样式
    'base/variables.css' => [
        '../templates/default/assets/css/style_optimized.css',
        ':root'
    ],
    'base/typography.css' => [
        '../templates/default/assets/css/style_optimized.css',
        'h1, h2, h3, h4, h5, h6'
    ],
    
    // 组件样式
    'components/buttons.css' => [
        '../templates/default/assets/css/style_optimized.css',
        '.btn-primary'
    ],
    'components/forms.css' => [
        '../templates/default/assets/css/style_optimized.css',
        '.footer-inquiry-form'
    ],
    'components/cards.css' => [
        '../admin/assets/css/admin-optimized.css',
        '.admin-card'
    ],
    
    // 布局样式
    'layout/grid.css' => [
        '../templates/default/assets/css/style_optimized.css',
        '.container'
    ],
    'layout/header.css' => [
        '../admin/assets/css/admin-optimized.css',
        '.layui-layout-admin .layui-header'
    ],
    'layout/footer.css' => [
        '../templates/default/assets/css/fixes.css',
        '.site-footer'
    ],
    
    // 页面样式
    'pages/frontend/home.css' => [
        '../templates/default/assets/css/frontend/homepage.css',
        '.home-page'
    ],
    'pages/admin/admin.css' => [
        '../admin/assets/css/admin-optimized.css',
        '.layui-layout-admin'
    ],
    'pages/admin/dashboard.css' => [
        '../admin/assets/css/admin-optimized.css',
        '.today-stats'
    ]
];

// 迁移文件内容
foreach ($file_mappings as $target_file => $source_info) {
    $source_file = $source_info[0];
    $marker = $source_info[1];
    
    if (file_exists($source_file)) {
        $content = file_get_contents($source_file);
        
        // 提取相关样式
        $extracted_content = extract_styles($content, $marker);
        
        if (!empty($extracted_content)) {
            // 保存到目标文件
            file_put_contents($target_dir . '/' . $target_file, $extracted_content);
            echo "已迁移: $target_file\n";
        }
    }
}

/**
 * 从CSS文件中提取特定标记的样式
 */
function extract_styles($content, $marker) {
    $lines = explode("\n", $content);
    $extracted = [];
    $in_target_section = false;
    $brace_count = 0;
    
    foreach ($lines as $line) {
        // 检查是否是目标标记的开始
        if (strpos($line, $marker) !== false) {
            $in_target_section = true;
            $brace_count = 0;
        }
        
        if ($in_target_section) {
            $extracted[] = $line;
            
            // 计算大括号数量
            $brace_count += substr_count($line, '{');
            $brace_count -= substr_count($line, '}');
            
            // 如果大括号匹配完成，结束提取
            if ($brace_count === 0 && strpos($line, '}') !== false) {
                $in_target_section = false;
                $extracted[] = ''; // 添加空行分隔
            }
        }
    }
    
    return implode("\n", $extracted);
}

echo "CSS文件迁移完成！\n";
?>