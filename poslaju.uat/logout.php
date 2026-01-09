<?php
// logout.php

// Start the session
session_start();

// Check if user is logged in
$was_logged_in = isset($_SESSION['user_id']);

// Log the logout action if user was logged in
if ($was_logged_in && file_exists('config.php')) {
    try {
        require_once 'config.php';
        
        // Create connection for logging
        $conn = createConnection();
        
        if ($conn) {
            // Log logout activity
            $logout_sql = "INSERT INTO admin_activity_log (admin_id, admin_username, action, ip_address, user_agent, created_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())";
            $logout_stmt = $conn->prepare($logout_sql);
            
            if ($logout_stmt) {
                $admin_id = $_SESSION['user_id'] ?? 0;
                $admin_username = $_SESSION['username'] ?? 'Unknown';
                $action = 'User logged out';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                $logout_stmt->bind_param("issss", $admin_id, $admin_username, $action, $ip_address, $user_agent);
                $logout_stmt->execute();
                $logout_stmt->close();
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        // Silently handle logging errors - don't prevent logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any other authentication cookies if they exist
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 3600, '/');
}

// Clear browser cache to prevent back button access
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page or home page
$redirect_url = 'index.php';

// Check if there's a specific redirect parameter
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
    // Only allow local redirects for security
    if (!filter_var($redirect, FILTER_VALIDATE_URL) || 
        (parse_url($redirect, PHP_URL_HOST) === null || 
         parse_url($redirect, PHP_URL_HOST) === $_SERVER['HTTP_HOST'])) {
        $redirect_url = $redirect;
    }
}

// Add logout success parameter
$redirect_url .= (strpos($redirect_url, '?') !== false) ? '&' : '?';
$redirect_url .= 'logout=success';

// Redirect with proper HTTP status
header("Location: " . $redirect_url, true, 302);
exit();
?>