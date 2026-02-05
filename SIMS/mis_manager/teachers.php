<?php
// Teacher/User Registration - MIS Manager
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/audit.php';

setSecurityHeaders();
initSecureSession();
requireRole('mis_manager');

$pdo = getDB();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();

    $validation = validateForm([
        'name' => ['type' => 'string', 'required' => true, 'max' => 100],
        'email' => ['type' => 'email', 'required' => true],
        'password' => ['type' => 'string', 'required' => true, 'min' => 8],
        'role' => ['type' => 'enum', 'required' => true, 'values' => ['teacher', 'coordinator']],
        'department_id' => ['type' => 'positive_int', 'required' => false],
    ], $_POST);

    if ($validation['valid']) {
        try {
            $d = $validation['data'];
            $hashed_password = hashPassword($d['password']);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$d['name'], $d['email'], $hashed_password, $d['role'], $d['department_id']]);

            logAudit(getCurrentUserId(), 'CREATE_USER', 'users', "Created {$d['role']}: {$d['email']}", $pdo->lastInsertId());
            $success = ucfirst($d['role']) . ' account created successfully!';
        } catch (PDOException $e) {
            $error = strpos($e->getMessage(), 'Duplicate') !== false ? 'Email already exists' : 'Database error';
        }
    } else {
        $error = implode(', ', $validation['errors']);
    }
}

$teachers = $pdo->query("
    SELECT u.*, d.name as dept_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.department_id 
    WHERE u.role IN ('teacher', 'coordinator')
    ORDER BY u.role, u.name
")->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$page_title = 'Teachers & Coordinators';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>Teachers & Coordinators</h1>
            <p class="text-secondary">Manage faculty accounts</p>
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">+ Add
            User</button>
    </div>

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
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $t): ?>
                            <tr>
                                <td>
                                    <?= $t['user_id'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($t['name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($t['email']) ?>
                                </td>
                                <td><span class="badge badge-<?= $t['role'] === 'coordinator' ? 'secondary' : 'primary' ?>">
                                        <?= ucfirst($t['role']) ?>
                                    </span></td>
                                <td>
                                    <?= htmlspecialchars($t['dept_name'] ?? 'N/A') ?>
                                </td>
                                <td><span class="badge badge-<?= $t['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $t['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span></td>
                                <td>
                                    <?= date('M d, Y', strtotime($t['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="createModal"
    style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
    <div class="card" style="max-width:600px;margin:2rem;">
        <div class="card-header">
            <h3 class="card-title">Add Teacher/Coordinator</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="teacher">Teacher</option>
                        <option value="coordinator">Coordinator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Department (Optional)</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['department_id'] ?>">
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                    <button type="button" onclick="this.closest('#createModal').style.display='none'"
                        class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('createModal').addEventListener('click', e => { if (e.target.id === 'createModal') e.target.style.display = 'none'; });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>