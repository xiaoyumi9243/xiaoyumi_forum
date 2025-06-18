<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active']) {
                $_SESSION['user'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                redirect('index.php');
            } else {
                $error = '您的账号已被禁用，请联系管理员';
            }
        } else {
            $error = '用户名或密码错误';
        }
    }
}

$pageTitle = "用户登录";
require_once 'includes/header.php';
?>

<div class="auth-container">
    <h1>用户登录</h1>
    
    <?php if ($error): ?>
        <div class="error-message"><?= sanitize($error) ?></div>
    <?php endif; ?>
    
    <form method="post" class="auth-form">
        <div class="form-group">
            <label for="username">用户名:</label>
            <input type="text" id="username" name="username" 
                   value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">密码:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">登录</button>
        </div>
    </form>
    
    <div class="auth-links">
        <?php if(isFeatureEnabled('user_registration')): ?>
            没有账号? <a href="register.php">立即注册</a>
        <?php endif; ?>
        <a href="forgot_password.php">忘记密码?</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>