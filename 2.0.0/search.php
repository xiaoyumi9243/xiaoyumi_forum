<?php
require_once 'includes/config.php';

// 检查搜索功能是否启用
if (!isFeatureEnabled('search')) {
    die("搜索功能已被管理员禁用");
}

$query = sanitize($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$results = [];
$totalResults = 0;

if (!empty($query)) {
    $searchTerm = "%$query%";
    
    // 获取总数
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM posts p
        JOIN boards b ON p.board_id = b.id
        WHERE (p.title LIKE ? OR p.content LIKE ?) 
        AND p.is_active = 1 AND b.is_active = 1
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $totalResults = $stmt->fetchColumn();
    
    // 获取结果
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.content, p.created_at, 
               u.username, b.name AS board_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN boards b ON p.board_id = b.id
        WHERE (p.title LIKE ? OR p.content LIKE ?) 
        AND p.is_active = 1 AND b.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $searchTerm);
    $stmt->bindValue(2, $searchTerm);
    $stmt->bindValue(3, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();
}

$pagination = [
    'total' => ceil($totalResults / $perPage),
    'current' => $page,
    'prev' => $page > 1 ? $page - 1 : null,
    'next' => $page < ceil($totalResults / $perPage) ? $page + 1 : null
];

$pageTitle = "搜索: " . sanitize($query);
require_once 'includes/header.php';
?>

<div class="search-container">
    <div class="search-header">
        <h1>搜索结果: "<?= sanitize($query) ?>"</h1>
        <p class="search-count">共找到 <?= $totalResults ?> 条结果</p>
    </div>
    
    <?php if (!empty($results)): ?>
        <div class="search-results">
            <?php foreach ($results as $post): ?>
            <div class="search-result">
                <h3 class="result-title">
                    <a href="post.php?id=<?= $post['id'] ?>">
                        <?= sanitize($post['title']) ?>
                    </a>
                </h3>
                <div class="result-meta">
                    <span class="result-author">作者: <?= sanitize($post['username']) ?></span>
                    <span class="result-board">板块: <?= sanitize($post['board_name']) ?></span>
                    <span class="result-date">时间: <?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                </div>
                <div class="result-excerpt">
                    <?= excerpt(sanitize($post['content']), 200) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if($pagination['total'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['prev']): ?>
                <a href="search.php?q=<?= urlencode($query) ?>&page=<?= $pagination['prev'] ?>" class="page-link">上一页</a>
            <?php endif; ?>
            
            <span class="page-info">
                第 <?= $pagination['current'] ?> 页 / 共 <?= $pagination['total'] ?> 页
            </span>
            
            <?php if ($pagination['next']): ?>
                <a href="search.php?q=<?= urlencode($query) ?>&page=<?= $pagination['next'] ?>" class="page-link">下一页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-results">
            <div class="alert alert-warning">没有找到匹配的结果</div>
            <a href="index.php" class="btn btn-secondary">返回首页</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>