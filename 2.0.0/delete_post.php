<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("请先登录");
}

$postId = (int)($_GET['id'] ?? 0);

// 验证帖子所有权
$stmt = $db->prepare("
    SELECT id, user_id 
    FROM posts 
    WHERE id = ?
");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    die("帖子不存在");
}

// 检查权限
if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    die("您没有权限删除此帖子");
}

// 执行删除
try {
    // 软删除
    $stmt = $db->prepare("UPDATE posts SET is_active = 0 WHERE id = ?");
    $stmt->execute([$postId]);
    
    // 或者硬删除
    // $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    // $stmt->execute([$postId]);
    
    $_SESSION['success'] = "帖子已删除";
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    die("删除失败: " . $e->getMessage());
}