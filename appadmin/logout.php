<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 销毁所有会话数据
$_SESSION = array();

// 如果使用基于cookie的会话，删除会话cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 最终销毁会话
session_destroy();

// 重定向到登录页面
redirect('login.php');
?>