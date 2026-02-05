<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
setSecurityHeaders();
initSecureSession();
requireRole('teacher');
$page_title = 'My Schedule';
include __DIR__ . '/../includes/header.php';
?>
<div class="fade-in">
    <h1>My Schedule</h1>
    <p class="text-secondary">Your weekly teaching timetable</p>
    <div class="alert alert-info">This page would display your weekly class schedule in a calendar format.</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>