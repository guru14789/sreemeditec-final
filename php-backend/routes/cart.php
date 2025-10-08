<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for cart
switch (true) {
    case str_starts_with($route, '/cart') && $method === 'GET':
        handleGetCart();
        break;
        
    case str_starts_with($route, '/cart/add') && $method === 'POST':
        handleAddToCart();
        break;
        
    case str_starts_with($route, '/cart/update') && $method === 'PUT':
        handleUpdateCartItem();
        break;
        
    case str_starts_with($route, '/cart/clear') && $method === 'DELETE':
        handleClearCart();
        break;
}

// Cart handlers
function handleGetCart(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $cartModel = new \Models\Cart();
    $result = $cartModel->getCart($user->uid);
    
    sendJsonResponse($result);
}

function handleAddToCart(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_id'])) {
        sendJsonResponse(['error' => 'Product ID is required'], 400);
    }
    
    $quantity = (int)($input['quantity'] ?? 1);
    
    $cartModel = new \Models\Cart();
    $result = $cartModel->addToCart($user->uid, $input['product_id'], $quantity);
    
    sendJsonResponse($result, $result['success'] ? 201 : 400);
}

function handleUpdateCartItem(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_id']) || !isset($input['quantity'])) {
        sendJsonResponse(['error' => 'Product ID and quantity are required'], 400);
    }
    
    $cartModel = new \Models\Cart();
    $result = $cartModel->updateCartItem($user->uid, $input['product_id'], (int)$input['quantity']);
    
    sendJsonResponse($result, $result['success'] ? 200 : 400);
}

function handleClearCart(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    $cartModel = new \Models\Cart();
    $result = $cartModel->clearCart($user->uid);
    
    sendJsonResponse($result);
}
?>