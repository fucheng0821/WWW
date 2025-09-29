<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 格式化文件大小
function format_bytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

// 处理备份操作
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    try {
        // 获取数据库名称
        $dbname = $db->query("SELECT DATABASE()")->fetchColumn();
        
        // 获取所有表名
        $tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // 生成备份内容
        $backup_content = "-- 数据库备份\n";
        $backup_content .= "-- 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- 数据库: " . $dbname . "\n\n";
        
        foreach ($tables as $table) {
            // 获取表结构
            $stmt = $db->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup_content .= $row[1] . ";\n\n";
            
            // 获取表数据
            $stmt = $db->query("SELECT * FROM `$table`");
            $columns = $stmt->columnCount();
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $backup_content .= "INSERT INTO `$table` VALUES(";
                for ($i = 0; $i < $columns; $i++) {
                    $backup_content .= ($i > 0 ? ',' : '') . ($row[$i] === null ? 'NULL' : "'" . addslashes($row[$i]) . "'");
                }
                $backup_content .= ");\n";
            }
            $backup_content .= "\n";
        }
        
        // 保存备份文件
        $backup_filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_filepath = '../../../backups/' . $backup_filename;
        
        // 创建备份目录（如果不存在）
        if (!is_dir('../../../backups')) {
            mkdir('../../../backups', 0755, true);
        }
        
        file_put_contents($backup_filepath, $backup_content);
        
        $message = '数据库备份成功: ' . $backup_filename;
        $message_type = 'success';
    } catch(PDOException $e) {
        $message = '备份失败: ' . $e->getMessage();
        $message_type = 'error';
        error_log($message);
    }
}

// 获取备份文件列表
$backup_files = [];
if (is_dir('../../../backups')) {
    $files = scandir('../../../backups');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = [
                'name' => $file,
                'size' => filesize('../../../backups/' . $file),
                'date' => date('Y-m-d H:i:s', filemtime('../../../backups/' . $file))
            ];
        }
    }
    // 按时间倒序排列
    usort($backup_files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>数据备份 - 移动管理后台</title>
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
                <h1>数据备份</h1>
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
                    <li class="menu-item">
                        <a href="../template/">
                            <i class="fas fa-paint-brush"></i>
                            <span>模板管理</span>
                        </a>
                    </li>
                    <li class="menu-item active">
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
                <h1>数据备份</h1>
                <p>数据库备份与恢复</p>
            </div>
            
            <?php if (isset($message)): ?>
            <div class="message-toast <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-group">
                    <label>备份操作</label>
                    <p>点击下方按钮创建数据库备份</p>
                    <a href="?action=backup" class="btn-primary" style="display: inline-block; margin-top: 10px;">
                        <i class="fas fa-database"></i> 创建备份
                    </a>
                </div>
            </div>
            
            <div class="data-table" style="margin-top: 20px;">
                <div class="table-container">
                    <h3>备份文件列表</h3>
                    <?php if (empty($backup_files)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-archive"></i>
                        <p>暂无备份文件</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($backup_files as $file): ?>
                    <div class="table-row">
                        <div class="row-content">
                            <div class="row-main">
                                <h4><?php echo htmlspecialchars($file['name']); ?></h4>
                                <p>大小: <?php echo format_bytes($file['size']); ?> | 时间: <?php echo $file['date']; ?></p>
                            </div>
                        </div>
                        <div class="row-actions">
                            <a href="../../../backups/<?php echo urlencode($file['name']); ?>" class="action-btn view" download>
                                <i class="fas fa-download"></i> 下载
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="data-table" style="margin-top: 20px;">
                <div class="table-container">
                    <h3>备份说明</h3>
                    <div class="setting-item">
                        <div class="setting-link">
                            <div class="setting-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="setting-content">
                                <h4>备份注意事项</h4>
                                <p>1. 建议定期备份数据库以防止数据丢失</p>
                                <p>2. 备份文件保存在 /backups/ 目录中</p>
                                <p>3. 备份文件包含完整的数据库结构和数据</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 20px;">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> 返回系统设置
                </a>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/mobile-admin.js"></script>
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    // 消息提示自动隐藏
    document.addEventListener('DOMContentLoaded', function() {
        const messageToast = document.querySelector('.message-toast');
        if (messageToast) {
            setTimeout(() => {
                messageToast.style.opacity = '0';
                setTimeout(() => {
                    messageToast.remove();
                }, 300);
            }, 3000);
        }
    });
    </script>
</body>
</html>