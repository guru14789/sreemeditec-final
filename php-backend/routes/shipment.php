<?php
require_once __DIR__ . '/../models/Shipment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for shipments
switch (true) {
    case str_starts_with($route, '/shipment/create') && $method === 'POST':
        handleCreateShipment();
        break;
        
    case preg_match('/^\/shipment\/track\/([a-zA-Z0-9]+)$/', $route, $matches) && $method === 'GET':
        handleTrackShipment($matches[1]);
        break;
        
    case preg_match('/^\/shipment\/order\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'GET':
        handleGetShipmentByOrder($matches[1]);
        break;
        
    case str_starts_with($route, '/shipments') && $method === 'GET':
        handleGetUserShipments();
        break;
        
    case preg_match('/^\/shipment\/label\/([a-zA-Z0-9]+)$/', $route, $matches) && $method === 'GET':
        handleGenerateLabel($matches[1]);
        break;
        
    case preg_match('/^\/shipment\/cancel\/([a-zA-Z0-9]+)$/', $route, $matches) && $method === 'POST':
        handleCancelShipment($matches[1]);
        break;
}

function handleCreateShipment(): void
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
        sendJsonResponse(['error' => 'Order not found'], 404);
        return;
    }
    
    if ($order['paymentStatus'] !== 'completed') {
        sendJsonResponse(['error' => 'Payment not completed for this order'], 400);
        return;
    }
    
    $shipmentData = [
        'consignee_name' => $input['consignee_name'] ?? $order['shippingAddress']['name'] ?? '',
        'consignee_address' => $input['consignee_address'] ?? $order['shippingAddress']['address'] ?? '',
        'consignee_phone' => $input['consignee_phone'] ?? $order['shippingAddress']['phone'] ?? '',
        'consignee_pincode' => $input['consignee_pincode'] ?? $order['shippingAddress']['pincode'] ?? '',
        'consignee_city' => $input['consignee_city'] ?? $order['shippingAddress']['city'] ?? '',
        'consignee_state' => $input['consignee_state'] ?? $order['shippingAddress']['state'] ?? '',
        'product_type' => $input['product_type'] ?? 'Medical Equipment',
        'weight' => $input['weight'] ?? 1.0,
        'pieces' => $input['pieces'] ?? 1,
        'declared_value' => $input['declared_value'] ?? $order['totalAmount'],
        'service_type' => $input['service_type'] ?? 'Express'
    ];
    
    $shipmentModel = new \Models\Shipment();
    $result = $shipmentModel->createShipment($input['order_id'], $user->uid, $shipmentData);
    
    if ($result['success']) {
        $orderModel->updateOrderStatus($input['order_id'], 'shipped');
    }
    
    sendJsonResponse($result, $result['success'] ? 201 : 400);
}

function handleTrackShipment(string $awbNumber): void
{
    AuthMiddleware::handle();
    
    $shipmentModel = new \Models\Shipment();
    $result = $shipmentModel->trackShipment($awbNumber);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handleGetShipmentByOrder(string $orderId): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $orderModel = new \Models\Order();
    $order = $orderModel->getOrderById($orderId, $user->uid);
    
    if (!$order) {
        sendJsonResponse(['error' => 'Order not found'], 404);
        return;
    }
    
    $shipmentModel = new \Models\Shipment();
    $shipment = $shipmentModel->getShipmentByOrderId($orderId);
    
    if ($shipment) {
        sendJsonResponse([
            'success' => true,
            'shipment' => $shipment
        ]);
    } else {
        sendJsonResponse(['error' => 'Shipment not found'], 404);
    }
}

function handleGetUserShipments(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $shipmentModel = new \Models\Shipment();
    $shipments = $shipmentModel->getUserShipments($user->uid);
    
    sendJsonResponse([
        'success' => true,
        'shipments' => $shipments,
        'total' => count($shipments)
    ]);
}

function handleGenerateLabel(string $referenceNumber): void
{
    AuthMiddleware::handle();
    
    $shipmentModel = new \Models\Shipment();
    $result = $shipmentModel->generateLabel($referenceNumber);
    
    if ($result['success']) {
        header('Content-Type: application/json');
        sendJsonResponse($result, 200);
    } else {
        sendJsonResponse($result, 400);
    }
}

function handleCancelShipment(string $referenceNumber): void
{
    AuthMiddleware::handle();
    
    $shipmentModel = new \Models\Shipment();
    $result = $shipmentModel->cancelShipment($referenceNumber);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}
