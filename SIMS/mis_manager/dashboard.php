<?php
/**
 * MIS Manager Dashboard
 * Overview and quick stats
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

setSecurityHeaders();
initSecureSession();
requireRole('mis_manager');

$pdo = getDB();

// Get statistics
$stats = [
    'departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
    'programs' => $pdo->query("SELECT COUNT(*) FROM programs")->fetchColumn(),
    'batches' => $pdo->query("SELECT COUNT(*) FROM batches")->fetchColumn(),
    'students' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = TRUE")->fetchColumn(),
    'teachers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = TRUE")->fetchColumn(),
    'coordinators' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coordinator' AND is_active = TRUE")->fetchColumn(),
];

// Recent activities
$recent_logs = $pdo->query("
    SELECT al.*, u.name as user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetchAll();

$page_title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>MIS Manager Dashboard</h1>
    <p class="text-secondary mb-4">System overview and quick statistics</p>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['departments'] ?>
            </div>
            <div class="stat-label">Departments</div>
        </div>

        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['programs'] ?>
            </div>
            <div class="stat-label">Programs</div>
        </div>

        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['batches'] ?>
            </div>
            <div class="stat-label">Batches</div>
        </div>

        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['students'] ?>
            </div>
            <div class="stat-label">Active Students</div>
        </div>

        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['teachers'] ?>
            </div>
            <div class="stat-label">Teachers</div>
        </div>

        <div class="stat-card">
            <div class="stat-value">
                <?= $stats['coordinators'] ?>
            </div>
            <div class="stat-label">Coordinators</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2" style="flex-wrap: wrap;">
                <a href="departments.php" class="btn btn-primary">Manage Departments</a>
                <a href="programs.php" class="btn btn-primary">Manage Programs</a>
                <a href="students.php" class="btn btn-primary">Register Student</a>
                <a href="teachers.php" class="btn btn-primary">Add Teacher</a>
                <a href="users.php" class="btn btn-secondary">Manage Users</a>
                <a href="logs.php" class="btn btn-outline">View Audit Logs</a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent System Activity</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Resource</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No recent activity</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td>
                                        <?= date('M d, H:i', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['resource']) ?>
                                    </td>
                                    <td class="text-muted">
                                        <?= htmlspecialchars($log['ip_address']) ?>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>