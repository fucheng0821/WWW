<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 先检查数据库连接和栏目表
try {
    // 自动检测状态字段名
    $stmt = $db->query("SHOW COLUMNS FROM categories");
    $columns = $stmt->fetchAll();
    $status_field = 'is_enabled'; // 默认值
    
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['is_enabled', 'is_active'])) {
            $status_field = $col['Field'];
            break;
        }
    }
    
    // 使用检测到的字段名查询
    $stmt = $db->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) as sub_count
        FROM categories c 
        WHERE c.{$status_field} = 1 
        AND (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) = 0
        ORDER BY c.id ASC
    ");
    $leaf_categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $leaf_categories = [];
    $error_msg = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>内容生成工具入口 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .tool-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 20px;
            margin: 15px 0;
            text-align: center;
        }
        .tool-card h3 {
            margin-top: 0;
            color: #007bff;
        }
        .status-ok {
            color: #28a745;
            font-weight: 600;
        }
        .status-error {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📊 内容批量生成工具</h2>
        
        <div class="layui-alert layui-alert-normal">
            <h3>🎯 工具功能</h3>
            <p>根据您的要求：<strong>"随机为每个最底级栏目各增加10条数据，内容要和栏目名称相关，列表就随机用图片，内容页就随机图文"</strong></p>
            <ul>
                <li>🎯 自动识别最底级栏目（没有子栏目的栏目）</li>
                <li>📝 根据栏目名称生成相关内容模板</li>
                <li>🖼️ 列表页：随机缩略图</li>
                <li>📄 内容页：随机图文内容</li>
                <li>🔢 每个栏目生成10条数据</li>
                <li>🔍 SEO优化：自动生成标题、关键词、描述</li>
            </ul>
        </div>
        
        <?php if (isset($error_msg)): ?>
            <div class="layui-alert layui-alert-danger">
                <h4>❌ 数据库连接错误</h4>
                <p>错误信息：<?php echo htmlspecialchars($error_msg); ?></p>
                <p>请检查数据库配置和表结构。</p>
            </div>
        <?php else: ?>
            <div class="tool-card">
                <h3>🔍 栏目结构检查</h3>
                <p>检查当前数据库中的栏目结构，确定最底级栏目</p>
                <p class="<?php echo count($leaf_categories) > 0 ? 'status-ok' : 'status-error'; ?>">
                    找到 <?php echo count($leaf_categories); ?> 个最底级栏目
                </p>
            </div>
            
            <?php if (count($leaf_categories) > 0): ?>
                <div class="tool-card">
                    <h3>🚀 批量生成数据</h3>
                    <p>为 <?php echo count($leaf_categories); ?> 个最底级栏目生成内容数据</p>
                    <p class="status-ok">将生成 <?php echo count($leaf_categories) * 10; ?> 条内容</p>
                    <a href="ultimate_generate_data.php" class="layui-btn layui-btn-normal">🎯 开始生成</a>
                </div>
                
                <div class="layui-alert layui-alert-success">
                    <h4>✅ 准备就绪</h4>
                    <p>系统已准备好为以下栏目生成内容：</p>
                    <ul>
                        <?php foreach ($leaf_categories as $cat): ?>
                            <li><strong><?php echo htmlspecialchars($cat['name']); ?></strong> (<?php echo $cat['template_type']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="layui-alert layui-alert-warning">
                    <h4>⚠️ 没有可用栏目</h4>
                    <p>当前没有找到最底级栏目。可能的原因：</p>
                    <ul>
                        <li>数据库中没有栏目数据</li>
                        <li>所有栏目都有子栏目</li>
                        <li>栏目状态未启用</li>
                    </ul>
                    <p>建议先检查栏目结构或创建一些二级栏目。</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="tool-card">
            <h3>📁 相关管理</h3>
            <p>管理栏目和内容</p>
            <a href="../category/" class="layui-btn layui-btn-primary">栏目管理</a>
            <a href="../content/" class="layui-btn layui-btn-primary">内容管理</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="../../" class="layui-btn layui-btn-primary">🏠 返回后台首页</a>
        </div>
    </div>
</body>
</html>