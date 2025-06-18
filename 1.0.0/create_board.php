<?php
require 'config.php';

// 检查是否管理员登录
if (!isAdmin()) {
    die("只有管理员可以创建板块");
}

$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // 验证输入
    if (empty($name)) {
        $error = '板块名称不能为空';
    } elseif (strlen($name) > 100) {
        $error = '板块名称不能超过100个字符';
    } else {
        try {
            // 检查板块是否已存在
            $stmt = $db->prepare("SELECT id FROM boards WHERE name = ?");
            $stmt->execute([$name]);
            
            if ($stmt->fetch()) {
                $error = '板块名称已存在';
            } else {
                // 插入新板块
                $stmt = $db->prepare("INSERT INTO boards (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success = '板块创建成功';
            }
        } catch (PDOException $e) {
            $error = '创建板块失败: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>创建新板块</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>创建新板块</h1>
        <nav>
            <a href="index.php">返回首页</a>
        </nav>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error"><?= sanitize($error) ?></div>
        <?php elseif ($success): ?>
            <div class="success"><?= sanitize($success) ?></div>
            <p><a href="index.php">返回首页查看</a> 或 <a href="create_board.php">继续创建</a></p>
        <?php endif; ?>

        <form method="post">
            <div>
                <label for="name">板块名称* (最多100字符):</label>
                <input type="text" id="name" name="name" required maxlength="100">
            </div>
            <div>
                <label for="description">板块描述 (可选):</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit">创建板块</button>
        </form>
    </main>
</body>
</html>