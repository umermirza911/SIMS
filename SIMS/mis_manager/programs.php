<?php
/**
 * Program Management
 * CRUD operations for academic programs
 */

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
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $validation = validateForm([
                'name' => ['type' => 'string', 'required' => true, 'max' => 100],
                'level' => ['type' => 'enum', 'required' => true, 'values' => ['Undergraduate', 'Graduate', 'Postgraduate']],
                'duration_years' => ['type' => 'positive_int', 'required' => true],
                'department_id' => ['type' => 'positive_int', 'required' => true]
            ], $_POST);

            if (!$validation['valid']) {
                $error = implode(', ', $validation['errors']);
            } else {
                $data = $validation['data'];
                $stmt = $pdo->prepare("INSERT INTO programs (name, level, duration_years, department_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$data['name'], $data['level'], $data['duration_years'], $data['department_id']]);

                logAudit(
                    getCurrentUserId(),
                    'CREATE_PROGRAM',
                    'programs',
                    "Created program: {$data['name']}",
                    $pdo->lastInsertId()
                );

                $success = 'Program created successfully!';
            }
        } elseif ($action === 'update') {
            $validation = validateForm([
                'id' => ['type' => 'positive_int', 'required' => true],
                'name' => ['type' => 'string', 'required' => true, 'max' => 100],
                'level' => ['type' => 'enum', 'required' => true, 'values' => ['Undergraduate', 'Graduate', 'Postgraduate']],
                'duration_years' => ['type' => 'positive_int', 'required' => true],
                'department_id' => ['type' => 'positive_int', 'required' => true]
            ], $_POST);

            if (!$validation['valid']) {
                $error = implode(', ', $validation['errors']);
            } else {
                $data = $validation['data'];
                $stmt = $pdo->prepare("UPDATE programs SET name = ?, level = ?, duration_years = ?, department_id = ? WHERE program_id = ?");
                $stmt->execute([$data['name'], $data['level'], $data['duration_years'], $data['department_id'], $data['id']]);

                logAudit(
                    getCurrentUserId(),
                    'UPDATE_PROGRAM',
                    'programs',
                    "Updated program ID {$data['id']}",
                    $data['id']
                );

                $success = 'Program updated successfully!';
            }
        } elseif ($action === 'delete') {
            $id = validatePositiveInt($_POST['id']);

            if (!$id) {
                $error = 'Invalid program ID.';
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM batches WHERE program_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Cannot delete program with existing batches.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM programs WHERE program_id = ?");
                    $stmt->execute([$id]);

                    logAudit(getCurrentUserId(), 'DELETE_PROGRAM', 'programs', "Deleted program ID: $id", $id);
                    $success = 'Program deleted successfully!';
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Program operation error: " . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
}

// Fetch all programs with department info
$programs = $pdo->query("
    SELECT p.*, d.name as department_name,
           (SELECT COUNT(*) FROM batches WHERE program_id = p.program_id) as batch_count
    FROM programs p
    JOIN departments d ON p.department_id = d.department_id
    ORDER BY d.name, p.name
")->fetchAll();

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$page_title = 'Programs';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>Program Management</h1>
            <p class="text-secondary">Manage academic programs</p>
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">
            + New Program
        </button>
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
                            <th>Program Name</th>
                            <th>Level</th>
                            <th>Duration</th>
                            <th>Department</th>
                            <th>Batches</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($programs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No programs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($programs as $prog): ?>
                                <tr>
                                    <td>
                                        <?= $prog['program_id'] ?>
                                    </td>
                                    <td><strong>
                                            <?= htmlspecialchars($prog['name']) ?>
                                        </strong></td>
                                    <td><span class="badge badge-secondary">
                                            <?= htmlspecialchars($prog['level']) ?>
                                        </span></td>
                                    <td>
                                        <?= $prog['duration_years'] ?> year(s)
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($prog['department_name']) ?>
                                    </td>
                                    <td>
                                        <?= $prog['batch_count'] ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick='editProgram(<?= json_encode($prog) ?>)'
                                                class="btn btn-sm btn-secondary">Edit</button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirmAction('Delete this program?')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $prog['program_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 600px; margin: 2rem; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title">Create New Program</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label class="form-label">Program Name</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-control" required>
                        <option value="">Select Level</option>
                        <option value="Undergraduate">Undergraduate</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Postgraduate">Postgraduate</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Duration (Years)</label>
                    <input type="number" name="duration_years" class="form-control" min="1" max="10" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>">
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Program</button>
                    <button type="button" onclick="document.getElementById('createModal').style.display='none'"
                        class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal (similar structure) -->
<div id="editModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 600px; margin: 2rem; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title">Edit Program</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Program Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label">Level</label>
                    <select name="level" id="edit_level" class="form-control" required>
                        <option value="Undergraduate">Undergraduate</option>
                        <option value="Graduate">Graduate</option>
                        <option value="Postgraduate">Postgraduate</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Duration (Years)</label>
                    <input type="number" name="duration_years" id="edit_duration" class="form-control" min="1" max="10"
                        required>
                </div>

                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="edit_dept" class="form-control" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>">
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'"
                        class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editProgram(prog) {
        document.getElementById('edit_id').value = prog.program_id;
        document.getElementById('edit_name').value = prog.name;
        document.getElementById('edit_level').value = prog.level;
        document.getElementById('edit_duration').value = prog.duration_years;
        document.getElementById('edit_dept').value = prog.department_id;
        document.getElementById('editModal').style.display = 'flex';
    }

    document.getElementById('createModal').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });

    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>