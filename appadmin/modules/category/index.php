<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
check_admin_auth();

// 设置每页显示数量
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// 搜索条件
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

try {
    // 构建查询条件
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $where_clause .= " AND (name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category_id > 0) {
        $where_clause .= " AND id = ?";
        $params[] = $category_id;
    }
    
    // 获取栏目总数
    $count_sql = "SELECT COUNT(*) as total FROM categories $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取栏目列表
    $sql = "SELECT * FROM categories $where_clause ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // 获取所有顶级栏目用于筛选
    $top_categories_stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC");
    $top_categories_stmt->execute();
    $top_categories = $top_categories_stmt->fetchAll();
    
    // 计算总页数
    $total_pages = ceil($total / $per_page);
    
} catch(PDOException $e) {
    error_log("获取栏目列表失败: " . $e->getMessage());
    $categories = [];
    $total = 0;
    $total_pages = 1;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>栏目管理 - 移动管理后台</title>
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
                <h1>栏目管理</h1>
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
                    <li class="menu-item active">
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
                            <input type="text" name="search" placeholder="搜索栏目名称..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="action-buttons">
                    <a href="add.php" class="btn-primary">
                        <i class="fas fa-plus"></i> 添加栏目
                    </a>
                </div>
            </div>
            
            <!-- 栏目列表 -->
            <div class="data-table">
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>暂无栏目数据</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <?php foreach ($categories as $category): ?>
                        <div class="table-row">
                            <div class="row-content">
                                <div class="row-main">
                                    <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                                </div>
                                <div class="row-meta">
                                    <span class="status-badge <?php echo $category['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $category['is_active'] ? '启用' : '禁用'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a href="edit.php?id=<?php echo $category['id']; ?>" class="action-btn edit">
                                    <i class="fas fa-edit"></i> 编辑
                                </a>
                                <a href="javascript:void(0);" class="action-btn delete" 
                                   onclick="deleteCategory(<?php echo $category['id']; ?>)">
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            <?php echo $page; ?> / <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
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
        // 删除栏目确认
        function deleteCategory(id) {
            if (confirm('确定要删除这个栏目吗？此操作不可恢复！')) {
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