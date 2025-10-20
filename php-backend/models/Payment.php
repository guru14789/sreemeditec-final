<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

class Payment 
{
    private $firestore;
    private $paymentsCollection;
    private $razorpay;
    private $keyId;
    private $keySecret;

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
        $this->paymentsCollection = $this->firestore->collection('payments');
        
        $this->keyId = getenv('RAZORPAY_KEY_ID');
        $this->keySecret = getenv('RAZORPAY_KEY_SECRET');
        
        if ($this->keyId && $this->keySecret) {
            $this->razorpay = new Api($this->keyId, $this->keySecret);
        }
    }
    
    public function createRazorpayOrder(string $userId, float $amount, string $orderId, array $metadata = []): array
    {
        if (!$this->razorpay) {
            return [
                'success' => false, 
                'errors' => ['Razorpay credentials not configured']
            ];
        }
        
        try {
            $orderData = [
                'receipt' => $orderId,
                'amount' => $amount * 100, // Amount in paise
                'currency' => 'INR',
                'notes' => array_merge([
                    'user_id' => $userId,
                    'order_id' => $orderId
                ], $metadata)
            ];
            
            $razorpayOrder = $this->razorpay->order->create($orderData);
            
            $paymentId = uniqid('payment_', true);
            
            $this->paymentsCollection->document($paymentId)->set([
                'paymentId' => $paymentId,
                'userId' => $userId,
                'orderId' => $orderId,
                'razorpayOrderId' => $razorpayOrder['id'],
                'amount' => $amount,
                'currency' => 'INR',
                'status' => 'created',
                'paymentMethod' => 'razorpay',
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
            ]);

            return [
                'success' => true,
                'razorpay_order_id' => $razorpayOrder['id'],
                'razorpay_key_id' => $this->keyId,
                'amount' => $amount,
                'currency' => 'INR',
                'payment_id' => $paymentId
            ];
        } catch (\Exception $e) {
            error_log("Razorpay order creation error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Failed to create Razorpay order: ' . $e->getMessage()]
            ];
        }
    }
    
    public function verifyPayment(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): array
    {
        if (!$this->razorpay) {
            return [
                'success' => false, 
                'errors' => ['Razorpay credentials not configured']
            ];
        }
        
        try {
            $attributes = [
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature
            ];
            
            $this->razorpay->utility->verifyPaymentSignature($attributes);
            
            $query = $this->paymentsCollection->where('razorpayOrderId', '=', $razorpayOrderId);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $paymentData = $document->data();
                    
                    $this->paymentsCollection->document($document->id())->update([
                        ['path' => 'razorpayPaymentId', 'value' => $razorpayPaymentId],
                        ['path' => 'razorpaySignature', 'value' => $razorpaySignature],
                        ['path' => 'status', 'value' => 'success'],
                        ['path' => 'verifiedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())],
                        ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'order_id' => $paymentData['orderId'],
                        'payment_id' => $paymentData['paymentId']
                    ];
                }
            }
            
            return [
                'success' => false, 
                'errors' => ['Payment record not found']
            ];
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            error_log("Payment verification error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Invalid payment signature']
            ];
        } catch (\Exception $e) {
            error_log("Payment verification error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Payment verification failed']
            ];
        }
    }
    
    public function getPaymentByOrderId(string $orderId): ?array
    {
        try {
            $query = $this->paymentsCollection->where('orderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    return $document->data();
                }
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Get payment error: " . $e->getMessage());
            return null;
        }
    }
    
    public function capturePayment(string $paymentId, float $amount): array
    {
        if (!$this->razorpay) {
            return [
                'success' => false, 
                'errors' => ['Razorpay credentials not configured']
            ];
        }
        
        try {
            $payment = $this->razorpay->payment->fetch($paymentId);
            $payment->capture(['amount' => $amount * 100]);
            
            return [
                'success' => true,
                'message' => 'Payment captured successfully'
            ];
        } catch (\Exception $e) {
            error_log("Payment capture error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Payment capture failed']
            ];
        }
    }
}
