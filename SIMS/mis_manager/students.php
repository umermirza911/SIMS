<?php
// Student Management - MIS Manager
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
            $validation = validateForm([
                'reg_number' => ['type' => 'string', 'required' => true, 'max' => 50],
                'first_name' => ['type' => 'string', 'required' => true, 'max' => 50],
                'last_name' => ['type' => 'string', 'required' => true, 'max' => 50],
                'email' => ['type' => 'email', 'required' => true],
                'date_of_birth' => ['type' => 'date', 'required' => false],
                'batch_id' => ['type' => 'positive_int', 'required' => true],
            ], $_POST);

            if ($validation['valid']) {
                $d = $validation['data'];
                $stmt = $pdo->prepare("INSERT INTO students (reg_number, first_name, last_name, email, date_of_birth, batch_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$d['reg_number'], $d['first_name'], $d['last_name'], $d['email'], $d['date_of_birth'], $d['batch_id']]);
                logAudit(getCurrentUserId(), 'CREATE_STUDENT', 'students', "Registered student: {$d['reg_number']}", $pdo->lastInsertId());
                $success = 'Student registered successfully!';
            } else {
                $error = implode(', ', $validation['errors']);
            }
        } elseif ($action === 'toggle_status') {
            $id = validatePositiveInt($_POST['id']);
            $stmt = $pdo->prepare("UPDATE students SET is_active = NOT is_active WHERE student_id = ?");
            $stmt->execute([$id]);
            logAudit(getCurrentUserId(), 'TOGGLE_STUDENT_STATUS', 'students', "Toggled status for student ID: $id", $id);
            $success = 'Student status updated!';
        }
    } catch (PDOException $e) {
        $error = strpos($e->getMessage(), 'Duplicate') !== false ? 'Registration number or email already exists' : 'Database error occurred';
    }
}

$students = $pdo->query("SELECT * FROM v_student_details ORDER BY reg_number DESC")->fetchAll();
$batches = $pdo->query("SELECT b.*, p.name as program_name FROM batches b JOIN programs p ON b.program_id = p.program_id ORDER BY b.name")->fetchAll();

$page_title = 'Students';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>Student Management</h1>
            <p class="text-secondary">Register and manage students</p>
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">+ Register
            Student</button>
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
        <div class="card-header">
            <input type="text" id="searchInput" placeholder="Search students..." class="form-control"
                style="max-width:400px;">
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Reg No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Batch</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><strong>
                                        <?= htmlspecialchars($s['reg_number']) ?>
                                    </strong></td>
                                <td>
                                    <?= htmlspecialchars($s['full_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['email']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['batch_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['program_name']) ?>
                                </td>
                                <td>
                                    <?= $s['current_semester'] ?>
                                </td>
                                <td>
                                    <span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $s['student_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary">
                                            <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
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
    <div class="card" style="max-width:700px;margin:2rem;max-height:90vh;overflow-y:auto;">
        <div class="card-header">
            <h3 class="card-title">Register New Student</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Registration Number</label>
                    <input type="text" name="reg_number" class="form-control" placeholder="2024-CS-001" required>
                </div>
                <div class="d-flex gap-2">
                    <div class="form-group" style="flex:1">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Batch</label>
                    <select name="batch_id" class="form-control" required>
                        <option value="">Select Batch</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['batch_id'] ?>">
                                <?= htmlspecialchars($b['name']) ?> -
                                <?= htmlspecialchars($b['program_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Register Student</button>
                    <button type="button" onclick="this.closest('#createModal').style.display='none'"
                        class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    filterTable('searchInput', 'studentsTable');
    document.getElementById('createModal').addEventListener('click', e => { if (e.target.id === 'createModal') e.target.style.display = 'none'; });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>