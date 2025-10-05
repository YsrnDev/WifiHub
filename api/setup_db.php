<?php
// Database setup script for WifiHub
// Run this script to create the database and tables

header('Content-Type: text/html');

// Database configuration
$db_host = 'localhost';
$db_name = 'wifihub';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password is empty

echo "<h2>WifiHub Database Setup</h2>";

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=$db_host;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`;");
    echo "<p>✓ Database '$db_name' created or already exists</p>";
    
    // Select the database
    $pdo->exec("USE `$db_name`;");
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `user_id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) UNIQUE NOT NULL,
            `phone` VARCHAR(20),
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
    ");
    echo "<p>✓ Users table created</p>";
    
    // Create packages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `packages` (
            `package_id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `price` INT NOT NULL,
            `data_limit` VARCHAR(20) DEFAULT 'Unlimited',
            `duration_hours` INT NOT NULL,
            `validity_hours` INT NOT NULL,
            `speed` VARCHAR(20) DEFAULT 'High Speed',
            `priority` VARCHAR(50) DEFAULT 'Prioritas Tinggi',
            `support_available` BOOLEAN DEFAULT true,
            `is_active` BOOLEAN DEFAULT true,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "<p>✓ Packages table created</p>";
    
    // Create orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `order_id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `package_id` INT,
            `order_status` VARCHAR(20) DEFAULT 'pending',
            `payment_method` VARCHAR(50),
            `payment_account` VARCHAR(100),
            `amount_paid` INT,
            `voucher_code` VARCHAR(50) UNIQUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `expires_at` TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
            FOREIGN KEY (`package_id`) REFERENCES `packages`(`package_id`)
        );
    ");
    echo "<p>✓ Orders table created</p>";
    
    // Create vouchers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vouchers` (
            `voucher_id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT,
            `voucher_code` VARCHAR(50) UNIQUE NOT NULL,
            `username` VARCHAR(50) UNIQUE NOT NULL,
            `password` VARCHAR(50) NOT NULL,
            `profile_name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `activated_at` TIMESTAMP NULL,
            `expires_at` TIMESTAMP NULL,
            `is_used` BOOLEAN DEFAULT false,
            `is_active` BOOLEAN DEFAULT true,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`)
        );
    ");
    echo "<p>✓ Vouchers table created</p>";
    
    // Insert initial packages
    $packages = [
        ['Paket Basic', 5000, 6, 24],
        ['Paket Plus', 10000, 12, 24],
        ['Paket Premium', 20000, 24, 48],
        ['Paket Ultimate', 30000, 48, 72],
        ['Paket Elite', 40000, 96, 120],
        ['Paket Pro', 50000, 144, 168]
    ];
    
    $inserted = 0;
    foreach ($packages as $pkg) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO `packages` (`name`, `price`, `duration_hours`, `validity_hours`) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute($pkg);
        $inserted += $stmt->rowCount();
    }
    
    echo "<p>✓ $inserted initial packages inserted (or already existed)</p>";
    echo "<p style='color: green; font-weight: bold;'>Setup completed successfully!</p>";
    echo "<p>You can now use the WifiHub system with your MySQL database.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure XAMPP is running and MySQL is started.</p>";
}
?>