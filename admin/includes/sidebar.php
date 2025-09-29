<div class="layui-side layui-bg-black">
    <div class="layui-side-scroll">
        <ul class="layui-nav layui-nav-tree" lay-filter="side" lay-shrink="all">
            <li class="layui-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/admin' ? 'layui-this' : ''; ?>">
                <a href="<?php echo ADMIN_URL; ?>/index.php">
                    <i class="layui-icon layui-icon-home"></i> 控制台
                </a>
            </li>
            
            <li class="layui-nav-item">
                <a href="javascript:;">
                    <i class="layui-icon layui-icon-template-1"></i> 栏目管理
                </a>
                <dl class="layui-nav-child">
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/category/">栏目列表</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/category/add.php">添加栏目</a></dd>
                </dl>
            </li>
            
            <li class="layui-nav-item">
                <a href="javascript:;">
                    <i class="layui-icon layui-icon-file"></i> 内容管理
                </a>
                <dl class="layui-nav-child">
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/content/">内容列表</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/content/add.php">添加内容</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/content/ai_optimization_settings.php">AI优化设置</a></dd>
                </dl>
            </li>
            
            <li class="layui-nav-item">
                <a href="javascript:;">
                    <i class="layui-icon layui-icon-survey"></i> 询价管理
                </a>
                <dl class="layui-nav-child">
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/inquiry/">询价列表</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/inquiry/pending.php">待处理询价</a></dd>
                </dl>
            </li>
            
            <li class="layui-nav-item">
                <a href="javascript:;">
                    <i class="layui-icon layui-icon-template"></i> 模板管理
                </a>
                <dl class="layui-nav-child">
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/template/">模板列表</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/template/edit.php">编辑模板</a></dd>
                </dl>
            </li>
            
            <li class="layui-nav-item">
                <a href="javascript:;">
                    <i class="layui-icon layui-icon-set"></i> 系统设置
                </a>
                <dl class="layui-nav-child">
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/">基本设置</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/basic.php">网站设置</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/seo.php">SEO设置</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/contact.php">联系方式</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/mail.php">邮件设置</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/init_config.php">初始化配置</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/platform_config.php">平台配置管理</a></dd>
                    <dd><a href="<?php echo ADMIN_URL; ?>/modules/system/admin.php">管理员管理</a></dd>
                </dl>
            </li>
        </ul>
    </div>
</div>