<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理搜索和筛选
$search_keyword = $_GET['keyword'] ?? '';
$template_type = $_GET['template_type'] ?? '';
$is_active = $_GET['is_active'] ?? '';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

if (!empty($template_type)) {
    $where_conditions[] = "template_type = ?";
    $params[] = $template_type;
}

if ($is_active !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = intval($is_active);
}

$where_clause = implode(' AND ', $where_conditions);

// 获取模板列表
try {
    // 统计总数
    $count_sql = "SELECT COUNT(*) as total FROM templates WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取模板列表
    $sql = "SELECT * FROM templates WHERE $where_clause
            ORDER BY template_type ASC, sort_order ASC, created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll();
    
    // 计算分页信息
    $total_pages = ceil($total / $per_page);
    
    // 获取统计数据
    $stats_sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN template_type = 'index' THEN 1 ELSE 0 END) as index_count,
                    SUM(CASE WHEN template_type = 'channel' THEN 1 ELSE 0 END) as channel_count,
                    SUM(CASE WHEN template_type = 'list' THEN 1 ELSE 0 END) as list_count,
                    SUM(CASE WHEN template_type = 'content' THEN 1 ELSE 0 END) as content_count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                  FROM templates";
    $stats_stmt = $db->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
} catch(PDOException $e) {
    $templates = [];
    $total = 0;
    $total_pages = 0;
    $stats = ['total_count' => 0, 'index_count' => 0, 'channel_count' => 0, 'list_count' => 0, 'content_count' => 0, 'active_count' => 0];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>模板管理 - 移动管理后台</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/mobile-admin.css">
    <link rel="stylesheet" href="../../assets/css/mobile-modules.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="mobile-layout">
        <!-- 顶部导航栏 -->
        <div class="mobile-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="header-title">
                <h1>模板管理</h1>
            </div>
            <div class="header-right">
                <button class="notification-btn" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationBadge" style="display: none;">0</span>
                </button>
            </div>
        </div>
        
        <!-- 侧边栏菜单 -->
        <div class="mobile-sidebar" id="mobileSidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '管理员'); ?></h3>
                        <p>在线</p>
                    </div>
                </div>
                <button class="close-sidebar" id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li class="menu-item">
                        <a href="../../index.php">
                            <i class="fas fa-home"></i>
                            <span>控制台</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../category/">
                            <i class="fas fa-folder"></i>
                            <span>栏目管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../content/">
                            <i class="fas fa-file-alt"></i>
                            <span>内容管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../inquiry/">
                            <i class="fas fa-comment"></i>
                            <span>询价管理</span>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="../template/">
                            <i class="fas fa-paint-brush"></i>
                            <span>模板管理</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../system/">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="../../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>安全退出</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- 遮罩层 -->
        <div class="overlay" id="overlay"></div>
        
        <!-- 主要内容区域 -->
        <div class="mobile-main">
            <!-- 统计卡片 -->
            <div class="dashboard-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_count']; ?></h3>
                            <p>总模板数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['index_count']; ?></h3>
                            <p>首页模板</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['channel_count']; ?></h3>
                            <p>频道模板</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-red">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['list_count']; ?></h3>
                            <p>列表模板</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 搜索和操作栏 -->
            <div class="action-bar">
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <div class="input-group">
                            <input type="text" name="keyword" placeholder="搜索模板名称..." 
                                   value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="action-buttons">
                    <a href="add.php" class="btn-primary">
                        <i class="fas fa-plus"></i> 添加模板
                    </a>
                </div>
            </div>
            
            <!-- 筛选条件 -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <div class="filter-group">
                        <select name="template_type" onchange="this.form.submit()">
                            <option value="">全部类型</option>
                            <option value="index" <?php echo ($template_type === 'index') ? 'selected' : ''; ?>>首页模板</option>
                            <option value="channel" <?php echo ($template_type === 'channel') ? 'selected' : ''; ?>>频道模板</option>
                            <option value="list" <?php echo ($template_type === 'list') ? 'selected' : ''; ?>>列表模板</option>
                            <option value="content" <?php echo ($template_type === 'content') ? 'selected' : ''; ?>>内容模板</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="is_active" onchange="this.form.submit()">
                            <option value="">全部状态</option>
                            <option value="1" <?php echo ($is_active === '1') ? 'selected' : ''; ?>>启用</option>
                            <option value="0" <?php echo ($is_active === '0') ? 'selected' : ''; ?>>禁用</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- 模板列表 -->
            <div class="data-table">
                <?php if (empty($templates)): ?>
                    <div class="empty-state">
                        <i class="fas fa-paint-brush"></i>
                        <p>暂无模板数据</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <?php foreach ($templates as $template): ?>
                        <div class="table-row">
                            <div class="row-content">
                                <div class="row-main">
                                    <h4><?php echo htmlspecialchars($template['name']); ?></h4>
                                    <p><?php echo htmlspecialchars(truncate_string($template['description'] ?? '', 100)); ?></p>
                                </div>
                                <div class="row-meta">
                                    <div class="meta-item">
                                        <span class="category">
                                            <?php 
                                            $type_map = [
                                                'index' => '首页',
                                                'channel' => '频道',
                                                'list' => '列表',
                                                'content' => '内容'
                                            ];
                                            echo $type_map[$template['template_type']] ?? $template['template_type'];
                                            ?>
                                        </span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="status-badge <?php echo $template['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $template['is_active'] ? '启用' : '禁用'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a href="edit.php?id=<?php echo $template['id']; ?>" class="action-btn edit">
                                    <i class="fas fa-edit"></i> 编辑
                                </a>
                                <a href="javascript:void(0);" class="action-btn delete" 
                                   onclick="deleteTemplate(<?php echo $template['id']; ?>)">
                                    <i class="fas fa-trash"></i> 删除
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($search_keyword); ?>&template_type=<?php echo $template_type; ?>&is_active=<?php echo $is_active; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            <?php echo $page; ?> / <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($search_keyword); ?>&template_type=<?php echo $template_type; ?>&is_active=<?php echo $is_active; ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script src="../../assets/js/mobile-admin.js"></script>
    <script>
        // 删除模板确认
        function deleteTemplate(id) {
            if (confirm('确定要删除这个模板吗？此操作不可恢复！')) {
                // 发送删除请求
                fetch('delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('删除成功');
                        location.reload();
                    } else {
                        alert('删除失败：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('删除请求失败');
                });
            }
        }
        
        // 侧边栏功能
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.add('open');
            document.getElementById('overlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            document.body.style.overflow = '';
        });
        
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('mobileSidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
            document.body.style.overflow = '';
        });
    </script>
</body>
</html>