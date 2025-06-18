<?php
require 'config.php';

// 1. 验证用户登录
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    die("请先登录");
}

// 2. 防频繁提交
checkPostFrequency();

// 3. 验证数据
$board_id = (int)($_POST['board_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if (empty($title) || empty($content) || $board_id < 1) {
    header("Location: create_post.php?board_id=$board_id&error=输入不完整");
    exit;
}

// 4. 保存到数据库
try {
    $stmt = $db->prepare("
        INSERT INTO posts (title, content, user_id, board_id) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$title, $content, $_SESSION['user_id'], $board_id]);
    
    // 5. 跳转到新帖子
    $post_id = $db->lastInsertId();
    header("Location: post.php?id=$post_id");
    exit;

} catch (PDOException $e) {
    header("Location: create_post.php?board_id=$board_id&error=发帖失败");
    exit;
}