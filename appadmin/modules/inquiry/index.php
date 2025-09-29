<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理搜索和筛选
$search_keyword = $_GET['keyword'] ?? '';
$status = $_GET['status'] ?? '';
$service_type = $_GET['service_type'] ?? '';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = ['1=1'];
$params = [];

if (!empty($search_keyword)) {
    $where_conditions[] = "(name LIKE ? OR company LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
}

if (!empty($status)) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($service_type)) {
    $where_conditions[] = "service_type LIKE ?";
    $params[] = "%$service_type%";
}

$where_clause = implode(' AND ', $where_conditions);

// 获取询价列表
try {
    // 统计总数
    $count_sql = "SELECT COUNT(*) as total FROM inquiries WHERE $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    
    // 获取询价列表
    $sql = "SELECT * FROM inquiries WHERE $where_clause
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    // 为列表查询创建新的参数数组，避免影响统计查询
    $list_params = $params;
    $list_params[] = $per_page;
    $list_params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($list_params);
    $inquiries = $stmt->fetchAll();
    
    // 计算分页信息
    $total_pages = ceil($total / $per_page);
    
    // 获取统计数据
    $stats_sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
                  FROM inquiries";
    $stats_stmt = $db->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
} catch(PDOException $e) {
    $inquiries = [];
    $total = 0;
    $total_pages = 0;
    $stats = ['total_count' => 0, 'pending_count' => 0, 'processing_count' => 0, 'completed_count' => 0, 'today_count' => 0];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>询价管理 - 移动管理后台</title>
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
                <h1>询价管理</h1>
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
                    <li class="menu-item active">
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
            <!-- 统计卡片 -->
            <div class="dashboard-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_count']; ?></h3>
                            <p>总询价数</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pending_count']; ?></h3>
                            <p>待处理</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['completed_count']; ?></h3>
                            <p>已完成</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-red">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['today_count']; ?></h3>
                            <p>今日新增</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 搜索和操作栏 -->
            <div class="action-bar">
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <div class="input-group">
                            <input type="text" name="keyword" placeholder="搜索询价信息..." 
                                   value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="action-buttons">
                    <a href="export.php" class="btn-secondary">
                        <i class="fas fa-download"></i> 导出数据
                    </a>
                </div>
            </div>
            
            <!-- 筛选条件 -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <div class="filter-group">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">全部状态</option>
                            <option value="pending" <?php echo ($status === 'pending') ? 'selected' : ''; ?>>待处理</option>
                            <option value="processing" <?php echo ($status === 'processing') ? 'selected' : ''; ?>>处理中</option>
                            <option value="completed" <?php echo ($status === 'completed') ? 'selected' : ''; ?>>已完成</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- 询价列表 -->
            <div class="data-table">
                <?php if (empty($inquiries)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comment"></i>
                        <p>暂无询价数据</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <?php foreach ($inquiries as $inquiry): ?>
                        <div class="table-row">
                            <div class="row-content">
                                <div class="row-main">
                                    <h4><?php echo htmlspecialchars($inquiry['name']); ?></h4>
                                    <p><?php echo htmlspecialchars(truncate_string($inquiry['message'] ?? '', 100)); ?></p>
                                </div>
                                <div class="row-meta">
                                    <div class="meta-item">
                                        <span class="service"><?php echo htmlspecialchars($inquiry['service_type'] ?? ''); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="status-badge <?php echo 'status-' . $inquiry['status']; ?>">
                                            <?php 
                                            $status_map = [
                                                'pending' => '待处理',
                                                'processing' => '处理中',
                                                'completed' => '已完成'
                                            ];
                                            echo $status_map[$inquiry['status']] ?? $inquiry['status'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row-actions">
                                <a href="view.php?id=<?php echo $inquiry['id']; ?>" class="action-btn view">
                                    <i class="fas fa-eye"></i> 查看
                                </a>
                                <a href="edit.php?id=<?php echo $inquiry['id']; ?>" class="action-btn edit">
                                    <i class="fas fa-edit"></i> 编辑
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($search_keyword); ?>&status=<?php echo $status; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            <?php echo $page; ?> / <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($search_keyword); ?>&status=<?php echo $status; ?>" class="page-link">
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