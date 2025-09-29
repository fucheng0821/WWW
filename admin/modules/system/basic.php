<?php
// 增加 str_starts_with 函数的兼容性支持
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

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
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $config_key = substr($key, 7); // 去掉 'config_' 前缀
                $config_value = trim($value);
                
                // 更新配置
                $stmt = $db->prepare("UPDATE config SET config_value = ?, updated_at = NOW() WHERE config_key = ?");
                $stmt->execute([$config_value, $config_key]);
            }
        }
        
        $db->commit();
        $success = '配置更新成功！';
        
    } catch(Exception $e) {
        $db->rollBack();
        $errors[] = '更新失败：' . $e->getMessage();
    }
}

// 获取基本配置
try {
    $stmt = $db->query("SELECT * FROM config WHERE config_group = 'basic' ORDER BY sort_order ASC");
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
    <title>基本设置 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="layui-body">
            <div class="layui-card" style="margin: 20px;">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>基本设置</h2>
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
                            <i class="layui-icon layui-icon-set"></i>
                            <h3>暂无配置</h3>
                            <p>请先初始化配置表</p>
                            <a href="init_config.php" class="layui-btn layui-btn-normal">初始化配置</a>
                        </div>
                    <?php else: ?>
                        <form class="layui-form" method="POST">
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
                                                      rows="4"
                                                      <?php echo $config['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($config['config_value']); ?></textarea>
                                        <?php elseif ($config['config_type'] === 'number'): ?>
                                            <input type="number" 
                                                   name="config_<?php echo $config['config_key']; ?>" 
                                                   placeholder="<?php echo htmlspecialchars($config['config_description']); ?>" 
                                                   value="<?php echo htmlspecialchars($config['config_value']); ?>" 
                                                   class="layui-input"
                                                   <?php echo $config['is_required'] ? 'required' : ''; ?>>
                                        <?php elseif ($config['config_type'] === 'image'): ?>
                                            <input type="text" 
                                                   name="config_<?php echo $config['config_key']; ?>" 
                                                   placeholder="请输入图片URL或上传图片" 
                                                   value="<?php echo htmlspecialchars($config['config_value']); ?>" 
                                                   class="layui-input"
                                                   <?php echo $config['is_required'] ? 'required' : ''; ?>
                                                   id="config_<?php echo $config['config_key']; ?>">
                                            <div class="layui-form-mid layui-word-aux">
                                                支持URL地址或相对路径，建议尺寸: <?php echo $config['config_key'] === 'site_logo' ? '200x60px' : '32x32px'; ?>
                                            </div>
                                            <!-- 添加上传按钮 -->
                                            <div style="margin-top: 10px;">
                                                <button type="button" class="layui-btn layui-btn-primary upload-btn" data-config="<?php echo $config['config_key']; ?>">
                                                    <i class="layui-icon layui-icon-upload"></i> 上传图片
                                                </button>
                                                <?php 
                                                $preview_btn_url = $config['config_value'];
                                                if (!empty($preview_btn_url) && !str_starts_with($preview_btn_url, 'http://') && !str_starts_with($preview_btn_url, 'https://')) {
                                                    if (!str_starts_with($preview_btn_url, '/')) {
                                                        $preview_btn_url = '/' . $preview_btn_url;
                                                    }
                                                }
                                                ?>
                                                <button type="button" class="layui-btn layui-btn-primary preview-btn" data-url="<?php echo htmlspecialchars($preview_btn_url); ?>" data-config="<?php echo $config['config_key']; ?>" <?php echo empty($config['config_value']) ? 'style="display:none;"' : ''; ?>>
                                                    <i class="layui-icon layui-icon-search"></i> 预览
                                                </button>
                                            </div>
                                            <!-- 实时预览区域 -->
                                            <div class="image-preview" id="preview_<?php echo $config['config_key']; ?>" style="margin-top: 10px;<?php echo empty($config['config_value']) ? 'display:none;' : ''; ?>">
                                                <?php 
                                                $preview_url = $config['config_value'];
                                                if (!empty($preview_url) && !str_starts_with($preview_url, 'http://') && !str_starts_with($preview_url, 'https://')) {
                                                    if (!str_starts_with($preview_url, '/')) {
                                                        $preview_url = '/' . $preview_url;
                                                    }
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($preview_url); ?>" style="max-width: 200px; max-height: 100px; border: 1px solid #eee; padding: 5px;">
                                            </div>
                                        <?php else: ?>
                                            <input type="text" 
                                                   name="config_<?php echo $config['config_key']; ?>" 
                                                   placeholder="<?php echo htmlspecialchars($config['config_description']); ?>" 
                                                   value="<?php echo htmlspecialchars($config['config_value']); ?>" 
                                                   class="layui-input"
                                                   <?php echo $config['is_required'] ? 'required' : ''; ?>>
                                        <?php endif; ?>
                                        
                                        <?php if ($config['config_description'] && $config['config_type'] !== 'image'): ?>
                                            <div class="layui-form-mid layui-word-aux">
                                                <?php echo htmlspecialchars($config['config_description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button type="submit" class="layui-btn layui-btn-normal">保存设置</button>
                                    <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <style>
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        .image-preview img {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #eee;
            padding: 5px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
    <script>
    layui.use(['form', 'element', 'upload', 'layer'], function(){
        var form = layui.form;
        var element = layui.element;
        var upload = layui.upload;
        var layer = layui.layer;
        
        form.render();
        element.render();
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
        
        // 构建完整URL的函数
        function buildFullUrl(relativePath) {
            if (!relativePath) return '';
            if (relativePath.startsWith('http://') || relativePath.startsWith('https://')) {
                return relativePath;
            }
            // 确保路径以/开头
            if (!relativePath.startsWith('/')) {
                relativePath = '/' + relativePath;
            }
            // 使用站点URL构建完整路径
            return relativePath;
        }
        
        // 文件上传
        var uploadInst = upload.render({
            elem: '.upload-btn',
            url: '../content/upload.php',
            accept: 'images',
            exts: 'jpg|jpeg|png|gif|webp',
            field: 'file',
            size: 10240, // 10MB
            before: function(obj) {
                // 预读本地文件示例，不支持ie8
                obj.preview(function(index, file, result) {
                    console.log('开始上传文件：', file.name);
                });
            },
            done: function(res, index, upload) {
                // 上传成功回调
                if (res.success) {
                    // 获取配置键名
                    var configKey = this.item[0].getAttribute('data-config');
                    // 使用缩略图路径（如果存在）或完整图片路径
                    var imagePath = res.thumbnail || res.location;
                    // 更新对应输入框的值
                    document.getElementById('config_' + configKey).value = imagePath;
                    
                    // 更新预览区域
                    var previewContainer = document.getElementById('preview_' + configKey);
                    var previewImage = previewContainer.querySelector('img');
                    previewImage.src = buildFullUrl(imagePath);
                    previewContainer.style.display = 'block';
                    
                    // 显示预览按钮并更新URL
                    var previewBtn = document.querySelector('.preview-btn[data-config="' + configKey + '"]');
                    if (previewBtn) {
                        previewBtn.setAttribute('data-url', buildFullUrl(imagePath));
                        previewBtn.style.display = 'inline-block';
                    }
                    
                    layer.msg('上传成功');
                } else {
                    layer.msg('上传失败：' + res.error);
                }
            },
            error: function() {
                // 请求异常回调
                layer.msg('上传请求异常');
            }
        });
        
        // 预览图片
        function bindPreviewEvents() {
            var previewBtns = document.querySelectorAll('.preview-btn');
            previewBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var imgUrl = btn.getAttribute('data-url');
                    if (imgUrl) {
                        layer.open({
                            type: 1,
                            title: '图片预览',
                            area: ['600px', '400px'],
                            content: '<div style="text-align:center;padding:20px;"><img src="' + buildFullUrl(imgUrl) + '" style="max-width:100%;max-height:100%;"></div>'
                        });
                    }
                });
            });
        }
        
        // 初始化预览按钮事件
        bindPreviewEvents();
        
        // 监听输入框变化，更新预览
        var configInputs = document.querySelectorAll('input[id^="config_"]');
        configInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                var configKey = input.id.replace('config_', '');
                var previewContainer = document.getElementById('preview_' + configKey);
                if (previewContainer) {
                    var previewImage = previewContainer.querySelector('img');
                    if (input.value) {
                        previewImage.src = buildFullUrl(input.value);
                        previewContainer.style.display = 'block';
                        
                        // 显示并更新预览按钮
                        var previewBtn = document.querySelector('.preview-btn[data-config="' + configKey + '"]');
                        if (previewBtn) {
                            previewBtn.setAttribute('data-url', buildFullUrl(input.value));
                            previewBtn.style.display = 'inline-block';
                        }
                    } else {
                        previewContainer.style.display = 'none';
                        
                        // 隐藏预览按钮
                        var previewBtn = document.querySelector('.preview-btn[data-config="' + configKey + '"]');
                        if (previewBtn) {
                            previewBtn.style.display = 'none';
                        }
                    }
                }
            });
            
            // 初始化时也触发一次change事件来确保预览按钮状态正确
            input.dispatchEvent(new Event('change'));
        });
    });
    </script>
</body>
</html>