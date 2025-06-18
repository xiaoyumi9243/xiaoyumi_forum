<?php
require 'config.php';

// 1. 验证用户登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. 防频繁提交
checkPostFrequency();

// 3. 获取并验证数据
$post_id = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($post_id < 1 || empty($content)) {
    header("Location: post.php?id=$post_id&error=回复内容不能为空");
    exit;
}

// 4. 验证帖子是否存在
$stmt = $db->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    header("Location: index.php?error=帖子不存在");
    exit;
}

// 5. 保存回复
try {
    $stmt = $db->prepare("
        INSERT INTO replies (content, user_id, post_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$content, $_SESSION['user_id'], $post_id]);
    
    // 6. 返回原帖子
    header("Location: post.php?id=$post_id");
    exit;

} catch (PDOException $e) {
    header("Location: post.php?id=$post_id&error=回复失败");
    exit;
}