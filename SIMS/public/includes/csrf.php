<?php
/**
 * CSRF Protection Module
 * Generates and validates CSRF tokens
 */

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken()
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }

    // Regenerate token if expired
    if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_LIFETIME) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }

    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token)
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }

    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token HTML input field
 * @return string
 */
function csrfField()
{
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token from POST request
 * Dies if invalid
 */
function verifyCSRFToken()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';

        if (!validateCSRFToken($token)) {
            // Log CSRF attempt
            if (function_exists('logAudit')) {
                $user_id = $_SESSION['user_id'] ?? null;
                logAudit($user_id, 'CSRF_VIOLATION', 'security', 'Invalid CSRF token detected');
            }

            http_response_code(403);
            die('Security violation detected. Please refresh the page and try again.');
        }
    }
}
