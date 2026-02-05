<?php
/**
 * Login Page
 * Handles user authentication
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Set security headers
setSecurityHeaders();

// Initialize session
initSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = login($email, $password);

        if ($result['success']) {
            // Redirect based on role
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="card auth-card glass-card fade-in">
            <div class="auth-header">
                <div class="auth-logo">🎓</div>
                <h1 class="auth-title">Welcome to SIMS</h1>
                <p class="auth-subtitle">Student Information Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">
                    You have been logged out successfully.
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="your.email@sims.edu"
                        required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter your password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full mt-3">
                    Sign In
                </button>
            </form>

            <div class="mt-4 text-center text-muted" style="font-size: 0.875rem;">
               
           
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>

</html>