<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// API routing for products
switch (true) {
    case str_starts_with($route, '/products') && $method === 'GET':
        handleGetProducts();
        break;
        
    case preg_match('/^\/products\/([a-zA-Z0-9\-]+)$/', $route, $matches) && $method === 'GET':
        handleGetProductById($matches[1]);
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
?>