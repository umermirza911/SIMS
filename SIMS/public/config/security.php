<?php
/**
 * Security Configuration
 * Central security settings and constants
 */

// Session configuration
define('SESSION_NAME', 'SIMS_SESSION');
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('SESSION_REGENERATE_INTERVAL', 300); // Regenerate session ID every 5 minutes

// Base URL for redirects (adjust if deployed in a subdirectory)
define('BASE_URL', '/SIMS');

// Failed login attempt settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

// Password policy
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_ALGO', PASSWORD_DEFAULT); // bcrypt

// CSRF token settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Security headers
function setSecurityHeaders()
{
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");

    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");

    // Prevent clickjacking
    header("X-Frame-Options: DENY");

    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

    // Referrer policy
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Force HTTPS in production (uncomment when deployed)
    // header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Configure secure session settings
function initSecureSession()
{
    // Prevent session fixation
    ini_set('session.use_strict_mode', 1);

    // Set secure cookie parameters
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '', // Set to your domain in production
        'secure' => false, // Set to true when using HTTPS
        'httponly' => true, // Prevent JavaScript access
        'samesite' => 'Strict' // CSRF protection
    ]);

    session_name(SESSION_NAME);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

// Get client IP address
function getClientIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}

// Get user agent
function getUserAgent()
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}
