<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get all exchange requests with user and vendor information
$exchanges = $conn->query("
    SELECT e.*, 
           u.username as user_name,
           v.username as vendor_name
    FROM exchange_requests e 
    JOIN users u ON e.user_id = u.id 
    LEFT JOIN users v ON e.vendor_id = v.id
    ORDER BY e.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange Requests - Hardware Resell</title>
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
                    <a href="<?php echo BASE_URL; ?>/admin/exchanges.php" class="list-group-item list-group-item-action active">
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
                <h2 class="mb-4">Exchange Requests</h2>

                <!-- Exchange Requests List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Exchange Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>User</th>
                                        <th>Vendor</th>
                                        <th>Coin Offer</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exchanges as $exchange): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exchange['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($exchange['user_name']); ?></td>
                                            <td>
                                                <?php if ($exchange['vendor_name']): ?>
                                                    <?php echo htmlspecialchars($exchange['vendor_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($exchange['coin_offer']): ?>
                                                    <?php echo $exchange['coin_offer']; ?> coins
                                                <?php else: ?>
                                                    <span class="text-muted">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $exchange['status'] === 'completed' ? 'success' : 
                                                        ($exchange['status'] === 'rejected' ? 'danger' : 
                                                        ($exchange['status'] === 'inspected' ? 'info' : 'warning')); 
                                                ?>">
                                                    <?php echo ucfirst($exchange['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($exchange['created_at'])); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#exchangeModal<?php echo $exchange['id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Exchange Details Modal -->
                                        <div class="modal fade" id="exchangeModal<?php echo $exchange['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Exchange Request Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h6>Product Information</h6>
                                                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($exchange['product_name']); ?></p>
                                                            <p class="mb-1"><strong>Description:</strong></p>
                                                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($exchange['description'])); ?></p>
                                                        </div>

                                                        <?php if ($exchange['images']): ?>
                                                            <div class="mb-3">
                                                                <h6>Product Images</h6>
                                                                <div class="row">
                                                                    <?php foreach (explode(',', $exchange['images']) as $image): ?>
                                                                        <div class="col-md-4 mb-2">
                                                                            <img src="<?php echo BASE_URL . $image; ?>" class="img-fluid rounded" alt="Product Image">
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>User Information</h6>
                                                                <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($exchange['user_name']); ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Vendor Information</h6>
                                                                <?php if ($exchange['vendor_name']): ?>
                                                                    <p class="mb-1"><strong>Vendor:</strong> <?php echo htmlspecialchars($exchange['vendor_name']); ?></p>
                                                                    <p class="mb-1"><strong>Coin Offer:</strong> <?php echo $exchange['coin_offer']; ?> coins</p>
                                                                <?php else: ?>
                                                                    <p class="text-muted">No vendor assigned yet</p>
                                                                <?php endif; ?>
                                                            </div>
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
