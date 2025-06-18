<?php
require_once 'includes/config.php';

$boardId = (int)($_GET['id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 获取板块信息
$stmt = $db->prepare("
    SELECT b.*
    FROM boards b
    WHERE b.id = ? AND b.is_active = 1
");
$stmt->execute([$boardId]);
$board = $stmt->fetch();

if (!$board) {
    header("Location: index.php?error=板块不存在或已被禁用");
    exit;
}

// 获取帖子总数
$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM posts 
    WHERE board_id = ? AND is_active = 1
");
$stmt->execute([$boardId]);
$totalPosts = $stmt->fetchColumn();

// 获取帖子列表
$stmt = $db->prepare("
    SELECT p.*, u.username, 
           (SELECT COUNT(*) FROM replies WHERE post_id = p.id) AS reply_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.board_id = ? AND p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $boardId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pagination = [
    'total' => ceil($totalPosts / $perPage),
    'current' => $page,
    'prev' => $page > 1 ? $page - 1 : null,
    'next' => $page < ceil($totalPosts / $perPage) ? $page + 1 : null
];

$pageTitle = sanitize($board['name']);
require_once 'includes/header.php';
?>

<div class="board-container">
    <div class="board-header">
        <h1><?= sanitize($board['name']) ?></h1>
        <p class="board-description"><?= sanitize($board['description']) ?></p>
        <p class="board-meta">
            帖子数: <?= $totalPosts ?>
        </p>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if(isFeatureEnabled('post_creation')): ?>
                <a href="create_post.php?board_id=<?= $boardId ?>" class="btn btn-primary">发表新帖</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="posts-list">
        <?php if(empty($posts)): ?>
            <div class="alert alert-info">该板块暂无帖子</div>
        <?php else: ?>
            <?php foreach($posts as $post): ?>
            <div class="post-card">
                <h3 class="post-title">
                    <a href="post.php?id=<?= $post['id'] ?>">
                        <?= sanitize($post['title']) ?>
                    </a>
                </h3>
                <div class="post-meta">
                    <span class="post-author">作者: <?= sanitize($post['username']) ?></span>
                    <span class="post-replies">回复数: <?= $post['reply_count'] ?></span>
                    <span class="post-date">发布时间: <?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                </div>
                <?php if(!empty($post['admin_edited'])): ?>
                    <div class="post-admin-edited">
                        <span class="badge badge-warning">管理员已编辑</span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if($pagination['total'] > 1): ?>
    <div class="pagination">
        <?php if ($pagination['prev']): ?>
            <a href="?id=<?= $boardId ?>&page=<?= $pagination['prev'] ?>" class="page-link">上一页</a>
        <?php endif; ?>
        
        <span class="page-info">
            第 <?= $pagination['current'] ?> 页 / 共 <?= $pagination['total'] ?> 页
        </span>
        
        <?php if ($pagination['next']): ?>
            <a href="?id=<?= $boardId ?>&page=<?= $pagination['next'] ?>" class="page-link">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
