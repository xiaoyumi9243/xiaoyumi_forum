<?php
define('DB_HOST', '数据库地址');
define('DB_USER', '数据库用户名');
define('DB_PASS', '数据库密码');
define('DB_NAME', '数据库名称');

// 管理员用户名(可以创建板块)
define('ADMIN_USERNAME', 'admin');

// 创建数据库连接
try {
    $db = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

session_start();

// 安全头设置
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

// 简单防垃圾措施
function checkPostFrequency() {
    if(isset($_SESSION['last_post_time'])) {
        $elapsed = time() - $_SESSION['last_post_time'];
        if($elapsed < 30) {
            die("操作过于频繁，请等待 ". (30-$elapsed) ." 秒后再试");
        }
    }
    $_SESSION['last_post_time'] = time();
}

// 基础函数
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user'] === ADMIN_USERNAME;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>