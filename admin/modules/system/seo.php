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
        $success = 'SEO配置更新成功！';
        
    } catch(Exception $e) {
        $db->rollBack();
        $errors[] = '更新失败：' . $e->getMessage();
    }
}

// 获取SEO配置
try {
    $stmt = $db->query("SELECT * FROM config WHERE config_group = 'seo' ORDER BY sort_order ASC");
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
    <title>SEO设置 - 高光视刻</title>
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
                        <h2>SEO设置</h2>
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
                    
                    <!-- SEO提示信息 -->
                    <div class="layui-alert layui-alert-normal">
                        <h4>SEO优化建议</h4>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li><strong>标题长度</strong>：建议控制在30-60个字符之间</li>
                            <li><strong>描述长度</strong>：建议控制在120-160个字符之间</li>
                            <li><strong>关键词</strong>：建议3-5个核心关键词，用英文逗号分隔</li>
                            <li><strong>原创性</strong>：确保标题和描述的原创性和相关性</li>
                        </ul>
                    </div>
                    
                    <?php if (empty($configs)): ?>
                        <div class="empty-state">
                            <i class="layui-icon layui-icon-search"></i>
                            <h3>暂无SEO配置</h3>
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
                                            <div class="layui-form-mid layui-word-aux">
                                                当前字符数：<span id="count_<?php echo $config['config_key']; ?>"><?php echo mb_strlen($config['config_value'], 'UTF-8'); ?></span>
                                                <?php if ($config['config_key'] === 'seo_description'): ?>
                                                    （建议120-160字符）
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <input type="text" 
                                                   name="config_<?php echo $config['config_key']; ?>" 
                                                   placeholder="<?php echo htmlspecialchars($config['config_description']); ?>" 
                                                   value="<?php echo htmlspecialchars($config['config_value']); ?>" 
                                                   class="layui-input"
                                                   <?php echo $config['is_required'] ? 'required' : ''; ?>>
                                            <div class="layui-form-mid layui-word-aux">
                                                当前字符数：<span id="count_<?php echo $config['config_key']; ?>"><?php echo mb_strlen($config['config_value'], 'UTF-8'); ?></span>
                                                <?php if ($config['config_key'] === 'seo_title'): ?>
                                                    （建议30-60字符）
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($config['config_description']): ?>
                                            <div class="layui-form-mid layui-word-aux" style="margin-top: 5px;">
                                                <?php echo htmlspecialchars($config['config_description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button type="submit" class="layui-btn layui-btn-normal">保存SEO设置</button>
                                    <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                                </div>
                            </div>
                        </form>
                        
                        <!-- SEO预览 -->
                        <div class="info-section" style="margin-top: 30px;">
                            <h3>搜索结果预览</h3>
                            <div class="seo-preview">
                                <div class="seo-title" id="preview-title">
                                    <?php 
                                    $title_config = array_filter($configs, function($c) { return $c['config_key'] === 'seo_title'; });
                                    echo htmlspecialchars(reset($title_config)['config_value'] ?? '网站标题');
                                    ?>
                                </div>
                                <div class="seo-url">https://www.gaoguangshike.cn/</div>
                                <div class="seo-description" id="preview-description">
                                    <?php 
                                    $desc_config = array_filter($configs, function($c) { return $c['config_key'] === 'seo_description'; });
                                    echo htmlspecialchars(reset($desc_config)['config_value'] ?? '网站描述');
                                    ?>
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
    layui.use(['form', 'element'], function(){
        var form = layui.form;
        var element = layui.element;
        
        form.render();
        element.render();
        
        // 字符计数功能
        function setupCharCounter(inputName, counterId) {
            var input = document.querySelector('[name="config_' + inputName + '"]');
            var counter = document.getElementById('count_' + inputName);
            var preview = document.getElementById('preview-' + inputName.replace('seo_', ''));
            
            if (input && counter) {
                input.addEventListener('input', function() {
                    var length = input.value.length;
                    counter.textContent = length;
                    
                    // 更新预览
                    if (preview) {
                        preview.textContent = input.value || '未设置';
                    }
                    
                    // 根据长度给出颜色提示
                    if (inputName === 'seo_title') {
                        if (length < 30 || length > 60) {
                            counter.style.color = 'red';
                        } else {
                            counter.style.color = 'green';
                        }
                    } else if (inputName === 'seo_description') {
                        if (length < 120 || length > 160) {
                            counter.style.color = 'red';
                        } else {
                            counter.style.color = 'green';
                        }
                    }
                });
            }
        }
        
        // 为每个字段设置字符计数
        setupCharCounter('seo_title', 'count_seo_title');
        setupCharCounter('seo_description', 'count_seo_description');
        setupCharCounter('seo_keywords', 'count_seo_keywords');
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert-success, .layui-alert-danger');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    </script>
    
    <style>
    .seo-preview {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        background: #f9f9f9;
        max-width: 600px;
    }
    
    .seo-title {
        color: #1a0dab;
        font-size: 18px;
        font-weight: normal;
        margin-bottom: 5px;
        text-decoration: underline;
        cursor: pointer;
    }
    
    .seo-url {
        color: #006621;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .seo-description {
        color: #545454;
        font-size: 13px;
        line-height: 1.4;
    }
    </style>
</body>
</html>