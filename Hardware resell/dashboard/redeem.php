<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = '/dashboard/redeem.php';
    header('Location: /auth/login.php');
    exit;
}

$errors = [];

// Get user's coin balance
$stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's pending redemption requests
$stmt = $conn->prepare("
    SELECT * FROM redemption_requests 
    WHERE user_id = ? AND status = 'pending'
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coins = intval($_POST['coins']);
    
    // Validation
    if ($coins <= 0) {
        $errors[] = "Please enter a valid number of coins";
    } elseif ($coins > $user['coins']) {
        $errors[] = "You don't have enough coins";
    } elseif ($coins < 100) {
        $errors[] = "Minimum redemption amount is 100 coins";
    }

    // Check if user has any pending requests
    if (!empty($pending_requests)) {
        $errors[] = "You already have a pending redemption request";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO redemption_requests (user_id, coins) VALUES (?, ?)");
        $stmt->bind_param("ii", $_SESSION['user_id'], $coins);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Redemption request submitted successfully!');
            header('Location: /dashboard/redeem.php');
            exit;
        } else {
            $errors[] = "Failed to submit redemption request";
        }
    }
}

// Get redemption history
$stmt = $conn->prepare("
    SELECT * FROM redemption_requests 
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Coins - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Redemption Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Redeem Coins</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Redemption Guidelines:
                            <ul class="mb-0">
                                <li>1 coin = ₹1</li>
                                <li>Minimum redemption: 100 coins</li>
                                <li>Processing time: 2-3 business days</li>
                                <li>Cash will be handed over in person</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h6>Your Balance</h6>
                            <div class="coin-display">
                                <i class="fas fa-coins"></i> <?php echo $user['coins']; ?> coins
                            </div>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($flash = getFlashMessage()): ?>
                            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                                <?php echo $flash['message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($pending_requests)): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="coins" class="form-label">Coins to Redeem</label>
                                    <input type="number" class="form-control" id="coins" name="coins" 
                                           min="100" max="<?php echo $user['coins']; ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100" 
                                        <?php echo $user['coins'] < 100 ? 'disabled' : ''; ?>>
                                    Submit Redemption Request
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock"></i> You have a pending redemption request.
                                Please wait for it to be processed before submitting a new one.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Redemption History -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Redemption History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($history)): ?>
                            <div class="alert alert-info">
                                No redemption history yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Coins</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $request): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                                <td>
                                                    <span class="coin-display">
                                                        <i class="fas fa-coins"></i> <?php echo $request['coins']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class[$request['status']]; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
