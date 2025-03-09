<?php
require_once '../includes/config.php';
require_once '../includes/helpers.php';

if (!isVendor()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Get all exchange requests handled by this vendor
$sql = "SELECT e.*, u.username, u.phone 
    FROM exchange_requests e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.vendor_id = ? AND e.status != 'pending'
    ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$handled_requests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange History - Hardware Resell</title>
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
                    <a href="<?php echo BASE_URL; ?>/vendor" class="list-group-item list-group-item-action">
                        <i class="fas fa-list"></i> Pending Requests
                    </a>
                    <a href="<?php echo BASE_URL; ?>/vendor/history.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-history"></i> History
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h2 class="mb-4">Exchange History</h2>

                <?php if (empty($handled_requests)): ?>
                    <div class="alert alert-info">
                        No exchange history found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Coin Offer</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($handled_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['username']); ?></td>
                                        <td><?php echo htmlspecialchars($request['phone']); ?></td>
                                        <td><?php echo $request['coin_offer']; ?> coins</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $request['status'] === 'completed' ? 'success' : 
                                                    ($request['status'] === 'rejected' ? 'danger' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                    data-bs-target="#detailsModal<?php echo $request['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Exchange Details Modal -->
                                    <div class="modal fade" id="detailsModal<?php echo $request['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Exchange Request Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h6>Product Information</h6>
                                                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($request['product_name']); ?></p>
                                                        <p class="mb-1"><strong>Description:</strong></p>
                                                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                                                    </div>

                                                    <?php if ($request['images']): ?>
                                                        <div class="mb-3">
                                                            <h6>Product Images</h6>
                                                            <div class="row">
                                                                <?php foreach (explode(',', $request['images']) as $image): ?>
                                                                    <div class="col-md-4 mb-2">
                                                                        <a href="<?php echo BASE_URL . $image; ?>" target="_blank">
                                                                            <img src="<?php echo BASE_URL . $image; ?>" 
                                                                                 class="img-fluid rounded" alt="Product Image">
                                                                        </a>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>User Information</h6>
                                                            <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($request['username']); ?></p>
                                                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Exchange Information</h6>
                                                            <p class="mb-1"><strong>Coin Offer:</strong> <?php echo $request['coin_offer']; ?> coins</p>
                                                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($request['status']); ?></p>
                                                            <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></p>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
