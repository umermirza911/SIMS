<?php
// User Management - MIS Manager
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';

setSecurityHeaders();
initSecureSession();
requireRole('mis_manager');

$pdo = getDB();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'toggle_status') {
            $id = validatePositiveInt($_POST['id']);
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
            $stmt->execute([$id]);
            logAudit(getCurrentUserId(), 'TOGGLE_USER_STATUS', 'users', "Toggled status for user ID: $id", $id);
            $success = 'User status updated!';
        }
    } catch (PDOException $e) {
        $error = 'Operation failed';
    }
}

$users = $pdo->query("
    SELECT u.*, d.name as dept_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.department_id 
    ORDER BY u.role, u.name
")->fetchAll();

$page_title = 'User Management';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>User Management</h1>
    <p class="text-secondary mb-4">Manage system users and permissions</p>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <?= $u['user_id'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($u['name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($u['email']) ?>
                                </td>
                                <td><span class="badge badge-primary">
                                        <?= ucfirst(str_replace('_', ' ', $u['role'])) ?>
                                    </span></td>
                                <td>
                                    <?= htmlspecialchars($u['dept_name'] ?? 'N/A') ?>
                                </td>
                                <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span></td>
                                <td>
                                    <?= date('M d, Y H:i', strtotime($u['updated_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($u['user_id'] != getCurrentUserId()): ?>
                                        <form method="POST" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary">
                                                <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>