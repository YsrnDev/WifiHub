<?php
// Midtrans configuration for direct API calls
// Using cURL instead of the official PHP library to avoid dependency issues

// Load configuration from environment
define('MIDTRANS_SERVER_KEY', $_ENV['MIDTRANS_SERVER_KEY'] ?? '');
define('MIDTRANS_CLIENT_KEY', $_ENV['MIDTRANS_CLIENT_KEY'] ?? '');
define('MIDTRANS_IS_PRODUCTION', ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true');

// Determine environment
if (MIDTRANS_IS_PRODUCTION) {
    define('MIDTRANS_BASE_URL', 'https://api.midtrans.com');
} else {
    define('MIDTRANS_BASE_URL', 'https://api.sandbox.midtrans.com');
}

// Function to create Midtrans transaction using cURL
function createMidtransTransaction($orderData) {
    $serverKey = defined('MIDTRANS_SERVER_KEY') ? MIDTRANS_SERVER_KEY : $_ENV['MIDTRANS_SERVER_KEY'];
    
    $params = array(
        'transaction_details' => array(
            'order_id' => $orderData['order_id'],
            'gross_amount' => $orderData['amount'],
        ),
        'customer_details' => array(
            'first_name' => $orderData['customer_name'],
            'email' => $orderData['customer_email'],
            'phone' => $orderData['customer_phone'],
        ),
        'item_details' => array(
            array(
                'id' => $orderData['package_id'],
                'price' => $orderData['amount'],
                'quantity' => 1,
                'name' => $orderData['package_name']
            )
        )
    );
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => MIDTRANS_BASE_URL . "/v2/charge",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode($serverKey . ":")
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
        error_log("cURL Error: " . $err);
        return null;
    }
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log("Midtrans API Error: HTTP Code $httpCode, Response: $response");
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['token'])) {
        return $result['token'];
    } elseif (isset($result['redirect_url'])) {
        return $result; // Return full result for redirect
    }
    
    error_log("Midtrans response missing token: " . $response);
    return null;
}

// Webhook handler for Midtrans notifications
function handleMidtransWebhook() {
    $json = file_get_contents('php://input');
    $notification = json_decode($json, true);
    
    if (!$notification) {
        http_response_code(400);
        echo "Invalid notification";
        return;
    }
    
    $orderId = $notification['order_id'];
    $transactionStatus = $notification['transaction_status'];
    $fraudStatus = isset($notification['fraud_status']) ? $notification['fraud_status'] : 'accept';
    
    // Connect to database
    $pdo = connectDB();
    if (!$pdo) {
        error_log("Database connection failed in webhook");
        http_response_code(500);
        echo "Database connection failed";
        return;
    }
    
    try {
        // Get order details
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("Order not found: $orderId");
            http_response_code(404);
            echo "Order not found";
            return;
        }
        
        $newStatus = $order['order_status'];
        
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $newStatus = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $newStatus = 'paid';
            }
        } else if ($transactionStatus == 'settlement') {
            $newStatus = 'paid';
        } else if ($transactionStatus == 'pending') {
            $newStatus = 'pending';
        } else if ($transactionStatus == 'deny') {
            $newStatus = 'failed';
        } else if ($transactionStatus == 'expire') {
            $newStatus = 'expired';
        } else if ($transactionStatus == 'cancel') {
            $newStatus = 'cancelled';
        }
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET order_status = :order_status, updated_at = CURRENT_TIMESTAMP WHERE order_id = :order_id");
        $stmt->execute([
            ':order_status' => $newStatus,
            ':order_id' => $orderId
        ]);
        
        // If payment is successful, activate voucher
        if ($newStatus === 'paid') {
            // Update voucher to active
            $stmt = $pdo->prepare("UPDATE vouchers SET is_active = 1, activated_at = CURRENT_TIMESTAMP WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $orderId]);
            
            // Get user information to send notification
            $stmt = $pdo->prepare("
                SELECT u.name, u.email, o.voucher_code, v.username, v.password, p.name as package_name
                FROM users u
                JOIN orders o ON u.user_id = o.user_id
                JOIN vouchers v ON o.order_id = v.order_id
                JOIN packages p ON o.package_id = p.package_id
                WHERE o.order_id = :order_id
            ");
            $stmt->execute([':order_id' => $orderId]);
            $orderDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Here you could send email/SMS notification to user
            error_log("Payment successful for order $orderId. Voucher activated.");
        }
        
        error_log("Order $orderId status updated to: $newStatus");
        http_response_code(200);
        echo "OK";
        
    } catch (Exception $e) {
        error_log("Webhook error: " . $e->getMessage());
        http_response_code(500);
        echo "Error processing webhook";
    }
}
?>