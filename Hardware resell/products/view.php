<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/products/list.php');
    exit;
}

$product_id = $_GET['id'];
$error_message = '';
$success_message = '';

try {
    // Get product details
    $stmt = $conn->prepare("
        SELECT p.*, u.username as seller_name, u.email as seller_email, u.phone as seller_phone,
               u.id as seller_id
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ?
    ");

    if ($stmt === false) {
        throw new Exception('Error preparing product query: ' . $conn->error);
    }

    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        header('Location: ' . BASE_URL . '/products/list.php');
        exit;
    }

    // Get buyer's coins if logged in
    $buyer_coins = 0;
    if (isLoggedIn()) {
        $coins_stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
        if ($coins_stmt === false) {
            throw new Exception('Error preparing coins query: ' . $conn->error);
        }
        $coins_stmt->bind_param("i", $_SESSION['user_id']);
        $coins_stmt->execute();
        $result = $coins_stmt->get_result()->fetch_assoc();
        $buyer_coins = $result['coins'];
        $coins_stmt->close();
    }

    // Handle purchase request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_purchase'])) {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }

        if ($product['seller_id'] == $_SESSION['user_id']) {
            $error_message = "You cannot purchase your own product.";
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Check if there's already a pending request
                $check_stmt = $conn->prepare("
                    SELECT id 
                    FROM purchase_requests 
                    WHERE product_id = ? AND buyer_id = ? AND status = 'pending'
                ");

                if ($check_stmt === false) {
                    throw new Exception('Error preparing check query: ' . $conn->error);
                }

                $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
                $check_stmt->execute();
                $existing_request = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($existing_request) {
                    throw new Exception("You already have a pending request for this product.");
                }

                // Calculate coins to use (minimum of available coins and product price)
                $coins_to_use = min($buyer_coins, $product['price']);
                $cash_amount = $product['price'] - $coins_to_use;

                // Create purchase request
                $insert_stmt = $conn->prepare("
                    INSERT INTO purchase_requests (product_id, buyer_id, seller_id, coins_used, cash_amount, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");

                if ($insert_stmt === false) {
                    throw new Exception('Error preparing insert query: ' . $conn->error);
                }

                $insert_stmt->bind_param("iiiii", $product_id, $_SESSION['user_id'], $product['seller_id'], $coins_to_use, $cash_amount);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception('Failed to create purchase request: ' . $insert_stmt->error);
                }
                $insert_stmt->close();

                // Create notification for seller
                $notification_message = "New purchase request for your product: " . $product['title'];
                if ($coins_to_use > 0) {
                    $notification_message .= "\nBuyer wants to use {$coins_to_use} coins + ₹" . number_format($cash_amount) . " cash";
                }
                
                $notify_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, message, type)
                    VALUES (?, ?, 'purchase_request')
                ");

                if ($notify_stmt === false) {
                    throw new Exception('Error preparing notification query: ' . $conn->error);
                }

                $notify_stmt->bind_param("is", $product['seller_id'], $notification_message);
                
                if (!$notify_stmt->execute()) {
                    throw new Exception('Failed to create notification: ' . $notify_stmt->error);
                }
                $notify_stmt->close();

                $conn->commit();
                $success_message = "Purchase request sent successfully! Coins will be deducted after seller confirms and you approve the transfer.";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-image {
            max-height: 500px;
            width: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background: #f8f9fa;
            padding: 20px;
        }
        .product-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .product-price {
            font-size: 2rem;
            color: #28a745;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        .product-details {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .product-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #666;
            margin-bottom: 1.5rem;
        }
        .seller-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .seller-info i {
            width: 25px;
            color: #6c757d;
        }
        .action-buttons {
            margin-top: 2rem;
        }
        .badge {
            font-size: 1rem;
            padding: 8px 16px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-4">
                <img src="<?php 
                    echo $product['image_path'] 
                        ? BASE_URL . '/uploads/' . $product['image_path']
                        : BASE_URL . '/assets/images/placeholder.png';
                    ?>" 
                    class="product-image" 
                    alt="<?php echo htmlspecialchars($product['title']); ?>">
            </div>
            <div class="col-md-6">
                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                
                <div class="product-details">
                    <h4>Product Details</h4>
                    <div class="mb-3">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($product['category']); ?></span>
                        <span class="badge bg-info"><?php echo formatCondition($product['condition_status']); ?></span>
                    </div>
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <div class="seller-info">
                        <h5 class="mb-3">Seller Information</h5>
                        <p class="mb-2">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($product['seller_name']); ?>
                        </p>
                        <?php if (isLoggedIn() && isset($_SESSION['user_id']) && $product['seller_id'] == $_SESSION['user_id']): ?>
                            <p class="mb-2">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($product['seller_email']); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($product['seller_phone']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-coins"></i> Your Coins: <?php echo $buyer_coins; ?>
                            <?php if ($buyer_coins > 0): ?>
                                <?php
                                $usable_coins = min($buyer_coins, $product['price']);
                                $remaining_cash = $product['price'] - $usable_coins;
                                ?>
                                <hr>
                                <strong>Purchase Breakdown:</strong><br>
                                Coins to use: <?php echo $usable_coins; ?> (₹<?php echo number_format($usable_coins); ?>)<br>
                                Cash to pay: ₹<?php echo number_format($remaining_cash); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <?php if (isLoggedIn() && $product['seller_id'] != $_SESSION['user_id']): ?>
                            <form method="POST">
                                <button type="submit" name="request_purchase" class="btn btn-primary btn-lg">
                                    <i class="fas fa-shopping-cart"></i> Request Purchase
                                </button>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Login to Purchase
                            </a>
                        <?php elseif ($product['seller_id'] == $_SESSION['user_id']): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> This is your product listing
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
