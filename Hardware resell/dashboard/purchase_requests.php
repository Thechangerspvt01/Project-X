<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Add flash message display
if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif;

// Handle actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    try {
        $conn->begin_transaction();

        // Get request details first
        $get_request_sql = "
            SELECT pr.*, p.title as product_title, p.id as product_id,
                   s.email as seller_email, s.phone as seller_phone,
                   s.username as seller_name, b.username as buyer_name,
                   b.id as buyer_id, b.coins as buyer_coins
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            JOIN users s ON pr.seller_id = s.id
            JOIN users b ON pr.buyer_id = b.id
            WHERE pr.id = ? AND pr.seller_id = ? AND pr.status = 'pending'
        ";
        
        $stmt = $conn->prepare($get_request_sql);
        if (!$stmt) {
            throw new Exception("Error preparing request query: " . $conn->error);
        }
        
        if (!$stmt->bind_param("ii", $request_id, $_SESSION['user_id'])) {
            throw new Exception("Error binding parameters for request query: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing request query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();

        if (!$request) {
            throw new Exception('Invalid request or not authorized');
        }

        if ($action === 'accept') {
            // Update request status
            $update_request_sql = "
                UPDATE purchase_requests 
                SET status = 'accepted',
                    deal_status = 'pending_buyer'
                WHERE id = ? 
                AND seller_id = ? 
                AND status = 'pending'
            ";
            
            $stmt = $conn->prepare($update_request_sql);
            if (!$stmt) {
                throw new Exception("Error preparing accept status update: " . $conn->error);
            }
            
            if (!$stmt->bind_param("ii", $request_id, $_SESSION['user_id'])) {
                throw new Exception("Error binding parameters for accept status update: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing accept status update: " . $stmt->error);
            }
            $stmt->close();

            // Update product status but keep it as pending until coin transfer
            $update_product_sql = "
                UPDATE products 
                SET status = 'pending_payment'
                WHERE id = ?
            ";
            
            $stmt = $conn->prepare($update_product_sql);
            if (!$stmt) {
                throw new Exception("Error preparing product status update: " . $conn->error);
            }
            
            if (!$stmt->bind_param("i", $request['product_id'])) {
                throw new Exception("Error binding parameters for product status update: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing product status update: " . $stmt->error);
            }
            $stmt->close();

            // Reject other requests
            $reject_others_sql = "
                UPDATE purchase_requests 
                SET status = 'rejected',
                    rejection_reason = 'Product sold to another buyer'
                WHERE product_id = ? 
                AND id != ? 
                AND status = 'pending'
            ";
            
            $stmt = $conn->prepare($reject_others_sql);
            if (!$stmt) {
                throw new Exception("Error preparing other requests rejection: " . $conn->error);
            }
            
            if (!$stmt->bind_param("ii", $request['product_id'], $request_id)) {
                throw new Exception("Error binding parameters for other requests rejection: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing other requests rejection: " . $stmt->error);
            }
            $stmt->close();

            // Create notification
            $notification_message = 'Your purchase request for "' . $request['product_title'] . '" has been accepted! Please proceed with the coin transfer to complete the purchase.';
            $notification_sql = "
                INSERT INTO notifications (user_id, type, message, reference_id)
                VALUES (?, 'purchase_accepted', ?, ?)
            ";
            
            $stmt = $conn->prepare($notification_sql);
            if (!$stmt) {
                throw new Exception("Error preparing notification insert: " . $conn->error);
            }
            
            if (!$stmt->bind_param("isi", $request['buyer_id'], $notification_message, $request_id)) {
                throw new Exception("Error binding parameters for notification insert: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing notification insert: " . $stmt->error);
            }
            $stmt->close();

            $conn->commit();
            setFlashMessage('success', 'Purchase request accepted successfully! The product has been marked as sold.');
            
        } elseif ($action === 'reject') {
            // Update request status
            $reject_sql = "
                UPDATE purchase_requests 
                SET status = 'rejected'
                WHERE id = ? 
                AND seller_id = ? 
                AND status = 'pending'
            ";
            
            $stmt = $conn->prepare($reject_sql);
            if (!$stmt) {
                throw new Exception("Error preparing reject status update: " . $conn->error);
            }
            
            if (!$stmt->bind_param("ii", $request_id, $_SESSION['user_id'])) {
                throw new Exception("Error binding parameters for reject status update: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing reject status update: " . $stmt->error);
            }
            $stmt->close();

            // Update product status
            $update_product_sql = "
                UPDATE products 
                SET status = 'available'
                WHERE id = ?
            ";
            
            $stmt = $conn->prepare($update_product_sql);
            if (!$stmt) {
                throw new Exception("Error preparing product status update: " . $conn->error);
            }
            
            if (!$stmt->bind_param("i", $request['product_id'])) {
                throw new Exception("Error binding parameters for product status update: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing product status update: " . $stmt->error);
            }
            $stmt->close();

            // Create notification
            $notification_sql = "
                INSERT INTO notifications (user_id, type, message, reference_id)
                VALUES (?, 'purchase_rejected', ?, ?)
            ";
            
            $stmt = $conn->prepare($notification_sql);
            if (!$stmt) {
                throw new Exception("Error preparing notification insert: " . $conn->error);
            }
            
            $notification_message = 'Your purchase request has been rejected.';
            if (!$stmt->bind_param("isi", $request['buyer_id'], $notification_message, $request_id)) {
                throw new Exception("Error binding parameters for notification insert: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing notification insert: " . $stmt->error);
            }
            $stmt->close();

            $conn->commit();
            setFlashMessage('success', 'Purchase request rejected successfully!');
        }

    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('error', $e->getMessage());
    }
    
    // Redirect after processing
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle coin transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_coins']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    
    try {
        $conn->begin_transaction();

        // Get request details
        $stmt = $conn->prepare("
            SELECT pr.*, p.title, u.username as seller_name, u.coins as buyer_coins
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            JOIN users u ON pr.buyer_id = u.id
            WHERE pr.id = ? AND pr.buyer_id = ? AND pr.status = 'accepted' AND pr.deal_status = 'pending_buyer'
        ");

        if ($stmt === false) {
            throw new Exception('Error preparing request query: ' . $conn->error);
        }

        $stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$request) {
            throw new Exception('Invalid request or coins already transferred.');
        }

        // Check if buyer has enough coins
        if ($request['buyer_coins'] < $request['coins_used']) {
            throw new Exception('You do not have enough coins to complete this transfer.');
        }

        // Deduct coins from buyer
        $stmt = $conn->prepare("
            UPDATE users 
            SET coins = coins - ?
            WHERE id = ?
        ");

        if ($stmt === false) {
            throw new Exception('Error preparing buyer update query: ' . $conn->error);
        }

        $stmt->bind_param("ii", $request['coins_used'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        // Transfer coins to seller
        $stmt = $conn->prepare("
            UPDATE users 
            SET coins = coins + ?
            WHERE id = ?
        ");

        if ($stmt === false) {
            throw new Exception('Error preparing seller update query: ' . $conn->error);
        }

        $stmt->bind_param("ii", $request['coins_used'], $request['seller_id']);
        $stmt->execute();
        $stmt->close();

        // Update request status
        $stmt = $conn->prepare("
            UPDATE purchase_requests 
            SET deal_status = 'completed'
            WHERE id = ?
        ");

        if ($stmt === false) {
            throw new Exception('Error preparing request update query: ' . $conn->error);
        }

        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();

        // Update product status to sold
        $stmt = $conn->prepare("
            UPDATE products 
            SET status = 'sold'
            WHERE id = ?
        ");

        if ($stmt === false) {
            throw new Exception('Error preparing product update query: ' . $conn->error);
        }

        $stmt->bind_param("i", $request['product_id']);
        $stmt->execute();
        $stmt->close();

        // Notify seller
        $message = "Buyer has transferred {$request['coins_used']} coins for the product: {$request['title']}";
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type)
            VALUES (?, ?, 'purchase_completed')
        ");

        if ($stmt === false) {
            throw new Exception('Error preparing notification query: ' . $conn->error);
        }

        $stmt->bind_param("is", $request['seller_id'], $message);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        setFlashMessage('success', 'Coins transferred successfully to seller!');
    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('error', $e->getMessage());
    }
}

// Get pending purchase requests
$stmt = $conn->prepare("
    SELECT pr.*, p.title, p.image_path, p.price,
           u.username as buyer_name, u.email as buyer_email, u.phone as buyer_phone,
           s.username as seller_name, s.email as seller_email, s.phone as seller_phone
    FROM purchase_requests pr
    JOIN products p ON pr.product_id = p.id
    JOIN users u ON pr.buyer_id = u.id
    JOIN users s ON pr.seller_id = s.id
    WHERE pr.seller_id = ? OR pr.buyer_id = ?
    ORDER BY pr.created_at DESC
");

if ($stmt === false) {
    throw new Exception('Error preparing requests query: ' . $conn->error);
}

$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Include the header
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Requests - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .request-card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .request-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .request-body {
            padding: 20px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-accepted { background: #28a745; color: #fff; }
        .status-rejected { background: #dc3545; color: #fff; }
        .status-completed { background: #198754; color: #fff; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Purchase Requests</h2>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#pending">
                    Pending Requests
                    <?php if (count($requests) > 0): ?>
                        <span class="badge bg-primary"><?php echo count($requests); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#history">
                    Request History
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Pending Requests -->
            <div class="tab-pane fade show active" id="pending">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No pending purchase requests.
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="<?php 
                                        echo $request['image_path'] 
                                            ? BASE_URL . '/uploads/' . $request['image_path']
                                            : BASE_URL . '/assets/images/placeholder.png';
                                    ?>" class="product-image me-3" alt="Product Image">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($request['title']); ?></h5>
                                        <p class="mb-0 text-muted">
                                            Requested by: <?php echo htmlspecialchars($request['buyer_name']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="h5 mb-1">₹<?php echo number_format($request['price']); ?></div>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="request-body p-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mb-0">
                                                    <strong>Payment Details:</strong><br>
                                                    <?php if ($request['coins_used'] > 0): ?>
                                                        Coins: <?php echo $request['coins_used']; ?><br>
                                                        Cash: ₹<?php echo number_format($request['cash_amount']); ?>
                                                    <?php else: ?>
                                                        Full Payment: ₹<?php echo number_format($request['price']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div>
                                                <?php if ($request['buyer_id'] == $_SESSION['user_id'] && $request['status'] === 'accepted' && $request['deal_status'] === 'pending_buyer'): ?>
                                                    <form method="POST" class="d-inline-block">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <button type="submit" name="transfer_coins" class="btn btn-primary"
                                                                onclick="return confirm('Are you sure you want to transfer <?php echo number_format($request['coins_used']); ?> coins to the seller? This action cannot be undone.')">
                                                            <i class="fas fa-coins"></i> Transfer <?php echo number_format($request['coins_used']); ?> Coins
                                                        </button>
                                                    </form>
                                                <?php elseif ($request['seller_id'] == $_SESSION['user_id'] && $request['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline-block">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <button type="submit" name="action" value="accept" 
                                                                class="btn btn-success me-2"
                                                                onclick="return confirm('Are you sure you want to accept this purchase request?')">
                                                            <i class="fas fa-check"></i> Accept
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline-block">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <button type="submit" name="action" value="reject" 
                                                                class="btn btn-danger"
                                                                onclick="return confirm('Are you sure you want to reject this purchase request?')">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="buyer-details">
                                            <h6>Buyer Information:</h6>
                                            <p class="mb-0">
                                                <strong>Name:</strong> <?php echo htmlspecialchars($request['buyer_name']); ?><br>
                                                <strong>Email:</strong> <?php echo htmlspecialchars($request['buyer_email']); ?><br>
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($request['buyer_phone']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Request History -->
            <div class="tab-pane fade" id="history">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No request history available.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Buyer</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php 
                                                    echo $request['image_path'] 
                                                        ? BASE_URL . '/uploads/' . $request['image_path']
                                                        : BASE_URL . '/assets/images/placeholder.png';
                                                ?>" class="product-image me-2" style="width: 50px; height: 50px;" alt="Product Image">
                                                <?php echo htmlspecialchars($request['title']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['buyer_name']); ?></td>
                                        <td>₹<?php echo number_format($request['price']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $request['status'] === 'pending' ? 'warning' : 
                                                    ($request['status'] === 'accepted' ? 'success' : 
                                                    ($request['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                            <?php if ($request['status'] === 'accepted'): ?>
                                                <span class="badge bg-<?php 
                                                    echo $request['deal_status'] === 'pending_buyer' ? 'info' : 
                                                        ($request['deal_status'] === 'completed' ? 'success' : 'secondary'); 
                                                ?>">
                                                    <?php echo $request['deal_status'] === 'pending_buyer' ? 'Awaiting Coin Transfer' : 'Completed'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
