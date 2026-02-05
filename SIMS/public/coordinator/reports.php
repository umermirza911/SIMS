<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
setSecurityHeaders();
initSecureSession();
requireRole('coordinator');
$page_title = 'Reports';
include __DIR__ . '/../includes/header.php';
?>
<div class="fade-in">
    <h1>Reports</h1>
    <p class="text-secondary">Generate academic reports</p>
    <div class="alert alert-info">Generate attendance allocation, subject assignment summaries, and teacher workload
        reports.</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>