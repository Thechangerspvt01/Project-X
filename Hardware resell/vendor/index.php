<?php
require_once '../includes/config.php';

if (!isVendor()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get all exchange requests that need vendor attention
$sql = "SELECT e.*, u.username, u.phone, u.email 
    FROM exchange_requests e 
    JOIN users u ON e.user_id = u.id 
    WHERE (e.status = 'pending' OR (e.status = 'inspected' AND e.vendor_id = ?))
    ORDER BY 
        CASE 
            WHEN e.status = 'pending' THEN 1
            ELSE 2
        END,
        e.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind the vendor's ID
    $stmt->bind_param("i", $_SESSION['user_id']);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Result failed: " . $stmt->error);
    }

    $exchange_requests = $result->fetch_all(MYSQLI_ASSOC);
    $total_requests = count($exchange_requests);

} catch (Exception $e) {
    error_log("Error in vendor/index.php: " . $e->getMessage());
    $exchange_requests = [];
    $total_requests = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
            margin: 5px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .product-image-container {
            display: inline-block;
            position: relative;
            margin: 5px;
        }
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 8px;
        }
        .image-overlay i {
            color: white;
            font-size: 1.5em;
        }
        .product-image-container:hover .image-overlay {
            opacity: 1;
        }
        .product-image-container:hover .product-image {
            transform: scale(1.05);
        }
        .description-cell {
            max-width: 300px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .table td {
            vertical-align: middle;
        }
        .user-info i {
            width: 20px;
            margin-right: 5px;
            color: #6c757d;
        }
        .badge {
            font-size: 0.9em;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">
            <i class="fas fa-exchange-alt"></i> Exchange Requests
            <span class="badge bg-primary"><?php echo $total_requests; ?> Total</span>
        </h2>

        <?php if ($flash = getFlashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($exchange_requests)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No exchange requests available at the moment.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Description</th>
                            <th>User Info</th>
                            <th>Status</th>
                            <th>Images</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exchange_requests as $request): ?>
                            <tr>
                                <td class="align-middle">
                                    <strong><?php echo htmlspecialchars($request['product_name']); ?></strong>
                                    <div class="text-muted small">ID: <?php echo $request['id']; ?></div>
                                </td>
                                <td class="align-middle description-cell">
                                    <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                                </td>
                                <td class="align-middle user-info">
                                    <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($request['username']); ?></div>
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['phone']); ?></div>
                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($request['email']); ?></div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-<?php echo $request['status'] === 'pending' ? 'warning' : 'info'; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <?php 
                                    if (!empty($request['images'])) {
                                        $images = explode(',', $request['images']);
                                        foreach ($images as $image) {
                                            $imagePath = trim($image);
                                            if (!empty($imagePath)) {
                                                $fullImagePath = BASE_URL . $imagePath;
                                                ?>
                                                <div class="product-image-container">
                                                    <img src="<?php echo htmlspecialchars($fullImagePath); ?>" 
                                                         class="product-image" 
                                                         alt="<?php echo htmlspecialchars($request['product_name']); ?>"
                                                         onclick="window.open('<?php echo htmlspecialchars($fullImagePath); ?>', '_blank')"
                                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlOWVjZWYiLz48dGV4dCB4PSI1MCIgeT0iNTAiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmb250LWZhbWlseT0ic2Fucy1zZXJpZiIgZmlsbD0iIzZjNzU3ZCI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+'">
                                                    <div class="image-overlay">
                                                        <i class="fas fa-search-plus"></i>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                    } else {
                                        echo '<div class="text-muted"><i class="fas fa-image me-1"></i>No images available</div>';
                                    }
                                    ?>
                                </td>
                                <td class="align-middle">
                                    <div><?php echo date('M j, Y', strtotime($request['created_at'])); ?></div>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($request['created_at'])); ?></small>
                                    <div class="small text-muted"><?php echo timeAgo($request['created_at']); ?></div>
                                </td>
                                <td class="align-middle">
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#offerModal<?php echo $request['id']; ?>">
                                            <i class="fas fa-coins"></i> Make Offer
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="if(confirm('Are you sure you want to reject this request?')) {
                                                    document.getElementById('rejectForm<?php echo $request['id']; ?>').submit();
                                                }">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                    <form id="rejectForm<?php echo $request['id']; ?>" 
                                          action="process_exchange.php" 
                                          method="POST" 
                                          style="display: none;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                    </form>
                                </td>
                            </tr>

                            <!-- Offer Modal -->
                            <div class="modal fade" id="offerModal<?php echo $request['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-coins"></i> Make an Offer
                                                <div class="small text-muted">Product: <?php echo htmlspecialchars($request['product_name']); ?></div>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="process_exchange.php" method="POST" class="needs-validation" novalidate>
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="action" value="inspect">
                                                
                                                <div class="mb-3">
                                                    <label for="coin_offer<?php echo $request['id']; ?>" class="form-label">
                                                        Coin Offer Amount <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                                        <input type="number" class="form-control" 
                                                               id="coin_offer<?php echo $request['id']; ?>" 
                                                               name="coin_offer" 
                                                               min="1" 
                                                               required
                                                               placeholder="Enter coin amount">
                                                        <div class="invalid-feedback">
                                                            Please enter a valid coin amount (minimum 1)
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-check"></i> Submit Offer
                                                    </button>
                                                </div>
                                            </form>
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

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>
