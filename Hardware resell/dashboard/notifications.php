<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Mark notification as read if requested
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    markNotificationAsRead($notification_id, $_SESSION['user_id']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get all notifications for the user
$stmt = $conn->prepare("
    SELECT n.*, er.id as exchange_id 
    FROM notifications n
    LEFT JOIN exchange_requests er ON n.reference_id = er.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .notification-card {
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #ccc;
            transition: all 0.3s ease;
        }
        .notification-card:hover {
            transform: translateX(5px);
        }
        .notification-unread {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .notification-read {
            border-left-color: #6c757d;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .notification-type {
            font-size: 0.85rem;
            padding: 2px 8px;
            border-radius: 12px;
            background-color: #e9ecef;
        }
        .type-purchase_accepted { background-color: #d1e7dd; color: #0f5132; }
        .type-purchase_rejected { background-color: #f8d7da; color: #842029; }
        .type-purchase_completed { background-color: #cff4fc; color: #055160; }
        .type-exchange_inspected { background-color: #fff3cd; color: #664d03; }
        .type-exchange_rejected { background-color: #f8d7da; color: #842029; }
        .type-exchange_completed { background-color: #d1e7dd; color: #0f5132; }
        .type-general { background-color: #e9ecef; color: #495057; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">
            <i class="fas fa-bell"></i> Notifications
            <?php 
            $unread_count = array_reduce($notifications, function($count, $n) {
                return $count + ($n['is_read'] ? 0 : 1);
            }, 0);
            if ($unread_count > 0): 
            ?>
                <span class="badge bg-danger"><?php echo $unread_count; ?> unread</span>
            <?php endif; ?>
        </h2>

        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You don't have any notifications.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="card notification-card <?php echo $notification['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="notification-type type-<?php echo $notification['type']; ?>">
                                <?php 
                                $type_display = [
                                    'purchase_accepted' => 'Purchase Accepted',
                                    'purchase_rejected' => 'Purchase Rejected',
                                    'purchase_completed' => 'Purchase Completed',
                                    'exchange_inspected' => 'Exchange Offer',
                                    'exchange_rejected' => 'Exchange Rejected',
                                    'exchange_completed' => 'Exchange Completed',
                                    'general' => 'General'
                                ];
                                echo $type_display[$notification['type']] ?? ucwords(str_replace('_', ' ', $notification['type']));
                                ?>
                            </span>
                            <small class="notification-time">
                                <?php echo timeAgo($notification['created_at']); ?>
                            </small>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <?php if ($notification['type'] === 'exchange_inspected' && $notification['exchange_id']): ?>
                                    <a href="<?php echo BASE_URL; ?>/exchange/respond.php?id=<?php echo $notification['exchange_id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-reply"></i> Respond to Offer
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" class="ms-2">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-light">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
