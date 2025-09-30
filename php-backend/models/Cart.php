<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/Product.php';

class Cart 
{
    private $firestore;
    private $cartsCollection;

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
        $this->cartsCollection = $this->firestore->collection('carts');
    }
    
    public function addToCart(string $userId, string $productId, int $quantity = 1): array
    {
        try {
            $cartRef = $this->cartsCollection->document($userId);
            $cartSnapshot = $cartRef->snapshot();

            if (!$cartSnapshot->exists()) {
                // Create new cart
                $cartRef->set([
                    'userId' => $userId,
                    'items' => [
                        $productId => [
                            'productId' => $productId,
                            'quantity' => $quantity,
                            'addedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
                        ]
                    ],
                    'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                    'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
                ]);
            } else {
                // Update existing cart
                $items = $cartSnapshot->get('items');
                if (isset($items[$productId])) {
                    $items[$productId]['quantity'] += $quantity;
                } else {
                    $items[$productId] = [
                        'productId' => $productId,
                        'quantity' => $quantity,
                        'addedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
                    ];
                }
                $cartRef->update([
                    ['path' => 'items', 'value' => $items],
                    ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
                ]);
            }
            
            return ['success' => true, 'message' => 'Product added to cart successfully'];
        } catch (\Exception $e) {
            error_log("Add to cart error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to add product to cart']];
        }
    }
    
    public function getCart(string $userId): array
    {
        try {
            $cartSnapshot = $this->cartsCollection->document($userId)->snapshot();

            if (!$cartSnapshot->exists()) {
                return ['success' => true, 'items' => [], 'total_items' => 0, 'total_amount' => 0];
            }

            $productModel = new Product();
            $cartData = $cartSnapshot->data();
            $cartItems = [];
            $totalAmount = 0;
            $totalItems = 0;

            foreach ($cartData['items'] as $item) {
                $product = $productModel->getProductById($item['productId']);
                if ($product) {
                    $subtotal = $product['price'] * $item['quantity'];
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal
                    ];
                    $totalAmount += $subtotal;
                    $totalItems += $item['quantity'];
                }
            }
            
            return [
                'success' => true,
                'items' => $cartItems,
                'total_items' => $totalItems,
                'total_amount' => $totalAmount
            ];
        } catch (\Exception $e) {
            error_log("Get cart error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to retrieve cart']];
        }
    }
    
    public function updateCartItem(string $userId, string $productId, int $quantity): array
    {
        try {
            $cartRef = $this->cartsCollection->document($userId);
            $cartSnapshot = $cartRef->snapshot();

            if (!$cartSnapshot->exists()) {
                return ['success' => false, 'errors' => ['Cart not found']];
            }

            $items = $cartSnapshot->get('items');

            if (!isset($items[$productId])) {
                return ['success' => false, 'errors' => ['Product not found in cart']];
            }

            if ($quantity <= 0) {
                unset($items[$productId]);
            } else {
                $items[$productId]['quantity'] = $quantity;
            }

            $cartRef->update([
                ['path' => 'items', 'value' => $items],
                ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
            ]);

            return ['success' => true, 'message' => 'Cart updated successfully'];
        } catch (\Exception $e) {
            error_log("Update cart error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update cart']];
        }
    }
    
    public function clearCart(string $userId): array
    {
        try {
            $this->cartsCollection->document($userId)->delete();
            return ['success' => true, 'message' => 'Cart cleared successfully'];
        } catch (\Exception $e) {
            error_log("Clear cart error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to clear cart']];
        }
    }
}
?>