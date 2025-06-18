<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../index.php');
}

// 处理功能状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['features'])) {
    $db->beginTransaction();
    
    try {
        // 首先禁用所有功能
        $db->exec("UPDATE forum_features SET is_enabled = 0");
        
        // 然后启用提交的功能
        $enabledFeatures = array_keys($_POST['features']);
        if (!empty($enabledFeatures)) {
            $placeholders = implode(',', array_fill(0, count($enabledFeatures), '?'));
            $stmt = $db->prepare("
                UPDATE forum_features 
                SET is_enabled = 1 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($enabledFeatures);
        }
        
        $db->commit();
        $_SESSION['success'] = "功能设置已更新";
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "更新失败: " . $e->getMessage();
    }
    
    redirect('dashboard.php');
}

// 获取所有功能
$features = $db->query("SELECT * FROM forum_features ORDER BY feature_name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-container">
    <h1>论坛功能控制面板</h1>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form method="post">
        <table class="feature-table">
            <thead>
                <tr>
                    <th>功能名称</th>
                    <th>功能描述</th>
                    <th>状态</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($features as $feature): ?>
                <tr>
                    <td><?= sanitize($feature['feature_name']) ?></td>
                    <td><?= sanitize($feature['feature_description']) ?></td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="features[<?= $feature['id'] ?>]" 
                                <?= $feature['is_enabled'] ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="form-actions">
            <button type="submit" class="btn-save">保存设置</button>
        </div>
    </form>
</div>
<div class="admin-links">
        <h2>快捷管理</h2>
        <ul>
            <li><a href="users.php">用户管理</a></li>
            <li><a href="posts.php">帖子管理</a></li>
            <li><a href="boards.php">板块管理</a></li>
            <li><a href="../index.php">返回论坛</a></li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>