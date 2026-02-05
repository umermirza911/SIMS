<?php
/**
 * Audit Logging Module
 * Records all sensitive operations for accountability
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

/**
 * Log an audit event
 * @param int|null $user_id User who performed the action
 * @param string $action Action performed (e.g., 'LOGIN', 'CREATE_STUDENT')
 * @param string $resource Resource affected (e.g., 'students', 'departments')
 * @param string $details Additional details about the action
 * @param int|null $resource_id ID of affected resource
 * @return bool Success status
 */
function logAudit($user_id, $action, $resource, $details = '', $resource_id = null)
{
    try {
        $pdo = getDB();

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, resource, resource_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $user_id,
            $action,
            $resource,
            $resource_id,
            $details,
            getClientIP()
        ]);
    } catch (PDOException $e) {
        // Log to error log but don't fail the operation
        error_log("Audit Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit logs with optional filtering
 * @param array $filters Optional filters (user_id, action, resource, date_from, date_to)
 * @param int $limit Number of records to return
 * @param int $offset Offset for pagination
 * @return array
 */
function getAuditLogs($filters = [], $limit = 100, $offset = 0)
{
    $pdo = getDB();

    $where = [];
    $params = [];

    if (!empty($filters['user_id'])) {
        $where[] = "al.user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (!empty($filters['action'])) {
        $where[] = "al.action = ?";
        $params[] = $filters['action'];
    }

    if (!empty($filters['resource'])) {
        $where[] = "al.resource = ?";
        $params[] = $filters['resource'];
    }

    if (!empty($filters['date_from'])) {
        $where[] = "DATE(al.created_at) >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = "DATE(al.created_at) <= ?";
        $params[] = $filters['date_to'];
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT al.*, u.name as user_name, u.email as user_email
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Get total count of audit logs (for pagination)
 * @param array $filters Optional filters
 * @return int
 */
function getAuditLogsCount($filters = [])
{
    $pdo = getDB();

    $where = [];
    $params = [];

    if (!empty($filters['user_id'])) {
        $where[] = "user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (!empty($filters['action'])) {
        $where[] = "action = ?";
        $params[] = $filters['action'];
    }

    if (!empty($filters['resource'])) {
        $where[] = "resource = ?";
        $params[] = $filters['resource'];
    }

    if (!empty($filters['date_from'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs $where_clause");
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

/**
 * Get recent activities for a user
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function getUserRecentActivities($user_id, $limit = 10)
{
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT *
        FROM audit_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");

    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}
