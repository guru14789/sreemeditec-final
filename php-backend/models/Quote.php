<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';

class Quote 
{
    private $firestore;
    private $quotesCollection;

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
        $this->quotesCollection = $this->firestore->collection('quotes');
    }
    
    public function createQuote(array $quoteData): array
    {
        $requiredFields = ['name', 'email', 'phone', 'equipmentType', 'requirements'];
        $errors = \validateRequiredFields($quoteData, $requiredFields);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $quoteId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            
            $quote = [
                'name' => \sanitizeInput($quoteData['name']),
                'email' => strtolower(trim($quoteData['email'])),
                'phone' => \sanitizeInput($quoteData['phone']),
                'company' => \sanitizeInput($quoteData['company'] ?? ''),
                'equipmentType' => \sanitizeInput($quoteData['equipmentType']),
                'quantity' => \sanitizeInput($quoteData['quantity'] ?? ''),
                'budget' => \sanitizeInput($quoteData['budget'] ?? ''),
                'timeline' => \sanitizeInput($quoteData['timeline'] ?? ''),
                'requirements' => \sanitizeInput($quoteData['requirements']),
                'additionalInfo' => \sanitizeInput($quoteData['additionalInfo'] ?? ''),
                'userId' => $quoteData['userId'] ?? null,
                'status' => 'pending',
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
            ];

            $this->quotesCollection->document($quoteId)->set($quote);

            return [
                'success' => true,
                'quote_id' => $quoteId,
                'message' => 'Quote request submitted successfully'
            ];
        } catch (\Exception $e) {
            error_log("Quote creation error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to submit quote request']];
        }
    }
    
    public function getQuoteById(string $quoteId): ?array
    {
        try {
            $quoteDoc = $this->quotesCollection->document($quoteId)->snapshot();
            if ($quoteDoc->exists()) {
                $data = $quoteDoc->data();
                $data['id'] = $quoteId;
                return $data;
            }
            return null;
        } catch (\Exception $e) {
            error_log("Get quote error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getUserQuotes(string $userId): array
    {
        try {
            $documents = $this->quotesCollection
                ->where('userId', '=', $userId)
                ->orderBy('createdAt', 'DESC')
                ->documents();
            
            $quotes = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id();
                    $quotes[] = $data;
                }
            }
            
            return $quotes;
        } catch (\Exception $e) {
            error_log("Get user quotes error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllQuotes(): array
    {
        try {
            $documents = $this->quotesCollection
                ->orderBy('createdAt', 'DESC')
                ->documents();
            
            $quotes = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id();
                    $quotes[] = $data;
                }
            }
            
            return $quotes;
        } catch (\Exception $e) {
            error_log("Get all quotes error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateQuoteStatus(string $quoteId, string $status): array
    {
        try {
            $this->quotesCollection->document($quoteId)->update([
                ['path' => 'status', 'value' => $status],
                ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
            ]);
            
            return ['success' => true, 'message' => 'Quote status updated'];
        } catch (\Exception $e) {
            error_log("Update quote status error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update quote status']];
        }
    }
    
    public function deleteQuote(string $quoteId): array
    {
        try {
            $this->quotesCollection->document($quoteId)->delete();
            return ['success' => true, 'message' => 'Quote deleted successfully'];
        } catch (\Exception $e) {
            error_log("Delete quote error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to delete quote']];
        }
    }
}
