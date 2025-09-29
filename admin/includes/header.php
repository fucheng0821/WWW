<div class="layui-header">
    <div class="layui-logo layui-hide-xs layui-bg-black">
        <i class="layui-icon layui-icon-website"></i> 高光视刻管理后台
    </div>
    
    <ul class="layui-nav layui-layout-left">
        <li class="layui-nav-item layui-show-xs-inline-block layui-hide-sm" lay-header-event="menuLeft">
            <i class="layui-icon layui-icon-spread-left"></i>
        </li>
    </ul>
    
    <ul class="layui-nav layui-layout-right">
        <li class="layui-nav-item layui-hide layui-show-md-inline-block">
            <a href="<?php echo SITE_URL; ?>" target="_blank" title="访问前台">
                <i class="layui-icon layui-icon-website"></i> 前台首页
            </a>
        </li>
        
        <!-- 通知铃铛 -->
        <li class="layui-nav-item" lay-unselect>
            <a href="javascript:;" id="noticeBtn" title="查看通知">
                <i class="layui-icon layui-icon-notice"></i>
                <span class="layui-badge-dot" id="noticeDot" style="display: none;"></span>
            </a>
            <dl class="layui-nav-child layui-anim layui-anim-upbit" style="width: 300px;">
                <dd class="layui-nav-header">通知中心</dd>
                <dd><a href="/admin/modules/inquiry/" id="pendingInquiry">待处理询价 (<span id="pendingCount">0</span>)</a></dd>
                <dd><a href="/admin/modules/content/" id="draftContent">草稿内容 (<span id="draftCount">0</span>)</a></dd>
                <dd><a href="/admin/modules/system/backup.php" id="backupReminder">数据备份提醒</a></dd>
                <dd class="layui-nav-header">快捷操作</dd>
                <dd><a href="/admin/modules/content/add.php"><i class="layui-icon layui-icon-add-1"></i> 添加内容</a></dd>
                <dd><a href="/admin/modules/inquiry/"><i class="layui-icon layui-icon-survey"></i> 查看询价</a></dd>
            </dl>
        </li>
        
        <!-- 用户菜单 -->
        <li class="layui-nav-item">
            <a href="javascript:;" title="用户菜单">
                <div class="admin-avatar">
                    <i class="layui-icon layui-icon-username"></i>
                </div>
                <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '管理员'); ?></span>
            </a>
            <dl class="layui-nav-child">
                <dd class="layui-nav-header">账户管理</dd>
                <dd><a href="/admin/modules/system/profile.php"><i class="layui-icon layui-icon-username"></i> 基本资料</a></dd>
                <dd><a href="/admin/modules/system/"><i class="layui-icon layui-icon-set"></i> 系统设置</a></dd>
                <dd class="layui-nav-header">会话管理</dd>
                <dd><a href="/admin/logout.php"><i class="layui-icon layui-icon-logout"></i> 安全退出</a></dd>
            </dl>
        </li>
    </ul>
</div>

<!-- 通知系统将在主页面的layui加载后初始化 -->