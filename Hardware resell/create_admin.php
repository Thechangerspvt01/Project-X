<?php
require_once 'includes/config.php';

$username = 'admin';
$password = 'Admin@123'; // This is the password you'll use to login
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@hardwareresell.com';
$fullName = 'Admin User';
$phone = '1234567890';
$address = 'Admin Address';

// First, delete any existing admin account
$stmt = $conn->prepare("DELETE FROM users WHERE username = 'admin'");
$stmt->execute();

// Create new admin account
$stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'admin')");
$stmt->bind_param("ssssss", 
    $username,
    $hashedPassword,
    $email,
    $fullName,
    $phone,
    $address
);

if ($stmt->execute()) {
    echo "Admin account created successfully!\n";
    echo "Username: admin\n";
    echo "Password: Admin@123\n";
} else {
    echo "Error creating admin account: " . $stmt->error;
}
