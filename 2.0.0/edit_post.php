<?php
require_once 'includes/config.php';

// 验证登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$postId = (int)($_GET['id'] ?? 0);
$error = '';

// 获取原帖子内容
$stmt = $db->prepare("
    SELECT p.*, b.id AS board_id
    FROM posts p
    JOIN boards b ON p.board_id = b.id
    WHERE p.id = ? AND (p.user_id = ? OR ? = 1)
");
$stmt->execute([$postId, $_SESSION['user_id'], isAdmin() ? 1 : 0]);
$post = $stmt->fetch();

if (!$post) {
    die("帖子不存在或您没有编辑权限");
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostFrequency();
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // 验证输入
    if (empty($title)) {
        $error = '标题不能为空';
    } elseif (strlen($title) > 200) {
        $error = '标题不能超过200个字符';
    } elseif (empty($content)) {
        $error = '内容不能为空';
    } else {
        try {
            // 更新帖子 - 如果是管理员编辑则标记
            if (isAdmin()) {
                $stmt = $db->prepare("
                    UPDATE posts 
                    SET title = ?, content = ?, 
                        admin_edited = 1, 
                        last_edited_by = ?,
                        last_edited_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $content, $_SESSION['user_id'], $postId]);
            } else {
                $stmt = $db->prepare("
                    UPDATE posts 
                    SET title = ?, content = ?,
                        last_edited_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $content, $postId]);
            }
            
            header("Location: post.php?id=$postId");
            exit;
        } catch (PDOException $e) {
            $error = '更新失败: ' . $e->getMessage();
        }
    }
}

$pageTitle = "编辑帖子";
require_once 'includes/header.php';
?>

<div class="edit-post-container">
    <h1>编辑帖子</h1>
    
    <?php if ($error): ?>
        <div class="error-message"><?= sanitize($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="title">标题:</label>
            <input type="text" id="title" name="title" 
                   value="<?= isset($_POST['title']) ? sanitize($_POST['title']) : sanitize($post['title']) ?>" 
                   required maxlength="200">
        </div>
        
        <div class="form-group">
            <label for="content">内容:</label>
            <textarea id="content" name="content" required><?= 
                isset($_POST['content']) ? sanitize($_POST['content']) : sanitize($post['content']) 
            ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">保存更改</button>
            <a href="post.php?id=<?= $postId ?>" class="btn-cancel">取消</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>