<?php
// login.php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    // *** 请务必更改此密码 ***
    $ADMIN_PASSWORD = '123456';

    if ($password === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        // 重定向到管理员页面
        header('Location: admin.php');
        exit();
    } else {
        $error = '密码错误，请重试！';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
    <style>
        /* 简单的登录页面样式 */
        body { font-family: Arial, sans-serif; background: #f0f0f0; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        h2 { margin-bottom: 1rem; }
        input[type="password"] { width: 100%; padding: 0.5rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background-color: #0056b3; }
        .error { color: red; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>管理员登录</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="password" name="password" placeholder="请输入管理员密码" required>
            <button type="submit">登录</button>
        </form>
         <p><a href="index.php">返回首页</a></p>
    </div>
</body>
</html>