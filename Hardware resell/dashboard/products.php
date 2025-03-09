<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/dashboard/products.php';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get user's products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$total_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE seller_id = " . $_SESSION['user_id'])->fetch_assoc()['count'];
$total_pages = ceil($total_count / $per_page);

// Get products for current page
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE seller_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $_SESSION['user_id'], $per_page, $offset);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Products</h2>
            <a href="<?php echo BASE_URL; ?>/products/create.php" class="btn btn-success">
                <i class="fas fa-plus"></i> List New Product
            </a>
        </div>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't listed any products yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Listed On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php 
                                        echo $product['image_path'] 
                                            ? BASE_URL . '/uploads/' . $product['image_path']
                                            : BASE_URL . '/assets/images/placeholder.png';
                                    ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                         class="img-thumbnail"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                <td>₹<?php echo number_format($product['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $product['status'] === 'available' ? 'success' : 
                                            ($product['status'] === 'pending' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/products/view.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($product['status'] === 'available'): ?>
                                        <a href="<?php echo BASE_URL; ?>/products/edit.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Product navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
