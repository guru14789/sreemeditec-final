<?php
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for payments
switch (true) {
    case str_starts_with($route, '/payment/create-order') && $method === 'POST':
        handleCreatePaymentOrder();
        break;
        
    case str_starts_with($route, '/payment/verify') && $method === 'POST':
        handleVerifyPayment();
        break;
        
    case str_starts_with($route, '/payment/webhook') && $method === 'POST':
        handlePaymentWebhook();
        break;
        
    case preg_match('/^\/payment\/order\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'GET':
        handleGetPaymentByOrder($matches[1]);
        break;
}

function handleCreatePaymentOrder(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['order_id'])) {
        sendJsonResponse(['error' => 'Order ID is required'], 400);
        return;
    }
    
    $orderModel = new \Models\Order();
    $order = $orderModel->getOrderById($input['order_id'], $user->uid);
    
    if (!$order) {
        sendJsonResponse(['error' => 'Order not found or unauthorized'], 404);
        return;
    }
    
    if (!isset($order['totalAmount']) || $order['totalAmount'] <= 0) {
        sendJsonResponse(['error' => 'Invalid order amount'], 400);
        return;
    }
    
    $serverAmount = (float)$order['totalAmount'];
    
    $paymentModel = new \Models\Payment();
    $result = $paymentModel->createRazorpayOrder(
        $user->uid,
        $serverAmount,
        $input['order_id'],
        $input['metadata'] ?? []
    );
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handleVerifyPayment(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['razorpay_order_id']) || 
        !isset($input['razorpay_payment_id']) || 
        !isset($input['razorpay_signature'])) {
        sendJsonResponse(['error' => 'Payment details are required'], 400);
        return;
    }
    
    $paymentModel = new \Models\Payment();
    $result = $paymentModel->verifyPayment(
        $input['razorpay_order_id'],
        $input['razorpay_payment_id'],
        $input['razorpay_signature']
    );
    
    if ($result['success']) {
        $orderModel = new \Models\Order();
        $orderModel->updatePaymentStatus($result['order_id'], 'completed');
        
        $autoCreateShipment = $input['auto_create_shipment'] ?? true;
        
        if ($autoCreateShipment) {
            require_once __DIR__ . '/../models/Shipment.php';
            
            $order = $orderModel->getOrderById($result['order_id'], $user->uid);
            
            if ($order && isset($order['shippingAddress'])) {
                $shipmentData = [
                    'consignee_name' => $order['shippingAddress']['name'] ?? '',
                    'consignee_address' => $order['shippingAddress']['address'] ?? '',
                    'consignee_phone' => $order['shippingAddress']['phone'] ?? '',
                    'consignee_pincode' => $order['shippingAddress']['pincode'] ?? '',
                    'consignee_city' => $order['shippingAddress']['city'] ?? '',
                    'consignee_state' => $order['shippingAddress']['state'] ?? '',
                    'product_type' => 'Medical Equipment',
                    'weight' => $input['shipment_weight'] ?? 1.0,
                    'pieces' => $input['shipment_pieces'] ?? 1,
                    'declared_value' => $order['totalAmount'],
                    'service_type' => 'Express'
                ];
                
                $shipmentModel = new \Models\Shipment();
                $shipmentResult = $shipmentModel->createShipment(
                    $result['order_id'], 
                    $user->uid, 
                    $shipmentData
                );
                
                if ($shipmentResult['success']) {
                    $orderModel->updateOrderStatus($result['order_id'], 'shipped');
                    $result['shipment'] = $shipmentResult;
                }
            }
        }
    }
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handlePaymentWebhook(): void
{
    $webhookSecret = getenv('RAZORPAY_WEBHOOK_SECRET');
    $webhookSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
    $webhookBody = file_get_contents('php://input');
    
    if ($webhookSecret) {
        $expectedSignature = hash_hmac('sha256', $webhookBody, $webhookSecret);
        
        if ($webhookSignature !== $expectedSignature) {
            sendJsonResponse(['error' => 'Invalid webhook signature'], 401);
            return;
        }
    }
    
    $payload = json_decode($webhookBody, true);
    
    if (!$payload || !isset($payload['event'])) {
        sendJsonResponse(['error' => 'Invalid webhook payload'], 400);
        return;
    }
    
    $event = $payload['event'];
    $paymentEntity = $payload['payload']['payment']['entity'] ?? null;
    
    if ($event === 'payment.captured' && $paymentEntity) {
        $orderId = $paymentEntity['notes']['order_id'] ?? null;
        
        if ($orderId) {
            $orderModel = new \Models\Order();
            $orderModel->updatePaymentStatus($orderId, 'completed');
        }
    }
    
    sendJsonResponse(['success' => true, 'message' => 'Webhook processed']);
}

function handleGetPaymentByOrder(string $orderId): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $orderModel = new \Models\Order();
    $order = $orderModel->getOrderById($orderId, $user->uid);
    
    if (!$order) {
        sendJsonResponse(['error' => 'Order not found'], 404);
        return;
    }
    
    $paymentModel = new \Models\Payment();
    $payment = $paymentModel->getPaymentByOrderId($orderId);
    
    if ($payment) {
        sendJsonResponse([
            'success' => true,
            'payment' => $payment
        ]);
    } else {
        sendJsonResponse(['error' => 'Payment not found'], 404);
    }
}
