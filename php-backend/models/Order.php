<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/User.php';

class Order 
{
    private $firestore;
    private $ordersCollection;

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
        $this->ordersCollection = $this->firestore->collection('orders');
    }
    
    public function createOrder(string $userId, array $orderData): array
    {
        $requiredFields = ['items', 'total_amount', 'shipping_address'];
        $errors = \validateRequiredFields($orderData, $requiredFields);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $orderRef = $this->ordersCollection->newDocument();
            $orderId = $orderRef->id();

            $orderRef->set([
                'orderId' => $orderId,
                'userId' => $userId,
                'items' => $orderData['items'],
                'totalAmount' => (float)$orderData['total_amount'],
                'shippingAddress' => $orderData['shipping_address'],
                'billingAddress' => $orderData['billing_address'] ?? $orderData['shipping_address'],
                'paymentMethod' => $orderData['payment_method'] ?? 'cod',
                'paymentStatus' => 'pending',
                'orderStatus' => 'pending',
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Order created successfully'
            ];
        } catch (\Exception $e) {
            error_log("Create order error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Order creation failed']];
        }
    }
    
    public function getOrderById(string $orderId, ?string $userId = null): ?array
    {
        try {
            $orderSnapshot = $this->ordersCollection->document($orderId)->snapshot();

            if (!$orderSnapshot->exists()) {
                return null;
            }

            $order = $orderSnapshot->data();

            if ($userId && $order['userId'] !== $userId) {
                return null;
            }

            return $order;
        } catch (\Exception $e) {
            error_log("Get order error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getUserOrders(string $userId): array
    {
        try {
            $query = $this->ordersCollection->where('userId', '=', $userId);
            $orders = [];
            foreach ($query->documents() as $document) {
                if ($document->exists()) {
                    $orders[] = $document->data();
                }
            }
            return $orders;
        } catch (\Exception $e) {
            error_log("Get user orders error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateOrderStatus(string $orderId, string $status): array
    {
        try {
            $this->ordersCollection->document($orderId)->update([
                ['path' => 'orderStatus', 'value' => $status],
                ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
            ]);
            return ['success' => true, 'message' => 'Order status updated successfully'];
        } catch (\Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update order status']];
        }
    }
    
    public function updatePaymentStatus(string $orderId, string $status): array
    {
        try {
            $updateData = [
                ['path' => 'paymentStatus', 'value' => $status],
                ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
            ]

            if ($status === 'completed') {
                $updateData[] = ['path' => 'orderStatus', 'value' => 'confirmed'];
            }

            $this->ordersCollection->document($orderId)->update($updateData);
            
            return ['success' => true, 'message' => 'Payment status updated successfully'];
        } catch (\Exception $e) {
            error_log("Update payment status error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update payment status']];
        }
    }

    public function getAllOrders(): array
    {
        try {
            $userModel = new User();
            $orders = [];
            $documents = $this->ordersCollection->documents();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $order = $document->data();
                    $user = $userModel->getUserById($order['userId']);
                    $order['username'] = $user ? $user['username'] : 'Unknown User';
                    $orders[] = $order;
                }
            }
            return $orders;
        } catch (\Exception $e) {
            error_log("Get all orders error: " . $e->getMessage());
            return [];
        }
    }
}
?>