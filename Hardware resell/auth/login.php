<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    switch($_SESSION['user_role']) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/');
            break;
        case 'vendor':
            header('Location: ' . BASE_URL . '/vendor/');
            break;
        default:
            header('Location: ' . BASE_URL . '/');
    }
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("s", $username);
            if (!$stmt->execute()) {
                throw new Exception("Query failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                // Log session data for debugging
                error_log("Login successful - User: {$user['username']}, Role: {$user['role']}");
                error_log("Session data: " . print_r($_SESSION, true));

                // Role-based redirect
                switch($_SESSION['user_role']) {
                    case 'admin':
                        header('Location: ' . BASE_URL . '/admin/');
                        break;
                    case 'vendor':
                        header('Location: ' . BASE_URL . '/vendor/');
                        break;
                    default:
                        header('Location: ' . BASE_URL . '/');
                }
                exit;
            } else {
                $errors[] = "Invalid username or password";
                error_log("Login failed for username: $username");
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center mb-4">Login</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            <label for="username">Username</label>
                        </div>

                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
