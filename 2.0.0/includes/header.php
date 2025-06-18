<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>轻量论坛</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <h1><a href="index.php">轻量论坛</a></h1>
            <nav class="main-nav">
                <?php if(isset($_SESSION['user'])): ?>
                    <span>欢迎, <?= sanitize($_SESSION['user']) ?></span>
                    <?php if(isAdmin()): ?>
                        <a href="admin/dashboard.php">管理面板</a>
                    <?php endif; ?>
                    <a href="logout.php">登出</a>
                <?php else: ?>
                    <a href="login.php">登录</a>
                    <?php if(isFeatureEnabled('user_registration')): ?>
                        <a href="register.php">注册</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if(isFeatureEnabled('search')): ?>
                    <form action="search.php" method="get" class="search-form">
                        <input type="text" name="q" placeholder="搜索..." required>
                        <button type="submit">搜索</button>
                        <a href="create_post.php?board_id=<?= $boardId ?>" class="btn btn-primary">发表新帖</a>
                    </form>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="content-container">