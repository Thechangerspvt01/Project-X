<?php
require_once '../includes/config.php';

// Log the logout event
error_log("User logging out - Session data before logout: " . print_r($_SESSION, true));

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Set a flash message for the next login
setFlashMessage('success', 'You have been successfully logged out.');

// Redirect to login page
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
?>
