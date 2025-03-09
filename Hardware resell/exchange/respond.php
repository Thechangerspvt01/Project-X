<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/exchange/respond.php';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Invalid request');
    header('Location: ' . BASE_URL . '/exchange/list.php');
    exit;
}

$request_id = $_GET['id'];

// Get request details
$stmt = $conn->prepare("
    SELECT er.*, u.username as vendor_username
    FROM exchange_requests er
    LEFT JOIN users u ON er.vendor_id = u.id
    WHERE er.id = ? AND er.user_id = ? AND er.status = 'inspected'
");
$stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    setFlashMessage('error', 'Invalid request or not available for response');
    header('Location: ' . BASE_URL . '/exchange/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $conn->begin_transaction();
        
        if ($_POST['action'] === 'accept') {
            // Update request status
            $stmt = $conn->prepare("
                UPDATE exchange_requests 
                SET status = 'completed', completed_at = NOW()
                WHERE id = ? AND user_id = ? AND status = 'inspected'
            ");
            $stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Add coins to user's balance
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET coins = coins + ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("ii", $request['coin_offer'], $_SESSION['user_id']);
                $stmt->execute();

                // Create notification for vendor
                $message = "The user has accepted your coin offer of {$request['coin_offer']} for '{$request['product_name']}'.";
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message)
                    VALUES (?, 'exchange_completed', ?)
                ");
                $stmt->bind_param("is", $request['vendor_id'], $message);
                $stmt->execute();

                $conn->commit();
                setFlashMessage('success', "Exchange completed! {$request['coin_offer']} coins have been added to your balance.");
            } else {
                throw new Exception('Failed to update request status');
            }
        } elseif ($_POST['action'] === 'reject') {
            // Update request status back to pending and clear vendor info
            $stmt = $conn->prepare("
                UPDATE exchange_requests 
                SET status = 'pending', vendor_id = NULL, coin_offer = NULL
                WHERE id = ? AND user_id = ? AND status = 'inspected'
            ");
            $stmt->bind_param("ii", $request_id, $_SESSION['user_id']);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Create notification for vendor
                $message = "The user has rejected your coin offer for '{$request['product_name']}'.";
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message)
                    VALUES (?, 'exchange_rejected', ?)
                ");
                $stmt->bind_param("is", $request['vendor_id'], $message);
                $stmt->execute();

                $conn->commit();
                setFlashMessage('success', 'You have rejected the coin offer. Your request will be available for other vendors.');
            } else {
                throw new Exception('Failed to update request status');
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('error', $e->getMessage());
    }
    
    header('Location: ' . BASE_URL . '/exchange/list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Coin Offer - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Respond to Coin Offer</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5><?php echo htmlspecialchars($request['product_name']); ?></h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                        </div>

                        <?php if ($request['images']): ?>
                            <div class="mb-4">
                                <h6>Product Images:</h6>
                                <div class="row">
                                    <?php foreach (explode(',', $request['images']) as $image): ?>
                                        <div class="col-md-4 mb-2">
                                            <a href="<?php echo BASE_URL . $image; ?>" target="_blank" class="image-link">
                                                <img src="<?php echo BASE_URL . $image; ?>" 
                                                     class="img-fluid rounded" alt="Product Image">
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h6 class="alert-heading">Coin Offer Details:</h6>
                            <p class="mb-0">
                                Vendor <strong><?php echo htmlspecialchars($request['vendor_username']); ?></strong> 
                                has offered <strong><?php echo $request['coin_offer']; ?> coins</strong> for your hardware.
                            </p>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn btn-success btn-lg" 
                                        onclick="return confirm('Are you sure you want to accept this offer? You will receive <?php echo $request['coin_offer']; ?> coins.')">
                                    <i class="fas fa-check"></i> Accept Offer
                                </button>
                            </form>

                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger btn-lg"
                                        onclick="return confirm('Are you sure you want to reject this offer? Your request will be available for other vendors.')">
                                    <i class="fas fa-times"></i> Reject Offer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize image preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        const imageLinks = document.querySelectorAll('.image-link');
        imageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                window.open(this.href, '_blank', 'width=800,height=600');
            });
        });
    });
    </script>
</body>
</html>
