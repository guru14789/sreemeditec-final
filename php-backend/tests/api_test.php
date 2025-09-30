<?php
// Define the project root
define('PROJECT_ROOT', dirname(__DIR__));

// Include the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include the main config file
require_once PROJECT_ROOT . '/config/config.php';

// Use the models
use Models\User;
use Models\Product;
use Models\Cart;
use Models\Order;

// Mock the necessary global variables
$_SERVER['REQUEST_METHOD'] = 'POST';
$GLOBALS['user'] = (object) ['uid' => null];

function runTest(string $description, callable $testFunction): void
{
    echo "Running test: $description...\n";
    try {
        $testFunction();
        echo "Test PASSED.\n\n";
    } catch (Exception $e) {
        echo "Test FAILED: " . $e->getMessage() . "\n\n";
    }
}

// Test User Registration
runTest("User Registration", function () {
    $userModel = new User();
    $email = "testuser_" . uniqid() . "@example.com";
    $password = "password123";
    $userData = [
        'email' => $email,
        'password' => $password,
        'username' => 'Test User',
        'phone' => '1234567890'
    ];
    $result = $userModel->register($userData);
    
    if (!$result['success']) {
        throw new Exception("User registration failed: " . $result['errors'][0]);
    }
    
    $GLOBALS['user']->uid = $result['user_id'];
});

// Test User Login
runTest("User Login", function () {
    $userModel = new User();
    $email = "testuser_" . uniqid() . "@example.com";
    $password = "password123";
    $userData = [
        'email' => $email,
        'password' => $password,
        'username' => 'Test User',
        'phone' => '1234567890'
    ];
    $userModel->register($userData);

    $result = $userModel->login($email, $password);
    
    if (!$result['success']) {
        throw new Exception("User login failed: " . $result['errors'][0]);
    }
});

// Test Product Retrieval
runTest("Product Retrieval", function () {
    $productModel = new Product();
    $products = $productModel->getAllProducts([]);
    
    if (empty($products)) {
        // if no products, create one
        $productModel->createProduct(['name' => 'Test Product', 'description' => 'Test Description', 'category' => 'Test Category', 'price' => 100]);
        $products = $productModel->getAllProducts([]);
    }
    
    $productId = $products[0]['id'];
    $product = $productModel->getProductById($productId);
    
    if (!$product) {
        throw new Exception("Could not retrieve a single product.");
    }
});

// Test Cart Management
runTest("Cart Management", function () {
    $cartModel = new Cart();
    $productModel = new Product();
    
    $products = $productModel->getAllProducts([]);
    $productId = $products[0]['id'];
    
    // Add to cart
    $result = $cartModel->addToCart($GLOBALS['user']->uid, $productId, 2);
    if (!$result['success']) {
        throw new Exception("Failed to add to cart.");
    }
    
    // Get cart
    $cart = $cartModel->getCart($GLOBALS['user']->uid);
    if (empty($cart['items'])) {
        throw new Exception("Cart is empty after adding an item.");
    }
    
    // Update cart
    $result = $cartModel->updateCartItem($GLOBALS['user']->uid, $productId, 3);
    if (!$result['success']) {
        throw new Exception("Failed to update cart item.");
    }
    
    // Remove from cart by setting quantity to 0
    $result = $cartModel->updateCartItem($GLOBALS['user']->uid, $productId, 0);
    if (!$result['success']) {
        throw new Exception("Failed to remove from cart.");
    }
});

// Test Order Placement
runTest("Order Placement", function () {
    $orderModel = new Order();
    $cartModel = new Cart();
    $productModel = new Product();
    
    $products = $productModel->getAllProducts([]);
    $productId = $products[0]['id'];
    $productPrice = $products[0]['price'];
    
    $cartModel->addToCart($GLOBALS['user']->uid, $productId, 1);
    
    $orderDetails = [
        'items' => [[ 'productId' => $productId, 'quantity' => 1, 'price' => $productPrice ]],
        'total_amount' => $productPrice,
        'shipping_address' => '123 Test St'
    ];
    
    $result = $orderModel->createOrder($GLOBALS['user']->uid, $orderDetails);
    
    if (!$result['success']) {
        throw new Exception("Failed to create order.");
    }
    
    $orders = $orderModel->getUserOrders($GLOBALS['user']->uid);
    if (empty($orders)) {
        throw new Exception("No orders found for user.");
    }
});

echo "All tests completed.\n";
?>