<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

// 处理表单提交
$success_msg = '';
$error_msg = '';
$config_data = [];

// 获取现有的配置项（如果表存在）
try {
    $stmt = $db->query("SHOW TABLES LIKE 'config'");
    if ($stmt->rowCount() > 0) {
        $config_stmt = $db->query("SELECT * FROM config ORDER BY sort_order ASC");
        $config_data = $config_stmt->fetchAll();
    }
} catch(PDOException $e) {
    // 表不存在，继续执行
}

// 处理初始化请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initialize') {
    try {
        // 开始事务
        $db->beginTransaction();
        
        // 1. 创建config表（如果不存在）
        $create_config_table_sql = "
        CREATE TABLE IF NOT EXISTS `config` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `config_key` varchar(100) NOT NULL COMMENT '配置键名',
          `config_value` text COMMENT '配置值',
          `config_group` varchar(50) DEFAULT 'basic' COMMENT '配置分组',
          `config_type` enum('text','textarea','number','select','radio','checkbox','image','file') DEFAULT 'text' COMMENT '配置类型',
          `config_options` text COMMENT '配置选项(JSON格式)',
          `config_title` varchar(200) DEFAULT NULL COMMENT '配置标题',
          `config_description` text COMMENT '配置描述',
          `sort_order` int(11) DEFAULT '0' COMMENT '排序',
          `is_required` tinyint(1) DEFAULT '0' COMMENT '是否必填',
          `is_system` tinyint(1) DEFAULT '0' COMMENT '是否系统配置',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
          PRIMARY KEY (`id`),
          UNIQUE KEY `config_key` (`config_key`),
          KEY `config_group` (`config_group`),
          KEY `sort_order` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表'";
        
        $db->exec($create_config_table_sql);
        
        // 2. 插入默认配置项（只在表为空时插入）
        $count_stmt = $db->query("SELECT COUNT(*) as count FROM config");
        $count = $count_stmt->fetch()['count'];
        
        if ($count == 0) {
            $default_configs = [
                // 基本设置
                ['site_name', '高光视刻', 'basic', 'text', null, '网站名称', '网站的名称，显示在浏览器标题栏和页面标题中', 1, 1, 0],
                ['site_description', '专业的创意服务提供商', 'basic', 'textarea', null, '网站描述', '网站的简短描述，用于SEO和分享', 2, 0, 0],
                ['site_keywords', '视频制作,平面设计,网站建设,商业摄影,活动策划', 'basic', 'text', null, '网站关键词', '网站关键词，用英文逗号分隔', 3, 0, 0],
                ['site_logo', '', 'basic', 'image', null, '网站LOGO', '网站的标志图片', 4, 0, 0],
                ['site_favicon', '', 'basic', 'image', null, '网站图标', '网站的favicon图标', 5, 0, 0],
                
                // SEO设置
                ['seo_title', '高光视刻 - 专业创意服务', 'seo', 'text', null, 'SEO标题', '搜索引擎显示的页面标题', 1, 0, 0],
                ['seo_description', '高光视刻提供专业的创意服务，拥有丰富经验和专业团队，为客户提供高质量的解决方案。', 'seo', 'textarea', null, 'SEO描述', '搜索引擎显示的页面描述', 2, 0, 0],
                ['seo_keywords', '视频制作,平面设计,网站建设,商业摄影,活动策划', 'seo', 'text', null, 'SEO关键词', '搜索引擎优化关键词', 3, 0, 0],
                
                // 联系信息
                ['contact_company', '高光视刻文化传媒有限公司', 'contact', 'text', null, '公司名称', '公司的正式名称', 1, 0, 0],
                ['contact_address', '北京市朝阳区创意园区', 'contact', 'textarea', null, '公司地址', '公司的详细地址', 2, 0, 0],
                ['contact_phone', '400-888-8888', 'contact', 'text', null, '联系电话', '公司的联系电话', 3, 0, 0],
                ['contact_mobile', '138-0000-0000', 'contact', 'text', null, '手机号码', '公司的手机号码', 4, 0, 0],
                ['contact_email', 'info@gaoguangshike.cn', 'contact', 'text', null, '邮箱地址', '公司的邮箱地址', 5, 0, 0],
                ['contact_qq', '888888888', 'contact', 'text', null, 'QQ号码', '公司的QQ客服号码', 6, 0, 0],
                ['contact_wechat', 'gaoguangshike', 'contact', 'text', null, '微信号', '公司的微信号', 7, 0, 0],
                
                // 邮件设置
                ['mail_smtp_host', '', 'mail', 'text', null, 'SMTP服务器', '邮件服务器SMTP地址，如smtp.qq.com', 1, 0, 0],
                ['mail_smtp_port', '', 'mail', 'text', null, 'SMTP端口', '邮件服务器SMTP端口，如465或587', 2, 0, 0],
                ['mail_smtp_username', '', 'mail', 'text', null, 'SMTP用户名', '邮件账户用户名', 3, 0, 0],
                ['mail_smtp_password', '', 'mail', 'text', null, 'SMTP密码', '邮件账户密码或授权码', 4, 0, 0],
                ['mail_smtp_encryption', '', 'mail', 'select', null, '加密方式', '邮件加密方式，如ssl或tls', 5, 0, 0],
                ['mail_from_address', '', 'mail', 'text', null, '发件人邮箱', '发送邮件时显示的发件人邮箱地址', 6, 0, 0],
                ['mail_from_name', '', 'mail', 'text', null, '发件人名称', '发送邮件时显示的发件人名称', 7, 0, 0],
                ['mail_admin_address', '', 'mail', 'text', null, '管理员邮箱', '接收系统通知的管理员邮箱地址', 8, 0, 0]
            ];
            
            $stmt = $db->prepare("
                INSERT INTO config 
                (config_key, config_value, config_group, config_type, config_options, config_title, config_description, sort_order, is_required, is_system) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($default_configs as $config) {
                $stmt->execute($config);
            }
        }
        
        // 3. 创建uploads表（如果不存在）
        $create_uploads_table_sql = "
        CREATE TABLE IF NOT EXISTS `uploads` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `filename` varchar(255) NOT NULL COMMENT '文件名',
          `original_name` varchar(255) NOT NULL COMMENT '原始文件名',
          `file_path` varchar(500) NOT NULL COMMENT '文件路径',
          `file_url` varchar(500) NOT NULL COMMENT '访问URL',
          `file_type` varchar(50) NOT NULL COMMENT '文件类型',
          `file_size` int(11) NOT NULL COMMENT '文件大小',
          `uploaded_by` int(11) NOT NULL COMMENT '上传者ID',
          `created_at` datetime NOT NULL COMMENT '上传时间',
          PRIMARY KEY (`id`),
          KEY `uploaded_by` (`uploaded_by`),
          KEY `file_type` (`file_type`),
          KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件上传记录表'";
        
        $db->exec($create_uploads_table_sql);
        
        // 提交事务
        $db->commit();
        
        $success_msg = '系统初始化配置完成！';
    } catch(Exception $e) {
        // 回滚事务
        $db->rollback();
        $error_msg = '初始化失败：' . $e->getMessage();
    }
}

