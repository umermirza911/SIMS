<?php
// My Courses - Teacher
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

setSecurityHeaders();
initSecureSession();
requireRole('teacher');

$pdo = getDB();
$teacher_id = getCurrentUserId();

$courses = $pdo->prepare("
    SELECT sa.*, s.name as subject_name, s.code, b.name as batch_name, p.name as program_name,
           (SELECT COUNT(*) FROM students WHERE batch_id = sa.batch_id AND is_active = TRUE) as student_count
    FROM subject_assignments sa
    JOIN subjects s ON sa.subject_id = s.subject_id
    JOIN batches b ON sa.batch_id = b.batch_id
    JOIN programs p ON b.program_id = p.program_id
    WHERE sa.teacher_id = ?
    ORDER BY sa.academic_year DESC, sa.semester
");
$courses->execute([$teacher_id]);
$my_courses = $courses->fetchAll();

$page_title = 'My Courses';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>My Assigned Courses</h1>
    <p class="text-secondary mb-4">Courses you are currently teaching</p>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr><th>Subject Code</th><th>Subject Name</th><th>Batch</th><th>Program</th><th>Semester</th><th>Academic Year</th><th>Students</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_courses)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No courses assigned</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_courses as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['code']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($c['batch_name']) ?></td>
                                    <td><?= htmlspecialchars($c['program_name']) ?></td>
                                    <td><?= $c['semester'] ?></td>
                                    <td><?= htmlspecialchars($c['academic_year']) ?></td>
                                    <td><?= $c['student_count'] ?></td>
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
