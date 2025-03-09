<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ' . BASE_URL);
    exit;
}

$success_message = '';
$error_message = '';

// Handle vendor role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    // Check if user exists and is not already a vendor
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error_message = "User not found!";
    } elseif ($user['role'] === 'vendor') {
        $error_message = "User is already a vendor!";
    } elseif ($user['role'] === 'admin') {
        $error_message = "Cannot change admin role!";
    } else {
        // Update user role to vendor
        $stmt = $conn->prepare("UPDATE users SET role = 'vendor' WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        
        if ($stmt->execute()) {
            $success_message = "User '{$username}' has been successfully made a vendor!";
        } else {
            $error_message = "Error updating user role. Please try again.";
        }
    }
}

// Get list of current vendors
$vendors = $conn->query("
    SELECT username, email, full_name, phone, created_at 
    FROM users 
    WHERE role = 'vendor' 
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Hardware Resell</title>
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
                    <a href="<?php echo BASE_URL; ?>/admin/vendors.php" class="list-group-item list-group-item-action active">
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
                    <a href="<?php echo BASE_URL; ?>/admin/redemptions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-coins"></i> Redemptions
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h2 class="mb-4">Vendor Management</h2>

                <!-- Add Vendor Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Make User a Vendor</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Make Vendor</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Vendors List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Current Vendors</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vendors)): ?>
                            <div class="alert alert-info">
                                No vendors found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Joined Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vendor['username']); ?></td>
                                                <td><?php echo htmlspecialchars($vendor['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                                                <td><?php echo htmlspecialchars($vendor['phone']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($vendor['created_at'])); ?></td>
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
