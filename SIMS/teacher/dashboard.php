<?php
// Teacher Dashboard
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

setSecurityHeaders();
initSecureSession();
requireRole('teacher');

$pdo = getDB();
$teacher_id = getCurrentUserId();

$stats = [
    'assigned_courses' => $pdo->prepare("SELECT COUNT(*) FROM subject_assignments WHERE teacher_id = ?")->execute([$teacher_id]) ? $pdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0,
];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM subject_assignments WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$stats['assigned_courses'] = $stmt->fetchColumn();

$page_title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>Teacher Dashboard</h1>
    <p class="text-secondary mb-4">Welcome,
        <?= htmlspecialchars(getCurrentUserName()) ?>
    </p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['assigned_courses'] ?>
            </div>
            <div class="stat-label">Assigned Courses</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quick Access</h3>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="my_courses.php" class="btn btn-primary">My Courses</a>
                <a href="students.php" class="btn btn-primary">View Students</a>
                <a href="schedule.php" class="btn btn-secondary">My Schedule</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>