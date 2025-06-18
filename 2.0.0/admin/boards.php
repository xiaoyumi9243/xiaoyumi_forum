<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// 处理板块状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $boardId = (int)$_POST['board_id'];
        $stmt = $db->prepare("UPDATE boards SET is_active = 0 WHERE id = ?");
        $stmt->execute([$boardId]);
        $_SESSION['success'] = "板块已禁用";
    } elseif (isset($_POST['enable'])) {
        $boardId = (int)$_POST['board_id'];
        $stmt = $db->prepare("UPDATE boards SET is_active = 1 WHERE id = ?");
        $stmt->execute([$boardId]);
        $_SESSION['success'] = "板块已启用";
    }
    redirect('boards.php');
}

$boards = $db->query("
    SELECT b.*, 
           (SELECT COUNT(*) FROM posts WHERE board_id = b.id) AS post_count,
           u.username AS creator_name
    FROM boards b
    LEFT JOIN users u ON b.created_by = u.id
    ORDER BY b.is_active DESC, b.created_at DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-container">
    <h1>板块管理</h1>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>板块名称</th>
                <th>描述</th>
                <th>帖子数</th>
                <th>创建者</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($boards as $board): ?>
            <tr>
                <td><?= $board['id'] ?></td>
                <td><?= sanitize($board['name']) ?></td>
                <td><?= sanitize($board['description']) ?></td>
                <td><?= $board['post_count'] ?></td>
                <td><?= sanitize($board['creator_name']) ?></td>
                <td><?= $board['is_active'] ? '启用' : '禁用' ?></td>
                <td class="actions">
                    <?php if($board['is_active']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="board_id" value="<?= $board['id'] ?>">
                            <button type="submit" name="delete" class="btn-delete">禁用</button>
                        </form>
                    <?php else: ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="board_id" value="<?= $board['id'] ?>">
                            <button type="submit" name="enable" class="btn-edit">启用</button>
                        </form>
                    <?php endif; ?>
                    <a href="edit_board.php?id=<?= $board['id'] ?>" class="btn-edit">编辑</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="admin-actions">
        <a href="create_board.php" class="btn-create">创建新板块</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>