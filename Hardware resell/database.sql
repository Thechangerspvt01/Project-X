-- Create database
CREATE DATABASE IF NOT EXISTS hardware_resell;
USE hardware_resell;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(20),
    whatsapp VARCHAR(15),
    coins INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('admin', 'user', 'vendor') DEFAULT 'user'
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    coin_price INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    condition_status VARCHAR(20) NOT NULL,
    status ENUM('available', 'pending', 'sold') DEFAULT 'available',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- Exchange requests table
CREATE TABLE exchange_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    images VARCHAR(255),
    status ENUM('pending', 'inspected', 'completed', 'rejected') DEFAULT 'pending',
    coin_offer INT,
    vendor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vendor_id) REFERENCES users(id)
);

-- Purchase requests table
CREATE TABLE purchase_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    coins_used INT NOT NULL,
    cash_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    deal_status ENUM('pending_seller', 'pending_buyer', 'completed', 'cancelled') DEFAULT 'pending_seller',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- Coin redemption requests table
CREATE TABLE redemption_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    coins INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Drop existing notifications table if it exists
DROP TABLE IF EXISTS notifications;

-- Create notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM(
        'purchase_accepted', 
        'purchase_rejected', 
        'purchase_completed',
        'exchange_inspected',
        'exchange_rejected',
        'exchange_completed',
        'general'
    ) NOT NULL DEFAULT 'general',
    reference_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add new columns to users table for address details
ALTER TABLE users
ADD COLUMN city VARCHAR(100) AFTER address,
ADD COLUMN state VARCHAR(100) AFTER city,
ADD COLUMN pincode VARCHAR(20) AFTER state,
ADD COLUMN whatsapp VARCHAR(15) AFTER phone;

-- Add role column to users table
ALTER TABLE users
ADD COLUMN role ENUM('admin', 'user', 'vendor') DEFAULT 'user' AFTER id;

-- Create admin user
INSERT INTO users (username, password, email, full_name, phone, address, city, state, pincode, whatsapp, role)
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin@hardwareresell.com', 'Admin User', '1234567890', 'Admin Address', '', '', '', '', 'admin');

-- Update admin user's role
UPDATE users SET role = 'admin' WHERE username = 'admin';
