<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'],
    'vendors' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'vendor'")->fetch_assoc()['count'],
    'products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'exchanges' => $conn->query("SELECT COUNT(*) as count FROM exchange_requests")->fetch_assoc()['count'],
    'purchases' => $conn->query("SELECT COUNT(*) as count FROM purchase_requests")->fetch_assoc()['count']
];

// Get recent activities
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_products = $conn->query("SELECT p.*, u.username FROM products p JOIN users u ON p.seller_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_exchanges = $conn->query("SELECT e.*, u.username FROM exchange_requests e JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="list-group">
                    <a href="<?php echo BASE_URL; ?>/admin" class="list-group-item list-group-item-action active">
                        <i class="fas fa-dashboard"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/vendors.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-store"></i> Vendors
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/products.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/exchanges.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-exchange-alt"></i> Exchanges
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/purchases.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart"></i> Purchases
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/redemptions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-coins"></i> Redemptions
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h2 class="mb-4">Admin Dashboard</h2>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Regular Users</h5>
                                <h2 class="card-text"><?php echo $stats['users']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Vendors</h5>
                                <h2 class="card-text"><?php echo $stats['vendors']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <h2 class="card-text"><?php echo $stats['products']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Exchange Requests</h5>
                                <h2 class="card-text"><?php echo $stats['exchanges']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Users</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_users as $user): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo date('M j', strtotime($user['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Products -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_products as $product): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($product['title']); ?></strong><br>
                                                    <small class="text-muted">by <?php echo htmlspecialchars($product['username']); ?></small>
                                                </div>
                                                <span class="badge bg-<?php echo $product['status'] === 'available' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Exchanges -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Exchange Requests</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_exchanges as $exchange): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($exchange['product_name']); ?></strong><br>
                                                    <small class="text-muted">by <?php echo htmlspecialchars($exchange['username']); ?></small>
                                                </div>
                                                <span class="badge bg-<?php 
                                                    echo $exchange['status'] === 'completed' ? 'success' : 
                                                         ($exchange['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($exchange['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
