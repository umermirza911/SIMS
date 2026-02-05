<?php
// View Students - Teacher
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

setSecurityHeaders();
initSecureSession();
requireRole('teacher');

$pdo = getDB();
$teacher_id = getCurrentUserId();

// Get batches assigned to this teacher
$students = $pdo->prepare("
    SELECT DISTINCT s.reg_number, s.first_name, s.last_name, s.email, s.current_semester,
           b.name as batch_name, p.name as program_name
    FROM students s
    JOIN batches b ON s.batch_id = b.batch_id
    JOIN programs p ON b.program_id = p.program_id
    WHERE s.batch_id IN (
        SELECT DISTINCT batch_id FROM subject_assignments WHERE teacher_id = ?
    )
    AND s.is_active = TRUE
    ORDER BY b.name, s.reg_number
");
$students->execute([$teacher_id]);
$my_students = $students->fetchAll();

$page_title = 'Students';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>My Students</h1>
    <p class="text-secondary mb-4">Students in batches you teach</p>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Reg No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Batch</th>
                            <th>Program</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_students)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No students found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_students as $s): ?>
                                <tr>
                                    <td><strong>
                                            <?= htmlspecialchars($s['reg_number']) ?>
                                        </strong></td>
                                    <td>
                                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($s['email']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($s['batch_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($s['program_name']) ?>
                                    </td>
                                    <td>
                                        <?= $s['current_semester'] ?>
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