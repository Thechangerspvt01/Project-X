<?php
require_once '../includes/config.php';

if (!isVendor()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    try {
        $conn->begin_transaction();

        // Get request details
        $stmt = $conn->prepare("
            SELECT er.*, u.username, u.id as user_id
            FROM exchange_requests er
            JOIN users u ON er.user_id = u.id
            WHERE er.id = ? AND er.status = 'pending'
        ");
        
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$request) {
            throw new Exception('Invalid request or already processed');
        }

        if ($action === 'inspect') {
            $coin_offer = $_POST['coin_offer'];
            if (!is_numeric($coin_offer) || $coin_offer < 1) {
                throw new Exception('Invalid coin offer amount');
            }

            // Check if another vendor hasn't already made an offer
            $stmt = $conn->prepare("
                SELECT id FROM exchange_requests 
                WHERE id = ? AND status = 'pending'
                FOR UPDATE
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $current = $stmt->get_result()->fetch_assoc();
            
            if (!$current) {
                throw new Exception('This request is no longer available');
            }

            // Update request status and coin offer
            $stmt = $conn->prepare("
                UPDATE exchange_requests 
                SET status = 'inspected', coin_offer = ?, vendor_id = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->bind_param("iii", $coin_offer, $_SESSION['user_id'], $request_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception('Failed to update request. It may have been taken by another vendor.');
            }

            // Create notification for user
            $message = "Your exchange request for '{$request['product_name']}' has been inspected. The vendor is offering {$coin_offer} coins. Please respond to this offer.";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message)
                VALUES (?, 'exchange_inspected', ?)
            ");
            $stmt->bind_param("is", $request['user_id'], $message);
            $stmt->execute();

            $conn->commit();
            setFlashMessage('success', 'Coin offer submitted successfully!');
        } elseif ($action === 'reject') {
            // Update request status
            $stmt = $conn->prepare("
                UPDATE exchange_requests 
                SET status = 'rejected', vendor_id = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $request_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception('Failed to reject request. It may have been taken by another vendor.');
            }

            // Create notification for user
            $message = "Your exchange request for '{$request['product_name']}' has been rejected.";
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message)
                VALUES (?, 'exchange_rejected', ?)
            ");
            $stmt->bind_param("is", $request['user_id'], $message);
            $stmt->execute();

            $conn->commit();
            setFlashMessage('success', 'Exchange request rejected successfully.');
        }
    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('error', $e->getMessage());
    }
}

header('Location: ' . BASE_URL . '/vendor/index.php');
exit;