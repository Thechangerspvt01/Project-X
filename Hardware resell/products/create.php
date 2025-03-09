<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = '/Hardware resell/products/create.php';
    header('Location: /Hardware resell/auth/login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = trim($_POST['category']);
    $condition = trim($_POST['condition']);

    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }

    if (empty($description)) {
        $errors[] = "Description is required";
    }

    if ($price <= 0) {
        $errors[] = "Price must be greater than 0";
    }

    if (empty($category)) {
        $errors[] = "Category is required";
    }

    if (empty($condition)) {
        $errors[] = "Condition is required";
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid image type. Only JPG, PNG, and GIF are allowed.";
        } else {
            $file_name = uniqid() . '_' . $_FILES['image']['name'];
            $upload_path = $upload_dir . '/' . $file_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
            } else {
                $image_path = $file_name;
            }
        }
    }

    if (empty($errors)) {
        // Prepare the SQL query
        $sql = "INSERT INTO products (seller_id, title, description, price, category, condition_status, image_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'available')";
        
        // Prepare and execute the statement
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issdsss", 
                $_SESSION['user_id'], 
                $title, 
                $description, 
                $price,
                $category, 
                $condition, 
                $image_path
            );

            if ($stmt->execute()) {
                setFlashMessage('success', 'Product listed successfully!');
                header('Location: ' . BASE_URL . '/dashboard/products.php');
                exit;
            } else {
                $errors[] = "Failed to list product";
            }
        } else {
            $errors[] = "Database error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List New Product - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        #image-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="form-container">
            <h2 class="text-center mb-4">List New Product</h2>
            
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
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" class="form-control" id="price" name="price" 
                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                               min="0" step="0.01" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="CPU" <?php echo isset($_POST['category']) && $_POST['category'] === 'CPU' ? 'selected' : ''; ?>>CPU</option>
                            <option value="GPU" <?php echo isset($_POST['category']) && $_POST['category'] === 'GPU' ? 'selected' : ''; ?>>GPU</option>
                            <option value="RAM" <?php echo isset($_POST['category']) && $_POST['category'] === 'RAM' ? 'selected' : ''; ?>>RAM</option>
                            <option value="Storage" <?php echo isset($_POST['category']) && $_POST['category'] === 'Storage' ? 'selected' : ''; ?>>Storage</option>
                            <option value="Motherboard" <?php echo isset($_POST['category']) && $_POST['category'] === 'Motherboard' ? 'selected' : ''; ?>>Motherboard</option>
                            <option value="Power Supply" <?php echo isset($_POST['category']) && $_POST['category'] === 'Power Supply' ? 'selected' : ''; ?>>Power Supply</option>
                            <option value="Case" <?php echo isset($_POST['category']) && $_POST['category'] === 'Case' ? 'selected' : ''; ?>>Case</option>
                            <option value="Monitor" <?php echo isset($_POST['category']) && $_POST['category'] === 'Monitor' ? 'selected' : ''; ?>>Monitor</option>
                            <option value="Peripherals" <?php echo isset($_POST['category']) && $_POST['category'] === 'Peripherals' ? 'selected' : ''; ?>>Peripherals</option>
                            <option value="Other" <?php echo isset($_POST['category']) && $_POST['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="condition" class="form-label">Condition</label>
                    <select class="form-select" id="condition" name="condition" required>
                        <option value="">Select Condition</option>
                        <option value="new" <?php echo isset($_POST['condition']) && $_POST['condition'] === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="like_new" <?php echo isset($_POST['condition']) && $_POST['condition'] === 'like_new' ? 'selected' : ''; ?>>Like New</option>
                        <option value="good" <?php echo isset($_POST['condition']) && $_POST['condition'] === 'good' ? 'selected' : ''; ?>>Good</option>
                        <option value="fair" <?php echo isset($_POST['condition']) && $_POST['condition'] === 'fair' ? 'selected' : ''; ?>>Fair</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this);">
                    <img id="image-preview" class="mt-2" src="#" alt="Preview">
                </div>

                <button type="submit" class="btn btn-primary w-100">List Product</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function previewImage(input) {
        var preview = document.getElementById('image-preview');
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
