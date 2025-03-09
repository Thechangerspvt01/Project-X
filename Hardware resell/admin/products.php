<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get all products with seller information
$products = $conn->query("
    SELECT p.*, u.username as seller_name 
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Hardware Resell</title>
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
                    <a href="<?php echo BASE_URL; ?>/admin" class="list-group-item list-group-item-action">
                        <i class="fas fa-dashboard"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/vendors.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-store"></i> Vendors
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/products.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Product Management</h2>

                <!-- Products List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Coin Price</th>
                                        <th>Category</th>
                                        <th>Condition</th>
                                        <th>Status</th>
                                        <th>Listed Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/products/view.php?id=<?php echo $product['id']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($product['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                            <td><?php echo formatPrice($product['price']); ?></td>
                                            <td><?php echo $product['coin_price']; ?> coins</td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo formatCondition($product['condition_status']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $product['status'] === 'available' ? 'success' : 
                                                        ($product['status'] === 'pending' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
