<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../index.php');
}

$boardId = (int)($_GET['id'] ?? 0);
$error = '';

// 获取板块信息
$stmt = $db->prepare("SELECT * FROM boards WHERE id = ?");
$stmt->execute([$boardId]);
$board = $stmt->fetch();

if (!$board) {
    die("板块不存在");
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = '板块名称不能为空';
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE boards 
                SET name = ?, description = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $isActive, $boardId]);
            
            $_SESSION['success'] = "板块已更新";
            redirect('boards.php');
        } catch (PDOException $e) {
            $error = '更新失败: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="admin-edit-container">
    <div class="admin-header">
        <h1>编辑板块</h1>
        <a href="boards.php" class="btn btn-back">返回板块列表</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>
    
    <form method="post" class="edit-form">
        <div class="form-group">
            <label for="name">板块名称</label>
            <input type="text" id="name" name="name" class="form-control"
                   value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : sanitize($board['name']) ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <label for="description">板块描述</label>
            <textarea id="description" name="description" class="form-control" rows="3"><?= 
                isset($_POST['description']) ? sanitize($_POST['description']) : sanitize($board['description']) 
            ?></textarea>
        </div>
        
        <div class="form-group form-check">
            <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1"
                <?= ($board['is_active'] || !isset($_POST['is_active'])) ? 'checked' : '' ?>>
            <label for="is_active" class="form-check-label">启用板块</label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">保存更改</button>
            <a href="boards.php" class="btn btn-secondary">取消</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>