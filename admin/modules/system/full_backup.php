<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';
$zip_available = extension_loaded('zip');

// 处理全站备份请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'full_backup') {
    if (!$zip_available) {
        $errors[] = 'ZIP扩展未启用，无法创建全站备份。请在php.ini中启用zip扩展。';
    } else {
        try {
            $backup_dir = '../../../backup/';
            
            // 创建备份目录
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            // 生成备份文件名
            $timestamp = date('Y-m-d_H-i-s');
            $filename = 'full_backup_' . $timestamp . '.zip';
            $filepath = $backup_dir . $filename;
            
            // 网站根目录
            $website_root = realpath('../../../');
            
            // 创建ZIP压缩文件，直接从原始位置创建，保持原名和原路径
            if (create_zip_from_root($website_root, $filepath)) {
                $success = "全站备份成功！文件保存为: $filename";
                
                // 记录备份时间到localStorage（通过JavaScript）
                echo '<script>localStorage.setItem("lastBackupDate", "' . date('Y-m-d') . '");</script>';
            } else {
                $errors[] = '创建ZIP文件失败';
            }
            
        } catch (Exception $e) {
            $errors[] = '备份失败：' . $e->getMessage();
        }
    }
}

// 获取现有全站备份文件列表
$backup_dir = '../../../backup/';
$full_backup_files = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . 'full_backup_*.zip');
    foreach ($files as $file) {
        $full_backup_files[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'time' => filemtime($file)
        ];
    }
    // 按时间倒序排列
    usort($full_backup_files, function($a, $b) {
        return $b['time'] - $a['time'];
    });
}

// 从网站根目录创建ZIP文件，保持原名和原路径
function create_zip_from_root($root_path, $destination) {
    global $db;
    
    if (!extension_loaded('zip')) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE)) {
        return false;
    }

    $root_path = str_replace('\\', '/', realpath($root_path));
    $root_path_length = strlen($root_path);
    
    // 要排除的目录和文件
    $exclude_dirs = ['backup', '.git', '.qoder'];
    $exclude_patterns = ['/temp_backup_/'];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $file_path = str_replace('\\', '/', $file->getRealPath());
        
        // 检查是否需要排除
        $relative_path = substr($file_path, $root_path_length);
        $should_exclude = false;
        
        // 检查目录排除
        foreach ($exclude_dirs as $exclude_dir) {
            if (strpos($relative_path, '/' . $exclude_dir . '/') === 0 || 
                strpos($relative_path, '/' . $exclude_dir) === 0) {
                $should_exclude = true;
                break;
            }
        }
        
        // 检查模式排除
        if (!$should_exclude) {
            foreach ($exclude_patterns as $pattern) {
                if (preg_match($pattern, $relative_path)) {
                    $should_exclude = true;
                    break;
                }
            }
        }
        
        if ($should_exclude) {
            continue;
        }
        
        // 添加到ZIP文件
        if ($file->isDir()) {
            // 添加目录
            $zip_path = substr($file_path, $root_path_length + 1);
            if (!empty($zip_path)) {
                $zip->addEmptyDir($zip_path);
            }
        } else {
            // 添加文件
            $zip_path = substr($file_path, $root_path_length + 1);
            $zip->addFile($file_path, $zip_path);
        }
    }
    
    // 创建数据库备份并添加到ZIP文件
    try {
        // 获取所有表
        $tables_stmt = $db->query("SHOW TABLES");
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $backup_content = "-- 全站数据库备份文件\n";
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
        
        // 添加数据库备份文件到ZIP
        $zip->addFromString('database_backup.sql', $backup_content);
    } catch (Exception $e) {
        // 如果数据库备份失败，关闭ZIP文件并返回false
        $zip->close();
        return false;
    }

    return $zip->close();
}

