<?php
require_once __DIR__ . '/../config/config.php';

// Parse the URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove /api prefix
$route = str_replace('/api', '', $uri);

// Route to the correct resource
if (str_starts_with($route, '/auth')) {
    require_once __DIR__ . '/auth.php';
} elseif (str_starts_with($route, '/products')) {
    require_once __DIR__ . '/product.php';
} elseif (str_starts_with($route, '/cart')) {
    require_once __DIR__ . '/cart.php';
} elseif (str_starts_with($route, '/orders')) {
    require_once __DIR__ . '/order.php';
} else {
    sendJsonResponse(['error' => 'API endpoint not found'], 404);
}
?>