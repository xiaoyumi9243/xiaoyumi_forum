<?php
session_start();

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 数据库配置
define('DB_HOST', '数据库地址');
define('DB_USER', '数据库用户名');
define('DB_PASS', '数据库密码');
define('DB_NAME', '数据库名称');

// 管理员设置
define('ADMIN_USERNAME', 'admin');
define('ADMIN_EMAIL', 'admin@example.com');

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

// 加载函数
require_once __DIR__.'/functions.php';

// 安全头
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// 初始化功能控制
if (!tableExists('forum_features')) {
    initForumFeatures();
}

// 中间件检查
require_once __DIR__.'/middleware.php';
?>
