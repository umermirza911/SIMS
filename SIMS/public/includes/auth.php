<?php
/**
 * Authentication Module
 * Handles user login, logout, and session management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/audit.php';

/**
 * Check login attempts and enforce account lockout
 * @param string $email
 * @return array ['allowed' => bool, 'remaining_attempts' => int, 'lockout_time' => int]
 */
function checkLoginAttempts($email)
{
    $pdo = getDB();

    // Clean up old attempts (older than lockout duration)
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([LOCKOUT_DURATION]);

    // Count failed attempts within lockout period
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt
        FROM login_attempts 
        WHERE email = ? AND success = FALSE 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$email, LOCKOUT_DURATION]);
    $result = $stmt->fetch();

    $attempts = $result['attempts'];
    $remaining = MAX_LOGIN_ATTEMPTS - $attempts;

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $last_attempt_time = strtotime($result['last_attempt']);
        $lockout_end = $last_attempt_time + LOCKOUT_DURATION;
        $time_remaining = $lockout_end - time();

        return [
            'allowed' => false,
            'remaining_attempts' => 0,
            'lockout_time' => max(0, $time_remaining)
        ];
    }

    return [
        'allowed' => true,
        'remaining_attempts' => $remaining,
        'lockout_time' => 0
    ];
}

/**
 * Record login attempt
 * @param string $email
 * @param bool $success
 */
function recordLoginAttempt($email, $success)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)");
    $stmt->execute([$email, getClientIP(), $success]);
}

/**
 * Authenticate user
 * @param string $email
 * @param string $password
 * @return array|false User data or false on failure
 */
function login($email, $password)
{
    $pdo = getDB();

    // Sanitize email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Check login attempts first
    $attemptCheck = checkLoginAttempts($email);
    if (!$attemptCheck['allowed']) {
        recordLoginAttempt($email, false);
        return [
            'success' => false,
            'error' => 'Account temporarily locked due to too many failed attempts. Please try again in ' . ceil($attemptCheck['lockout_time'] / 60) . ' minutes.',
            'locked' => true
        ];
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        recordLoginAttempt($email, true);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Log audit
        logAudit($user['user_id'], 'LOGIN', 'users', 'User logged in successfully');

        // Store session in database
        $session_id = session_id();
        $stmt = $pdo->prepare("
            INSERT INTO sessions (session_id, user_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$session_id, $user['user_id'], getClientIP(), getUserAgent()]);

        return [
            'success' => true,
            'user' => $user
        ];
    } else {
        // Failed login
        recordLoginAttempt($email, false);

        // Log failed attempt
        if ($user) {
            logAudit($user['user_id'], 'LOGIN_FAILED', 'users', 'Failed login attempt');
        } else {
            logAudit(null, 'LOGIN_FAILED', 'users', 'Failed login attempt for email: ' . $email);
        }

        $remaining = $attemptCheck['remaining_attempts'] - 1;
        return [
            'success' => false,
            'error' => 'Invalid email or password. ' . ($remaining > 0 ? $remaining . ' attempts remaining.' : ''),
            'locked' => false
        ];
    }
}

/**
 * Logout user
 */
function logout()
{
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Delete session from database
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->execute([session_id()]);

        // Log audit
        logAudit($user_id, 'LOGOUT', 'users', 'User logged out');
    }

    // Clear session data
    $_SESSION = array();

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require login - redirect to login page if not authenticated
 * @param string $redirect_to Page to redirect to after login
 */
function requireLogin($redirect_to = null)
{
    if (!isLoggedIn()) {
        $redirect_url = BASE_URL . '/public/login.php';
        if ($redirect_to) {
            $redirect_url .= '?redirect=' . urlencode($redirect_to);
        }
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Require specific role - redirect if user doesn't have permission
 * @param string|array $allowed_roles
 */
function requireRole($allowed_roles)
{
    requireLogin();

    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        // Log unauthorized access attempt
        logAudit(
            $_SESSION['user_id'],
            'UNAUTHORIZED_ACCESS',
            'access_control',
            'Attempted to access restricted resource'
        );

        // Redirect to appropriate dashboard
        header('Location: ' . BASE_URL . '/public/index.php?error=unauthorized');
        exit;
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole()
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user name
 * @return string|null
 */
function getCurrentUserName()
{
    return $_SESSION['user_name'] ?? null;
}
