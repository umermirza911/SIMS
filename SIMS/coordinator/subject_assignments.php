<?php
// Subject Assignments - Coordinator
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/audit.php';

setSecurityHeaders();
initSecureSession();
requireRole('coordinator');

$pdo = getDB();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $subject_id = validatePositiveInt($_POST['subject_id']);
            $teacher_id = validatePositiveInt($_POST['teacher_id']);
            $batch_id = validatePositiveInt($_POST['batch_id']);
            $semester = validatePositiveInt($_POST['semester']);
            $academic_year = sanitizeString($_POST['academic_year']);

            if ($subject_id && $teacher_id && $batch_id && $semester && $academic_year) {
                $stmt = $pdo->prepare("INSERT INTO subject_assignments (subject_id, teacher_id, batch_id, semester, academic_year) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$subject_id, $teacher_id, $batch_id, $semester, $academic_year]);
                logAudit(getCurrentUserId(), 'CREATE_ASSIGNMENT', 'subject_assignments', "Assigned subject to teacher", $pdo->lastInsertId());
                $success = 'Subject assigned successfully!';
            }
        } elseif ($action === 'delete') {
            $id = validatePositiveInt($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM subject_assignments WHERE assignment_id = ?");
            $stmt->execute([$id]);
            logAudit(getCurrentUserId(), 'DELETE_ASSIGNMENT', 'subject_assignments', "Deleted assignment ID: $id", $id);
            $success = 'Assignment deleted!';
        }
    } catch (PDOException $e) {
        $error = strpos($e->getMessage(), 'Duplicate') !== false ? 'This assignment already exists' : 'Operation failed';
    }
}

$assignments = $pdo->query("
    SELECT sa.*, s.name as subject_name, s.code, u.name as teacher_name, b.name as batch_name, p.name as program_name
    FROM subject_assignments sa
    JOIN subjects s ON sa.subject_id = s.subject_id
    JOIN users u ON sa.teacher_id = u.user_id
    JOIN batches b ON sa.batch_id = b.batch_id
    JOIN programs p ON b.program_id = p.program_id
    ORDER BY sa.academic_year DESC, sa.semester
")->fetchAll();

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY code")->fetchAll();
$teachers = $pdo->query("SELECT * FROM users WHERE role='teacher' AND is_active=TRUE ORDER BY name")->fetchAll();
$batches = $pdo->query("SELECT b.*, p.name as prog_name FROM batches b JOIN programs p ON b.program_id=p.program_id ORDER BY b.name")->fetchAll();

$page_title = 'Subject Assignments';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>Subject Assignments</h1>
            <p class="text-secondary">Assign subjects to teachers</p>
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">+ New
            Assignment</button>
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
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Batch</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><strong>
                                        <?= htmlspecialchars($a['code']) ?>
                                    </strong> -
                                    <?= htmlspecialchars($a['subject_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($a['teacher_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($a['batch_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($a['program_name']) ?>
                                </td>
                                <td>
                                    <?= $a['semester'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($a['academic_year']) ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirmAction('Delete assignment?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $a['assignment_id'] ?>">
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
            <h3 class="card-title">Create Subject Assignment</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['subject_id'] ?>">
                                <?= htmlspecialchars($s['code']) ?> -
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teacher</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['user_id'] ?>">
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Batch</label>
                    <select name="batch_id" class="form-control" required>
                        <option value="">Select Batch</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['batch_id'] ?>">
                                <?= htmlspecialchars($b['name']) ?> (
                                <?= htmlspecialchars($b['prog_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Semester</label>
                        <input type="number" name="semester" class="form-control" min="1" max="12" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" placeholder="2024-2025" required>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
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