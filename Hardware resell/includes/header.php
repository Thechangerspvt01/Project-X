<?php
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/config.php';
}

// Get the current script name to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">Hardware Resell</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'list.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/products/list.php">Browse Products</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'create.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/products/create.php">List Product</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'request.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/exchange/request.php">Exchange Hardware</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user_coins = $result->fetch_assoc()['coins'] ?? 0;
                        
                        // Get pending purchase requests count
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_requests WHERE seller_id = ? AND status = 'pending'");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $pending_requests = $stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-coins text-warning"></i> <?php echo $user_coins; ?> coins
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/notifications.php">
                                <i class="fas fa-bell"></i>
                                <?php
                                // Get unread notification count
                                $unread_count = 0;
                                $notification_stmt = $conn->prepare("
                                    SELECT COUNT(*) as count 
                                    FROM notifications 
                                    WHERE user_id = ? AND is_read = FALSE
                                ");
                                if ($notification_stmt) {
                                    $notification_stmt->bind_param("i", $_SESSION['user_id']);
                                    if ($notification_stmt->execute()) {
                                        $result = $notification_stmt->get_result();
                                        if ($result) {
                                            $unread_count = $result->fetch_assoc()['count'];
                                        }
                                    }
                                }
                                if ($unread_count > 0):
                                ?>
                                <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/purchase_requests.php">
                                <i class="fas fa-shopping-basket"></i> Sell Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/my_purchases.php">
                                <i class="fas fa-shopping-cart"></i> My Purchases
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                My Account
                                <?php if ($pending_requests > 0): ?>
                                    <span class="badge bg-danger"><?php echo $pending_requests; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/index.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/products.php">My Products</a></li>
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center" 
                                       href="<?php echo BASE_URL; ?>/dashboard/purchase_requests.php">
                                        Purchase Requests
                                        <?php if ($pending_requests > 0): ?>
                                            <span class="badge bg-danger rounded-pill"><?php echo $pending_requests; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
