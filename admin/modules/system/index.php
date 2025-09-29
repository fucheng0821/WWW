<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 获取系统配置统计
try {
    // 检查config表是否存在
    $table_check = $db->query("SHOW TABLES LIKE 'config'");
    $config_table_exists = $table_check->rowCount() > 0;
    
    if ($config_table_exists) {
        $config_stmt = $db->query("SELECT COUNT(*) as count FROM config");
        $config_count = $config_stmt->fetch()['count'];
    } else {
        $config_count = 0;
    }
    
    // 检查admins表统计
    $admin_stmt = $db->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM admins");
    $admin_stats = $admin_stmt->fetch();
    
} catch(PDOException $e) {
    $config_count = 0;
    $admin_stats = ['total' => 0, 'active' => 0];
}

// 处理成功和错误消息
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <?php if ($success_msg): ?>
                <div class="layui-alert layui-alert-success" style="margin: 20px;">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="layui-alert layui-alert-danger" style="margin: 20px;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <!-- 统计卡片 -->
            <div class="layui-row layui-col-space15" style="margin: 20px;">
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-blue">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-set"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $config_count; ?></div>
                            <div class="admin-card-title">配置项数</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-green">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-username"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $admin_stats['total']; ?></div>
                            <div class="admin-card-title">管理员总数</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-orange">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-ok-circle"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo $admin_stats['active']; ?></div>
                            <div class="admin-card-title">活跃管理员</div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md3">
                    <div class="admin-card admin-card-red">
                        <div class="admin-card-icon">
                            <i class="layui-icon layui-icon-date"></i>
                        </div>
                        <div class="admin-card-content">
                            <div class="admin-card-number"><?php echo date('Y-m-d'); ?></div>
                            <div class="admin-card-title">今日日期</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <h2>系统设置</h2>
                </div>
                
                <div class="layui-card-body">
                    <div class="layui-row layui-col-space20">
                        <!-- 基本设置 -->
                        <div class="layui-col-md6">
                            <div class="info-section">
                                <h3>基本设置</h3>
                                <div class="setting-item">
                                    <a href="basic.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-website"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>网站基本信息</h4>
                                            <p>网站名称、描述、关键词等基本信息设置</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="seo.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-search"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>SEO设置</h4>
                                            <p>搜索引擎优化设置，TDK配置</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="contact.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-cellphone"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>联系信息</h4>
                                            <p>公司地址、电话、邮箱等联系方式设置</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="mail.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-email"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>邮件设置</h4>
                                            <p>配置邮件服务器和发送设置</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="init_config.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-set"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>初始化配置</h4>
                                            <p>创建配置表和默认配置项</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 管理设置 -->
                        <div class="layui-col-md6">
                            <div class="info-section">
                                <h3>管理设置</h3>
                                <div class="setting-item">
                                    <a href="admin.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-username"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>管理员管理</h4>
                                            <p>管理员账号添加、编辑、权限设置</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="backup.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-download-circle"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>数据备份</h4>
                                            <p>数据库备份与恢复管理</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="full_backup.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-tabs"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>全站备份</h4>
                                            <p>网站文件和数据库完整备份</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="../banner/" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-carousel"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>Banner管理</h4>
                                            <p>网站首页轮播图管理</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                                
                                <div class="setting-item">
                                    <a href="homepage.php" class="setting-link">
                                        <div class="setting-icon">
                                            <i class="layui-icon layui-icon-home"></i>
                                        </div>
                                        <div class="setting-content">
                                            <h4>首页设置</h4>
                                            <p>首页板块内容和样式设置</p>
                                        </div>
                                        <div class="setting-arrow">
                                            <i class="layui-icon layui-icon-right"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 系统信息 -->
                    <div class="info-section" style="margin-top: 20px;">
                        <h3>系统信息</h3>
                        <table class="layui-table">
                            <tbody>
                                <tr>
                                    <td width="150"><strong>PHP版本</strong></td>
                                    <td><?php echo PHP_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL版本</strong></td>
                                    <td>
                                        <?php 
                                        try {
                                            $version_stmt = $db->query("SELECT VERSION() as version");
                                            $mysql_version = $version_stmt->fetch()['version'];
                                            echo $mysql_version;
                                        } catch(Exception $e) {
                                            echo '无法获取';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Web服务器</strong></td>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? '未知'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>系统时间</strong></td>
                                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>系统编码</strong></td>
                                    <td>UTF-8</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element'], function(){
        var element = layui.element;
        element.render();
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    </script>
    
    <style>
    .setting-item {
        margin-bottom: 15px;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .setting-item:hover {
        border-color: #ff6b35;
        box-shadow: 0 2px 8px rgba(255, 107, 53, 0.1);
    }
    
    .setting-link {
        display: flex;
        align-items: center;
        padding: 20px;
        text-decoration: none;
        color: inherit;
        transition: background-color 0.3s ease;
    }
    
    .setting-link:hover {
        background-color: #f8f9fa;
        text-decoration: none;
        color: inherit;
    }
    
    .setting-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff6b35, #ff8a65);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    
    .setting-icon i {
        font-size: 24px;
        color: white;
    }
    
    .setting-content {
        flex: 1;
    }
    
    .setting-content h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .setting-content p {
        margin: 0;
        font-size: 14px;
        color: #666;
        line-height: 1.4;
    }
    
    .setting-arrow {
        color: #ccc;
        font-size: 16px;
        margin-left: 15px;
        transition: color 0.3s ease;
    }
    
    .setting-link:hover .setting-arrow {
        color: #ff6b35;
    }
    </style>
</body>
</html>