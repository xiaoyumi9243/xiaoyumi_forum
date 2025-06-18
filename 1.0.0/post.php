<?php
require 'config.php';

$post_id = (int)($_GET['id'] ?? 0);

// 获取帖子内容
$stmt = $db->prepare("
    SELECT p.*, u.username, b.name AS board_name 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN boards b ON p.board_id = b.id
    WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: index.php?error=帖子不存在");
    exit;
}

// 获取回复（按时间正序排列）
$replies = $db->query("
    SELECT r.*, u.username 
    FROM replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.post_id = $post_id
    ORDER BY r.created_at ASC
")->fetchAll();

// 显示错误信息（如果有）
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= sanitize($post['title']) ?> - 轻量论坛</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?= sanitize($post['title']) ?></h1>
        <nav>
            <a href="board.php?id=<?= $post['board_id'] ?>">返回板块</a>
        </nav>
    </header>

    <main>
        <!-- 帖子内容 -->
        <article class="post">
            <div class="post-meta">
                <span>作者: <?= sanitize($post['username']) ?></span>
                <span>发布于: <?= $post['created_at'] ?></span>
            </div>
            <div class="post-content">
                <?= nl2br(sanitize($post['content'])) ?>
            </div>
        </article>

        <!-- 错误提示 -->
        <?php if ($error): ?>
            <div class="error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <!-- 回复表单 -->
        <section class="reply-form">
            <h2>发表回复</h2>
            <form method="post" action="reply.php">
                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                <textarea name="content" required placeholder="输入回复内容..."></textarea>
                <button type="submit">提交回复</button>
            </form>
        </section>

        <!-- 回复列表 -->
        <section class="replies">
            <h2>回复 (<?= count($replies) ?>)</h2>
            
            <?php if (empty($replies)): ?>
                <p>暂无回复</p>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="reply">
                        <div class="reply-meta">
                            <span><?= sanitize($reply['username']) ?></span>
                            <span><?= $reply['created_at'] ?></span>
                        </div>
                        <div class="reply-content">
                            <?= nl2br(sanitize($reply['content'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>