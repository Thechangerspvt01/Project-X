<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get all purchase requests with buyer, seller, and product information
$purchases = $conn->query("
    SELECT pr.*,
           p.title as product_title,
           p.price as product_price,
           b.username as buyer_name,
           s.username as seller_name
    FROM purchase_requests pr
    JOIN products p ON pr.product_id = p.id
    JOIN users b ON pr.buyer_id = b.id
    JOIN users s ON pr.seller_id = s.id
    ORDER BY pr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Requests - Hardware Resell</title>
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
                    <a href="<?php echo BASE_URL; ?>/admin/products.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/exchanges.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-exchange-alt"></i> Exchanges
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/purchases.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-shopping-cart"></i> Purchases
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/redemptions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-coins"></i> Redemptions
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h2 class="mb-4">Purchase Requests</h2>

                <!-- Purchase Requests List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Purchase Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Buyer</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Coins Used</th>
                                        <th>Status</th>
                                        <th>Deal Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/products/view.php?id=<?php echo $purchase['product_id']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($purchase['product_title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($purchase['buyer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($purchase['seller_name']); ?></td>
                                            <td><?php echo formatPrice($purchase['cash_amount']); ?></td>
                                            <td><?php echo $purchase['coins_used']; ?> coins</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $purchase['status'] === 'completed' ? 'success' : 
                                                        ($purchase['status'] === 'rejected' ? 'danger' : 
                                                        ($purchase['status'] === 'accepted' ? 'info' : 'warning')); 
                                                ?>">
                                                    <?php echo ucfirst($purchase['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $purchase['deal_status'] === 'completed' ? 'success' : 
                                                        ($purchase['deal_status'] === 'cancelled' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo str_replace('_', ' ', ucfirst($purchase['deal_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($purchase['created_at'])); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#purchaseModal<?php echo $purchase['id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Purchase Details Modal -->
                                        <div class="modal fade" id="purchaseModal<?php echo $purchase['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Purchase Request Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h6>Product Information</h6>
                                                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($purchase['product_title']); ?></p>
                                                            <p class="mb-1"><strong>Original Price:</strong> <?php echo formatPrice($purchase['product_price']); ?></p>
                                                        </div>

                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <h6>Buyer Information</h6>
                                                                <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($purchase['buyer_name']); ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Seller Information</h6>
                                                                <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($purchase['seller_name']); ?></p>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <h6>Payment Details</h6>
                                                            <p class="mb-1"><strong>Cash Amount:</strong> <?php echo formatPrice($purchase['cash_amount']); ?></p>
                                                            <p class="mb-1"><strong>Coins Used:</strong> <?php echo $purchase['coins_used']; ?> coins</p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <h6>Status Information</h6>
                                                            <p class="mb-1"><strong>Request Status:</strong> <?php echo ucfirst($purchase['status']); ?></p>
                                                            <p class="mb-1"><strong>Deal Status:</strong> <?php echo str_replace('_', ' ', ucfirst($purchase['deal_status'])); ?></p>
                                                            <p class="mb-1"><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($purchase['created_at'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
