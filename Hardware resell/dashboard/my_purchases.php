<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle coin transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_coins'])) {
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
        $success_message = "Coins transferred successfully to seller!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

try {
    // Get user's purchases
    $stmt = $conn->prepare("
        SELECT pr.*, p.title, p.image_path, p.price,
               s.username as seller_name, s.email as seller_email, s.phone as seller_phone,
               b.username as buyer_name, b.email as buyer_email, b.phone as buyer_phone
        FROM purchase_requests pr
        JOIN products p ON pr.product_id = p.id
        JOIN users s ON pr.seller_id = s.id
        JOIN users b ON pr.buyer_id = b.id
        WHERE pr.buyer_id = ?
        ORDER BY pr.created_at DESC
    ");

    if ($stmt === false) {
        throw new Exception('Error preparing purchases query: ' . $conn->error);
    }

    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $purchases = [];
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="display-6">
                <i class="fas fa-shopping-cart text-primary"></i> My Purchases
            </h2>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($purchases)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven't made any purchases yet.
            <a href="<?php echo BASE_URL; ?>/products/list.php" class="alert-link">Browse products</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($purchases as $purchase): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0">
                                <a href="<?php echo BASE_URL; ?>/products/view.php?id=<?php echo $purchase['product_id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($purchase['title']); ?>
                                </a>
                            </h5>
                            <div class="d-flex gap-2">
                                <span class="badge bg-<?php 
                                    echo $purchase['status'] === 'pending' ? 'warning' : 
                                        ($purchase['status'] === 'accepted' ? 'success' : 
                                        ($purchase['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($purchase['status']); ?>
                                </span>
                                <?php if ($purchase['status'] === 'accepted'): ?>
                                    <span class="badge bg-<?php 
                                        echo $purchase['deal_status'] === 'pending_buyer' ? 'info' : 
                                            ($purchase['deal_status'] === 'completed' ? 'success' : 'secondary'); 
                                    ?>">
                                        <?php echo $purchase['deal_status'] === 'pending_buyer' ? 'Awaiting Coin Transfer' : 'Completed'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?php 
                                        echo $purchase['image_path'] 
                                            ? BASE_URL . '/uploads/' . $purchase['image_path']
                                            : BASE_URL . '/assets/images/placeholder.png';
                                    ?>" class="img-fluid rounded" alt="Product Image">
                                </div>
                                <div class="col-md-8 ps-md-4">
                                    <div class="mb-3">
                                        <h6 class="fw-bold text-primary mb-2">
                                            <i class="fas fa-money-bill-wave"></i> Payment Details
                                        </h6>
                                        <div class="bg-light p-3 rounded">
                                            <?php if ($purchase['coins_used'] > 0): ?>
                                                <div class="mb-2">
                                                    <i class="fas fa-coins text-warning"></i>
                                                    <strong>Coins:</strong> 
                                                    <?php echo number_format($purchase['coins_used']); ?>
                                                </div>
                                                <?php if ($purchase['status'] === 'accepted' && $purchase['deal_status'] === 'pending_buyer'): ?>
                                                    <form method="POST" class="mt-3" onsubmit="return confirm('Are you sure you want to transfer the coins to the seller? This action cannot be undone.');">
                                                        <input type="hidden" name="request_id" value="<?php echo $purchase['id']; ?>">
                                                        <button type="submit" name="transfer_coins" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-coins"></i> Transfer <?php echo number_format($purchase['coins_used']); ?> Coins to Seller
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div>
                                                <i class="fas fa-money-bill text-success"></i>
                                                <strong>Cash:</strong> 
                                                <span class="text-success">₹<?php echo number_format($purchase['cash_amount']); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($purchase['status'] === 'accepted'): ?>
                                        <div class="mb-3">
                                            <h6 class="fw-bold text-primary mb-2">
                                                <i class="fas fa-user"></i> Seller Information
                                            </h6>
                                            <div class="bg-light p-3 rounded">
                                                <div><strong>Name:</strong> <?php echo htmlspecialchars($purchase['seller_name']); ?></div>
                                                <div><strong>Email:</strong> <?php echo htmlspecialchars($purchase['seller_email']); ?></div>
                                                <div><strong>Phone:</strong> <?php echo htmlspecialchars($purchase['seller_phone']); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-muted">
                            <small>
                                <i class="far fa-clock"></i>
                                Requested on: <?php echo date('F j, Y g:i A', strtotime($purchase['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
