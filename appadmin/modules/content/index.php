<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理搜索和筛选
$search_keyword = $_GET['keyword'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$is_published = $_GET['is_published'] ?? '';
$is_featured = $_GET['is_featured'] ?? '';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "(c.title LIKE ? OR c.summary LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

if (!empty($category_id)) {
    $where_conditions[] = "c.category_id = ?";
    $params[] = $category_id;
}

if ($is_published !== '') {
    $where_conditions[] = "c.is_published = ?";
    $params[] = intval($is_published);
}

if ($is_featured !== '') {
    $where_conditions[] = "c.is_featured = ?";
    $params[] = intval($is_featured);
}

$where_clause = implode(' AND ', $where_conditions);

// 获取内容列表
try {
    // 统计总数
    $count_sql = "SELECT COUNT(*) as total FROM contents c 
                  LEFT JOIN categories cat ON c.category_id = cat.id 
                  WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取内容列表
    $sql = "SELECT c.*, cat.name as category_name, cat.slug as category_slug
            FROM contents c 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            WHERE $where_clause
            ORDER BY c.sort_order DESC, c.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $contents = $stmt->fetchAll();
    
    // 计算分页信息
    $total_pages = ceil($total / $per_page);
    
} catch(PDOException $e) {
    $contents = [];
    $total = 0;
    $total_pages = 0;
}

// 获取栏目列表
try {
    $categories_stmt = $db->query("SELECT id, name FROM categories ORDER BY parent_id ASC, sort_order ASC");
    $categories = $categories_stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>内容管理 - 移动管理后台</title>
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
                <h1>内容管理</h1>
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
                    <li class="menu-item active">
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
                    <li class="menu-item">
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
            <!-- 搜索和操作栏 -->
            <div class="action-bar">
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <div class="input-group">
                            <input type="text" name="keyword" placeholder="搜索内容标题..." 
                                   value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="action-buttons">
                    <a href="add.php" class="btn-primary">
                        <i class="fas fa-plus"></i> 添加内容
                    </a>
                </div>
            </div>
            
            <!-- 筛选条件 -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <div class="filter-group">
                        <select name="category_id" onchange="this.form.submit()">
                            <option value="">全部栏目</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="is_published" onchange="this.form.submit()">
                            <option value="">全部状态</option>
                            <option value="1" <?php echo ($is_published === '1') ? 'selected' : ''; ?>>已发布</option>
                            <option value="0" <?php echo ($is_published === '0') ? 'selected' : ''; ?>>草稿</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- 内容列表 -->
            <div class="data-table">
                <?php if (empty($contents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>暂无内容数据</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <?php foreach ($contents as $content): ?>
                        <div class="table-row">
                            <div class="row-content">
                                <div class="row-main">
                                    <h4><?php echo htmlspecialchars($content['title']); ?></h4>
                                    <p><?php echo htmlspecialchars(truncate_string($content['summary'] ?? '', 100)); ?></p>
                                </div>
                                <div class="row-meta">
                                    <div class="meta-item">
                                        <span class="category"><?php echo htmlspecialchars($content['category_name'] ?? '未分类'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="status-badge <?php echo $content['is_published'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $content['is_published'] ? '已发布' : '草稿'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a href="edit.php?id=<?php echo $content['id']; ?>" class="action-btn edit">
                                    <i class="fas fa-edit"></i> 编辑
                                </a>
                                <a href="javascript:void(0);" class="action-btn delete" 
                                   onclick="deleteContent(<?php echo $content['id']; ?>)">
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
                            <a href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($search_keyword); ?>&category_id=<?php echo $category_id; ?>&is_published=<?php echo $is_published; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            <?php echo $page; ?> / <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($search_keyword); ?>&category_id=<?php echo $category_id; ?>&is_published=<?php echo $is_published; ?>" class="page-link">
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
        // 删除内容确认
        function deleteContent(id) {
            if (confirm('确定要删除这个内容吗？此操作不可恢复！')) {
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