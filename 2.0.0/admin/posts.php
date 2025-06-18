<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../index.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 处理帖子状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)$_POST['post_id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        $stmt = $db->prepare("UPDATE posts SET is_active = 0 WHERE id = ?");
        $stmt->execute([$postId]);
        $_SESSION['success'] = "帖子已禁用";
    } elseif ($action === 'restore') {
        $stmt = $db->prepare("UPDATE posts SET is_active = 1 WHERE id = ?");
        $stmt->execute([$postId]);
        $_SESSION['success'] = "帖子已恢复";
    } elseif ($action === 'purge') {
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $_SESSION['success'] = "帖子已永久删除";
    }
    redirect('posts.php');
}

// 获取帖子列表
$totalPosts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$posts = $db->query("
    SELECT p.*, u.username, b.name AS board_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN boards b ON p.board_id = b.id
    ORDER BY p.is_active DESC, p.created_at DESC
    LIMIT $perPage OFFSET $offset
")->fetchAll();

$pagination = [
    'total' => ceil($totalPosts / $perPage),
    'current' => $page,
    'prev' => $page > 1 ? $page - 1 : null,
    'next' => $page < ceil($totalPosts / $perPage) ? $page + 1 : null
];

require_once '../includes/header.php';
?>

<div class="admin-container">
    <h1>帖子管理</h1>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>标题</th>
                <th>作者</th>
                <th>板块</th>
                <th>状态</th>
                <th>发布时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td><?= $post['id'] ?></td>
                <td><?= excerpt(sanitize($post['title']), 30) ?></td>
                <td><?= sanitize($post['username']) ?></td>
                <td><?= sanitize($post['board_name']) ?></td>
                <td><?= $post['is_active'] ? '启用' : '禁用' ?></td>
                <td><?= $post['created_at'] ?></td>
                <td class="actions">
                    <?php if($post['is_active']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-delete">禁用</button>
                        </form>
                    <?php else: ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="action" value="restore">
                            <button type="submit" class="btn-edit">恢复</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="action" value="purge">
                            <button type="submit" class="btn-delete" 
                                onclick="return confirm('永久删除此帖子？此操作不可撤销！')">删除</button>
                        </form>
                    <?php endif; ?>
                    <a href="../post.php?id=<?= $post['id'] ?>" class="btn-view">查看</a>
                    <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn-edit">编辑</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- 分页 -->
    <div class="pagination">
        <?php if ($pagination['prev']): ?>
            <a href="?page=<?= $pagination['prev'] ?>">上一页</a>
        <?php endif; ?>
        
        <span>第 <?= $pagination['current'] ?> 页 / 共 <?= $pagination['total'] ?> 页</span>
        
        <?php if ($pagination['next']): ?>
            <a href="?page=<?= $pagination['next'] ?>">下一页</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>