<?php
// Enhanced API with MySQL integration for XAMPP
// This file connects to your MySQL database and handles all API requests
// Located in api/api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept, Origin');
header('Access-Control-Max-Age: 3600');

// Load environment variables
require_once('../src/php/env_loader.php');

// Load Midtrans configuration
require_once('midtrans_config.php');

// Database configuration for XAMPP MySQL
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'wifihub'; // Make sure you create this database in phpMyAdmin
$db_user = $_ENV['DB_USER'] ?? 'root';     // Default XAMPP MySQL user
$db_pass = $_ENV['DB_PASS'] ?? '';         // Default XAMPP MySQL password (empty)

// Mikrotik configuration (if needed)
$mikrotik_host = $_ENV['MIKROTIK_HOST'] ?? '192.168.100.1'; // Change to your Mikrotik IP
$mikrotik_user = $_ENV['MIKROTIK_USER'] ?? 'yusronAPI';
$mikrotik_pass = $_ENV['MIKROTIK_PASS'] ?? 'yusronAPI';
$mikrotik_port = (int)($_ENV['MIKROTIK_PORT'] ?? 8778); // or 8729 for SSL

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("API Request received: " . file_get_contents('php://input'));
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_log("Invalid JSON input received");
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit();
    }
    
    error_log("API Action requested: " . ($input['action'] ?? 'none'));
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'login':
                handleLogin($input);
                break;
            case 'register':
                handleRegister($input);
                break;
            case 'contact':
                handleContact($input);
                break;
            case 'checkout':
                handleCheckout($input);
                break;
            case 'get_packages':
                getPackages($input);
                break;
            case 'get_user_vouchers':
                getUserVouchers($input);
                break;
            case 'update_profile':
                updateProfile($input);
                break;
            case 'get_payment_config':
                getPaymentConfig($input);
                break;
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
}

function getPaymentConfig($data) {
    // Return only the client key, keep server key secure on server side
    $config = [
        'client_key' => defined('MIDTRANS_CLIENT_KEY') ? MIDTRANS_CLIENT_KEY : '',
        'is_production' => defined('MIDTRANS_IS_PRODUCTION') ? MIDTRANS_IS_PRODUCTION : false
    ];
    
    echo json_encode([
        'status' => 'success',
        'data' => $config
    ]);
}

// Function to connect to MySQL
function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

function handleLogin($data) {
    $pdo = connectDB();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        return;
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT user_id, name, email, password_hash FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Login berhasil',
                'user' => [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email atau password salah']);
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat login']);
    }
}

function handleRegister($data) {
    $pdo = connectDB();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        return;
    }
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid']);
        return;
    }
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar']);
            return;
        }
        
        // Create new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash) 
            VALUES (:name, :email, :password_hash)
        ");
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $hashedPassword
        ]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Registrasi berhasil! Silakan login.'
        ]);
    } catch(PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat registrasi']);
    }
}

function handleContact($data) {
    // For demo purposes, just return success
    // In a real application, you would store in database or send email
    echo json_encode([
        'status' => 'success', 
        'message' => 'Pesan Anda telah dikirim! Kami akan segera menghubungi Anda.'
    ]);
}

// Include Midtrans configuration
require_once('midtrans_config.php');

