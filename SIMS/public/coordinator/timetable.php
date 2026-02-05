<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
setSecurityHeaders();
initSecureSession();
requireRole('coordinator');
$page_title = 'Timetable';
include __DIR__ . '/../includes/header.php';
?>
<div class="fade-in">
    <h1>Timetable Management</h1>
    <p class="text-secondary">Create and manage class schedules</p>
    <div class="alert alert-info">This module allows scheduling classes with conflict detection for teachers.</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>