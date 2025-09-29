<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';

// 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db->beginTransaction();
                
                // 处理文本表单字段
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'config_') === 0) {
                        $config_key = substr($key, 7); // 去掉 'config_' 前缀
                        $config_value = trim($value);
                        
                        // 更新配置
                        $stmt = $db->prepare("UPDATE config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                        $stmt->execute([$config_value, $config_key]);
                    }
                }
                
                // 处理微信二维码文件上传
                if (isset($_FILES['config_wechat_qr']) && $_FILES['config_wechat_qr']['error'] === UPLOAD_ERR_OK) {
                    // 确保uploads目录存在
                    $upload_dir = '../../../uploads/images/qrcodes';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // 获取文件信息
                    $file_ext = strtolower(pathinfo($_FILES['config_wechat_qr']['name'], PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        // 生成唯一文件名
                        $filename = 'wechat_qr_' . time() . '.' . $file_ext;
                        $filepath = $upload_dir . '/' . $filename;
                        
                        if (move_uploaded_file($_FILES['config_wechat_qr']['tmp_name'], $filepath)) {
                            // 保存文件路径到配置（添加前导斜杠确保从网站根目录开始）
                            $file_url = '/uploads/images/qrcodes/' . $filename;
                            
                            // 检查是否已存在wechat_qr配置
                            $check_stmt = $db->prepare("SELECT COUNT(*) FROM config WHERE config_key = ?");
                            $check_stmt->execute(['wechat_qr']);
                            $exists = $check_stmt->fetchColumn() > 0;
                            
                            if ($exists) {
                                // 更新现有配置
                                $update_stmt = $db->prepare("UPDATE config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                                $update_stmt->execute([$file_url, 'wechat_qr']);
                            } else {
                                // 插入新配置
                                $insert_stmt = $db->prepare("INSERT INTO config (config_key, config_value, config_group, config_type, config_title, config_description, sort_order, is_required, is_system, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                                $insert_stmt->execute(['wechat_qr', $file_url, 'contact', 'image', '微信二维码', '网站显示的微信二维码图片', 8, 0, 0]);
                            }
                        } else {
                            throw new Exception('文件上传失败');
                        }
                    } else {
                        throw new Exception('不支持的文件类型，仅支持jpg、jpeg、png、gif、svg');
                    }
                }
                
                $db->commit();
                $success = '联系信息更新成功！';
                
            } catch(Exception $e) {
                $db->rollBack();
                $errors[] = '更新失败：' . $e->getMessage();
            }
        }

// 获取联系信息配置
try {
    $stmt = $db->query("SELECT * FROM config WHERE config_group = 'contact' ORDER BY sort_order ASC");
    $configs = $stmt->fetchAll();
} catch(Exception $e) {
    $configs = [];
    $errors[] = '获取配置失败：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>联系信息设置 - 高光视刻</title>
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
                        <h2>联系信息设置</h2>
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
                    
                    <?php if (empty($configs)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-cellphone"></i>
                            <h3>暂无联系信息配置</h3>
                            <p>请先初始化配置表</p>
                            <a href="init_config.php" class="layui-btn layui-btn-normal">初始化配置</a>
                        </div>
                    <?php else: ?>
                        <div class="layui-row layui-col-space20">
                            <!-- 左侧表单 -->
                            <div class="layui-col-md8">
                                <form class="layui-form" method="POST" enctype="multipart/form-data">
                                    <?php foreach ($configs as $config): ?>
                                        <div class="layui-form-item">
                                            <label class="layui-form-label">
                                                <?php echo htmlspecialchars($config['config_title']); ?>
                                                <?php if ($config['is_required']): ?>
                                                    <span style="color: red;">*</span>
                                                <?php endif; ?>
                                            </label>
                                            <div class="layui-input-block">
                                                <?php if ($config['config_type'] === 'textarea'): ?>
                                                    <textarea name="config_<?php echo $config['config_key']; ?>" 
                                                              placeholder="<?php echo htmlspecialchars($config['config_description']); ?>" 
                                                              class="layui-textarea" 
                                                              rows="3"
                                                              <?php echo $config['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($config['config_value']); ?></textarea>
                                                <?php else: ?>
                                                    <input type="text" 
                                                           name="config_<?php echo $config['config_key']; ?>" 
                                                           placeholder="<?php echo htmlspecialchars($config['config_description']); ?>" 
                                                           value="<?php echo htmlspecialchars($config['config_value']); ?>" 
                                                           class="layui-input"
                                                           <?php echo $config['is_required'] ? 'required' : ''; ?>>
                                                <?php endif; ?>
                                                 
                                                <?php if ($config['config_description']): ?>
                                                    <div class="layui-form-mid layui-word-aux">
                                                        <?php echo htmlspecialchars($config['config_description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- 微信二维码上传 -->
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">微信二维码</label>
                                        <div class="layui-input-block">
                                            <!-- 先尝试获取当前微信二维码配置 -->
                                            <?php 
                                            $wechat_qr_config = null;
                                            try {
                                                $stmt = $db->prepare("SELECT config_value FROM config WHERE config_key = ?");
                                                $stmt->execute(['wechat_qr']);
                                                $result = $stmt->fetch();
                                                if ($result) {
                                                    $wechat_qr_config = $result['config_value'];
                                                }
                                            } catch(Exception $e) {
                                                // 忽略错误
                                            }
                                            ?>
                                            
                                            <!-- 文件上传组件 -->
                                            <div class="layui-upload">
                                                <button type="button" class="layui-btn" id="wechatQrUpload">
                                                    <i class="layui-icon">&#xe67c;</i>选择图片
                                                </button>
                                                <input type="file" 
                                                       name="config_wechat_qr" 
                                                       accept=".jpg,.jpeg,.png,.gif,.svg" 
                                                       style="display: none;" 
                                                       id="wechatQrFileInput">
                                                <p style="margin-top: 5px; font-size: 12px; color: #666;">点击按钮选择图片进行上传</p>
                                            </div>
                                            
                                            <!-- 当前二维码预览 -->
                                            <div class="wechat-qr-preview" style="margin-top: 10px; min-height: 160px;">
                                                <?php if (!empty($wechat_qr_config)): ?>
                                                    <img src="<?php echo htmlspecialchars($wechat_qr_config); ?>" style="max-width: 150px; max-height: 150px; border: 1px solid #eee;" alt="微信二维码">
                                                    <p style="margin-top: 5px; font-size: 12px; color: #666;">当前微信二维码</p>
                                                <?php else: ?>
                                                    <div style="border: 1px dashed #ccc; width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;">
                                                        <p style="font-size: 12px; color: #999; text-align: center;">暂未上传微信二维码</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="layui-form-mid layui-word-aux">
                                                支持jpg、jpeg、png、gif、svg格式，建议尺寸为200×200像素
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button type="submit" class="layui-btn layui-btn-normal">保存联系信息</button>
                                            <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 右侧预览 -->
                            <div class="layui-col-md4">
                                <div class="info-section">
                                    <h3>联系信息预览</h3>
                                    <div class="contact-preview">
                                        <?php
                                        $contact_data = [];
                                        foreach ($configs as $config) {
                                            $contact_data[$config['config_key']] = $config['config_value'];
                                        }
                                        ?>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-home"></i>
                                            <div class="contact-info">
                                                <strong><?php echo htmlspecialchars($contact_data['contact_company'] ?? '公司名称'); ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-location"></i>
                                            <div class="contact-info">
                                                <?php echo nl2br(htmlspecialchars($contact_data['contact_address'] ?? '公司地址')); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-cellphone"></i>
                                            <div class="contact-info">
                                                <a href="tel:<?php echo $contact_data['contact_phone'] ?? ''; ?>">
                                                    <?php echo htmlspecialchars($contact_data['contact_phone'] ?? '联系电话'); ?>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-cellphone"></i>
                                            <div class="contact-info">
                                                <a href="tel:<?php echo $contact_data['contact_mobile'] ?? ''; ?>">
                                                    <?php echo htmlspecialchars($contact_data['contact_mobile'] ?? '手机号码'); ?>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-email"></i>
                                            <div class="contact-info">
                                                <a href="mailto:<?php echo $contact_data['contact_email'] ?? ''; ?>">
                                                    <?php echo htmlspecialchars($contact_data['contact_email'] ?? '邮箱地址'); ?>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-dialogue"></i>
                                            <div class="contact-info">
                                                QQ: <?php echo htmlspecialchars($contact_data['contact_qq'] ?? 'QQ号码'); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="contact-item">
                                            <i class="layui-icon layui-icon-chat"></i>
                                            <div class="contact-info">
                                                微信: <?php echo htmlspecialchars($contact_data['contact_wechat'] ?? '微信号'); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- 微信二维码预览 -->
                                        <?php if (!empty($wechat_qr_config)): ?>
                                        <div class="contact-item" style="padding-top: 10px;">
                                            <i class="layui-icon layui-icon-chat"></i>
                                            <div class="contact-info">
                                                <p style="margin-bottom: 5px;">微信二维码:</p>
                                                <img src="<?php echo htmlspecialchars($wechat_qr_config); ?>" style="max-width: 100px; max-height: 100px; border: 1px solid #eee;" alt="微信二维码">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'element', 'upload'], function(){
        var form = layui.form;
        var element = layui.element;
        var upload = layui.upload;
        
        form.render();
        element.render();
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert-success, .layui-alert-danger');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
        
        // 文件上传按钮交互
        document.getElementById('wechatQrUpload').addEventListener('click', function() {
            document.getElementById('wechatQrFileInput').click();
        });
        
        // 选择文件后自动显示文件名
        document.getElementById('wechatQrFileInput').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                var fileName = e.target.files[0].name;
                var fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2);
                var fileInfoText = '已选择文件: ' + fileName + ' (' + fileSize + 'MB)';
                
                // 显示文件信息
                var fileInfoEl = document.createElement('p');
                fileInfoEl.style.marginTop = '5px';
                fileInfoEl.style.fontSize = '12px';
                fileInfoEl.style.color = '#666';
                fileInfoEl.textContent = fileInfoText;
                
                // 移除旧的文件信息
                var parentEl = this.parentElement;
                var oldFileInfo = parentEl.querySelector('p:nth-child(3)');
                if (oldFileInfo) {
                    parentEl.removeChild(oldFileInfo);
                }
                
                parentEl.appendChild(fileInfoEl);
            }
        });
    });
    </script>
    
    <style>
    .contact-preview {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #eee;
    }
    
    .contact-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .contact-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .contact-item i {
        width: 30px;
        font-size: 16px;
        color: #ff6b35;
        margin-right: 10px;
        margin-top: 2px;
    }
    
    .contact-info {
        flex: 1;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .contact-info strong {
        font-size: 16px;
        color: #333;
    }
    
    .contact-info a {
        color: #ff6b35;
        text-decoration: none;
    }
    
    .contact-info a:hover {
        text-decoration: underline;
    }
    </style>
</body>
</html>