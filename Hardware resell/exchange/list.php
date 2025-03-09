<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/exchange/list.php';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get user's exchange requests
$stmt = $conn->prepare("SELECT er.*, u.username as vendor_username 
    FROM exchange_requests er
    LEFT JOIN users u ON er.vendor_id = u.id
    WHERE er.user_id = ? 
    ORDER BY er.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exchange Requests - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Exchange Requests</h2>
            <a href="<?php echo BASE_URL; ?>/exchange/request.php" class="btn btn-success">
                <i class="fas fa-plus"></i> New Exchange Request
            </a>
        </div>

        <?php if ($flash = getFlashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                You haven't submitted any exchange requests yet.
                <a href="<?php echo BASE_URL; ?>/exchange/request.php" class="alert-link">Submit your first request</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Vendor</th>
                            <th>Coin Offer</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['product_name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo substr(htmlspecialchars($request['description']), 0, 100); ?>...</small>
                                    <?php if ($request['images']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-image"></i> <?php echo count(explode(',', $request['images'])); ?> images
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'warning',
                                        'inspected' => 'info',
                                        'completed' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $status_icon = [
                                        'pending' => 'clock',
                                        'inspected' => 'search',
                                        'completed' => 'check-circle',
                                        'rejected' => 'times-circle'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $status_class[$request['status']]; ?>">
                                        <i class="fas fa-<?php echo $status_icon[$request['status']]; ?>"></i>
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $request['vendor_username'] ? htmlspecialchars($request['vendor_username']) : '-'; ?>
                                </td>
                                <td>
                                    <?php if ($request['coin_offer']): ?>
                                        <span class="coin-display">
                                            <i class="fas fa-coins"></i> <?php echo $request['coin_offer']; ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php if ($request['status'] === 'inspected'): ?>
                                        <a href="<?php echo BASE_URL; ?>/exchange/respond.php?id=<?php echo $request['id']; ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-reply"></i> Respond to Offer
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['images']): ?>
                                        <button type="button" class="btn btn-sm btn-info view-images" 
                                                data-images="<?php echo htmlspecialchars($request['images']); ?>">
                                            <i class="fas fa-images"></i> View Images
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Image Preview Modal -->
            <div class="modal fade" id="imagePreviewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Product Images</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="imageContainer" class="row"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        const imageContainer = document.getElementById('imageContainer');
        
        document.querySelectorAll('.view-images').forEach(button => {
            button.addEventListener('click', function() {
                const images = this.dataset.images.split(',');
                imageContainer.innerHTML = images.map(image => `
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo BASE_URL; ?>${image}" target="_blank">
                            <img src="<?php echo BASE_URL; ?>${image}" class="img-fluid rounded" alt="Product Image">
                        </a>
                    </div>
                `).join('');
                imageModal.show();
            });
        });
    });
    </script>
</body>
</html>
