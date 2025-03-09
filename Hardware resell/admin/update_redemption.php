<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required parameters are present
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id = (int)$_POST['id'];
$status = $_POST['status'];

// Validate status
if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get redemption request details
    $request = $conn->query("SELECT * FROM redemption_requests WHERE id = $id")->fetch_assoc();
    if (!$request) {
        throw new Exception('Redemption request not found');
    }

    // If rejecting and status was pending, return coins to user
    if ($status === 'rejected' && $request['status'] === 'pending') {
        $coins = (int)$request['coins'];
        $user_id = (int)$request['user_id'];
        
        // Return coins to user
        $result = $conn->query("UPDATE users SET coins = coins + $coins WHERE id = $user_id");
        if (!$result) {
            throw new Exception('Failed to return coins to user');
        }
    }

    // Update redemption status
    $result = $conn->query("UPDATE redemption_requests SET status = '$status' WHERE id = $id");
    if (!$result) {
        throw new Exception('Failed to update redemption status');
    }

    // Create notification for user
    $message = $status === 'approved' ? 
        'Your coin redemption request has been approved.' : 
        'Your coin redemption request has been rejected. The coins have been returned to your account.';
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'general')");
    $stmt->bind_param('is', $request['user_id'], $message);
    if (!$stmt->execute()) {
        throw new Exception('Failed to create notification');
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
