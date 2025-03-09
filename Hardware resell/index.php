<?php
require_once 'includes/config.php';

// Get latest products
$query = "SELECT p.*, u.username as seller_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.status = 'available' 
          ORDER BY p.created_at DESC 
          LIMIT 12";
$result = $conn->query($query);
$products = [];
if ($result) {
    $products = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Resell - Buy, Sell & Exchange Used Hardware</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .price-tag {
            color: #28a745;
            font-weight: bold;
        }
        .coin-tag {
            color: #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Flash Messages -->
        <?php if ($flash = getFlashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <div class="jumbotron text-center py-5 mb-4">
            <h1>Welcome to Hardware Resell</h1>
            <p class="lead">Buy, Sell, and Exchange Used Hardware with our Unique Coin System</p>
            <div class="mt-4">
                <a href="products/list.php" class="btn btn-primary me-2">Browse Products</a>
                <?php if (!isLoggedIn()): ?>
                    <a href="auth/register.php" class="btn btn-outline-primary">Join Now</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Features Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Buy & Sell</h5>
                        <p class="card-text">List your used hardware or find great deals on products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-coins fa-3x mb-3 text-warning"></i>
                        <h5 class="card-title">Coin System</h5>
                        <p class="card-text">Use our coin system for flexible payments (1 coin = ₹1)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt fa-3x mb-3 text-success"></i>
                        <h5 class="card-title">Exchange</h5>
                        <p class="card-text">Exchange your hardware for coins</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Products -->
        <h2 class="mb-4">Latest Products</h2>
        <div class="row">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No products available at the moment.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 product-card">
                            <?php
                            $imagePath = $product['image_path'] ? 'uploads/' . $product['image_path'] : 'assets/images/placeholder.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                                <p class="card-text">
                                    <span class="price-tag">₹<?php echo number_format($product['price']); ?></span><br>
                                    <span class="coin-tag"><i class="fas fa-coins"></i> <?php echo $product['coin_price']; ?> coins</span><br>
                                    <strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?><br>
                                    <strong>Condition:</strong> <?php echo formatCondition($product['condition_status']); ?>
                                </p>
                                <a href="products/view.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary w-100">View Details</a>
                            </div>
                            <div class="card-footer text-muted">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($product['seller_name']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($products)): ?>
            <div class="text-center mt-4">
                <a href="products/list.php" class="btn btn-outline-primary">View All Products</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
