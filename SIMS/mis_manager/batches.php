<?php
// Batch Management - MIS Manager
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
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = sanitizeString($_POST['name']);
            $start_year = validatePositiveInt($_POST['start_year']);
            $end_year = validatePositiveInt($_POST['end_year']);
            $program_id = validatePositiveInt($_POST['program_id']);

            if ($start_year && $end_year && $program_id && $name) {
                $stmt = $pdo->prepare("INSERT INTO batches (name, start_year, end_year, program_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $start_year, $end_year, $program_id]);
                logAudit(getCurrentUserId(), 'CREATE_BATCH', 'batches', "Created batch: $name", $pdo->lastInsertId());
                $success = 'Batch created successfully!';
            }
        } elseif ($action === 'delete') {
            $id = validatePositiveInt($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM batches WHERE batch_id = ?");
            $stmt->execute([$id]);
            logAudit(getCurrentUserId(), 'DELETE_BATCH', 'batches', "Deleted batch ID: $id", $id);
            $success = 'Batch deleted successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Operation failed: ' . (strpos($e->getMessage(), 'Duplicate') ? 'Batch already exists' : 'Database error');
    }
}

$batches = $pdo->query("
    SELECT b.*, p.name as program_name, d.name as dept_name,
           (SELECT COUNT(*) FROM students WHERE batch_id = b.batch_id) as student_count
    FROM batches b
    JOIN programs p ON b.program_id = p.program_id
    JOIN departments d ON p.department_id = d.department_id
    ORDER BY b.start_year DESC, b.name
")->fetchAll();

$programs = $pdo->query("SELECT p.*, d.name as dept_name FROM programs p JOIN departments d ON p.department_id = d.department_id ORDER BY d.name, p.name")->fetchAll();

$page_title = 'Batches';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>Batch Management</h1>
            <p class="text-secondary">Manage student batches and cohorts</p>
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">+ New
            Batch</button>
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
                            <th>Batch Name</th>
                            <th>Program</th>
                            <th>Department</th>
                            <th>Duration</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td>
                                    <?= $b['batch_id'] ?>
                                </td>
                                <td><strong>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </strong></td>
                                <td>
                                    <?= htmlspecialchars($b['program_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($b['dept_name']) ?>
                                </td>
                                <td>
                                    <?= $b['start_year'] ?> -
                                    <?= $b['end_year'] ?>
                                </td>
                                <td>
                                    <?= $b['student_count'] ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirmAction('Delete batch?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $b['batch_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
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
            <h3 class="card-title">Create New Batch</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Batch Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Fall 2024" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Program</label>
                    <select name="program_id" class="form-control" required>
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= $p['program_id'] ?>">
                                <?= htmlspecialchars($p['name']) ?> (
                                <?= htmlspecialchars($p['dept_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Start Year</label>
                        <input type="number" name="start_year" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">End Year</label>
                        <input type="number" name="end_year" class="form-control" value="<?= date('Y') + 4 ?>" required>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Batch</button>
                    <button type="button" onclick="this.closest('#createModal').style.display='none'"
                        class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('createModal').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>