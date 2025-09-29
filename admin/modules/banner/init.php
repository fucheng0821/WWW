<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

// 检查管理员权限
check_admin_auth();
if ($_SESSION['admin_role'] !== 'admin') {
    die('权限不足');
}

try {
    // 检查是否已存在banner_type字段
    $check_sql = "SHOW COLUMNS FROM `banners` LIKE 'banner_type'";
    $stmt = $db->prepare($check_sql);
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    // 如果banner_type字段不存在，则添加该字段
    if (!$column_exists) {
        $alter_sql = "ALTER TABLE `banners` 
                      ADD COLUMN `banner_type` ENUM('home', 'inner') NOT NULL DEFAULT 'home' COMMENT 'Banner类型(home:首页,inner:内页)' AFTER `link_url`,
                      ADD INDEX `idx_banner_type` (`banner_type`)";
        $db->exec($alter_sql);
    }
    
    // 创建或更新banners表结构
    $sql = "CREATE TABLE IF NOT EXISTS `banners` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) DEFAULT NULL COMMENT 'Banner标题',
        `subtitle` varchar(300) DEFAULT NULL COMMENT 'Banner副标题',
        `image_url` varchar(500) NOT NULL COMMENT '图片URL',
        `link_url` varchar(500) DEFAULT NULL COMMENT '链接URL',
        `banner_type` enum('home','inner') NOT NULL DEFAULT 'home' COMMENT 'Banner类型(home:首页,inner:内页)',
        `sort_order` int(11) DEFAULT '0' COMMENT '排序',
        `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        KEY `idx_sort_order` (`sort_order`),
        KEY `idx_is_active` (`is_active`),
        KEY `idx_banner_type` (`banner_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Banner表'";

    $db->exec($sql);
    
    // 检查是否已存在数据
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM banners");
    $stmt->execute();
    $count = $stmt->fetch()['count'];

    $message = "Banner表创建/更新成功";
    
    if ($count == 0) {
        // 插入默认Banner示例数据
        $insert_sql = "INSERT INTO `banners` (`title`, `subtitle`, `image_url`, `link_url`, `banner_type`, `sort_order`, `is_active`) VALUES 
            ('首页Banner示例1', '专业创意服务', 'https://picsum.photos/1920/600?random=1', '/', 'home', 1, 1),
            ('首页Banner示例2', '高品质视觉体验', 'https://picsum.photos/1920/600?random=2', '/', 'home', 2, 1),
            ('首页Banner示例3', '定制化解决方案', 'https://picsum.photos/1920/600?random=3', '/', 'home', 3, 1),
            ('内页Banner示例1', '专业服务展示', 'https://picsum.photos/1920/600?random=4', '/', 'inner', 1, 1),
            ('内页Banner示例2', '创意无限可能', 'https://picsum.photos/1920/600?random=5', '/', 'inner', 2, 1)";

        $db->exec($insert_sql);
        $message .= "\\n默认Banner示例数据插入成功";
    } else {
        $message .= "\\nBanner表中已存在数据，跳过示例数据插入";
    }

    echo "<script>alert('Banner表初始化完成！'); window.location.href='index.php';</script>";
} catch(Exception $e) {
    echo "<script>alert('初始化失败：" . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>