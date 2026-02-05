<?php
// Audit Logs Viewer - MIS Manager
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';

setSecurityHeaders();
initSecureSession();
requireRole('mis_manager');

$pdo = getDB();

// Build filters
$filters = [];
if (!empty($_GET['action']))
    $filters['action'] = $_GET['action'];
if (!empty($_GET['user_id']))
    $filters['user_id'] = $_GET['user_id'];
if (!empty($_GET['date_from']))
    $filters['date_from'] = $_GET['date_from'];
if (!empty($_GET['date_to']))
    $filters['date_to'] = $_GET['date_to'];

$logs = getAuditLogs($filters, 100);

// Get unique actions for filter
$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT user_id, name FROM users ORDER BY name")->fetchAll();

$page_title = 'Audit Logs';
include __DIR__ . '/../includes/header.php';
?>

<div class="fade-in">
    <h1>Audit Logs</h1>
    <p class="text-secondary mb-4">System activity and security events</p>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2" style="flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-control">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?= htmlspecialchars($action) ?>" <?= ($_GET['action'] ?? '') === $action ? 'selected' : '' ?>>
                                <?= htmlspecialchars($action) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1;min-width:200px;">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-control">
                        <option value="">All Users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['user_id'] ?>" <?= ($_GET['user_id'] ?? '') == $u['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1;min-width:150px;">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control"
                        value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                <div style="flex:1;min-width:150px;">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control"
                        value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>
                <div style="align-self:flex-end;" class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="logs.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Resource</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                    </td>
                                    <td><span class="badge badge-primary">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span></td>
                                    <td>
                                        <?= htmlspecialchars($log['resource'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['details'] ?? '-') ?>
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