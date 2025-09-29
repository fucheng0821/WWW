<?php
// 异步处理全站备份请求并返回进度
session_start();

// 设置正确的路径
define('BASE_PATH', dirname(dirname(dirname(dirname(__FILE__)))));

// 增加内存限制和执行时间以支持处理更大的文件
ini_set('memory_limit', '2048M'); // 2GB内存限制
set_time_limit(3600); // 1小时执行时间

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/functions.php';

check_admin_auth();

// 设置JSON响应头
header('Content-Type: application/json');

// 设置执行时间限制
set_time_limit(300); // 5分钟执行时间

// 初始化会话中的进度变量
$_SESSION['backup_progress'] = 0;
$_SESSION['backup_current_file'] = '开始备份...';
session_write_close(); // 关闭会话锁，允许其他请求读取进度

// 检查ZIP扩展是否可用
if (!extension_loaded('zip')) {
    echo json_encode(['status' => 'error', 'message' => 'ZIP扩展未启用，无法创建全站备份。请在php.ini中启用zip扩展。']);
    exit;
}

try {
    $backup_dir = BASE_PATH . '/backup/';
    
    // 创建备份目录
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0755, true)) {
            throw new Exception('无法创建备份目录: ' . $backup_dir);
        }
    }
    
    // 检查目录是否可写
    if (!is_writable($backup_dir)) {
        throw new Exception('备份目录不可写: ' . $backup_dir);
    }
    
    // 生成备份文件名
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'full_backup_' . $timestamp . '.zip';
    $filepath = $backup_dir . $filename;
    
    // 网站根目录
    $website_root = BASE_PATH;
    
    // 计算总文件数，用于进度计算
    $total_files = count_files_recursive($website_root);
    
    // 创建ZIP压缩文件，直接从原始位置创建，保持原名和原路径
    $result = create_zip_with_progress($website_root, $filepath, $total_files);
    
    if ($result === true) {
        // 获取文件大小
        $fileSize = filesize($filepath);
        $fileSizeMB = round($fileSize / (1024 * 1024), 2);
        
        // 记录备份时间到localStorage（通过前端JavaScript）
        echo json_encode([
            'status' => 'success', 
            'message' => "全站备份成功！文件保存为: $filename (大小: {$fileSizeMB} MB)", 
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => '创建ZIP文件失败: ' . $result]);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => '备份失败：' . $e->getMessage()]);
    error_log('全站备份错误: ' . $e->getMessage());
}

// 递归计算目录中的文件总数
function count_files_recursive($dir) {
    $count = 0;
    
    $dir = str_replace('\\', '/', realpath($dir));
    
    // 要排除的目录和文件
    $exclude_dirs = ['backup', '.git', '.qoder'];
    $exclude_patterns = ['/temp_backup_/'];

    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file_path = str_replace('\\', '/', $file->getRealPath());
            
            // 检查是否需要排除
            $relative_path = substr($file_path, strlen($dir));
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
            
            if (!$should_exclude) {
                $count++;
            }
        }
    } catch (Exception $e) {
        error_log('计算文件数量时出错: ' . $e->getMessage());
    }
    
    return $count;
}

// 从网站根目录创建ZIP文件，支持进度报告
function create_zip_with_progress($root_path, $destination, $total_files) {
    global $db;
    
    if (!extension_loaded('zip')) {
        return 'ZIP扩展未加载';
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE)) {
        return '无法创建ZIP文件';
    }

    $root_path = str_replace('\\', '/', realpath($root_path));
    $root_path_length = strlen($root_path);
    
    // 要排除的目录和文件
    $exclude_dirs = ['backup', '.git', '.qoder'];
    $exclude_patterns = ['/temp_backup_/'];

    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $processed_files = 0;
        $last_progress = 0;
        
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
                // 检查文件是否存在且可读
                if (file_exists($file_path) && is_readable($file_path)) {
                    // 检查文件大小，避免过大文件导致内存问题
                    $fileSize = filesize($file_path);
                    if ($fileSize > 20 * 1024 * 1024 * 1024) { // 超过20GB的文件
                        error_log("跳过大文件: $file_path (" . round($fileSize / (1024*1024*1024), 2) . " GB)");
                        continue;
                    }
                    $zip->addFile($file_path, $zip_path);
                }
            }
            
            // 更新进度
            $processed_files++;
            $progress = round(($processed_files / max($total_files, 1)) * 80); // 文件处理占80%进度
            
            // 避免过于频繁的进度更新
            if ($progress - $last_progress >= 1) {
                // 将会话重新打开以更新进度
                session_start();
                $_SESSION['backup_progress'] = $progress;
                $_SESSION['backup_current_file'] = basename($file_path);
                session_write_close();
                $last_progress = $progress;
            }
        }
        
        // 更新进度为85%，表示文件部分已完成
        session_start();
        $_SESSION['backup_progress'] = 85;
        $_SESSION['backup_current_file'] = '正在备份数据库...';
        session_write_close();
        
        // 创建数据库备份并添加到ZIP文件
        try {
            // 获取所有表
            $tables_stmt = $db->query("SHOW TABLES");
            $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $backup_content = "-- 全站数据库备份文件\n";
            $backup_content .= "-- 备份时间: " . date('Y-m-d H:i:s') . "\n";
            $backup_content .= "-- 数据库: " . DB_NAME . "\n\n";
            $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            $total_tables = count($tables);
            $processed_tables = 0;
            
            foreach ($tables as $table) {
                // 获取创建表的SQL
                $create_stmt = $db->query("SHOW CREATE TABLE `$table`");
                $create_row = $create_stmt->fetch();
                if (!$create_row) {
                    continue;
                }
                $create_sql = $create_row['Create Table'];
                
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
                
                // 更新数据库备份进度
                $processed_tables++;
                $db_progress = 85 + round(($processed_tables / max($total_tables, 1)) * 10); // 数据库部分占10%进度
                
                session_start();
                $_SESSION['backup_progress'] = $db_progress;
                $_SESSION['backup_current_file'] = "正在备份数据库表: $table";
                session_write_close();
            }
            
            $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // 添加数据库备份文件到ZIP
            $zip->addFromString('database_backup.sql', $backup_content);
            
            // 更新进度为95%，表示数据库备份已完成
            session_start();
            $_SESSION['backup_progress'] = 95;
            $_SESSION['backup_current_file'] = '正在完成备份...';
            session_write_close();
            
        } catch (Exception $e) {
            // 如果数据库备份失败，关闭ZIP文件并返回false
            error_log('数据库备份失败: ' . $e->getMessage());
            $zip->close();
            return '数据库备份失败: ' . $e->getMessage();
        }
        
        // 更新进度为100%，表示备份已完成
        session_start();
        $_SESSION['backup_progress'] = 100;
        $_SESSION['backup_current_file'] = '备份完成';
        session_write_close();

        return $zip->close() ? true : '关闭ZIP文件失败';
    } catch (Exception $e) {
        error_log('创建ZIP文件时出错: ' . $e->getMessage());
        $zip->close();
        return '创建ZIP文件时出错: ' . $e->getMessage();
    }
}

// 获取备份进度的函数
function get_backup_progress() {
    session_start();
    $progress = isset($_SESSION['backup_progress']) ? $_SESSION['backup_progress'] : 0;
    $current_file = isset($_SESSION['backup_current_file']) ? $_SESSION['backup_current_file'] : '';
    session_write_close();
    
    return ['progress' => $progress, 'current_file' => $current_file];
}