// 获取配置表状态
$config_table_exists = false;
$config_count = 0;
$uploads_table_exists = false;

try {
    // 检查config表是否存在
    $stmt = $db->query("SHOW TABLES LIKE 'config'");
    $config_table_exists = $stmt->rowCount() > 0;
    
    if ($config_table_exists) {
        $count_stmt = $db->query("SELECT COUNT(*) as count FROM config");
        $config_count = $count_stmt->fetch()['count'];
    }
    
    // 检查uploads表是否存在
    $stmt = $db->query("SHOW TABLES LIKE 'uploads'");
    $uploads_table_exists = $stmt->rowCount() > 0;
    
} catch(PDOException $e) {
    $error_msg = '数据库查询错误：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>初始化配置 - 高光视刻</title>
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
                    <h2>初始化配置</h2>
                </div>
                
                <div class="layui-card-body">
                    <?php if ($success_msg): ?>
                        <div class="layui-alert layui-alert-success">
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_msg): ?>
                        <div class="layui-alert layui-alert-danger">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-section">
                        <h3>系统状态</h3>
                        <table class="layui-table">
                            <tbody>
                                <tr>
                                    <td width="150"><strong>配置表状态</strong></td>
                                    <td>
                                        <?php if ($config_table_exists): ?>
                                            <span class="layui-badge layui-bg-green">已创建</span>
                                            (<?php echo $config_count; ?> 个配置项)
                                        <?php else: ?>
                                            <span class="layui-badge">未创建</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>上传记录表状态</strong></td>
                                    <td>
                                        <?php if ($uploads_table_exists): ?>
                                            <span class="layui-badge layui-bg-green">已创建</span>
                                        <?php else: ?>
                                            <span class="layui-badge">未创建</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="info-section">
                        <h3>初始化说明</h3>
                        <div class="layui-text">
                            <ul>
                                <li>点击"开始初始化"按钮将创建系统所需的配置表和上传记录表</li>
                                <li>如果配置表为空，将自动插入默认配置项</li>
                                <li>已存在的表和数据不会被覆盖</li>
                                <li>初始化过程是安全的，不会影响现有数据</li>
                            </ul>
                        </div>
                    </div>
                    
                    <form class="layui-form" method="POST">
                        <input type="hidden" name="action" value="initialize">
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal" lay-submit>
                                    <i class="layui-icon layui-icon-ok"></i> 开始初始化
                                </button>
                                <a href="index.php" class="layui-btn layui-btn-primary">
                                    <i class="layui-icon layui-icon-return"></i> 返回系统设置
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($config_data)): ?>
                    <div class="info-section">
                        <h3>现有配置项</h3>
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th>配置名称</th>
                                    <th>配置分组</th>
                                    <th>配置值</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($config_data as $config): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($config['config_title']); ?></td>
                                    <td><?php echo htmlspecialchars($config['config_group']); ?></td>
                                    <td><?php echo htmlspecialchars($config['config_value']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'layer'], function(){
        var form = layui.form;
        var layer = layui.layer;
        
        // 表单提交确认
        form.on('submit(*)', function(data){
            layer.confirm('确定要开始初始化配置吗？此操作不会影响现有数据。', {
                title: '确认初始化',
                icon: 3
            }, function(index){
                layer.close(index);
                layer.msg('正在初始化...', {icon: 16, time: 0});
                // 继续提交表单
                return true;
            });
            // 阻止默认提交
            return false;
        });
    });
    </script>
</body>
</html>