<?php
// config.php - Database configuration

// Session security settings - Must be set before any output
if (!headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
}

// Error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Database settings - Update these for production
$db_config = [
    'host' => 'localhost', // Change to your hosting database host, e.g. 'sql123.epizy.com'
    'username' => 'root', // Change to your database username, e.g. 'epiz_12345678'
    'password' => '', // Change to your database password
    'database' => 'poslaju_sla' // Change to your database name
];

// For production, you can also use environment variables:
// $db_config = [
//     'host' => getenv('DB_HOST') ?: 'localhost',
//     'username' => getenv('DB_USER') ?: 'root',
//     'password' => getenv('DB_PASS') ?: '',
//     'database' => getenv('DB_NAME') ?: 'poslaju_sla'
// ];

// Database connection function
if (!function_exists('createConnection')) {
    function createConnection() {
        global $db_config;
        $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Database connection failed. Please check logs.");
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    }
}

// Sanitize input function
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
}

// Hash password function
if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

// Verify password function
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Start secure session
if (!function_exists('startSecureSession')) {
    function startSecureSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        startSecureSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

// Logout function
if (!function_exists('logout')) {
    function logout() {
        startSecureSession();
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// Get app config
if (!function_exists('getAppConfig')) {
    function getAppConfig($key) {
        $configs = [
            'company_name' => 'Poslaju',
            'app_name' => 'Poslaju Tracking System',
            'version' => '1.0.0'
        ];
        return $configs[$key] ?? '';
    }
}

return $db_config;
?>
