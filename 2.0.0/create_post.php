<?php
require 'config.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$boardId = $_GET['board_id'] ?? 0;
$error = '';

// 验证板块是否存在
$stmt = $db->prepare("SELECT id, name FROM boards WHERE id = ?");
$stmt->execute([$boardId]);
$board = $stmt->fetch();

if (!$board) {
    die("无效的板块ID");
}

// 处理发帖表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkPostFrequency(); // 防止频繁发帖
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // 输入验证
    if (empty($title)) {
        $error = '标题不能为空';
    } elseif (strlen($title) > 200) {
        $error = '标题不能超过200个字符';
    } elseif (empty($content)) {
        $error = '内容不能为空';
    } else {
        try {
            // 插入新帖子
            $stmt = $db->prepare("
                INSERT INTO posts (title, content, user_id, board_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $content,
                $_SESSION['user_id'],
                $boardId
            ]);
            
            // 跳转到新创建的帖子
            $postId = $db->lastInsertId();
            header("Location: post.php?id=$postId");
            exit;
        } catch (PDOException $e) {
            $error = '发帖失败: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>在 <?= sanitize($board['name']) ?> 发帖</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>在 <?= sanitize($board['name']) ?> 发帖</h1>
        <nav>
            <a href="board.php?id=<?= $boardId ?>">返回板块</a>
        </nav>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="post" action="save_post.php">
<input type="hidden" name="board_id" value="<?= $boardId ?>">
            <div>
                <label for="title">标题* (最多200字符):</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    required 
                    maxlength="200"
                    value="<?= isset($_POST['title']) ? sanitize($_POST['title']) : '' ?>"
                >
            </div>
            <div>
                <label for="content">内容*:</label>
                <textarea 
                    id="content" 
                    name="content" 
                    required
                    rows="10"
                ><?= isset($_POST['content']) ? sanitize($_POST['content']) : '' ?></textarea>
            </div>
            <button type="submit">发表帖子</button>
        </form>
    </main>
</body>
</html>