<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$errors = [];
$success = '';

// 获取Banner ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

// 获取Banner信息
try {
    $stmt = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    
    if (!$banner) {
        header('Location: index.php?error=banner_not_found');
        exit();
    }
} catch(PDOException $e) {
    header('Location: index.php?error=database_error');
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $banner_type = $_POST['banner_type'] ?? 'home';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 验证输入
    if (empty($title)) {
        $errors[] = 'Banner标题不能为空';
    }
    
    if (empty($image_url)) {
        $errors[] = '请上传Banner图片';
    }
    
    // 如果没有错误，更新数据
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE banners 
                SET title = ?, subtitle = ?, image_url = ?, link_url = ?, banner_type = ?, sort_order = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $subtitle, $image_url, $link_url, $banner_type, $sort_order, $is_active, $id]);
            
            // 重定向到列表页并显示成功消息
            header('Location: index.php?success=updated');
            exit();
        } catch(PDOException $e) {
            $errors[] = '更新失败：' . $e->getMessage();
        }
    }
}

// 如果是GET请求且没有POST数据，则使用数据库中的数据
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_POST = [
        'title' => $banner['title'],
        'subtitle' => $banner['subtitle'],
        'image_url' => $banner['image_url'],
        'link_url' => $banner['link_url'],
        'banner_type' => $banner['banner_type'],
        'sort_order' => $banner['sort_order'],
        'is_active' => $banner['is_active']
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑Banner - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <script src="../../assets/js/admin-utils.js"></script>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php 
        $header_path = '../../includes/header.php';
        $sidebar_path = '../../includes/sidebar.php';
        if (file_exists($header_path)) {
            include $header_path;
        }
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        }
        ?>
        
        <div class="layui-body">
            <div class="layui-card">
                <div class="layui-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h2>编辑Banner</h2>
                        <a href="index.php" class="layui-btn layui-btn-primary">
                            <i class="layui-icon layui-icon-return"></i> 返回列表
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
                    
                    <form class="layui-form" method="POST">
                        <div class="layui-form-item">
                            <label class="layui-form-label">Banner标题 *</label>
                            <div class="layui-input-block">
                                <input type="text" name="title" placeholder="请输入Banner标题" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? $banner['title']); ?>" 
                                       class="layui-input" required>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">Banner副标题</label>
                            <div class="layui-input-block">
                                <input type="text" name="subtitle" placeholder="请输入Banner副标题" 
                                       value="<?php echo htmlspecialchars($_POST['subtitle'] ?? $banner['subtitle'] ?? ''); ?>" 
                                       class="layui-input">
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">Banner图片 *</label>
                            <div class="layui-input-block">
                                <input type="text" 
                                       name="image_url" 
                                       id="image_url"
                                       placeholder="请输入图片URL或上传图片" 
                                       value="<?php echo htmlspecialchars($_POST['image_url'] ?? $banner['image_url']); ?>" 
                                       class="layui-input" 
                                       required>
                                <div class="layui-form-mid layui-word-aux">
                                    建议尺寸: 1920x600px，支持 JPG/PNG 格式
                                </div>
                                <!-- 添加上传按钮 -->
                                <div style="margin-top: 10px;">
                                    <button type="button" class="layui-btn layui-btn-primary upload-btn">
                                        <i class="layui-icon layui-icon-upload"></i> 上传图片
                                    </button>
                                </div>
                                <!-- 实时预览区域 -->
                                <div class="image-preview" id="preview_image_url" style="margin-top: 10px;<?php echo empty($_POST['image_url'] ?? $banner['image_url']) ? 'display:none;' : ''; ?>">
                                    <?php if (!empty($_POST['image_url'] ?? $banner['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($_POST['image_url'] ?? $banner['image_url']); ?>" style="max-width: 300px; max-height: 150px; border: 1px solid #eee; padding: 5px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">链接地址</label>
                            <div class="layui-input-block">
                                <input type="text" name="link_url" placeholder="请输入链接地址，如：https://example.com" 
                                       value="<?php echo htmlspecialchars($_POST['link_url'] ?? $banner['link_url'] ?? ''); ?>" 
                                       class="layui-input">
                                <div class="layui-form-mid layui-word-aux">点击Banner后跳转的链接地址，留空则不跳转</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">Banner类型</label>
                            <div class="layui-input-block">
                                <select name="banner_type">
                                    <option value="home" <?php echo ($_POST['banner_type'] ?? $banner['banner_type']) === 'home' ? 'selected' : ''; ?>>首页Banner</option>
                                    <option value="inner" <?php echo ($_POST['banner_type'] ?? $banner['banner_type']) === 'inner' ? 'selected' : ''; ?>>内页Banner</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">排序</label>
                            <div class="layui-input-block">
                                <input type="number" name="sort_order" placeholder="请输入排序数字" 
                                       value="<?php echo htmlspecialchars($_POST['sort_order'] ?? $banner['sort_order']); ?>" 
                                       class="layui-input">
                                <div class="layui-form-mid layui-word-aux">数字越小越靠前</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">是否启用</label>
                            <div class="layui-input-block">
                                <input type="checkbox" name="is_active" lay-skin="switch" lay-text="启用|禁用" <?php echo (!isset($_POST['is_active']) && $banner['is_active']) || (isset($_POST['is_active']) && $_POST['is_active']) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn layui-btn-normal">更新Banner</button>
                                <a href="index.php" class="layui-btn layui-btn-primary">取消</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['form', 'upload'], function(){
        var form = layui.form;
        var upload = layui.upload;
        
        form.render();
        
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
                    // 更新对应输入框的值
                    document.getElementById('image_url').value = res.location;
                    
                    // 更新预览区域
                    var previewContainer = document.getElementById('preview_image_url');
                    var previewImage = previewContainer.querySelector('img');
                    if (previewImage) {
                        previewImage.src = res.location;
                    } else {
                        previewContainer.innerHTML = '<img src="' + res.location + '" style="max-width: 300px; max-height: 150px; border: 1px solid #eee; padding: 5px;">';
                    }
                    previewContainer.style.display = 'block';
                    
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
        
        // 自动隐藏提示消息
        setTimeout(function() {
            var alerts = document.querySelectorAll('.layui-alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    </script>
</body>
</html>