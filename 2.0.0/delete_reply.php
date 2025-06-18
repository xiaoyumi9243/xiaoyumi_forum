<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("请先登录");
}

$replyId = (int)($_GET['id'] ?? 0);

// 验证回复所有权
$stmt = $db->prepare("
    SELECT id, user_id, post_id 
    FROM replies 
    WHERE id = ?
");
$stmt->execute([$replyId]);
$reply = $stmt->fetch();

if (!$reply) {
    die("回复不存在");
}

// 检查权限
if ($_SESSION['user_id'] != $reply['user_id'] && !isAdmin()) {
    die("您没有权限删除此回复");
}

// 执行删除
try {
    $stmt = $db->prepare("DELETE FROM replies WHERE id = ?");
    $stmt->execute([$replyId]);
    
    $_SESSION['success'] = "回复已删除";
    header("Location: post.php?id=".$reply['post_id']);
    exit;
} catch (PDOException $e) {
    die("删除失败: " . $e->getMessage());
}