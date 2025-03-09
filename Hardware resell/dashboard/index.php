<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = '/Hardware resell/dashboard';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's products
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE seller_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's purchase requests
$stmt = $conn->prepare("
    SELECT pr.*, p.title, p.image_path, u.username as seller_name
    FROM purchase_requests pr
    JOIN products p ON pr.product_id = p.id
    JOIN users u ON pr.seller_id = u.id
    WHERE pr.buyer_id = ?
    ORDER BY pr.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's exchange requests
$stmt = $conn->prepare("
    SELECT * FROM exchange_requests 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$exchanges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats = [
    'total_products' => $conn->query("SELECT COUNT(*) as count FROM products WHERE seller_id = " . $_SESSION['user_id'])->fetch_assoc()['count'],
    'active_products' => $conn->query("SELECT COUNT(*) as count FROM products WHERE seller_id = " . $_SESSION['user_id'] . " AND status = 'available'")->fetch_assoc()['count'],
    'total_purchases' => $conn->query("SELECT COUNT(*) as count FROM purchase_requests WHERE buyer_id = " . $_SESSION['user_id'])->fetch_assoc()['count'],
    'total_exchanges' => $conn->query("SELECT COUNT(*) as count FROM exchange_requests WHERE user_id = " . $_SESSION['user_id'])->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- User Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h4>
                        <p class="text-muted mb-0">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="coin-display d-inline-block">
                            <i class="fas fa-coins"></i> <?php echo $user['coins']; ?> coins
                        </div>
                        <a href="<?php echo BASE_URL; ?>/dashboard/redeem.php" class="btn btn-warning ms-2">
                            <i class="fas fa-exchange-alt"></i> Redeem Coins
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Products</h6>
                        <h2 class="mb-0"><?php echo $stats['total_products']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Active Products</h6>
                        <h2 class="mb-0"><?php echo $stats['active_products']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Purchases</h6>
                        <h2 class="mb-0"><?php echo $stats['total_purchases']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Exchange Requests</h6>
                        <h2 class="mb-0"><?php echo $stats['total_exchanges']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- My Products -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">My Products</h5>
                        <a href="<?php echo BASE_URL; ?>/products/create.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> List New Product
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="alert alert-info">
                                You haven't listed any products yet.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($products as $product): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['title']); ?></h6>
                                                <small class="text-muted">
                                                    Price: <?php echo formatPrice($product['price']); ?> + 
                                                    <?php echo $product['coin_price']; ?> coins
                                                </small>
                                            </div>
                                            <span class="badge bg-<?php 
                                                echo $product['status'] === 'available' ? 'success' : 
                                                    ($product['status'] === 'pending' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($product['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?php echo BASE_URL; ?>/dashboard/products.php" class="btn btn-link">View All Products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Purchases -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Purchases</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($purchases)): ?>
                            <div class="alert alert-info">
                                You haven't made any purchases yet.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($purchases as $purchase): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($purchase['title']); ?></h6>
                                                <small class="text-muted">
                                                    From: <?php echo htmlspecialchars($purchase['seller_name']); ?><br>
                                                    Amount: <?php echo formatPrice($purchase['cash_amount']); ?> + 
                                                    <?php echo $purchase['coins_used']; ?> coins
                                                </small>
                                            </div>
                                            <span class="badge bg-<?php 
                                                echo $purchase['status'] === 'completed' ? 'success' : 
                                                    ($purchase['status'] === 'rejected' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($purchase['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?php echo BASE_URL; ?>/dashboard/purchases.php" class="btn btn-link">View All Purchases</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Exchange Requests -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Exchange Requests</h5>
                        <a href="<?php echo BASE_URL; ?>/exchange/request.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> New Exchange Request
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($exchanges)): ?>
                            <div class="alert alert-info">
                                You haven't submitted any exchange requests yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Status</th>
                                            <th>Coin Offer</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exchanges as $exchange): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exchange['product_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $exchange['status'] === 'completed' ? 'success' : 
                                                            ($exchange['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($exchange['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($exchange['coin_offer']): ?>
                                                        <span class="coin-display">
                                                            <i class="fas fa-coins"></i> <?php echo $exchange['coin_offer']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($exchange['created_at'])); ?></td>
                                                <td>
                                                    <a href="<?php echo BASE_URL; ?>/exchange/view.php?id=<?php echo $exchange['id']; ?>" 
                                                       class="btn btn-sm btn-primary">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?php echo BASE_URL; ?>/exchange/list.php" class="btn btn-link">View All Exchange Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
