<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../index.php');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 处理用户状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'ban') {
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = "用户已禁用";
    } elseif ($action === 'unban') {
        $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = "用户已解禁";
    } elseif ($action === 'delete') {
        // 先删除用户的内容
        $db->prepare("UPDATE posts SET user_id = NULL WHERE user_id = ?")->execute([$userId]);
        $db->prepare("UPDATE replies SET user_id = NULL WHERE user_id = ?")->execute([$userId]);
        
        // 然后删除用户
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = "用户已永久删除";
    }
    redirect('users.php');
}

// 获取用户列表
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$users = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS post_count,
           (SELECT COUNT(*) FROM replies WHERE user_id = u.id) AS reply_count
    FROM users u
    ORDER BY u.is_active DESC, u.created_at DESC
    LIMIT $perPage OFFSET $offset
")->fetchAll();

$pagination = [
    'total' => ceil($totalUsers / $perPage),
    'current' => $page,
    'prev' => $page > 1 ? $page - 1 : null,
    'next' => $page < ceil($totalUsers / $perPage) ? $page + 1 : null
];

require_once '../includes/header.php';
?>

<div class="admin-container">
    <h1>用户管理</h1>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>帖子数</th>
                <th>回复数</th>
                <th>注册时间</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= sanitize($user['username']) ?></td>
                <td><?= $user['post_count'] ?></td>
                <td><?= $user['reply_count'] ?></td>
                <td><?= $user['created_at'] ?></td>
                <td><?= $user['is_active'] ? '正常' : '禁用' ?></td>
                <td class="actions">
                    <?php if($user['is_active']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="ban">
                            <button type="submit" class="btn-delete">禁用</button>
                        </form>
                    <?php else: ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="unban">
                            <button type="submit" class="btn-edit">解禁</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-delete" 
                                onclick="return confirm('永久删除此用户及其所有内容？此操作不可撤销！')">删除</button>
                        </form>
                    <?php endif; ?>
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