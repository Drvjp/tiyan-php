<?php
// logout.php
session_start();
$_SESSION = array(); // 清空所有会话变量
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy(); // 销毁会话
header('Location: index.php'); // 重定向到首页
exit();
?>