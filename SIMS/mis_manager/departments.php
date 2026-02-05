<?php
/**
 * Department Management
 * CRUD operations for departments
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
            $name = sanitizeString($_POST['name'] ?? '');

            if (empty($name)) {
                $error = 'Department name is required.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
                $stmt->execute([$name]);

                logAudit(
                    getCurrentUserId(),
                    'CREATE_DEPARTMENT',
                    'departments',
                    "Created department: $name",
                    $pdo->lastInsertId()
                );

                $success = 'Department created successfully!';
            }
        } elseif ($action === 'update') {
            $id = validatePositiveInt($_POST['id']);
            $name = sanitizeString($_POST['name'] ?? '');

            if (!$id || empty($name)) {
                $error = 'Invalid input.';
            } else {
                $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE department_id = ?");
                $stmt->execute([$name, $id]);

                logAudit(
                    getCurrentUserId(),
                    'UPDATE_DEPARTMENT',
                    'departments',
                    "Updated department ID $id to: $name",
                    $id
                );

                $success = 'Department updated successfully!';
            }
        } elseif ($action === 'delete') {
            $id = validatePositiveInt($_POST['id']);

            if (!$id) {
                $error = 'Invalid department ID.';
            } else {
                // Check if department has programs
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM programs WHERE department_id = ?");
                $stmt->execute([$id]);
                $program_count = $stmt->fetchColumn();

                if ($program_count > 0) {
                    $error = "Cannot delete department. It has $program_count program(s) associated with it.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");
                    $stmt->execute([$id]);

                    logAudit(
                        getCurrentUserId(),
                        'DELETE_DEPARTMENT',
                        'departments',
                        "Deleted department ID: $id",
                        $id
                    );

                    $success = 'Department deleted successfully!';
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Department operation error: " . $e->getMessage());
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error = 'A department with this name already exists.';
        } else {
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Fetch all departments with program count
$departments = $pdo->query("
    SELECT d.*, COUNT(p.program_id) as program_count
    FROM departments d
    LEFT JOIN programs p ON d.department_id = p.department_id
    GROUP BY d.department_id
    ORDER BY d.name
")->fetchAll();

$page_title = 'Departments';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>Department Management</h1>
            <p class="text-secondary">Create and manage academic departments</p>
        </div>
        <button onclick="document.getElementById('createModal').style.display='block'" class="btn btn-primary">
            + New Department
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

    <!-- Departments List -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table id="departmentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department Name</th>
                            <th>Programs</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No departments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td>
                                        <?= $dept['department_id'] ?>
                                    </td>
                                    <td><strong>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </strong></td>
                                    <td>
                                        <?= $dept['program_count'] ?> program(s)
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($dept['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                onclick="editDepartment(<?= $dept['department_id'] ?>, '<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>')"
                                                class="btn btn-sm btn-secondary">
                                                Edit
                                            </button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirmAction('Delete this department? This cannot be undone.')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $dept['department_id'] ?>">
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

<!-- Create Department Modal -->
<div id="createModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 500px; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title">Create New Department</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="name" class="form-label">Department Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Computer Science"
                        required maxlength="100">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Department</button>
                    <button type="button" onclick="document.getElementById('createModal').style.display='none'"
                        class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div id="editModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 500px; margin: 2rem;">
        <div class="card-header">
            <h3 class="card-title">Edit Department</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label for="edit_name" class="form-label">Department Name</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required maxlength="100">
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
    function editDepartment(id, name) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('editModal').style.display = 'flex';
    }

    // Close modals when clicking outside
    document.getElementById('createModal').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });

    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>