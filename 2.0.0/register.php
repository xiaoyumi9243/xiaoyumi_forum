<?php
require_once 'includes/config.php';

// 检查注册功能是否启用
if (!isFeatureEnabled('user_registration')) {
    die("用户注册功能已被管理员禁用");
}

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度需在3-20个字符之间';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6个字符';
    } elseif ($password !== $confirm) {
        $error = '两次输入的密码不一致';
    } else {
        // 检查用户名是否已存在
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = '用户名已存在';
        } else {
            // 创建用户
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            
            if ($stmt->execute([$username, $hashed])) {
                $_SESSION['user'] = $username;
                $_SESSION['user_id'] = $db->lastInsertId();
                redirect('index.php');
            } else {
                $error = '注册失败，请稍后再试';
            }
        }
    }
}

$pageTitle = "用户注册";
require_once 'includes/header.php';
?>

<div class="auth-container">
    <h1>注册新账号</h1>
    
    <?php if ($error): ?>
        <div class="error-message"><?= sanitize($error) ?></div>
    <?php endif; ?>
    
    <form method="post" class="auth-form">
        <div class="form-group">
            <label for="username">用户名 (3-20字符):</label>
            <input type="text" id="username" name="username" 
                   value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>" 
                   required minlength="3" maxlength="20">
        </div>
        
        <div class="form-group">
            <label for="password">密码 (至少6字符):</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">确认密码:</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">注册</button>
        </div>
    </form>
    
    <div class="auth-links">
        已有账号? <a href="login.php">立即登录</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>