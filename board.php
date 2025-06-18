<?php
require 'config.php';

$boardId = $_GET['id'] ?? 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

// 获取板块信息
$stmt = $db->prepare("SELECT * FROM boards WHERE id = ?");
$stmt->execute([$boardId]);
$board = $stmt->fetch();

if(!$board) {
    die("板块不存在");
}

// 获取帖子列表
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT p.*, u.username 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.board_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $boardId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// 计算总页数
$stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE board_id = ?");
$stmt->execute([$boardId]);
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= sanitize($board['name']) ?> - 轻量论坛</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?= sanitize($board['name']) ?></h1>
        <nav>
            <a href="index.php">返回首页</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="create_post.php?board_id=<?= $boardId ?>">发帖</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <p><?= sanitize($board['description']) ?></p>
        
        <div class="posts">
            <?php if(empty($posts)): ?>
                <p>该板块暂无帖子</p>
            <?php else: ?>
                <?php foreach($posts as $post): ?>
                    <div class="post">
                        <h3>
                            <a href="post.php?id=<?= $post['id'] ?>">
                                <?= sanitize($post['title']) ?>
                            </a>
                        </h3>
                        <small>
                            作者: <?= sanitize($post['username']) ?> | 
                            时间: <?= $post['created_at'] ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="board.php?id=<?= $boardId ?>&page=<?= $page-1 ?>">上一页</a>
                <?php endif; ?>
                
                <span>第 <?= $page ?> 页 / 共 <?= $totalPages ?> 页</span>
                
                <?php if($page < $totalPages): ?>
                    <a href="board.php?id=<?= $boardId ?>&page=<?= $page+1 ?>">下一页</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>