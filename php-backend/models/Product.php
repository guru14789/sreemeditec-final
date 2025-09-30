<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';

class Product
{
    private $firestore;
    private $collectionName = 'products';

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
    }

    public function getAllProducts(array $filters = []): array
    {
        $productsCollection = $this->firestore->collection($this->collectionName);
        $query = $productsCollection->orderBy('name');

        if (!empty($filters['category'])) {
            $query = $query->where('category', '==', $filters['category']);
        }

        $documents = $query->documents();
        $products = [];
        foreach ($documents as $document) {
            if ($document->exists()) {
                $productData = $document->data();
                $productData['id'] = $document->id();
                $products[] = $productData;
            }
        }

        return $products;
    }

    public function getProductById(string $productId): ?array
    {
        $document = $this->firestore->collection($this->collectionName)->document($productId)->snapshot();

        if ($document->exists()) {
            $productData = $document->data();
            $productData['id'] = $document->id();
            return $productData;
        }

        return null;
    }

    public function createProduct(array $productData): array
    {
        $requiredFields = ['name', 'description', 'category', 'price'];
        $errors = \validateRequiredFields($productData, $requiredFields);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $newProduct = $this->firestore->collection($this->collectionName)->newDocument([
            'name' => \sanitizeInput($productData['name']),
            'description' => \sanitizeInput($productData['description']),
            'category' => \sanitizeInput($productData['category']),
            'price' => (float)$productData['price'],
            'image' => $productData['image'] ?? null,
            'stock' => (int)($productData['stock'] ?? 0),
        ]);

        $newProduct->create();

        return ['success' => true, 'product_id' => $newProduct->id()];
    }

    public function updateProduct(string $productId, array $updateData): array
    {
        $this->firestore->collection($this->collectionName)->document($productId)->set($updateData, ['merge' => true]);
        return ['success' => true, 'message' => 'Product updated successfully'];
    }

    public function deleteProduct(string $productId): array
    {
        $this->firestore->collection($this->collectionName)->document($productId)->delete();
        return ['success' => true, 'message' => 'Product deleted successfully'];
    }
}
?>