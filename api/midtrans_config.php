<?php
// Midtrans configuration for direct API calls
// Using cURL instead of the official PHP library to avoid dependency issues

// Configuration is loaded via environment variables
// Using functions to access values instead of constants for better safety

// Determine environment
if (($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true') {
    define('MIDTRANS_BASE_URL', 'https://api.midtrans.com');
} else {
    define('MIDTRANS_BASE_URL', 'https://api.sandbox.midtrans.com');
}

// Function to get Midtrans configuration
function getMidtransConfig($key) {
    switch ($key) {
        case 'server_key':
            return $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
        case 'client_key':
            return $_ENV['MIDTRANS_CLIENT_KEY'] ?? '';
        case 'is_production':
            // Force sandbox for development
            return false; // ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true';
        case 'base_url':
            // Always use sandbox for development
            return 'https://api.sandbox.midtrans.com'; // ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true' ? 
                   //'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        default:
            return null;
    }
}

// Function to create Midtrans transaction using cURL
function createMidtransTransaction($orderData) {
    $serverKey = getMidtransConfig('server_key');
    
    if (!$serverKey) {
        error_log("Midtrans server key not configured");
        return null;
    }
    
    error_log("Attempting to create Midtrans transaction for order: " . $orderData['order_id']);
    error_log("Server key length: " . strlen($serverKey));
    error_log("Base URL: " . getMidtransConfig('base_url'));
    
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
        ),
        // Add redirect URLs for your domain
        'callbacks' => array(
            'finish' => 'http://mikrotik.bulenetwork.com:4000/myApps/WifiHub/success.html',
        ),
        // Ensure we get token, not redirect
        'credit_card_3d_secure' => false,
        // Explicitly request Snap token (this is the key difference)
        'vtweb' => array(
            'enabled' => true
        )
    );
    
    $curl = curl_init();
    
    $baseUrl = getMidtransConfig('base_url');
    $url = $baseUrl . "/snap/v1/transactions";
    
    error_log("Making request to: $url");
    error_log("Request params: " . json_encode($params));
    error_log("Server key (first 10 chars): " . substr($serverKey, 0, 10) . "...");
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
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
    
    error_log("Midtrans API Response Code: $httpCode, Response: $response");
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log("Midtrans API Error: HTTP Code $httpCode, Response: $response");
        return null;
    }
    
    $result = json_decode($response, true);
    
    error_log("Midtrans full response for order " . $orderData['order_id'] . ": " . json_encode($result));
    
    // Midtrans Snap API should return both token and redirect_url
    // The token is what we need for Snap popup, redirect_url is fallback
    if (isset($result['token'])) {
        error_log("Midtrans transaction created successfully for order " . $orderData['order_id'] . " with token: " . substr($result['token'], 0, 20) . "...");
        // Return just the token for Snap popup
        return $result['token'];
    } elseif (isset($result['redirect_url'])) {
        error_log("Midtrans returned redirect_url but no token. This should not happen with Snap API.");
        error_log("Redirect URL: " . $result['redirect_url']);
        error_log("Full response: " . json_encode($result));
        // This is unexpected with Snap API, but if it happens, return null to trigger test mode
        // Also log specific error details
        if (isset($result['error_messages'])) {
            error_log("Midtrans error messages: " . json_encode($result['error_messages']));
        }
        return null;
    }
    
    error_log("Midtrans response missing token: " . $response);
    return null;
}

