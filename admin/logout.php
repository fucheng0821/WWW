<?php
session_start();
require_once '../includes/config.php';

// 清除所有会话数据
$_SESSION = array();

// 如果是通过cookie传递会话ID，则删除cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到登录页面
header('Location: login.php');
exit();
?>