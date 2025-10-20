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
            $orderId = uniqid('order_', true);

            $this->ordersCollection->document($orderId)->set([
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

            $paymentsCollection = $this->firestore->collection('payments');
            $shipmentsCollection = $this->firestore->collection('shipments');
            
            $paymentQuery = $paymentsCollection->where('orderId', '=', $orderId)->limit(1);
            $paymentDocs = $paymentQuery->documents();
            foreach ($paymentDocs as $paymentDoc) {
                if ($paymentDoc->exists()) {
                    $order['payment'] = $paymentDoc->data();
                    break;
                }
            }
            
            $shipmentQuery = $shipmentsCollection->where('orderId', '=', $orderId)->limit(1);
            $shipmentDocs = $shipmentQuery->documents();
            foreach ($shipmentDocs as $shipmentDoc) {
                if ($shipmentDoc->exists()) {
                    $order['shipment'] = $shipmentDoc->data();
                    break;
                }
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
            $orderIds = [];
            
            foreach ($query->documents() as $document) {
                if ($document->exists()) {
                    $order = $document->data();
                    $orderId = $order['orderId'] ?? $document->id();
                    $orderIds[] = $orderId;
                    $orders[$orderId] = $order;
                }
            }
            
            if (empty($orderIds)) {
                return [];
            }
            
            $paymentsCollection = $this->firestore->collection('payments');
            $shipmentsCollection = $this->firestore->collection('shipments');
            
            $paymentQuery = $paymentsCollection->where('userId', '=', $userId);
            foreach ($paymentQuery->documents() as $paymentDoc) {
                if ($paymentDoc->exists()) {
                    $payment = $paymentDoc->data();
                    $orderId = $payment['orderId'] ?? null;
                    if ($orderId && isset($orders[$orderId])) {
                        $orders[$orderId]['payment'] = $payment;
                    }
                }
            }
            
            $shipmentQuery = $shipmentsCollection->where('userId', '=', $userId);
            foreach ($shipmentQuery->documents() as $shipmentDoc) {
                if ($shipmentDoc->exists()) {
                    $shipment = $shipmentDoc->data();
                    $orderId = $shipment['orderId'] ?? null;
                    if ($orderId && isset($orders[$orderId])) {
                        $orders[$orderId]['shipment'] = $shipment;
                    }
                }
            }
            
            // Filter orders to only show successfully paid orders
            $paidOrders = array_filter($orders, function($order) {
                $paymentStatus = strtolower($order['payment']['status'] ?? $order['paymentStatus'] ?? '');
                return in_array($paymentStatus, ['captured', 'completed', 'paid', 'success', 'succeeded']);
            });
            
            // Sort by creation date (newest first)
            usort($paidOrders, function($a, $b) {
                $timeA = $a['createdAt']->get()->getTimestamp();
                $timeB = $b['createdAt']->get()->getTimestamp();
                return $timeB - $timeA;
            });
            
            return array_values($paidOrders);
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
            ];

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
            $paymentsCollection = $this->firestore->collection('payments');
            $shipmentsCollection = $this->firestore->collection('shipments');
            
            $orders = [];
            $orderIds = [];
            
            $documents = $this->ordersCollection->documents();
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $order = $document->data();
                    $orderId = $order['orderId'] ?? $document->id();
                    $user = $userModel->getUserById($order['userId']);
                    $order['username'] = $user ? $user['username'] : 'Unknown User';
                    $order['customer_name'] = $user ? $user['name'] : 'Unknown';
                    $order['email'] = $user ? $user['email'] : '';
                    $order['phone'] = $user ? $user['phone'] : '';
                    $orderIds[] = $orderId;
                    $orders[$orderId] = $order;
                }
            }
            
            // Fetch payment information for all orders
            $paymentQuery = $paymentsCollection->documents();
            foreach ($paymentQuery as $paymentDoc) {
                if ($paymentDoc->exists()) {
                    $payment = $paymentDoc->data();
                    $orderId = $payment['orderId'] ?? null;
                    if ($orderId && isset($orders[$orderId])) {
                        $orders[$orderId]['payment'] = $payment;
                    }
                }
            }
            
            // Fetch shipment information for all orders
            $shipmentQuery = $shipmentsCollection->documents();
            foreach ($shipmentQuery as $shipmentDoc) {
                if ($shipmentDoc->exists()) {
                    $shipment = $shipmentDoc->data();
                    $orderId = $shipment['orderId'] ?? null;
                    if ($orderId && isset($orders[$orderId])) {
                        $orders[$orderId]['shipment'] = $shipment;
                    }
                }
            }
            
            // Filter orders to only show successfully paid orders
            $paidOrders = array_filter($orders, function($order) {
                $paymentStatus = strtolower($order['payment']['status'] ?? $order['paymentStatus'] ?? '');
                return in_array($paymentStatus, ['captured', 'completed', 'paid', 'success', 'succeeded']);
            });
            
            // Sort by creation date (newest first)
            usort($paidOrders, function($a, $b) {
                $timeA = $a['createdAt']->get()->getTimestamp();
                $timeB = $b['createdAt']->get()->getTimestamp();
                return $timeB - $timeA;
            });
            
            return array_values($paidOrders);
        } catch (\Exception $e) {
            error_log("Get all orders error: " . $e->getMessage());
            return [];
        }
    }
}
?>