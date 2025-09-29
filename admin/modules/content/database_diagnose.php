<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 检查数据库表结构
$table_info = [];
$error_msg = '';
$status_field = '';

try {
    // 检查categories表字段
    $stmt = $db->query("SHOW COLUMNS FROM categories");
    $columns = $stmt->fetchAll();
    $table_info['categories'] = $columns;
    
    // 查找状态字段
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['is_enabled', 'is_active'])) {
            $status_field = $col['Field'];
            break;
        }
    }
    
    // 检查contents表字段
    $stmt = $db->query("SHOW COLUMNS FROM contents");
    $columns = $stmt->fetchAll();
    $table_info['contents'] = $columns;
    
    // 检查栏目数据
    $stmt = $db->query("SELECT COUNT(*) as total FROM categories");
    $category_count = $stmt->fetch()['total'];
    
    // 检查内容数据
    $stmt = $db->query("SELECT COUNT(*) as total FROM contents");
    $content_count = $stmt->fetch()['total'];
    
} catch(PDOException $e) {
    $error_msg = $e->getMessage();
}

// 如果找到了状态字段，测试查询
$test_query_result = '';
if ($status_field && !$error_msg) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM categories WHERE $status_field = 1");
        $enabled_count = $stmt->fetch()['total'];
        $test_query_result = "成功！启用的栏目数量：$enabled_count";
    } catch(PDOException $e) {
        $test_query_result = "查询失败：" . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库诊断和修复 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .container { 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        .field-highlight {
            background: #fffbee;
            color: #e6a23c;
            font-weight: 600;
        }
        .success {
            color: #28a745;
            font-weight: 600;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 数据库诊断和修复工具</h2>
        
        <?php if ($error_msg): ?>
            <div class="layui-alert layui-alert-danger">
                <h4>❌ 数据库连接错误</h4>
                <p><?php echo htmlspecialchars($error_msg); ?></p>
                <p>请检查数据库配置文件 /includes/config.php</p>
            </div>
        <?php else: ?>
            <div class="layui-alert layui-alert-success">
                <h4>✅ 数据库连接正常</h4>
                <p>栏目数据：<?php echo $category_count; ?> 条</p>
                <p>内容数据：<?php echo $content_count; ?> 条</p>
            </div>
            
            <div class="layui-tab layui-tab-brief">
                <ul class="layui-tab-title">
                    <li class="layui-this">字段诊断</li>
                    <li>categories表结构</li>
                    <li>contents表结构</li>
                    <li>自动修复</li>
                </ul>
                <div class="layui-tab-content">
                    <div class="layui-tab-item layui-show">
                        <h4>🔍 字段诊断结果</h4>
                        
                        <?php if ($status_field): ?>
                            <div class="layui-alert layui-alert-success">
                                <h5>✅ 找到状态字段</h5>
                                <p>categories表中找到状态字段：<code><?php echo $status_field; ?></code></p>
                                <p>测试查询结果：<?php echo $test_query_result; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="layui-alert layui-alert-danger">
                                <h5>❌ 未找到状态字段</h5>
                                <p>在categories表中未找到 is_enabled 或 is_active 字段</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // 检查SEO字段前缀
                        $seo_prefix = '';
                        foreach ($table_info['contents'] as $col) {
                            if (strpos($col['Field'], 'seo_title') !== false) {
                                $seo_prefix = 'seo_';
                                break;
                            } elseif (strpos($col['Field'], 'meta_title') !== false) {
                                $seo_prefix = 'meta_';
                                break;
                            }
                        }
                        ?>
                        
                        <?php if ($seo_prefix): ?>
                            <div class="layui-alert layui-alert-success">
                                <h5>✅ 找到SEO字段前缀</h5>
                                <p>contents表中SEO字段前缀：<code><?php echo $seo_prefix; ?></code></p>
                            </div>
                        <?php else: ?>
                            <div class="layui-alert layui-alert-warning">
                                <h5>⚠️ 未找到SEO字段</h5>
                                <p>在contents表中未找到 seo_title 或 meta_title 字段</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="layui-tab-item">
                        <h4>📋 categories表字段列表</h4>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th>字段名</th>
                                    <th>类型</th>
                                    <th>默认值</th>
                                    <th>注释</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($table_info['categories'] as $col): ?>
                                <tr<?php echo in_array($col['Field'], ['is_enabled', 'is_active']) ? ' class="field-highlight"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($col['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Default'] ?? 'NULL'); ?></td>
                                    <td><?php echo htmlspecialchars($col['Comment'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="layui-tab-item">
                        <h4>📋 contents表字段列表</h4>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th>字段名</th>
                                    <th>类型</th>
                                    <th>默认值</th>
                                    <th>注释</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($table_info['contents'] as $col): ?>
                                <tr<?php echo preg_match('/(seo|meta)_(title|keywords|description)/', $col['Field']) ? ' class="field-highlight"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($col['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($col['Default'] ?? 'NULL'); ?></td>
                                    <td><?php echo htmlspecialchars($col['Comment'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="layui-tab-item">
                        <h4>🔧 自动修复工具</h4>
                        
                        <?php if ($status_field): ?>
                            <div class="layui-alert layui-alert-success">
                                <h5>✅ 无需修复</h5>
                                <p>系统已正确识别状态字段 <code><?php echo $status_field; ?></code>，可以正常使用内容生成工具。</p>
                                <p><a href="data_generator.php" class="layui-btn layui-btn-normal">🚀 前往内容生成工具</a></p>
                            </div>
                        <?php else: ?>
                            <div class="layui-alert layui-alert-warning">
                                <h5>🛠️ 需要修复</h5>
                                <p>系统未找到状态字段，需要修复数据库表结构。</p>
                                
                                <h5>修复选项：</h5>
                                <ol>
                                    <li><strong>推荐方案：</strong>运行数据库初始化脚本重建表结构
                                        <p><a href="../category/init_all_tables.php" class="layui-btn layui-btn-primary">🔄 重新初始化数据库</a></p>
                                    </li>
                                    <li><strong>手动修复：</strong>添加缺失的状态字段
                                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px;">ALTER TABLE categories ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用';</pre>
                                    </li>
                                </ol>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="data_generator.php" class="layui-btn layui-btn-primary">🔙 返回工具入口</a>
            <a href="../category/" class="layui-btn layui-btn-primary">📁 栏目管理</a>
            <a href="../content/" class="layui-btn layui-btn-primary">📄 内容管理</a>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use('element', function(){
        var element = layui.element;
    });
    </script>
</body>
</html>