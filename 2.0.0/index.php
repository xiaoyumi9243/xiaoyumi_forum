<?php
require_once 'includes/config.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 获取板块和最新帖子
$boards = $db->query("
    SELECT b.*, 
           (SELECT COUNT(*) FROM posts WHERE board_id = b.id) AS post_count,
           (SELECT p.title FROM posts p WHERE p.board_id = b.id ORDER BY p.created_at DESC LIMIT 1) AS latest_post_title,
           (SELECT p.created_at FROM posts p WHERE p.board_id = b.id ORDER BY p.created_at DESC LIMIT 1) AS latest_post_time
    FROM boards b
    WHERE b.is_active = 1
    ORDER BY b.created_at DESC
")->fetchAll();

// 获取最新帖子
$latestPosts = $db->query("
    SELECT p.id, p.title, p.created_at, u.username, b.name AS board_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN boards b ON p.board_id = b.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = '首页';
require_once 'includes/header.php';
?>

<section class="forum-overview">
    <div class="boards-section">
        <h2>论坛板块</h2>
        <?php if(empty($boards)): ?>
            <p>暂无板块</p>
        <?php else: ?>
            <table class="boards-table">
                <thead>
                    <tr>
                        <th>板块名称</th>
                        <th>描述</th>
                        <th>帖子数</th>
                        <th>最新帖子</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($boards as $board): ?>
                    <tr>
                        <td>
                            <a href="board.php?id=<?= $board['id'] ?>">
                                <?= sanitize($board['name']) ?>
                            </a>
                        </td>
                        <td><?= sanitize($board['description']) ?></td>
                        <td><?= $board['post_count'] ?></td>
                        <td>
                            <?php if($board['latest_post_title']): ?>
                                <a href="board.php?id=<?= $board['id'] ?>">
                                    <?= excerpt(sanitize($board['latest_post_title']), 30) ?>
                                </a>
                                <br>
                                <small><?= $board['latest_post_time'] ?></small>
                            <?php else: ?>
                                暂无帖子
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="latest-posts">
        <h2>最新帖子</h2>
        <?php if(empty($latestPosts)): ?>
            <p>暂无帖子</p>
        <?php else: ?>
            <ul class="posts-list">
                <?php foreach($latestPosts as $post): ?>
                <li>
                    <a href="post.php?id=<?= $post['id'] ?>">
                        <?= sanitize($post['title']) ?>
                    </a>
                    <div class="post-meta">
                        <span>作者: <?= sanitize($post['username']) ?></span>
                        <span>板块: <?= sanitize($post['board_name']) ?></span>
                        <span>时间: <?= $post['created_at'] ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>