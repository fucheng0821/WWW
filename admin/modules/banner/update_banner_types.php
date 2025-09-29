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

// 如果是POST请求，更新Banner类型
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_types'])) {
    try {
        // 更新所有Banner的类型
        $stmt = $db->prepare("UPDATE banners SET banner_type = ? WHERE id = ?");
        
        foreach ($_POST['banner_types'] as $id => $type) {
            $stmt->execute([$type, $id]);
        }
        
        $message = "Banner类型更新成功！";
    } catch(Exception $e) {
        $error = "更新失败：" . $e->getMessage();
    }
}

// 获取所有Banner
try {
    $stmt = $db->prepare("SELECT * FROM banners ORDER BY id");
    $stmt->execute();
    $banners = $stmt->fetchAll();
} catch(Exception $e) {
    $error = "获取Banner数据失败：" . $e->getMessage();
    $banners = [];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新Banner类型 - 高光视刻后台管理</title>
    <link rel="stylesheet" href="../../../assets/css/admin.css">
    <style>
        .banner-list {
            margin: 20px 0;
        }
        .banner-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .banner-image {
            width: 150px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 3px;
        }
        .banner-info {
            flex: 1;
        }
        .banner-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .banner-url {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .banner-type-select {
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .btn-update {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 0;
        }
        .btn-update:hover {
            background: #0056b3;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>更新Banner类型</h1>
        
        <?php if (isset($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="update_types" value="1">
            
            <div class="banner-list">
                <?php if (empty($banners)): ?>
                    <p>暂无Banner数据</p>
                <?php else: ?>
                    <?php foreach ($banners as $banner): ?>
                    <div class="banner-item">
                        <?php if (!empty($banner['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" class="banner-image">
                        <?php else: ?>
                            <div class="banner-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <span>无图片</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="banner-info">
                            <div class="banner-title"><?php echo htmlspecialchars($banner['title']); ?></div>
                            <?php if (!empty($banner['subtitle'])): ?>
                                <div class="banner-subtitle"><?php echo htmlspecialchars($banner['subtitle']); ?></div>
                            <?php endif; ?>
                            <div class="banner-url"><?php echo htmlspecialchars($banner['image_url']); ?></div>
                            
                            <label>
                                Banner类型:
                                <select name="banner_types[<?php echo $banner['id']; ?>]" class="banner-type-select">
                                    <option value="home" <?php echo $banner['banner_type'] === 'home' ? 'selected' : ''; ?>>首页Banner</option>
                                    <option value="inner" <?php echo $banner['banner_type'] === 'inner' ? 'selected' : ''; ?>>内页Banner</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($banners)): ?>
                <button type="submit" class="btn-update">更新Banner类型</button>
            <?php endif; ?>
        </form>
        
        <p><a href="index.php">返回Banner管理</a></p>
    </div>
</body>
</html>