<?php
/**
 * Database Configuration (EXAMPLE)
 * 
 * INSTRUCTIONS:
 * 1. Rename this file to 'database.php'
 * 2. Update the credentials below with your local database settings
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'sims_db');
define('DB_USER', 'YOUR_DB_USER'); // e.g., root
define('DB_PASS', 'YOUR_DB_PASSWORD'); // e.g., empty string or your password
define('DB_CHARSET', 'utf8mb4');

// PDO options for security
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
];

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
} catch (PDOException $e) {
    // Don't expose database errors to users (psychological acceptability)
    error_log("Database Connection Error: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}

/**
 * Get database connection
 * @return PDO
 */
function getDB()
{
    global $pdo;
    return $pdo;
}
