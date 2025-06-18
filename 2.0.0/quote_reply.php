<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("请先登录");
}

$replyId = (int)($_GET['id'] ?? 0);

// 获取被引用的回复内容
$stmt = $db->prepare("
    SELECT r.content, u.username, p.id AS post_id
    FROM replies r
    JOIN users u ON r.user_id = u.id
    JOIN posts p ON r.post_id = p.id
    WHERE r.id = ?
");
$stmt->execute([$replyId]);
$reply = $stmt->fetch();

if (!$reply) {
    die("回复不存在");
}

// 构造引用内容
$quotedContent = "@{$reply['username']}:\n> " . str_replace("\n", "\n> ", $reply['content']);

// 存储引用内容到session
$_SESSION['quoted_reply'] = $quotedContent;

// 返回JSON响应
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'post_id' => $reply['post_id']
]);
exit;