<?php
// Coordinator Dashboard
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

setSecurityHeaders();
initSecureSession();
requireRole('coordinator');

$pdo = getDB();

$stats = [
    'students' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = TRUE")->fetchColumn(),
    'batches' => $pdo->query("SELECT COUNT(*) FROM batches")->fetchColumn(),
    'assignments' => $pdo->query("SELECT COUNT(*) FROM subject_assignments")->fetchColumn(),
    'timetable_entries' => $pdo->query("SELECT COUNT(*) FROM timetable")->fetchColumn(),
];

$page_title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>Coordinator Dashboard</h1>
    <p class="text-secondary mb-4">Academic Management and Coordination</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['students'] ?>
            </div>
            <div class="stat-label">Active Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['batches'] ?>
            </div>
            <div class="stat-label">Batches</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['assignments'] ?>
            </div>
            <div class="stat-label">Subject Assignments</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['timetable_entries'] ?>
            </div>
            <div class="stat-label">Timetable Entries</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2" style="flex-wrap:wrap;">
                <a href="view_students.php" class="btn btn-primary">View Students</a>
                <a href="subject_assignments.php" class="btn btn-primary">Manage Assignments</a>
                <a href="timetable.php" class="btn btn-primary">Manage Timetable</a>
                <a href="reports.php" class="btn btn-secondary">Generate Reports</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>