<?php
session_start();
require_once '../../../includes/config.php';
require_once '../../../includes/database.php';
require_once '../../../includes/functions.php';

check_admin_auth();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

try {
    // 获取模板详情
    $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    
    if (!$template) {
        header('Location: index.php?error=template_not_found');
        exit();
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=' . urlencode($e->getMessage()));
    exit();
}

$type_map = [
    'index' => '首页模板',
    'channel' => '频道模板',
    'list' => '列表模板',
    'content' => '内容模板'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>模板详情 - 高光视刻</title>
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
                        <h2>模板详情 #<?php echo $template['id']; ?></h2>
                        <div>
                            <a href="edit.php?id=<?php echo $template['id']; ?>" class="layui-btn layui-btn-normal">
                                <i class="layui-icon layui-icon-edit"></i> 编辑模板
                            </a>
                            <a href="index.php" class="layui-btn layui-btn-primary">
                                <i class="layui-icon layui-icon-return"></i> 返回列表
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="layui-card-body">
                    <div class="layui-row layui-col-space20">
                        <!-- 左侧基本信息 -->
                        <div class="layui-col-md4">
                            <div class="info-section">
                                <h3>基本信息</h3>
                                <table class="layui-table">
                                    <tbody>
                                        <tr>
                                            <td width="80"><strong>模板名称</strong></td>
                                            <td><?php echo htmlspecialchars($template['name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>模板类型</strong></td>
                                            <td>
                                                <span class="layui-badge layui-bg-blue">
                                                    <?php echo $type_map[$template['template_type']] ?? $template['template_type']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>文件路径</strong></td>
                                            <td><code><?php echo htmlspecialchars($template['file_path']); ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>状态</strong></td>
                                            <td>
                                                <?php if ($template['is_active']): ?>
                                                    <span class="status-badge status-completed">启用</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">禁用</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>默认模板</strong></td>
                                            <td>
                                                <?php if ($template['is_default']): ?>
                                                    <span class="layui-badge layui-bg-orange">是</span>
                                                <?php else: ?>
                                                    <span class="layui-badge">否</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>排序</strong></td>
                                            <td><?php echo $template['sort_order']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>创建时间</strong></td>
                                            <td><?php echo $template['created_at']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>更新时间</strong></td>
                                            <td><?php echo $template['updated_at']; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- 快速操作 -->
                            <div class="status-panel">
                                <h3>快速操作</h3>
                                
                                <?php if (!$template['is_active']): ?>
                                    <button class="layui-btn layui-btn-normal layui-btn-sm layui-btn-fluid" 
                                            onclick="updateStatus(1)" style="margin-bottom: 10px;">
                                        启用模板
                                    </button>
                                <?php else: ?>
                                    <button class="layui-btn layui-btn-warm layui-btn-sm layui-btn-fluid" 
                                            onclick="updateStatus(0)" style="margin-bottom: 10px;">
                                        禁用模板
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!$template['is_default']): ?>
                                    <button class="layui-btn layui-btn-normal layui-btn-sm layui-btn-fluid" 
                                            onclick="setDefault()" style="margin-bottom: 10px;">
                                        设为默认
                                    </button>
                                <?php endif; ?>
                                
                                <a href="edit.php?id=<?php echo $template['id']; ?>" 
                                   class="layui-btn layui-btn-primary layui-btn-sm layui-btn-fluid">
                                    编辑模板
                                </a>
                            </div>
                        </div>
                        
                        <!-- 右侧详细信息 -->
                        <div class="layui-col-md8">
                            <!-- 模板描述 -->
                            <?php if ($template['description']): ?>
                            <div class="info-section">
                                <h3>模板描述</h3>
                                <div class="content-box">
                                    <p><?php echo nl2br(htmlspecialchars($template['description'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 模板变量 -->
                            <?php if ($template['variables']): ?>
                            <div class="info-section">
                                <h3>模板变量</h3>
                                <div class="content-box">
                                    <?php
                                    $variables = explode(',', $template['variables']);
                                    foreach ($variables as $var) {
                                        $var = trim($var);
                                        if ($var) {
                                            echo '<span class="layui-badge layui-bg-green" style="margin: 2px;">' . htmlspecialchars($var) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 模板代码 -->
                            <div class="info-section">
                                <h3>模板代码</h3>
                                <div class="content-box">
                                    <?php if ($template['template_content']): ?>
                                        <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.4;"><code><?php echo htmlspecialchars($template['template_content']); ?></code></pre>
                                    <?php else: ?>
                                        <p class="text-muted">暂无模板代码内容</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
        
        element.render();
    });
    
    function updateStatus(status) {
        var statusText = status ? '启用' : '禁用';
        
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要' + statusText + '这个模板吗？', {
                icon: 3,
                title: '状态更改确认'
            }, function(index){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'update_status.php';
                
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?php echo $template['id']; ?>';
                form.appendChild(idInput);
                
                var statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'is_active';
                statusInput.value = status;
                form.appendChild(statusInput);
                
                document.body.appendChild(form);
                form.submit();
                
                layer.close(index);
            });
        });
    }
    
    function setDefault() {
        layui.use('layer', function(){
            var layer = layui.layer;
            
            layer.confirm('确定要将此模板设为默认模板吗？', {
                icon: 3,
                title: '设置默认模板'
            }, function(index){
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'set_default.php';
                
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?php echo $template['id']; ?>';
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
                
                layer.close(index);
            });
        });
    }
    </script>
</body>
</html>