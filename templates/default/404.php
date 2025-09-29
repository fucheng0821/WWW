<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// 页面SEO信息
$page_title = '页面未找到 - ' . get_config('site_name', '高光视刻');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="templates/default/assets/css/frontend/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-pink));
        }
        .error-content {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: var(--accent-blue);
            margin-bottom: 20px;
            line-height: 1;
        }
        .error-title {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        .error-message {
            color: var(--text-light);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .back-home {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-pink));
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.3s ease;
        }
        .back-home:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        .search-form {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">页面未找到</h1>
            <p class="error-message">
                抱歉，您访问的页面不存在或已被移动。<br>
                请检查网址是否正确，或者尝试以下操作：
            </p>
            
            <div class="error-actions">
                <a href="/" class="back-home">
                    <i class="fas fa-home"></i> 返回首页
                </a>
                <a href="/contact/" class="back-home">
                    <i class="fas fa-comment"></i> 联系我们
                </a>
            </div>
            
            <div class="search-form">
                <form action="/search/" method="GET">
                    <input type="text" name="keyword" class="search-input" placeholder="搜索您需要的内容...">
                    <button type="submit" class="layui-btn layui-btn-normal layui-btn-fluid">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 30px;">
                <p style="color: var(--text-light); font-size: 14px;">
                    如果问题持续存在，请联系我们：
                    <a href="tel:<?php echo get_config('contact_phone', '400-888-8888'); ?>" style="color: var(--accent-blue);">
                        <?php echo get_config('contact_phone', '400-888-8888'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js" defer></script>
</body>
</html>