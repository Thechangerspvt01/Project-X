<?php
/**
 * Authentication Helpers
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user has vendor role
 */
function isVendor() {
    // Add debug logging
    error_log("Checking vendor role - Session data: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['user_role'])) {
        error_log("No user_role in session");
        return false;
    }
    
    $isVendor = $_SESSION['user_role'] === 'vendor';
    error_log("Is vendor check result: " . ($isVendor ? 'true' : 'false'));
    return $isVendor;
}

/**
 * Check if user has regular user role
 */
function isUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

/**
 * User Data Helpers
 */

/**
 * Get user data by ID
 */
function getUserData($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * File Upload Helpers
 */

/**
 * Validate and sanitize file upload
 */
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File is too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file was uploaded';
                break;
            default:
                $errors[] = 'Unknown upload error';
        }
        return ['valid' => false, 'errors' => $errors];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = 'Invalid file type. Only JPG, PNG and GIF are allowed.';
        return ['valid' => false, 'errors' => $errors];
    }
    
    return ['valid' => true, 'errors' => []];
}

/**
 * Generate a unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
}

/**
 * Notification Helpers
 */

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 5) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId, $userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

/**
 * Time Helpers
 */

/**
 * Calculate time ago
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'just now';
}

/**
 * Format Helpers
 */

/**
 * Format condition status for display
 */
function formatCondition($condition) {
    return ucwords(str_replace('_', ' ', $condition));
}
?>
