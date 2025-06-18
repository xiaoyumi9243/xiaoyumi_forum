<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("请先登录");
}

$replyId = (int)($_GET['id'] ?? 0);
$error = '';

// 获取回复内容
$stmt = $db->prepare("
    SELECT r.*, p.id AS post_id
    FROM replies r
    JOIN posts p ON r.post_id = p.id
    WHERE r.id = ? AND (r.user_id = ? OR ? = 1)
");
$stmt->execute([$replyId, $_SESSION['user_id'], isAdmin() ? 1 : 0]);
$reply = $stmt->fetch();

if (!$reply) {
    die("回复不存在或您没有编辑权限");
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostFrequency();
    
    $content = trim($_POST['content'] ?? '');
    
    if (empty($content)) {
        $error = '内容不能为空';
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE replies 
                SET content = ?, edited_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$content, $replyId]);
            
            header("Location: post.php?id=".$reply['post_id']);
            exit;
        } catch (PDOException $e) {
            $error = '更新失败: ' . $e->getMessage();
        }
    }
}

$pageTitle = "编辑回复";
require_once 'includes/header.php';
?>

<div class="edit-reply-container">
    <h1>编辑回复</h1>
    
    <?php if ($error): ?>
        <div class="error-message"><?= sanitize($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="content">回复内容:</label>
            <textarea id="content" name="content" required><?= 
                isset($_POST['content']) ? sanitize($_POST['content']) : sanitize($reply['content']) 
            ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">保存更改</button>
            <a href="post.php?id=<?= $reply['post_id'] ?>" class="btn-cancel">取消</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>