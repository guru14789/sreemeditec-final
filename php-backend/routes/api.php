<?php
require_once __DIR__ . '/../config/config.php';

// Parse the URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove /api prefix
$route = str_replace('/api', '', $uri);

// Route to the correct resource
if (str_starts_with($route, '/admin')) {
    require_once __DIR__ . '/admin.php';
} elseif (str_starts_with($route, '/auth')) {
    require_once __DIR__ . '/auth.php';
} elseif (str_starts_with($route, '/products')) {
    require_once __DIR__ . '/product.php';
} elseif (str_starts_with($route, '/cart')) {
    require_once __DIR__ . '/cart.php';
} elseif (str_starts_with($route, '/orders')) {
    require_once __DIR__ . '/order.php';
} elseif (str_starts_with($route, '/payment')) {
    require_once __DIR__ . '/payment.php';
} elseif (str_starts_with($route, '/shipment')) {
    require_once __DIR__ . '/shipment.php';
} elseif (str_starts_with($route, '/quotes')) {
    require_once __DIR__ . '/quote.php';
} elseif (str_starts_with($route, '/contacts')) {
    require_once __DIR__ . '/contact.php';
} elseif (str_starts_with($route, '/user')) {
    require_once __DIR__ . '/user.php';
} else {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
}
?>