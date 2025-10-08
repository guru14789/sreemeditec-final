<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for orders
switch (true) {
    case str_starts_with($route, '/orders') && $method === 'GET':
        handleGetUserOrders();
        break;
        
    case preg_match('/^\/orders\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'GET':
        handleGetOrderById($matches[1]);
        break;
        
    case str_starts_with($route, '/orders') && $method === 'POST':
        handleCreateOrder();
        break;
}

// Order handlers
function handleGetUserOrders(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $orderModel = new \Models\Order();
    $orders = $orderModel->getUserOrders($user->uid);
    
    sendJsonResponse([
        'success' => true,
        'orders' => $orders,
        'total' => count($orders)
    ]);
}

function handleGetOrderById(string $orderId): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $orderModel = new \Models\Order();
    $order = $orderModel->getOrderById($orderId, $user->uid);
    
    if ($order) {
        sendJsonResponse([
            'success' => true,
            'order' => $order
        ]);
    } else {
        sendJsonResponse(['error' => 'Order not found'], 404);
    }
}

function handleCreateOrder(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['error' => 'Invalid JSON input'], 400);
    }
    
    $orderModel = new \Models\Order();
    $result = $orderModel->createOrder($user->uid, $input);
    
    sendJsonResponse($result, $result['success'] ? 201 : 400);
}
?>