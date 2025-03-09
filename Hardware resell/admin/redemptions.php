<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get all coin redemption requests with user information
$redemptions = $conn->query("
    SELECT r.*, u.username, u.email, u.phone
    FROM redemption_requests r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coin Redemptions - Hardware Resell</title>
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
                    <a href="<?php echo BASE_URL; ?>/admin/purchases.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart"></i> Purchases
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/redemptions.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-coins"></i> Redemptions
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h2 class="mb-4">Coin Redemption Requests</h2>

                <!-- Redemption Requests List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Redemption Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Coins</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($redemptions as $redemption): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($redemption['username']); ?></td>
                                            <td><?php echo $redemption['coins']; ?> coins</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $redemption['status'] === 'approved' ? 'success' : 
                                                        ($redemption['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($redemption['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($redemption['created_at'])); ?></td>
                                            <td>
                                                <?php if ($redemption['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success approve-btn" data-id="<?php echo $redemption['id']; ?>">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger reject-btn" data-id="<?php echo $redemption['id']; ?>">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Payment Details Modal -->
                                        <div class="modal fade" id="detailsModal<?php echo $redemption['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Payment Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h6>User Information</h6>
                                                            <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($redemption['username']); ?></p>
                                                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($redemption['email']); ?></p>
                                                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($redemption['phone']); ?></p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <h6>Redemption Details</h6>
                                                            <p class="mb-1"><strong>Coins:</strong> <?php echo $redemption['coins']; ?> coins</p>
                                                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($redemption['status']); ?></p>
                                                            <p class="mb-1"><strong>Request Date:</strong> <?php echo date('M j, Y g:i A', strtotime($redemption['created_at'])); ?></p>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle approve button clicks
        document.querySelectorAll('.approve-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to approve this redemption request?')) {
                    const id = this.dataset.id;
                    updateRedemptionStatus(id, 'approved');
                }
            });
        });

        // Handle reject button clicks
        document.querySelectorAll('.reject-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to reject this redemption request?')) {
                    const id = this.dataset.id;
                    updateRedemptionStatus(id, 'rejected');
                }
            });
        });

        // Function to update redemption status
        function updateRedemptionStatus(id, status) {
            fetch(`${BASE_URL}/admin/update_redemption.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating redemption status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the redemption status');
            });
        }
    });
    </script>
</body>
</html>
