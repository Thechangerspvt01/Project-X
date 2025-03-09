<?php
require_once '../includes/config.php';

// Get filters from query parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT p.*, u.username as seller_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.status = 'available'";

// Sort products
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY p.created_at ASC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

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
    <title>Browse Products - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Products Grid -->
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Available Products</h2>
                    <?php if (isLoggedIn()): ?>
                        <div>
                            <select name="sort" class="form-select d-inline-block w-auto me-2" onchange="window.location.href='?sort=' + this.value">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                            <a href="/Hardware resell/products/create.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> List New Product
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($products)): ?>
                    <div class="alert alert-info">No products found matching your criteria.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php
                                    $imagePath = $product['image_path'] ? '../uploads/' . $product['image_path'] : '../assets/images/placeholder.jpg';
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                                        <p class="card-text">
                                            <strong>Price:</strong> ₹<?php echo number_format($product['price']); ?><br>
                                            <strong>Coins:</strong> <?php echo $product['coin_price']; ?> coins<br>
                                            <strong>Condition:</strong> <?php echo formatCondition($product['condition_status']); ?><br>
                                            <strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?>
                                        </p>
                                        <a href="view.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary w-100">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
