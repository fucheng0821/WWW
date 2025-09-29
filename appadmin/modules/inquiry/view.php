<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取询价ID
$inquiry_id = intval($_GET['id'] ?? 0);

if (empty($inquiry_id)) {
    header("Location: index.php?error=" . urlencode('无效的询价ID'));
    exit();
}

// 获取当前询价信息
try {
    // 添加SQL调试信息
    error_log('查询询价ID: ' . $inquiry_id);
    
    $stmt = $db->prepare("SELECT * FROM inquiries WHERE id = ?");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch();
    
    if (!$inquiry) {
        header("Location: index.php?error=" . urlencode('询价不存在'));
        exit();
    }
    
    // 记录可用字段到错误日志
    error_log('Inquiry字段: ' . implode(', ', array_keys($inquiry)));
    
    // 检查所有字符串字段内容
    foreach ($inquiry as $key => $value) {
        if (is_string($value) && strlen(trim($value)) > 10) {
            error_log('可能的内容字段 - ' . $key . ': ' . substr(trim($value), 0, 50) . '...');
        }
    }
    
    // 尝试查询数据库结构
    try {
        $stmt_schema = $db->query("DESCRIBE inquiries");
        $schema = $stmt_schema->fetchAll(PDO::FETCH_ASSOC);
        error_log('Inquiries表结构: ' . print_r($schema, true));
    } catch(PDOException $e) {
        error_log('获取表结构失败: ' . $e->getMessage());
    }
    
} catch(PDOException $e) {
    error_log('获取询价信息失败: ' . $e->getMessage());
    header("Location: index.php?error=" . urlencode('获取询价信息失败'));
    exit();
}

// 状态映射
$status_map = [
    'pending' => '待处理',
    'processing' => '处理中',
    'completed' => '已完成'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>查看询价 - 移动管理后台</title>
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
                <h1>查看询价</h1>
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
            <div class="module-header">
                <h1>查看询价</h1>
                <p>询价详细信息</p>
            </div>
            
            <div class="data-table">
                <div class="table-container">
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <h4>询价信息</h4>
                                <p><strong>姓名:</strong> <?php echo htmlspecialchars($inquiry['name']); ?></p>
                                <p><strong>电话:</strong> <?php echo htmlspecialchars($inquiry['phone']); ?></p>
                                <p><strong>邮箱:</strong> <?php echo htmlspecialchars($inquiry['email']); ?></p>
                                <p><strong>服务类型:</strong> <?php echo htmlspecialchars($inquiry['service_type']); ?></p>
                                <p><strong>提交时间:</strong> <?php echo format_date($inquiry['created_at']); ?></p>
                                <p><strong>状态:</strong> 
                                    <span class="status-badge <?php echo $inquiry['status'] == 'pending' ? 'status-pending' : ($inquiry['status'] == 'processing' ? 'status-processing' : 'status-completed'); ?>">
                                        <?php echo $status_map[$inquiry['status']] ?? $inquiry['status']; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">服务类型</label>
                                    <div class="col-sm-9">
                                        <?php 
                                            // 添加服务类型中英文映射
                                            $service_type_map = [
                                                'graphic-design' => '平面设计',
                                                'web-design' => '网页设计',
                                                'video-production' => '视频制作',
                                                'photography' => '摄影服务',
                                                'copywriting' => '文案撰写',
                                                'marketing' => '市场营销',
                                                'branding' => '品牌设计'
                                                // 可以根据实际需要添加更多映射
                                            ];
                                            
                                            // 显示中文服务类型，如果没有映射则显示原始值
                                            $display_service_type = isset($service_type_map[$inquiry['service_type']]) ? 
                                                $service_type_map[$inquiry['service_type']] : $inquiry['service_type'];
                                            
                                            echo htmlspecialchars($display_service_type);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">询价内容</label>
                                    <div class="col-sm-9">
                                        <?php 
                                            // 直接显示project_description字段的内容
                                            if (isset($inquiry['project_description']) && !empty(trim($inquiry['project_description']))) {
                                                echo nl2br(htmlspecialchars(trim($inquiry['project_description'])));
                                            } else {
                                                echo '<div style="color: #999; text-align: center; padding: 20px;">暂无项目描述</div>';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($inquiry['admin_notes'])): ?>
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <h4>管理员备注</h4>
                                <div style="background-color: #e8f4ff; padding: 15px; border-radius: 8px; margin-top: 10px;">
                                    <?php echo nl2br(htmlspecialchars($inquiry['admin_notes'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions" style="margin-top: 20px;">
                        <a href="edit.php?id=<?php echo $inquiry['id']; ?>" class="btn-primary">
                            <i class="fas fa-edit"></i> 编辑询价
                        </a>
                        <a href="index.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> 返回列表
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/mobile-admin.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
</body>
</html>