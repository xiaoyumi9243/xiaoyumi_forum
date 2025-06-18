<?php
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user'] === ADMIN_USERNAME;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function checkPostFrequency() {
    if(isset($_SESSION['last_post_time'])) {
        $elapsed = time() - $_SESSION['last_post_time'];
        if($elapsed < 30) {
            die("操作过于频繁，请等待 ". (30-$elapsed) ." 秒后再试");
        }
    }
    $_SESSION['last_post_time'] = time();
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function tableExists($tableName) {
    global $db;
    try {
        $result = $db->query("SELECT 1 FROM $tableName LIMIT 1");
        return $result !== false;
    } catch (PDOException $e) {
        return false;
    }
}

function initForumFeatures() {
    global $db;
    $db->exec("
        CREATE TABLE `forum_features` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `feature_name` VARCHAR(50) NOT NULL UNIQUE,
          `feature_description` TEXT,
          `is_enabled` BOOLEAN DEFAULT 1,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $features = [
        ['search', '论坛搜索功能'],
        ['quote_reply', '引用回复功能'],
        ['post_editing', '帖子编辑功能'],
        ['post_deletion', '帖子删除功能'],
        ['reply_editing', '回复编辑功能'],
        ['reply_deletion', '回复删除功能'],
        ['board_creation', '板块创建功能'],
        ['user_registration', '用户注册功能'],
        ['admin_edit_mark', '管理员编辑标记']
    ];
    
    $stmt = $db->prepare("INSERT INTO forum_features (feature_name, feature_description) VALUES (?, ?)");
    foreach ($features as $feature) {
        $stmt->execute($feature);
    }
}

function isFeatureEnabled($featureName) {
    global $db;
    if(isAdmin()) return true;
    
    $stmt = $db->prepare("SELECT is_enabled FROM forum_features WHERE feature_name = ?");
    $stmt->execute([$featureName]);
    $result = $stmt->fetch();
    
    return $result ? (bool)$result['is_enabled'] : false;
}

function getAllFeatures() {
    global $db;
    return $db->query("SELECT * FROM forum_features ORDER BY feature_name")->fetchAll();
}

function excerpt($text, $length = 200) {
    $text = strip_tags($text);
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length) . '...';
    }
    return $text;
}
/**
 * 获取用户信息
 */
function getUserInfo($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * 获取用户发帖列表
 */
function getUserPosts($user_id, $limit = 10) {
    global $db;
    $stmt = $db->prepare("SELECT id, title, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * 检查当前用户是否查看自己的个人中心
 */
function isCurrentUserProfile($profile_user_id) {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id;
}
?>