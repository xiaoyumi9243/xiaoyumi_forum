<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("请先登录");
}

checkPostFrequency();

$postId = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$quotedReplyId = null;

// 验证帖子是否存在
$stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND is_active = 1");
$stmt->execute([$postId]);
if (!$stmt->fetch()) {
    die("帖子不存在或已被删除");
}

// 处理引用回复
if (!empty($_POST['quoted_reply'])) {
    $quotedContent = trim($_POST['quoted_reply']);
    $stmt = $db->prepare("
        SELECT r.id 
        FROM replies r
        JOIN users u ON r.user_id = u.id
        WHERE r.post_id = ? AND r.content LIKE ?
        ORDER BY r.id DESC
        LIMIT 1
    ");
    $stmt->execute([$postId, "%".substr($quotedContent, 0, 50)."%"]);
    $quotedReply = $stmt->fetch();
    $quotedReplyId = $quotedReply ? $quotedReply['id'] : null;
}

// 插入回复
try {
    $stmt = $db->prepare("
        INSERT INTO replies (content, user_id, post_id, quoted_reply_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $content,
        $_SESSION['user_id'],
        $postId,
        $quotedReplyId
    ]);
    
    // 清除引用内容
    unset($_SESSION['quoted_reply']);
    
    // 重定向回帖子
    header("Location: post.php?id=$postId");
    exit;
} catch (PDOException $e) {
    die("回复失败: " . $e->getMessage());
}