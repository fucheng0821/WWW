<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$success_messages = [];
$error_messages = [];

// 定义需要创建的子栏目结构
$subcategory_structure = [
    '视频制作' => [
        '服务项目',
        '成功案例', 
        '制作流程',
        '设备与技术'
    ],
    '平面设计' => [
        '品牌形象设计',
        '营销物料设计',
        '包装设计'
    ],
    '网站建设' => [
        '网站建设',
        '程序开发',
        'H5互动',
        '小程序开发'
    ],
    '商业摄影' => [
        '摄影服务',
        '摄影棚实景'
    ],
    '活动策划' => [
        '企业年会',
        '发布会庆典',
        '会议论坛',
        '促销路演'
    ]
];

// 处理批量创建
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_subcategories'])) {
    try {
        // 获取所有父栏目
        $stmt = $db->query("SELECT id, name FROM categories WHERE parent_id = 0");
        $parent_categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $db->beginTransaction();
        
        foreach ($subcategory_structure as $parent_name => $subcategories) {
            // 查找父栏目ID
            $parent_id = null;
            foreach ($parent_categories as $id => $name) {
                if (stripos($name, $parent_name) !== false || stripos($parent_name, $name) !== false) {
                    $parent_id = $id;
                    break;
                }
            }
            
            if (!$parent_id) {
                $error_messages[] = "未找到父栏目：{$parent_name}";
                continue;
            }
            
            // 创建子栏目
            $sort_order = 1;
            foreach ($subcategories as $sub_name) {
                $slug = generate_slug($sub_name);
                
                // 检查是否已存在
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
                $stmt->execute([$sub_name, $parent_id]);
                if ($stmt->fetch()) {
                    $error_messages[] = "子栏目已存在：{$parent_name} -> {$sub_name}";
                    continue;
                }
                
                // 插入子栏目
                $stmt = $db->prepare("
                    INSERT INTO categories (name, slug, description, template_type, sort_order, is_active, parent_id, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $description = "专业的{$sub_name}服务，为您提供高质量的解决方案。";
                $template_type = 'list';
                
                $stmt->execute([
                    $sub_name,
                    $slug,
                    $description,
                    $template_type,
                    $sort_order,
                    1, // is_active
                    $parent_id
                ]);
                
                $success_messages[] = "成功创建：{$parent_name} -> {$sub_name}";
                $sort_order++;
            }
        }
        
        $db->commit();
        
    } catch(PDOException $e) {
        $db->rollBack();
        $error_messages[] = '数据库错误：' . $e->getMessage();
    }
}

// 获取当前栏目结构用于显示
try {
    $stmt = $db->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id) as sub_count
        FROM categories c 
        ORDER BY c.parent_id ASC, c.sort_order ASC, c.id ASC
    ");
    $all_categories = $stmt->fetchAll();
    
    // 构建层级化的栏目列表
    function buildCategoryTree($categories, $parent_id = 0, $level = 0) {
        $result = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parent_id) {
                $category['level'] = $level;
                $result[] = $category;
                $children = buildCategoryTree($categories, $category['id'], $level + 1);
                $result = array_merge($result, $children);
            }
        }
        return $result;
    }
    
    $categories = buildCategoryTree($all_categories);
} catch(PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量添加子栏目 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .subcategory-plan {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .parent-name {
            color: #1890ff;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .sub-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sub-list li {
            padding: 4px 0;
            color: #666;
        }
        .sub-list li:before {
            content: "├─ ";
            color: #999;
            margin-right: 5px;
        }
        .category-tree {
            font-family: 'Microsoft YaHei', sans-serif;
        }
        .category-tree tr[data-level="1"] {
            background: #fafbfc !important;
        }
        .tree-indent {
            color: #999;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php 
        $header_path = '../../includes/header.php';
        $sidebar_path = '../../includes/sidebar.php';
        if (file_exists($header_path)) {
            include $header_path;
        }
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        }
        ?>
        
        <div class="layui-body">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>🚀 批量添加子栏目</h2>
                        <div>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回栏目管理
                            </a>
                            <a href="add.php" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-add-1"></i> 手动添加栏目
                            </a>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body">
                    <?php if (!empty($success_messages)): ?>
                        <div class="layui-alert layui-alert-success">
                            <h4>✅ 创建成功</h4>
                            <ul style="margin: 5px 0; padding-left: 20px;">
                                <?php foreach ($success_messages as $msg): ?>
                                    <li><?php echo htmlspecialchars($msg); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_messages)): ?>
                        <div class="layui-alert layui-alert-danger">
                            <h4>❌ 创建失败</h4>
                            <ul style="margin: 5px 0; padding-left: 20px;">
                                <?php foreach ($error_messages as $msg): ?>
                                    <li><?php echo htmlspecialchars($msg); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="layui-tab layui-tab-brief">
                        <ul class="layui-tab-title">
                            <li class="layui-this">批量创建计划</li>
                            <li>当前栏目结构</li>
                        </ul>
                        <div class="layui-tab-content">
                            <!-- 批量创建计划 -->
                            <div class="layui-tab-item layui-show">
                                <div class="layui-alert layui-alert-normal">
                                    <h4>📋 创建计划说明</h4>
                                    <p>将根据以下规划为对应的父栏目自动创建子栏目：</p>
                                </div>
                                
                                <div class="layui-row layui-col-space15">
                                    <?php foreach ($subcategory_structure as $parent_name => $subcategories): ?>
                                        <div class="layui-col-md6">
                                            <div class="subcategory-plan">
                                                <div class="parent-name">📁 <?php echo $parent_name; ?></div>
                                                <ul class="sub-list">
                                                    <?php foreach ($subcategories as $sub_name): ?>
                                                        <li><?php echo htmlspecialchars($sub_name); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div style="text-align: center; margin-top: 30px;">
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="create_subcategories" value="1" 
                                                class="layui-btn layui-btn-lg layui-btn-normal">
                                            <i class="layui-icon layui-icon-add-1"></i> 开始批量创建子栏目
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="layui-alert layui-alert-warning" style="margin-top: 20px;">
                                    <h4>⚠️ 注意事项</h4>
                                    <ul>
                                        <li>系统会自动匹配父栏目名称（支持模糊匹配）</li>
                                        <li>如果子栏目已存在，将跳过创建</li>
                                        <li>所有子栏目默认启用，模板类型为列表页</li>
                                        <li>子栏目别名将自动生成</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- 当前栏目结构 -->
                            <div class="layui-tab-item">
                                <?php if (!empty($categories)): ?>
                                    <table class="layui-table category-tree">
                                        <thead>
                                            <tr>
                                                <th width="60">ID</th>
                                                <th>栏目名称</th>
                                                <th width="80">类型</th>
                                                <th width="60">子栏目</th>
                                                <th width="60">状态</th>
                                                <th width="120">创建时间</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                            <tr data-level="<?php echo $category['level']; ?>">
                                                <td><?php echo $category['id']; ?></td>
                                                <td>
                                                    <?php 
                                                    if ($category['level'] > 0) {
                                                        echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $category['level']);
                                                        echo '<span class="tree-indent">├─ </span>';
                                                        echo '<span style="color: #666;">' . htmlspecialchars($category['name']) . '</span>';
                                                    } else {
                                                        echo '<span style="color: #1890ff; font-weight: 600;">' . htmlspecialchars($category['name']) . '</span>';
                                                        if ($category['sub_count'] > 0) {
                                                            echo ' <span class="layui-badge layui-badge-rim" style="margin-left: 5px;">' . $category['sub_count'] . '个子栏目</span>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="layui-badge layui-bg-blue">
                                                        <?php 
                                                        switch($category['template_type']) {
                                                            case 'channel': echo '频道页'; break;
                                                            case 'list': echo '列表页'; break;
                                                            case 'content': echo '内容页'; break;
                                                            default: echo '未设置';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $category['sub_count']; ?></td>
                                                <td>
                                                    <?php if ($category['is_active']): ?>
                                                        <span class="layui-badge layui-bg-green">启用</span>
                                                    <?php else: ?>
                                                        <span class="layui-badge">禁用</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('m-d H:i', strtotime($category['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="layui-alert layui-alert-warning">
                                        <p>暂无栏目数据</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
        
        // 监听表单提交
        document.querySelector('form').addEventListener('submit', function(e) {
            var btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i> 正在创建...';
            btn.disabled = true;
        });
    });
    </script>
</body>
</html>