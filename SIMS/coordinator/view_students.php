<?php
// View Students - Coordinator
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

setSecurityHeaders();
initSecureSession();
requireRole('coordinator');

$pdo = getDB();

// Filter options
$batch_filter = $_GET['batch'] ?? '';
$program_filter = $_GET['program'] ?? '';

$sql = "SELECT * FROM v_student_details WHERE 1=1";
$params = [];

if ($batch_filter) {
    $sql .= " AND batch_name = ?";
    $params[] = $batch_filter;
}

if ($program_filter) {
    $sql .= " AND program_name = ?";
    $params[] = $program_filter;
}

$sql .= " ORDER BY reg_number DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$batches = $pdo->query("SELECT DISTINCT name FROM batches ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$programs = $pdo->query("SELECT DISTINCT name FROM programs ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Students';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>Student List</h1>
    <p class="text-secondary mb-4">View enrolled students across all programs</p>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2" style="flex-wrap:wrap;">
                <select name="batch" class="form-control" style="max-width:250px;">
                    <option value="">All Batches</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $batch_filter === $b ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="program" class="form-control" style="max-width:250px;">
                    <option value="">All Programs</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $program_filter === $p ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="view_students.php" class="btn btn-outline">Clear</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Reg No.</th>
                            <th>Name</th>
                            <th>Batch</th>
                            <th>Program</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Status</th>
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
                                    <?= htmlspecialchars($s['batch_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['program_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['department_name']) ?>
                                </td>
                                <td>
                                    <?= $s['current_semester'] ?>
                                </td>
                                <td><span class="badge badge-<?= $s['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>