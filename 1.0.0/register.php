<?php
require 'config.php';

if(isset($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if(empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } elseif($password !== $confirm) {
        $error = '两次输入的密码不一致';
    } elseif(strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度需在3-20个字符之间';
    } elseif(strlen($password) < 6) {
        $error = '密码长度至少6个字符';
    } else {
        // 检查用户名是否已存在
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if($stmt->fetch()) {
            $error = '用户名已存在';
        } else {
            // 创建用户
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if($stmt->execute([$username, $hashed])) {
                $_SESSION['user'] = $username;
                $_SESSION['user_id'] = $db->lastInsertId();
                redirect('index.php');
            } else {
                $error = '注册失败，请稍后再试';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>注册</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>注册新账号</h1>
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
                <label for="username">用户名 (3-20字符):</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="20">
            </div>
            <div>
                <label for="password">密码 (至少6字符):</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div>
                <label for="confirm_password">确认密码:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit">注册</button>
        </form>
        
        <p>已有账号? <a href="login.php">立即登录</a></p>
    </main>
</body>
</html>