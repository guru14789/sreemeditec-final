<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for products
switch (true) {
    case $route === '/products' && $method === 'GET':
        handleGetProducts();
        break;
    
    case $route === '/products' && $method === 'POST':
        handleCreateProduct();
        break;
        
    case preg_match('/^\/products\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'GET':
        handleGetProductById($matches[1]);
        break;
        
    case preg_match('/^\/products\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'PUT':
        handleUpdateProduct($matches[1]);
        break;
        
    case preg_match('/^\/products\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'DELETE':
        handleDeleteProduct($matches[1]);
        break;
}

// Product handlers
function handleGetProducts(): void
{
    $productModel = new \Models\Product();
    
    // Get filters from query parameters
    $filters = [];
    if (isset($_GET['category'])) {
        $filters['category'] = $_GET['category'];
    }
    if (isset($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    if (isset($_GET['price_min'])) {
        $filters['price_min'] = $_GET['price_min'];
    }
    if (isset($_GET['price_max'])) {
        $filters['price_max'] = $_GET['price_max'];
    }
    
    $products = $productModel->getAllProducts($filters);
    
    sendJsonResponse([
        'success' => true,
        'products' => $products,
        'total' => count($products)
    ]);
}

function handleGetProductById(string $productId): void
{
    $productModel = new \Models\Product();
    $product = $productModel->getProductById($productId);
    
    if ($product) {
        sendJsonResponse([
            'success' => true,
            'product' => $product
        ]);
    } else {
        sendJsonResponse(['error' => 'Product not found'], 404);
    }
}

function handleCreateProduct(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
        return;
    }
    
    $productModel = new \Models\Product();
    $result = $productModel->createProduct($input);
    
    sendJsonResponse($result, $result['success'] ? 201 : 400);
}

function handleUpdateProduct(string $productId): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
        return;
    }
    
    $productModel = new \Models\Product();
    $result = $productModel->updateProduct($productId, $input);
    
    sendJsonResponse($result);
}

function handleDeleteProduct(string $productId): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    $productModel = new \Models\Product();
    $result = $productModel->deleteProduct($productId);
    
    sendJsonResponse($result);
}
?>