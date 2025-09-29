<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// Set JSON response header for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

$errors = [];
$success = '';

try {
    // Begin transaction
    $db->beginTransaction();
    
    // Clear existing templates
    $db->exec("TRUNCATE TABLE templates");
    
    // Insert default templates
    $default_templates = [
        [
            'name' => '默认首页模板',
            'description' => '网站首页默认模板，包含轮播图、产品展示、公司介绍等模块',
            'template_type' => 'index',
            'file_path' => 'templates/default/index.php',
            'is_active' => 1,
            'is_default' => 1,
            'sort_order' => 1,
            'variables' => 'slider_images, featured_products, company_intro, news_list'
        ],
        [
            'name' => '产品频道模板',
            'description' => '产品频道页面模板，展示产品分类和特色产品',
            'template_type' => 'channel',
            'file_path' => 'templates/default/channel/product.php',
            'is_active' => 1,
            'is_default' => 1,
            'sort_order' => 1,
            'variables' => 'category_list, featured_products, category_description'
        ],
        [
            'name' => '服务频道模板',
            'description' => '服务频道页面模板，展示服务项目和案例',
            'template_type' => 'channel',
            'file_path' => 'templates/default/channel/service.php',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 2,
            'variables' => 'service_list, case_studies, service_description'
        ],
        [
            'name' => '新闻列表模板',
            'description' => '新闻资讯列表页面模板',
            'template_type' => 'list',
            'file_path' => 'templates/default/list/news.php',
            'is_active' => 1,
            'is_default' => 1,
            'sort_order' => 1,
            'variables' => 'news_list, pagination, category_filter'
        ],
        [
            'name' => '产品列表模板',
            'description' => '产品列表页面模板，支持筛选和分页',
            'template_type' => 'list',
            'file_path' => 'templates/default/list/product.php',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 2,
            'variables' => 'product_list, pagination, filter_options'
        ],
        [
            'name' => '新闻内容模板',
            'description' => '新闻详情页面模板',
            'template_type' => 'content',
            'file_path' => 'templates/default/content/news.php',
            'is_active' => 1,
            'is_default' => 1,
            'sort_order' => 1,
            'variables' => 'news_content, related_news, share_buttons'
        ],
        [
            'name' => '产品详情模板',
            'description' => '产品详情页面模板，包含产品图片、参数、询价表单',
            'template_type' => 'content',
            'file_path' => 'templates/default/content/product.php',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 2,
            'variables' => 'product_info, product_images, inquiry_form, related_products'
        ],
        [
            'name' => '公司介绍模板',
            'description' => '公司介绍页面模板',
            'template_type' => 'content',
            'file_path' => 'templates/default/content/about.php',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 3,
            'variables' => 'company_info, team_members, company_history'
        ],
        // New image display template
        [
            'name' => '图片展示模板',
            'description' => '专门用于展示图片画廊的模板，支持响应式布局和图片放大功能',
            'template_type' => 'content',
            'file_path' => 'templates/default/content/image-gallery.php',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 4,
            'variables' => 'gallery_images, gallery_title, gallery_description'
        ],
        // New video display template
        [
            'name' => '视频展示模板',
            'description' => '专门用于展示视频内容的模板，支持多种视频格式和响应式播放器',
            'template_type' => 'content',
            'file_path' => 'templates/default/content/video-gallery.php',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 5,
            'variables' => 'video_list, video_title, video_description'
        ]
    ];
    
    $stmt = $db->prepare("INSERT INTO templates (name, description, template_type, file_path, is_active, is_default, sort_order, variables) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($default_templates as $template) {
        $stmt->execute([
            $template['name'],
            $template['description'],
            $template['template_type'],
            $template['file_path'],
            $template['is_active'],
            $template['is_default'],
            $template['sort_order'],
            $template['variables']
        ]);
    }
    
    // Commit transaction
    $db->commit();
    $success = '模板表初始化成功！已创建 ' . count($default_templates) . ' 个默认模板。';
    
    // Log the action
    error_log("Template table initialized by admin user ID: " . ($_SESSION['admin_id'] ?? 'unknown'));
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    $errors[] = '初始化失败：' . $e->getMessage();
    error_log("Template table initialization failed: " . $e->getMessage());
}

// If this is an AJAX request, return JSON response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => $errors[0]]);
    } else {
        echo json_encode(['success' => true, 'message' => $success]);
    }
    exit();
}

// For regular requests, redirect back to the template management page
if (!empty($errors)) {
    $_SESSION['error'] = $errors[0];
} else {
    $_SESSION['success'] = $success;
}

header('Location: index.php');
exit();