function handleCheckout($data) {
    error_log("Checkout process started with data: " . json_encode($data));
    
    $pdo = connectDB();
    if (!$pdo) {
        error_log("Database connection failed in checkout");
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['packageName', 'packagePrice', 'customerName', 'customerEmail', 'customerPhone', 'paymentMethod'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            error_log("Missing required field in checkout: " . $field);
            echo json_encode(['status' => 'error', 'message' => 'Field ' . $field . ' wajib diisi']);
            return;
        }
    }
    
    $customerEmail = $data['customerEmail'];
    $paymentMethod = $data['paymentMethod'];
    $paymentAccount = $data['paymentAccount'] ?? '';
    
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid']);
        return;
    }
    
    try {
        // Get user ID from email
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $customerEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
            return;
        }
        
        $userId = $user['user_id'];
        
        // Get package ID from name
        $stmt = $pdo->prepare("SELECT package_id, price, duration_hours, validity_hours FROM packages WHERE name = :name AND is_active = 1");
        $stmt->execute([':name' => $data['packageName']]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$package) {
            echo json_encode(['status' => 'error', 'message' => 'Paket tidak ditemukan']);
            return;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Generate voucher code and user credentials
        $voucherCode = "WH" . strtoupper(substr(md5(uniqid()), 0, 8));
        $username = "user_" . time() . rand(1000, 9999);
        $password = bin2hex(random_bytes(6)); // 12-character password
        
        // Calculate expiration time based on package validity
        $createdAt = new DateTime();
        $expiresAt = clone $createdAt;
        $expiresAt->add(new DateInterval('PT' . $package['validity_hours'] . 'H'));
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, package_id, order_status, payment_method, payment_account, amount_paid, voucher_code, created_at, expires_at) 
            VALUES (:user_id, :package_id, :order_status, :payment_method, :payment_account, :amount_paid, :voucher_code, :created_at, :expires_at)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':package_id' => $package['package_id'],
            ':order_status' => 'pending',
            ':payment_method' => $paymentMethod,
            ':payment_account' => $paymentAccount,
            ':amount_paid' => $package['price'],
            ':voucher_code' => $voucherCode,
            ':created_at' => $createdAt->format('Y-m-d H:i:s'),
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert voucher
        $stmt = $pdo->prepare("
            INSERT INTO vouchers (order_id, voucher_code, username, password, profile_name, created_at, expires_at) 
            VALUES (:order_id, :voucher_code, :username, :password, :profile_name, :created_at, :expires_at)
        ");
        
        $stmt->execute([
            ':order_id' => $orderId,
            ':voucher_code' => $voucherCode,
            ':username' => $username,
            ':password' => $password,
            ':profile_name' => 'default', // Mikrotik profile name
            ':created_at' => $createdAt->format('Y-m-d H:i:s'),
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Create Midtrans transaction
        $orderData = [
            'order_id' => $orderId,
            'amount' => $package['price'],
            'customer_name' => $data['customerName'],
            'customer_email' => $data['customerEmail'],
            'customer_phone' => $data['customerPhone'],
            'package_name' => $data['packageName'],
            'package_id' => $package['package_id']
        ];
        
        // For testing, return a mock response
        $snapToken = null;
        $isTestMode = false; // Set to false when ready to use real Midtrans
        
        // Try to create Midtrans transaction
        if (!$isTestMode) {
            try {
                $snapToken = createMidtransTransaction($orderData);
                error_log("Midtrans attempt for order $orderId, result: " . ($snapToken ? 'success' : 'failed'));
            } catch (Exception $e) {
                error_log("Midtrans integration error: " . $e->getMessage());
                // Fall back to test mode
                $isTestMode = true;
            }
        }
        
        // If Midtrans fails or we're in test mode, use mock
        if (!$snapToken || $isTestMode) {
            $snapToken = 'test-token-' . $orderId;
            $isTestMode = true;
            error_log("Using test token for order $orderId. Midtrans may have failed.");
        }
        
        if ($snapToken) {
            // Update order with Midtrans transaction data
            $pdo = connectDB(); // Reconnect after commit
            $stmt = $pdo->prepare("UPDATE orders SET payment_token = :payment_token WHERE order_id = :order_id");
            $stmt->execute([
                ':payment_token' => $snapToken,
                ':order_id' => $orderId
            ]);
            
            $message = $isTestMode ? 
                'Checkout berhasil! (Test Mode - Silakan hubungi admin untuk status pembayaran sebenarnya)' : 
                'Checkout berhasil! Silakan selesaikan pembayaran.';
            
            echo json_encode([
                'status' => 'success', 
                'message' => $message,
                'data' => [
                    'order_id' => $orderId,
                    'snap_token' => $snapToken,
                    'order_status' => 'pending'
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Gagal membuat transaksi pembayaran. Silakan coba lagi.'
            ]);
        }
        
    } catch(PDOException $e) {
        $pdo->rollback();
        error_log("Checkout error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat checkout: ' . $e->getMessage()]);
    }
}

function getPackages($data) {
    $pdo = connectDB();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        return;
    }
    
    try {
        $stmt = $pdo->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY price");
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $packages
        ]);
    } catch(PDOException $e) {
        error_log("Get packages error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengambil paket']);
    }
}

function getUserVouchers($data) {
    $pdo = connectDB();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        return;
    }
    
    $userId = $data['user_id'] ?? 0;
    
    if (empty($userId)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID wajib diisi']);
        return;
    }
    
    try {
        // Use LEFT JOIN in case a user has no vouchers yet
        $stmt = $pdo->prepare("
            SELECT o.order_status, v.voucher_code, v.username, v.password, 
                   v.expires_at, v.created_at as voucher_created, p.name as package_name, p.price
            FROM orders o
            INNER JOIN vouchers v ON o.order_id = v.order_id
            INNER JOIN packages p ON o.package_id = p.package_id
            WHERE o.user_id = :user_id
            ORDER BY v.created_at DESC
        ");
        
        $stmt->execute([':user_id' => $userId]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log for debugging
        error_log("getUserVouchers: userId=$userId, result_count=" . count($vouchers));
        
        echo json_encode([
            'status' => 'success',
            'data' => $vouchers
        ]);
    } catch(PDOException $e) {
        error_log("Get user vouchers error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mengambil voucher: ' . $e->getMessage()]);
    }
}

function updateProfile($data) {
    $pdo = connectDB();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        return;
    }
    
    // Validate required fields
    $userId = $data['user_id'] ?? 0;
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    
    if (empty($userId) || empty($name) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID, name, and email are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid']);
        return;
    }
    
    try {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $userId
        ]);
        
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan oleh pengguna lain']);
            return;
        }
        
        // If password is being changed, verify current password
        $newPassword = $data['new_password'] ?? '';
        if (!empty($newPassword)) {
            $currentPassword = $data['current_password'] ?? '';
            
            if (empty($currentPassword)) {
                echo json_encode(['status' => 'error', 'message' => 'Password saat ini diperlukan untuk verifikasi']);
                return;
            }
            
            // Get current password hash
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                echo json_encode(['status' => 'error', 'message' => 'Password saat ini salah']);
                return;
            }
        }
        
        // Prepare update query
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = :name, email = :email, phone = :phone, password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $data['phone'] ?? null,
                ':password_hash' => $hashedPassword,
                ':user_id' => $userId
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = :name, email = :email, phone = :phone, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $data['phone'] ?? null,
                ':user_id' => $userId
            ]);
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Profile berhasil diperbarui'
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Tidak ada perubahan yang dilakukan'
            ]);
        }
        
    } catch(PDOException $e) {
        error_log("Update profile error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui profile']);
    }
}
?>