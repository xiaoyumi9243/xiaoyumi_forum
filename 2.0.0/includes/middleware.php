<?php
// 检查用户注册功能
if (basename($_SERVER['PHP_SELF']) === 'register.php' && !isFeatureEnabled('user_registration')) {
    die("用户注册功能已被管理员禁用");
}

// 检查板块创建功能
if (basename($_SERVER['PHP_SELF']) === 'create_board.php' && !isFeatureEnabled('board_creation')) {
    die("板块创建功能已被管理员禁用");
}

// 检查搜索功能
if (basename($_SERVER['PHP_SELF']) === 'search.php' && !isFeatureEnabled('search')) {
    die("搜索功能已被管理员禁用");
}
?>