<?php
// Session security enhancements - MUST be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Start the session
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hardware_resell';

// Base URL configuration
define('BASE_URL', '/Hardware resell');

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Include helper functions
require_once __DIR__ . '/helpers.php';

// Session validation
function validateSession() {
    if (isset($_SESSION['user_id'])) {
        // Check if user exists and role matches
        global $conn;
        $stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user || $user['role'] !== $_SESSION['user_role']) {
            // Session data doesn't match database, destroy session
            error_log("Invalid session detected - User ID: {$_SESSION['user_id']}, Role: {$_SESSION['user_role']}");
            session_destroy();
            return false;
        }
        return true;
    }
    return false;
}

// Validate session on every request
if (!validateSession() && isset($_SESSION['user_id'])) {
    // Clear invalid session
    session_destroy();
    session_start();
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Debug session data
error_log("Session data in config.php: " . print_r($_SESSION, true));
?>
