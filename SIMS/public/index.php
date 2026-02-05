<?php
/**
 * Main Dashboard - Role-based redirect
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Set security headers
setSecurityHeaders();

// Initialize session
initSecureSession();

// Require login
requireLogin();



// Redirect based on role
$role = getCurrentUserRole();

switch ($role) {
    case 'mis_manager':
        header('Location: ../mis_manager/dashboard.php');
        break;
    case 'coordinator':
        header('Location: ../coordinator/dashboard.php');
        break;
    case 'teacher':
        header('Location: ../teacher/dashboard.php');
        break;
    default:
        // Shouldn't happen, but logout if invalid role
        logout();
        header('Location: login.php?error=invalid_role');
        break;
}
exit;
