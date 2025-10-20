<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// Admin route handling
switch (true) {
    case str_starts_with($route, '/admin/stats') && $method === 'GET':
        handleGetAdminStats();
        break;
        
    case str_starts_with($route, '/admin/users') && $method === 'GET':
        handleGetAllUsers();
        break;
        
    case str_starts_with($route, '/admin/users') && $method === 'PUT':
        handleUpdateUserRole();
        break;
        
    case str_starts_with($route, '/admin/orders') && $method === 'GET':
        handleGetAllOrders();
        break;
        
    case str_starts_with($route, '/admin/orders/') && $method === 'PUT':
        handleUpdateOrderStatus();
        break;
}

// Admin handlers
function handleGetAdminStats(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    try {
        $orderModel = new \Models\Order();
        $userModel = new \Models\User();
        $productModel = new \Models\Product();
        
        // Get all orders
        $allOrders = $orderModel->getAllOrders();
        
        // Calculate total revenue
        $totalRevenue = 0;
        foreach ($allOrders as $order) {
            $totalRevenue += floatval($order['totalAmount'] ?? $order['total_amount'] ?? 0);
        }
        
        // Get total users
        $allUsers = $userModel->getAllUsers();
        $totalUsers = count($allUsers);
        
        // Get total orders
        $totalOrders = count($allOrders);
        
        // Calculate monthly revenue (last 2 months, current month, next 3 months)
        $monthlyRevenue = [];
        $currentDate = new DateTime();
        
        // Generate months: -2, -1, 0 (current), +1, +2, +3
        for ($i = -2; $i <= 3; $i++) {
            $month = clone $currentDate;
            if ($i !== 0) {
                $modifier = $i > 0 ? "+$i months" : "$i months";
                $month->modify($modifier);
            }
            $monthKey = $month->format('Y-m');
            
            $monthRevenue = 0;
            foreach ($allOrders as $order) {
                $orderDate = null;
                $timestamp = $order['createdAt'] ?? $order['created_at'] ?? null;
                
                if ($timestamp) {
                    if (is_string($timestamp)) {
                        $orderDate = new DateTime($timestamp);
                    } elseif (is_object($timestamp) && method_exists($timestamp, 'get')) {
                        $orderDate = $timestamp->get();
                    } elseif (is_object($timestamp) && method_exists($timestamp, 'format')) {
                        $orderDate = $timestamp;
                    }
                }
                
                if ($orderDate && $orderDate->format('Y-m') === $monthKey) {
                    $monthRevenue += floatval($order['totalAmount'] ?? $order['total_amount'] ?? 0);
                }
            }
            
            $monthlyRevenue[] = [
                'month' => $monthKey,
                'revenue' => $monthRevenue,
                'is_current' => $i === 0,
                'is_future' => $i > 0
            ];
        }
        
        // Get recent orders (last 5)
        $recentOrders = array_slice($allOrders, -5);
        $recentOrders = array_reverse($recentOrders);
        
        // Calculate payment statistics
        $totalPayments = 0;
        $todayRevenue = 0;
        $paymentMethods = [];
        $today = (new DateTime())->format('Y-m-d');
        
        foreach ($allOrders as $order) {
            $totalPayments++;
            
            // Calculate today's revenue
            $orderDate = null;
            $timestamp = $order['createdAt'] ?? $order['created_at'] ?? null;
            
            if ($timestamp) {
                if (is_string($timestamp)) {
                    $orderDate = new DateTime($timestamp);
                } elseif (is_object($timestamp) && method_exists($timestamp, 'get')) {
                    // Firestore Timestamp - use get() to get DateTime
                    $orderDate = $timestamp->get();
                } elseif (is_object($timestamp) && method_exists($timestamp, 'format')) {
                    $orderDate = $timestamp;
                }
            }
            
            if ($orderDate && $orderDate->format('Y-m-d') === $today) {
                $todayRevenue += floatval($order['totalAmount'] ?? $order['total_amount'] ?? 0);
            }
            
            // Count payment methods
            $method = $order['paymentMethod'] ?? $order['payment_method'] ?? 'unknown';
            if (!isset($paymentMethods[$method])) {
                $paymentMethods[$method] = 0;
            }
            $paymentMethods[$method]++;
        }
        
        // Calculate average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        sendJsonResponse([
            'success' => true,
            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'total_payments' => $totalPayments,
                'today_revenue' => $todayRevenue,
                'avg_order_value' => $avgOrderValue,
                'payment_methods' => $paymentMethods,
                'monthly_revenue' => $monthlyRevenue,
                'recent_orders' => $recentOrders
            ]
        ]);
    } catch (\Exception $e) {
        error_log("Get admin stats error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'error' => 'Failed to fetch stats'], 500);
    }
}

function handleGetAllUsers(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    try {
        $userModel = new \Models\User();
        $users = $userModel->getAllUsers();
        
        sendJsonResponse([
            'success' => true,
            'users' => $users
        ]);
    } catch (\Exception $e) {
        error_log("Get all users error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'error' => 'Failed to fetch users'], 500);
    }
}

function handleUpdateUserRole(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['uid']) || !isset($input['role'])) {
        sendJsonResponse(['success' => false, 'error' => 'User ID and role are required'], 400);
        return;
    }
    
    try {
        $userModel = new \Models\User();
        $result = $userModel->updateProfile($input['uid'], ['role' => $input['role']]);
        sendJsonResponse($result);
    } catch (\Exception $e) {
        error_log("Update user role error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'error' => 'Failed to update user role'], 500);
    }
}

function handleGetAllOrders(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    try {
        $orderModel = new \Models\Order();
        $orders = $orderModel->getAllOrders();
        
        sendJsonResponse([
            'success' => true,
            'orders' => $orders
        ]);
    } catch (\Exception $e) {
        error_log("Get all orders error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'error' => 'Failed to fetch orders'], 500);
    }
}

function handleUpdateOrderStatus(): void
{
    AuthMiddleware::handle();
    $user = $GLOBALS['user'];
    
    if ($user->role !== 'admin') {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        return;
    }
    
    // Extract order ID from route
    $orderId = str_replace('/admin/orders/', '', $route);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['status'])) {
        sendJsonResponse(['success' => false, 'error' => 'Status is required'], 400);
        return;
    }
    
    try {
        $orderModel = new \Models\Order();
        $result = $orderModel->updateOrderStatus($orderId, $input['status']);
        sendJsonResponse($result);
    } catch (\Exception $e) {
        error_log("Update order status error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'error' => 'Failed to update order status'], 500);
    }
}
?>
