<?php
// Simple placeholder for course offerings and timetable - Coordinator
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
setSecurityHeaders();
initSecureSession();
requireRole('coordinator');
$page_title = 'Course Offerings';
include __DIR__ . '/../includes/header.php';
?>
<div class="fade-in">
    <h1>Course Offerings</h1>
    <p class="text-secondary">Manage semester course catalog (Feature implementation similar to assignments)</p>
    <div class="alert alert-info">This module manages which courses are offered in each semester for each program.</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>