<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/exchange/request.php';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);

    // Validation
    if (empty($product_name)) {
        $errors[] = "Product name is required";
    }

    if (empty($description)) {
        $errors[] = "Description is required";
    }

    // Handle images upload
    $image_paths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_type = $_FILES['images']['type'][$key];
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Invalid image type. Only JPG, PNG, and GIF are allowed.";
                break;
            }
            
            $file_name = uniqid() . '_' . $_FILES['images']['name'][$key];
            $upload_path = dirname(__DIR__) . '/uploads/' . $file_name;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $image_paths[] = '/uploads/' . $file_name;
            } else {
                $errors[] = "Failed to upload image: " . $_FILES['images']['name'][$key];
            }
        }
    }

    if (empty($errors)) {
        $images = !empty($image_paths) ? implode(',', $image_paths) : null;
        
        $stmt = $conn->prepare("INSERT INTO exchange_requests (user_id, product_name, description, images) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_SESSION['user_id'], $product_name, $description, $images);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Exchange request submitted successfully!');
            header('Location: ' . BASE_URL . '/exchange/list.php');
            exit;
        } else {
            $errors[] = "Failed to submit exchange request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Exchange Request - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="form-container">
            <h2 class="text-center mb-4">Submit Exchange Request</h2>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Exchange your used hardware for coins!
                <ul class="mb-0 mt-2">
                    <li>Submit your hardware details</li>
                    <li>Our vendor will inspect and evaluate your hardware</li>
                    <li>Accept or reject the coin offer</li>
                    <li>Get coins instantly upon acceptance</li>
                </ul>
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

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" 
                           value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required
                              placeholder="Please provide detailed information about your hardware including:
- Brand and model
- Age and usage history
- Current condition
- Any issues or defects"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="images" class="form-label">Product Images (Max 5)</label>
                    <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                    <div class="form-text">Upload clear images showing the condition of your hardware.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit Exchange Request</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('images').addEventListener('change', function() {
        if (this.files.length > 5) {
            alert('You can only upload up to 5 images');
            this.value = '';
        }
    });
    </script>
</body>
</html>
