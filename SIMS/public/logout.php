<?php
/**
 * Logout Handler
 * Securely logs out the user
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session to access session data for logout
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Logout
logout();

// Redirect to login page
header('Location: login.php?logout=1');
exit;
