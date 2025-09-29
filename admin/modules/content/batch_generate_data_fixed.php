<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$success_messages = [];
$error_messages = [];

// 处理批量生成数据请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_data'])) {
    try {
        // 获取所有最底级栏目（根据实际字段名）
        $stmt = $db->query("
            SELECT c.* FROM categories c 
            WHERE c.is_enabled = 1 
            AND (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) = 0
            ORDER BY c.id ASC
        ");
        $leaf_categories = $stmt->fetchAll();
        
        if (empty($leaf_categories)) {
            $error_messages[] = "没有找到最底级栏目！";
        } else {
            $db->beginTransaction();
            $total_created = 0;
            
            foreach ($leaf_categories as $category) {
                // 根据栏目名称生成相关内容
                $content_templates = generateContentTemplates($category['name']);
                
                // 为每个栏目生成10条数据
                for ($i = 1; $i <= 10; $i++) {
                    $template = $content_templates[array_rand($content_templates)];
                    
                    $title = $template['title'] . ' ' . $i;
                    $slug = generate_slug($title . '-' . time() . '-' . $i);
                    
                    // 生成缩略图和图片集合
                    $thumbnail = '/uploads/images/demo/' . rand(1, 20) . '.jpg';
                    $images = [];
                    if ($category['template_type'] === 'content' || rand(0, 1)) {
                        for ($j = 1; $j <= rand(2, 4); $j++) {
                            $images[] = '/uploads/images/demo/' . rand(1, 20) . '.jpg';
                        }
                    }
                    
                    // 插入内容（使用正确的SEO字段名）
                    $stmt = $db->prepare("
                        INSERT INTO contents 
                        (category_id, title, slug, summary, content, thumbnail, images, tags, 
                         view_count, sort_order, is_featured, is_published, published_at, 
                         seo_title, seo_keywords, seo_description, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $published_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
                    
                    $result = $stmt->execute([
                        $category['id'], $title, $slug, $template['summary'], $template['content'],
                        $thumbnail, $images ? json_encode($images) : null, $template['tags'],
                        rand(10, 500), $i, ($i <= 3 ? 1 : 0), 1, $published_at,
                        $title . ' - ' . $category['name'], $template['keywords'], $template['summary']
                    ]);
                    
                    if ($result) $total_created++;
                }
                
                $success_messages[] = "栏目「{$category['name']}」已生成 10 条内容";
            }
            
            $db->commit();
            $success_messages[] = "总共成功生成 {$total_created} 条内容数据！";
        }
        
    } catch(PDOException $e) {
        $db->rollBack();
        $error_messages[] = "生成失败：" . $e->getMessage();
    }
}

// 内容模板生成函数
function generateContentTemplates($category_name) {
    $templates = [];
    
    if (strpos($category_name, '视频') !== false) {
        $templates = [
            [
                'title' => '企业宣传片制作案例',
                'summary' => '专业的企业宣传片制作服务，通过精心策划和专业拍摄，为企业打造独特的品牌形象展示片。',
                'content' => '<h2>视频制作服务</h2><p>专业的视频制作团队，提供企业宣传片、产品演示、活动记录等视频服务。</p><ul><li>前期策划创意</li><li>专业设备拍摄</li><li>后期剪辑制作</li><li>多格式交付</li></ul>',
                'tags' => '视频制作,企业宣传片,品牌形象',
                'keywords' => '视频制作,企业宣传片,品牌推广'
            ],
            [
                'title' => '产品演示视频拍摄',
                'summary' => '高质量的产品演示视频制作，突出产品特色和优势。',
                'content' => '<h2>产品视频制作</h2><p>专业的产品演示视频，展现产品优势，提升营销效果。</p>',
                'tags' => '产品视频,演示拍摄,营销视频',
                'keywords' => '产品视频,视频营销,产品推广'
            ]
        ];
    } elseif (strpos($category_name, '设计') !== false) {
        $templates = [
            [
                'title' => '品牌LOGO设计案例',
                'summary' => '专业的品牌LOGO设计服务，结合企业文化打造独特标识。',
                'content' => '<h2>设计服务</h2><p>专业设计团队，提供品牌设计、平面设计等服务。</p><ul><li>LOGO设计</li><li>VI系统</li><li>宣传物料</li><li>包装设计</li></ul>',
                'tags' => '品牌设计,LOGO设计,视觉识别',
                'keywords' => 'LOGO设计,品牌设计,视觉设计'
            ],
            [
                'title' => '宣传册设计制作',
                'summary' => '高端宣传册设计制作，提升企业形象。',
                'content' => '<h2>宣传册设计</h2><p>专业的宣传册设计，融合创意和营销理念。</p>',
                'tags' => '宣传册设计,画册设计,平面设计',
                'keywords' => '宣传册设计,画册制作,平面设计'
            ]
        ];
    } elseif (strpos($category_name, '网站') !== false || strpos($category_name, '开发') !== false) {
        $templates = [
            [
                'title' => '企业官网建设方案',
                'summary' => '专业的企业官网建设服务，采用现代化技术。',
                'content' => '<h2>网站建设</h2><p>专业的网站开发团队，提供企业官网、电商平台等建设服务。</p><ul><li>响应式设计</li><li>SEO优化</li><li>安全防护</li><li>性能优化</li></ul>',
                'tags' => '网站建设,企业官网,响应式设计',
                'keywords' => '网站建设,企业网站,网站开发'
            ]
        ];
    } elseif (strpos($category_name, '摄影') !== false) {
        $templates = [
            [
                'title' => '商业产品摄影服务',
                'summary' => '专业的商业产品摄影服务，展现产品最佳效果。',
                'content' => '<h2>摄影服务</h2><p>专业摄影团队，提供产品摄影、企业形象照等服务。</p><ul><li>产品摄影</li><li>人像摄影</li><li>活动摄影</li><li>后期精修</li></ul>',
                'tags' => '产品摄影,商业摄影,专业拍摄',
                'keywords' => '产品摄影,商业拍摄,摄影服务'
            ]
        ];
    } elseif (strpos($category_name, '活动') !== false || strpos($category_name, '策划') !== false) {
        $templates = [
            [
                'title' => '企业年会策划方案',
                'summary' => '专业的企业年会策划服务，打造难忘的企业活动。',
                'content' => '<h2>活动策划</h2><p>专业的活动策划团队，提供年会、发布会、展览等策划服务。</p><ul><li>创意策划</li><li>现场执行</li><li>资源协调</li><li>效果保障</li></ul>',
                'tags' => '年会策划,活动策划,企业活动',
                'keywords' => '年会策划,活动策划,企业年会'
            ]
        ];
    } else {
        $templates = [
            [
                'title' => '专业服务介绍',
                'summary' => '我们提供专业的' . $category_name . '服务，为客户提供高质量解决方案。',
                'content' => '<h2>' . $category_name . '服务</h2><p>专业团队，提供' . $category_name . '相关服务。</p><ul><li>专业团队</li><li>定制方案</li><li>质量保证</li><li>及时交付</li></ul>',
                'tags' => $category_name . ',专业服务,解决方案',
                'keywords' => $category_name . ',专业服务,高质量'
            ]
        ];
    }
    
    return $templates;
}

// 获取栏目统计
try {
    $stmt = $db->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) as sub_count,
               (SELECT COUNT(*) FROM contents WHERE category_id = c.id) as content_count
        FROM categories c 
        WHERE c.is_enabled = 1
        ORDER BY c.parent_id ASC, c.sort_order ASC
    ");
    $all_categories = $stmt->fetchAll();
    
    $leaf_categories = array_filter($all_categories, function($cat) {
        return $cat['sub_count'] == 0;
    });
    
} catch(PDOException $e) {
    $all_categories = [];
    $leaf_categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量生成内容数据 - 高光视刻</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: #007bff;
        }
        .category-item {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📊 批量生成内容数据</h2>
        
        <?php if ($success_messages): ?>
            <div class="layui-alert layui-alert-success">
                <?php foreach ($success_messages as $msg): ?>
                    <p><?php echo htmlspecialchars($msg); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_messages): ?>
            <div class="layui-alert layui-alert-danger">
                <?php foreach ($error_messages as $msg): ?>
                    <p><?php echo htmlspecialchars($msg); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($all_categories); ?></div>
                <div>总栏目数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($leaf_categories); ?></div>
                <div>最底级栏目</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($leaf_categories) * 10; ?></div>
                <div>将生成内容数</div>
            </div>
        </div>
        
        <div class="layui-alert layui-alert-normal">
            <h3>📋 功能说明</h3>
            <ul>
                <li>为每个最底级栏目生成10条相关内容</li>
                <li>根据栏目名称自动匹配内容模板</li>
                <li>列表页使用随机缩略图，内容页包含图片集合</li>
                <li>自动生成SEO优化信息</li>
            </ul>
        </div>
        
        <h4>🌲 最底级栏目列表（共 <?php echo count($leaf_categories); ?> 个）</h4>
        <?php if (empty($leaf_categories)): ?>
            <p class="layui-text-danger">没有找到最底级栏目！</p>
        <?php else: ?>
            <?php foreach ($leaf_categories as $cat): ?>
                <div class="category-item">
                    <strong>ID: <?php echo $cat['id']; ?></strong> - 
                    <?php echo htmlspecialchars($cat['name']); ?> 
                    <span style="color: #666;">(<?php echo $cat['template_type']; ?>)</span>
                    <span style="color: #999; margin-left: 10px;">现有内容: <?php echo $cat['content_count']; ?> 条</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <form method="post" style="display: inline;">
                <button type="submit" name="generate_data" class="layui-btn layui-btn-normal layui-btn-lg" 
                        onclick="return confirm('确认为 <?php echo count($leaf_categories); ?> 个栏目生成 <?php echo count($leaf_categories) * 10; ?> 条内容数据？')">
                    🚀 开始生成内容数据
                </button>
            </form>
            <a href="../content/" class="layui-btn layui-btn-primary layui-btn-lg">📋 查看内容管理</a>
        </div>
    </div>
</body>
</html>