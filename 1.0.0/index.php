<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>轻量论坛</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>轻量论坛</h1>
        <nav>
            <?php if(isset($_SESSION['user'])): ?>
                <span>欢迎, <?= sanitize($_SESSION['user']) ?></span>
                <?php if(isAdmin()): ?>
                    <a href="create_board.php">创建板块</a>
                <?php endif; ?>
                <a href="logout.php">登出</a>
            <?php else: ?>
                <a href="login.php">登录</a>
                <a href="register.php">注册</a>
            <?php endif; ?>
            <a href="terms.php">用户条款</a>
        </nav>
    </header>

    <main>
        <h2>板块列表</h2>
        <?php
        $stmt = $db->query("SELECT * FROM boards ORDER BY created_at DESC");
        while($board = $stmt->fetch()):
        ?>
            <div class="board">
                <h3>
                    <a href="board.php?id=<?= $board['id'] ?>">
                        <?= sanitize($board['name']) ?>
                    </a>
                </h3>
                <p><?= sanitize($board['description']) ?></p>
                <small>创建时间: <?= $board['created_at'] ?></small>
            </div>
        <?php endwhile; ?>
        
        <?php if($stmt->rowCount() === 0): ?>
            <p>暂无板块</p>
        <?php endif; ?>
    </main>
</body>
</html>