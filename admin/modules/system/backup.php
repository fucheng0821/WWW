<?php
// 数据库备份页面 - 仅备份数据库内容
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';

// 处理数据库备份请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    try {
        $backup_dir = '../../../backup/';
        
        // 创建备份目录
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // 生成备份文件名
        $timestamp = date('Y-m-d_H-i-s');
        $filename = 'backup_' . $timestamp . '.sql';
        $filepath = $backup_dir . $filename;
        
        // 执行数据库备份
        if (backup_database($filepath)) {
            $success = "数据库备份成功！文件保存为: $filename";
            
            // 记录备份时间到localStorage（通过JavaScript）
            echo '<script>localStorage.setItem("lastDatabaseBackupDate", "' . date('Y-m-d') . '");</script>';
        } else {
            $errors[] = '数据库备份失败';
        }
        
    } catch (Exception $e) {
        $errors[] = '备份失败：' . $e->getMessage();
    }
}

// 数据库备份函数
function backup_database($filepath) {
    global $db;
    
    try {
        // 获取所有表
        $tables_stmt = $db->query("SHOW TABLES");
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $backup_content = "-- 数据库备份文件\n";
        $backup_content .= "-- 备份时间: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- 数据库: " . DB_NAME . "\n\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // 获取创建表的SQL
            $create_stmt = $db->query("SHOW CREATE TABLE `$table`");
            $create_sql = $create_stmt->fetch()['Create Table'];
            
            $backup_content .= "-- 表结构: $table\n";
            $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup_content .= $create_sql . ";\n\n";
            
            // 获取表数据
            $data_stmt = $db->query("SELECT * FROM `$table`");
            $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $backup_content .= "-- 表数据: $table\n";
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // 将备份内容写入文件
        if (file_put_contents($filepath, $backup_content) === false) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// 获取现有数据库备份文件列表
$backup_dir = '../../../backup/';
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . 'backup_*.sql');
    foreach ($files as $file) {
        $backup_files[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'time' => filemtime($file)
        ];
    }
    // 按时间倒序排列
    usort($backup_files, function($a, $b) {
        return $b['time'] - $a['time'];
    });
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库备份 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>数据库备份</h2>
                        <a href="index.php" class="layui-btn layui-btn-primary">
                            <i class="layui-icon layui-icon-return"></i> 返回系统设置
                        </a>
                    </div>
                </div>
                
                <div class="layui-card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="layui-alert layui-alert-danger">
                            <ul style="margin: 0; padding-left: 20px;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="layui-row layui-col-space20">
                        <!-- 左侧备份操作 -->
                        <div class="layui-col-md6">
                            <div class="info-section">
                                <h3>创建数据库备份</h3>
                                <p>点击下面的按钮可以创建数据库完整备份，仅包含数据库的表结构和数据。</p>
                                
                                <form method="POST" class="layui-form">
                                    <input type="hidden" name="action" value="backup">
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button type="submit" class="layui-btn layui-btn-normal layui-btn-lg">
                                                <i class="layui-icon layui-icon-database"></i> 立即备份数据库
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="layui-alert layui-alert-normal">
                                    <h4>备份说明</h4>
                                    <ul style="margin: 10px 0; padding-left: 20px;">
                                        <li>备份将包含所有数据库的表结构和数据</li>
                                        <li>备份文件为SQL格式</li>
                                        <li>备份文件保存在 backup/ 目录下</li>
                                        <li>建议定期进行数据库备份</li>
                                        <li>备份文件可用于数据库恢复</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 右侧数据库信息 -->
                        <div class="layui-col-md6">
                            <div class="info-section">
                                <h3>数据库信息</h3>
                                <table class="layui-table">
                                    <tbody>
                                        <tr>
                                            <td width="120"><strong>数据库名称</strong></td>
                                            <td><?php echo DB_NAME; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>数据库主机</strong></td>
                                            <td><?php echo DB_HOST; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>备份目录</strong></td>
                                            <td>/backup/</td>
                                        </tr>
                                        <tr>
                                            <td><strong>PHP版本</strong></td>
                                            <td><?php echo PHP_VERSION; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 备份文件列表 -->
                    <div class="info-section" style="margin-top: 30px;">
                        <h3>数据库备份文件列表</h3>
                        
                        <?php if (empty($backup_files)): ?>
                            <div class="empty-state">
                                <i class="layui-icon layui-icon-file-text"></i>
                                <h3>暂无数据库备份文件</h3>
                                <p>点击上方"立即备份数据库"按钮创建第一个数据库备份文件</p>
                            </div>
                        <?php else: ?>
                            <table class="layui-table">
                                <thead>
                                    <tr>
                                        <th>文件名</th>
                                        <th>文件大小</th>
                                        <th>备份时间</th>
                                        <th width="150">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backup_files as $file): ?>
                                    <tr>
                                        <td>
                                            <i class="layui-icon layui-icon-file-text"></i>
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </td>
                                        <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                                        <td><?php echo date('Y-m-d H:i:s', $file['time']); ?></td>
                                        <td>
                                            <a href="download_backup.php?file=<?php echo urlencode($file['name']); ?>" 
                                               class="layui-btn layui-btn-xs">下载</a>
                                            <a href="javascript:;" 
                                               onclick="deleteBackup('<?php echo htmlspecialchars($file['name']); ?>')"
                                               class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'element', 'layer'], function(){
        var form = layui.form;
        var element = layui.element;
        var layer = layui.layer;
        
        form.render();
        element.render();
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert-success, .layui-alert-danger');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    
    function deleteBackup(filename) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除数据库备份文件 "' + filename + '" 吗？', {
                icon: 3,
                title: '删除确认'
            }, function(index){
                window.location.href = 'delete_backup.php?file=' + encodeURIComponent(filename);
                layer.close(index);
            });
        });
    }
    </script>
</body>
</html>