// 删除目录的函数（保留用于清理可能的临时文件）
function remove_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!remove_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全站备份 - 高光视刻</title>
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
                        <h2>全站备份</h2>
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
                                <h3>创建全站备份</h3>
                                <?php if (!$zip_available): ?>
                                    <div class="layui-alert layui-alert-danger">
                                        <p>警告：ZIP扩展未启用，无法创建全站备份。</p>
                                        <p>请在php.ini中启用zip扩展，然后重启Web服务器。</p>
                                    </div>
                                <?php else: ?>
                                    <p>点击下面的按钮可以创建全站完整备份，包括所有网站文件和数据库。</p>
                                    
                                    <div class="layui-form">
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button type="button" id="startBackupBtn" class="layui-btn layui-btn-normal layui-btn-lg">
                                                    <i class="layui-icon layui-icon-download-circle"></i> 立即备份全站
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="layui-alert layui-alert-normal">
                                    <h4>备份说明</h4>
                                    <ul style="margin: 10px 0; padding-left: 20px;">
                                        <li>备份将包含所有网站文件和数据库</li>
                                        <li>备份文件为ZIP压缩格式</li>
                                        <li>备份文件保存在 backup/ 目录下</li>
                                        <li>建议定期进行全站备份</li>
                                        <li>备份文件可用于完整站点恢复</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 右侧系统信息 -->
                        <div class="layui-col-md6">
                            <div class="info-section">
                                <h3>系统信息</h3>
                                <table class="layui-table">
                                    <tbody>
                                        <tr>
                                            <td width="120"><strong>网站根目录</strong></td>
                                            <td><?php echo realpath('../../../'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>备份目录</strong></td>
                                            <td>/backup/</td>
                                        </tr>
                                        <tr>
                                            <td><strong>PHP版本</strong></td>
                                            <td><?php echo PHP_VERSION; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Zip扩展</strong></td>
                                            <td><?php echo $zip_available ? '已安装' : '未安装'; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 备份文件列表 -->
                    <div class="info-section" style="margin-top: 30px;">
                        <h3>全站备份文件列表</h3>
                        
                        <?php if (empty($full_backup_files)): ?>
                            <div class="empty-state">
                                <i class="layui-icon layui-icon-file"></i>
                                <h3>暂无全站备份文件</h3>
                                <p><?php echo $zip_available ? '点击上方"立即备份全站"按钮创建第一个全站备份文件' : 'ZIP扩展未启用，无法创建备份文件'; ?></p>
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
                                    <?php foreach ($full_backup_files as $file): ?>
                                    <tr>
                                        <td>
                                            <i class="layui-icon layui-icon-file"></i>
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </td>
                                        <td><?php echo number_format($file['size'] / 1024 / 1024, 2); ?> MB</td>
                                        <td><?php echo date('Y-m-d H:i:s', $file['time']); ?></td>
                                        <td>
                                            <a href="download_full_backup.php?file=<?php echo urlencode($file['name']); ?>" 
                                               class="layui-btn layui-btn-xs">下载</a>
                                            <a href="javascript:;" 
                                               onclick="deleteFullBackup('<?php echo htmlspecialchars($file['name']); ?>')"
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
        
        // 开始备份按钮点击事件
        document.getElementById('startBackupBtn').addEventListener('click', function() {
            startBackup();
        });
    });
    
    function deleteFullBackup(filename) {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除全站备份文件 "' + filename + '" 吗？', {
                icon: 3,
                title: '删除确认'
            }, function(index){
                window.location.href = 'delete_full_backup.php?file=' + encodeURIComponent(filename);
                layer.close(index);
            });
        });
    }
    
    // 开始备份函数
    function startBackup() {
        layui.use(['layer', 'element'], function(){
            var layer = layui.layer;
            var element = layui.element;
            
            // 显示进度条弹窗，添加取消按钮
            var progressIndex = layer.open({
                type: 1,
                title: '正在备份全站',
                content: '<div style="padding: 20px;">' +
                         '<div class="layui-progress layui-progress-big" lay-showpercent="true" lay-filter="backupProgress">' +
                         '<div class="layui-progress-bar layui-bg-green" lay-percent="0%"></div>' +
                         '</div>' +
                         '<div id="backupStatus" style="margin-top: 10px; text-align: center;">准备开始备份...</div>' +
                         '<div style="margin-top: 15px; text-align: center;">' +
                         '<button id="cancelBackupBtn" class="layui-btn layui-btn-primary layui-btn-sm">取消备份</button>' +
                         '</div>' +
                         '</div>',
                area: ['450px', '220px'],
                closeBtn: 0,
                shadeClose: false,
                success: function(layero, index) {
                    // 绑定取消按钮事件
                    document.getElementById('cancelBackupBtn').addEventListener('click', function() {
                        layer.confirm('确定要取消备份吗？', {
                            icon: 3,
                            title: '取消备份'
                        }, function(confirmIndex) {
                            // 这里可以添加取消备份的逻辑
                            layer.close(progressIndex);
                            layer.close(confirmIndex);
                            
                            // 重新启用备份按钮
                            document.getElementById('startBackupBtn').disabled = false;
                            document.getElementById('startBackupBtn').innerHTML = '<i class="layui-icon layui-icon-download-circle"></i> 立即备份全站';
                        });
                    });
                }
            });
            
            // 禁用备份按钮
            document.getElementById('startBackupBtn').disabled = true;
            document.getElementById('startBackupBtn').innerHTML = '<i class="layui-icon layui-icon-loading layui-icon layui-anim layui-anim-rotate layui-anim-loop"></i> 备份进行中...';
            
            // 开始异步备份
            var xhrBackup = new XMLHttpRequest();
            xhrBackup.open('POST', 'ajax_full_backup.php', true);
            xhrBackup.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            // 添加超时处理
            xhrBackup.timeout = 300000; // 5分钟超时
            
            xhrBackup.onreadystatechange = function() {
                if (xhrBackup.readyState === 4) {
                    // 备份完成后处理
                    document.getElementById('startBackupBtn').disabled = false;
                    document.getElementById('startBackupBtn').innerHTML = '<i class="layui-icon layui-icon-download-circle"></i> 立即备份全站';
                    
                    // 关闭进度条弹窗
                    layer.close(progressIndex);
                    
                    if (xhrBackup.status === 200) {
                        try {
                            var response = JSON.parse(xhrBackup.responseText);
                            // 显示备份结果弹窗
                            if (response.status === 'success') {
                                // 记录备份时间到localStorage
                                localStorage.setItem('lastBackupDate', new Date().toISOString().split('T')[0]);
                                
                                // 显示成功弹窗
                                layer.alert(response.message, {
                                    icon: 1,
                                    title: '备份成功',
                                    btn: ['确定'],
                                    yes: function() {
                                        // 刷新页面以显示最新备份文件
                                        location.reload();
                                    }
                                });
                            } else {
                                // 显示失败弹窗
                                layer.alert(response.message, {
                                    icon: 2,
                                    title: '备份失败'
                                });
                            }
                        } catch (e) {
                            layer.alert('备份过程中返回数据格式错误: ' + e.message, {
                                icon: 2,
                                title: '备份失败'
                            });
                        }
                    } else {
                        // 显示失败弹窗
                        var errorMessage = '备份过程中发生错误，HTTP状态码：' + xhrBackup.status;
                        if (xhrBackup.responseText) {
                            errorMessage += '<br><br>错误详情：<br>' + xhrBackup.responseText;
                        }
                        layer.alert(errorMessage, {
                            icon: 2,
                            title: '备份失败'
                        });
                    }
                }
            };
            
            // 处理请求超时
            xhrBackup.ontimeout = function() {
                layer.close(progressIndex);
                document.getElementById('startBackupBtn').disabled = false;
                document.getElementById('startBackupBtn').innerHTML = '<i class="layui-icon layui-icon-download-circle"></i> 立即备份全站';
                
                layer.alert('备份请求超时，请检查服务器配置或稍后重试', {
                    icon: 2,
                    title: '备份超时'
                });
            };
            
            // 处理网络错误
            xhrBackup.onerror = function() {
                layer.close(progressIndex);
                document.getElementById('startBackupBtn').disabled = false;
                document.getElementById('startBackupBtn').innerHTML = '<i class="layui-icon layui-icon-download-circle"></i> 立即备份全站';
                
                layer.alert('备份请求发生网络错误，请检查网络连接后重试', {
                    icon: 2,
                    title: '网络错误'
                });
            };
            
            xhrBackup.send();
            
            // 轮询获取备份进度
            var progressInterval = setInterval(function() {
                var xhrProgress = new XMLHttpRequest();
                xhrProgress.open('GET', 'get_backup_progress.php?timestamp=' + new Date().getTime(), true);
                xhrProgress.onreadystatechange = function() {
                    if (xhrProgress.readyState === 4 && xhrProgress.status === 200) {
                        try {
                            var progressResponse = JSON.parse(xhrProgress.responseText);
                            var progress = progressResponse.progress;
                            var currentFile = progressResponse.current_file;
                            
                            // 更新进度条
                            element.progress('backupProgress', progress + '%');
                            
                            // 更新状态信息
                            if (currentFile) {
                                document.getElementById('backupStatus').innerHTML = '正在处理: ' + currentFile;
                            }
                            
                            // 如果备份完成或发生错误，停止轮询
                            if (progress >= 100) {
                                clearInterval(progressInterval);
                            }
                        } catch (e) {
                            console.error('解析进度数据失败:', e);
                        }
                    }
                };
                xhrProgress.send();
            }, 1000); // 每秒更新一次进度
        });
    }
    </script>
</body>
</html>