<?php
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Hardware Resell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h1 class="mb-4">About Hardware Resell</h1>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Our Mission</h5>
                        <p class="card-text">
                            Hardware Resell is dedicated to creating a sustainable marketplace for used computer hardware. 
                            Our platform connects buyers and sellers while promoting the reuse of technology, reducing 
                            electronic waste, and making quality hardware accessible to everyone.
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">How It Works</h5>
                        <div class="row g-4">
                            <div class="col-md-4 text-center">
                                <i class="fas fa-upload fa-2x mb-3 text-primary"></i>
                                <h6>List Your Hardware</h6>
                                <p class="small">Upload photos and details of your hardware for sale</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-coins fa-2x mb-3 text-warning"></i>
                                <h6>Earn Coins</h6>
                                <p class="small">Get coins for your hardware through our exchange program</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <i class="fas fa-shopping-cart fa-2x mb-3 text-success"></i>
                                <h6>Buy Hardware</h6>
                                <p class="small">Use coins or cash to purchase hardware from other users</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Why Choose Us?</h5>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Verified Sellers and Quality Assurance
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Secure Payment System
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Unique Coin-Based Exchange System
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Community-Driven Marketplace
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
