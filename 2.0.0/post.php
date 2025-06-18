<?php
require_once 'includes/config.php';

// 检查用户登录状态
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = $is_logged_in && isAdmin(); // 假设isAdmin()函数在config.php或functions.php中定义

$postId = (int)($_GET['id'] ?? 0);

// 获取帖子内容
$stmt = $db->prepare("
    SELECT p.*, u.username, b.name AS board_name, 
           a.username AS admin_editor
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN boards b ON p.board_id = b.id
    LEFT JOIN users a ON p.last_edited_by = a.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: index.php");
    exit;
}

// 获取回复
$replies = $db->query("
    SELECT r.*, u.username, 
           q.content AS quoted_content, q.username AS quoted_username
    FROM replies r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN (
        SELECT r2.id, r2.content, u2.username
        FROM replies r2
        JOIN users u2 ON r2.user_id = u2.id
    ) q ON r.quoted_reply_id = q.id
    WHERE r.post_id = $postId
    ORDER BY r.created_at ASC
")->fetchAll();

$pageTitle = sanitize($post['title']);
require_once 'includes/header.php';
?>

<article class="post-detail">
    <?php if($post['admin_edited']): ?>
    <div class="admin-edited-notice">
        <i class="icon-info"></i> 该帖子已被管理员 <?= sanitize($post['admin_editor']) ?> 编辑改进
    </div>
    <?php endif; ?>
    
    <h1><?= sanitize($post['title']) ?></h1>
    
    <div class="post-meta">
        <span>作者: <?= sanitize($post['username']) ?></span>
        <span>板块: 
            <a href="board.php?id=<?= $post['board_id'] ?>">
                <?= sanitize($post['board_name']) ?>
            </a>
        </span>
        <span>发布时间: <?= $post['created_at'] ?></span>
        <?php if($post['last_edited_at']): ?>
            <span>最后编辑: <?= $post['last_edited_at'] ?></span>
        <?php endif; ?>
    </div>
    
    <div class="post-content">
        <?= nl2br(sanitize($post['content'])) ?>
    </div>
    
    <?php if($is_logged_in): ?>
    <div class="post-actions">
        <?php if(isFeatureEnabled('post_editing') && ($_SESSION['user_id'] == $post['user_id'] || $is_admin)): ?>
            <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn-edit">编辑</a>
        <?php endif; ?>
        
        <?php if(isFeatureEnabled('post_deletion') && ($_SESSION['user_id'] == $post['user_id'] || $is_admin)): ?>
            <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn-delete" 
               onclick="return confirm('确定删除此帖子？')">删除</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</article>

<section class="replies-section">
    <h2>回复 (<?= count($replies) ?>)</h2>
    
    <?php if(empty($replies)): ?>
        <p>暂无回复</p>
    <?php else: ?>
        <div class="replies-list">
            <?php foreach($replies as $reply): ?>
            <div class="reply" id="reply-<?= $reply['id'] ?>">
                <?php if($reply['quoted_content']): ?>
                <div class="quoted-reply">
                    <strong>引用 @<?= sanitize($reply['quoted_username']) ?> 的回复:</strong>
                    <blockquote><?= nl2br(sanitize($reply['quoted_content'])) ?></blockquote>
                </div>
                <?php endif; ?>
                
                <div class="reply-content">
                    <?= nl2br(sanitize($reply['content'])) ?>
                </div>
                
                <div class="reply-meta">
                    <span>作者: <?= sanitize($reply['username']) ?></span>
                    <span>时间: <?= $reply['created_at'] ?></span>
                    
                    <?php if($is_logged_in): ?>
                    <div class="reply-actions">
                        <?php if(isFeatureEnabled('quote_reply')): ?>
                            <a href="quote_reply.php?id=<?= $reply['id'] ?>" class="btn-quote">引用</a>
                        <?php endif; ?>
                        
                        <?php if(isFeatureEnabled('reply_editing') && ($_SESSION['user_id'] == $reply['user_id'] || $is_admin)): ?>
                            <a href="edit_reply.php?id=<?= $reply['id'] ?>" class="btn-edit">编辑</a>
                        <?php endif; ?>
                        
                        <?php if(isFeatureEnabled('reply_deletion') && ($_SESSION['user_id'] == $reply['user_id'] || $is_admin)): ?>
                            <a href="delete_reply.php?id=<?= $reply['id'] ?>" class="btn-delete" 
                               onclick="return confirm('确定删除此回复？')">删除</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if($is_logged_in): ?>
    <div class="reply-form">
        <h3>发表回复</h3>
        <form action="reply.php" method="post">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            
            <?php if(isset($_SESSION['quoted_reply'])): ?>
            <div class="quoted-preview">
                <strong>引用内容:</strong>
                <div><?= nl2br(sanitize($_SESSION['quoted_reply'])) ?></div>
                <a href="?id=<?= $post['id'] ?>&clear_quote=1">取消引用</a>
            </div>
            <input type="hidden" name="quoted_reply" value="<?= sanitize($_SESSION['quoted_reply']) ?>">
            <?php unset($_SESSION['quoted_reply']); ?>
            <?php endif; ?>
            
            <textarea name="content" required placeholder="输入回复内容..."></textarea>
            <button type="submit" class="btn-submit">提交回复</button>
        </form>
    </div>
    <?php else: ?>
    <div class="login-prompt">
        <a href="login.php">登录</a> 后即可回复
    </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>