// Webhook handler for Midtrans notifications
function handleMidtransWebhook() {
    // Write detailed log to a specific file to track webhook calls
    $webhookLogFile = __DIR__ . '/webhook_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $json = file_get_contents('php://input');
    
    // Log the raw input for debugging
    file_put_contents($webhookLogFile, 
        "\n[$timestamp] Webhook received: " . $json . "\n", 
        FILE_APPEND | LOCK_EX);
    
    error_log("Webhook received: " . $json);
    
    $notification = json_decode($json, true);
    
    if (!$notification) {
        $errorMsg = "[$timestamp] Webhook: Invalid notification JSON - " . $json;
        file_put_contents($webhookLogFile, $errorMsg . "\n", FILE_APPEND | LOCK_EX);
        error_log("Webhook: Invalid notification JSON - " . $json);
        http_response_code(400);
        echo "Invalid notification";
        return;
    }
    
    $orderId = $notification['order_id'];
    $transactionStatus = $notification['transaction_status'];
    $fraudStatus = isset($notification['fraud_status']) ? $notification['fraud_status'] : 'accept';
    
    $logMsg = "[$timestamp] Webhook: Processing order $orderId with status $transactionStatus";
    file_put_contents($webhookLogFile, $logMsg . "\n", FILE_APPEND | LOCK_EX);
    error_log("Webhook: Processing order $orderId with status $transactionStatus");
    
    // Connect to database
    $pdo = connectDB();
    if (!$pdo) {
        $errorMsg = "[$timestamp] Webhook: Database connection failed";
        file_put_contents($webhookLogFile, $errorMsg . "\n", FILE_APPEND | LOCK_EX);
        error_log("Webhook: Database connection failed");
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
            $errorMsg = "[$timestamp] Webhook: Order not found: $orderId";
            file_put_contents($webhookLogFile, $errorMsg . "\n", FILE_APPEND | LOCK_EX);
            error_log("Webhook: Order not found: $orderId");
            // Even if order is not found, we still return 200 to acknowledge the webhook
            http_response_code(200);
            echo "OK";
            return;
        }
        
        // Log original status
        $statusMsg = "[$timestamp] Webhook: Original status for order $orderId was: " . $order['order_status'];
        file_put_contents($webhookLogFile, $statusMsg . "\n", FILE_APPEND | LOCK_EX);
        error_log("Webhook: Original status for order $orderId was: " . $order['order_status']);
        
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
        } else if ($transactionStatus == 'failure') {
            $newStatus = 'failed';
        }
        
        // Only update if status actually changed
        if ($newStatus !== $order['order_status']) {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET order_status = :order_status WHERE order_id = :order_id");
            $result = $stmt->execute([
                ':order_status' => $newStatus,
                ':order_id' => $orderId
            ]);
            
            if ($result) {
                $successMsg = "[$timestamp] Webhook: Successfully updated order $orderId status to: $newStatus";
                file_put_contents($webhookLogFile, $successMsg . "\n", FILE_APPEND | LOCK_EX);
                error_log("Webhook: Successfully updated order $orderId status to: $newStatus");
            } else {
                $failMsg = "[$timestamp] Webhook: Failed to update order $orderId status";
                file_put_contents($webhookLogFile, $failMsg . "\n", FILE_APPEND | LOCK_EX);
                error_log("Webhook: Failed to update order $orderId status");
            }
            
            // If payment is successful, activate voucher
            if ($newStatus === 'paid') {
                $stmt = $pdo->prepare("UPDATE vouchers SET is_active = 1 WHERE order_id = :order_id");
                $result = $stmt->execute([':order_id' => $orderId]);
                
                if ($result) {
                    $voucherMsg = "[$timestamp] Webhook: Voucher for order $orderId activated";
                    file_put_contents($webhookLogFile, $voucherMsg . "\n", FILE_APPEND | LOCK_EX);
                    error_log("Webhook: Voucher for order $orderId activated");
                } else {
                    $voucherFailMsg = "[$timestamp] Webhook: Failed to activate voucher for order $orderId";
                    file_put_contents($webhookLogFile, $voucherFailMsg . "\n", FILE_APPEND | LOCK_EX);
                    error_log("Webhook: Failed to activate voucher for order $orderId");
                }
                
                // Get user information to track the transaction
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
                
                $paymentMsg = "[$timestamp] Webhook: Payment successful for order $orderId. Voucher activated.";
                file_put_contents($webhookLogFile, $paymentMsg . "\n", FILE_APPEND | LOCK_EX);
                error_log("Webhook: Payment successful for order $orderId. Voucher activated.");
            }
        } else {
            $noChangeMsg = "[$timestamp] Webhook: Order $orderId status unchanged ($newStatus), no update needed";
            file_put_contents($webhookLogFile, $noChangeMsg . "\n", FILE_APPEND | LOCK_EX);
            error_log("Webhook: Order $orderId status unchanged ($newStatus), no update needed");
        }
        
        http_response_code(200);
        echo "OK";
        
    } catch (Exception $e) {
        $exceptionMsg = "[$timestamp] Webhook error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString();
        file_put_contents($webhookLogFile, $exceptionMsg . "\n", FILE_APPEND | LOCK_EX);
        error_log("Webhook error: " . $e->getMessage());
        error_log("Webhook exception trace: " . $e->getTraceAsString());
        // Even if there's an error processing, we return 200 to acknowledge the webhook
        http_response_code(200);
        echo "OK";
    }
}
?>