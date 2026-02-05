<?php
/**
 * Page Header Component
 * Reusable header for all authenticated pages
 */

// Ensure this is included in a page with auth
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = getCurrentUserName();
$user_role = getCurrentUserRole();

// Role display names
$role_names = [
    'mis_manager' => 'MIS Manager',
    'coordinator' => 'Program Coordinator',
    'teacher' => 'Teacher'
];
$role_display = $role_names[$user_role] ?? 'User';

// Navigation menus based on role
$nav_menus = [
    'mis_manager' => [
        'dashboard' => 'Dashboard',
        'departments' => 'Departments',
        'programs' => 'Programs',
        'batches' => 'Batches',
        'students' => 'Students',
        'teachers' => 'Teachers',
        'users' => 'Users',
        'logs' => 'Audit Logs'
    ],
    'coordinator' => [
        'dashboard' => 'Dashboard',
        'view_students' => 'Students',
        'subject_assignments' => 'Assignments',
        'course_offerings' => 'Course Offerings',
        'timetable' => 'Timetable',
        'reports' => 'Reports'
    ],
    'teacher' => [
        'dashboard' => 'Dashboard',
        'my_courses' => 'My Courses',
        'students' => 'Students',
        'schedule' => 'My Schedule'
    ]
];

$nav_items = $nav_menus[$user_role] ?? [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($page_title ?? 'SIMS') ?> - Student Information Management System
    </title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= time() ?>">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="<?= BASE_URL ?>/public/index.php" class="navbar-brand">🎓 SIMS</a>

                <ul class="nav-menu">
                    <?php foreach ($nav_items as $page => $label): ?>
                        <li>
                            <a href="<?= $page ?>.php" class="nav-link <?= $current_page === $page ? 'active' : '' ?>">
                                <?= htmlspecialchars($label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="d-flex align-center gap-2">
                    <div class="text-right">
                        <div style="font-weight: 600;">
                            <?= htmlspecialchars($user_name) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">
                            <?= htmlspecialchars($role_display) ?>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/public/logout.php" class="btn btn-sm btn-outline"
                        onclick="return confirmAction('Are you sure you want to logout?')">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="margin-top: 2rem; margin-bottom: 2rem;">