<?php
require 'config.php';

if(isset($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            redirect('index.php');
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>登录</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>登录</h1>
        <nav>
            <a href="index.php">返回首页</a>
        </nav>
    </header>

    <main>
        <?php if($error): ?>
            <div class="error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div>
                <label for="username">用户名:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">密码:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">登录</button>
        </form>
        
        <p>还没有账号? <a href="register.php">立即注册</a></p>
    </main>
</body>
</html>