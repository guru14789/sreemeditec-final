<?php
namespace Models;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/firebase.php';

class Shipment 
{
    private $firestore;
    private $shipmentsCollection;
    private $dtdcBaseUrl;
    private $dtdcApiKey;
    private $dtdcUsername;
    private $dtdcPassword;

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
        $this->shipmentsCollection = $this->firestore->collection('shipments');
        
        $this->dtdcBaseUrl = getenv('DTDC_BASE_URL');
        $this->dtdcApiKey = getenv('DTDC_API_KEY');
        $this->dtdcUsername = getenv('DTDC_USERNAME');
        $this->dtdcPassword = getenv('DTDC_PASSWORD');
    }
    
    private function makeDTDCRequest(string $endpoint, array $data, string $method = 'POST'): array
    {
        if (!$this->dtdcBaseUrl || !$this->dtdcApiKey) {
            return [
                'success' => false,
                'errors' => ['DTDC credentials not configured']
            ];
        }
        
        $url = rtrim($this->dtdcBaseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->dtdcApiKey,
        ];
        
        if ($this->dtdcUsername && $this->dtdcPassword) {
            $headers[] = 'X-Username: ' . $this->dtdcUsername;
            $headers[] = 'X-Password: ' . $this->dtdcPassword;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            return [
                'success' => false,
                'errors' => ['DTDC API request failed']
            ];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData
            ];
        }
        
        return [
            'success' => false,
            'errors' => [$responseData['message'] ?? 'DTDC API error'],
            'data' => $responseData
        ];
    }
    
    public function createShipment(string $orderId, string $userId, array $shipmentData): array
    {
        $requiredFields = ['consignee_name', 'consignee_address', 'consignee_phone', 'consignee_pincode', 'product_type', 'weight'];
        $errors = \validateRequiredFields($shipmentData, $requiredFields);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $dtdcPayload = [
                'consignee_name' => $shipmentData['consignee_name'],
                'consignee_address' => $shipmentData['consignee_address'],
                'consignee_phone' => $shipmentData['consignee_phone'],
                'consignee_pincode' => $shipmentData['consignee_pincode'],
                'consignee_city' => $shipmentData['consignee_city'] ?? '',
                'consignee_state' => $shipmentData['consignee_state'] ?? '',
                'product_type' => $shipmentData['product_type'],
                'weight' => (float)$shipmentData['weight'],
                'pieces' => (int)($shipmentData['pieces'] ?? 1),
                'declared_value' => (float)($shipmentData['declared_value'] ?? 0),
                'reference_number' => $orderId,
                'service_type' => $shipmentData['service_type'] ?? 'Express'
            ];
            
            $dtdcResponse = $this->makeDTDCRequest('/shipments/create', $dtdcPayload);
            
            if (!$dtdcResponse['success']) {
                return $dtdcResponse;
            }
            
            $awbNumber = $dtdcResponse['data']['awb_number'] ?? $dtdcResponse['data']['awbNumber'] ?? null;
            $labelData = $dtdcResponse['data']['label'] ?? $dtdcResponse['data']['labelData'] ?? null;
            
            if (!$awbNumber) {
                return [
                    'success' => false,
                    'errors' => ['AWB number not received from DTDC']
                ];
            }
            
            $shipmentDoc = $this->shipmentsCollection->newDocument();
            $shipmentId = $shipmentDoc->id();
            
            $shipmentDoc->set([
                'shipmentId' => $shipmentId,
                'orderId' => $orderId,
                'userId' => $userId,
                'awbNumber' => $awbNumber,
                'labelData' => $labelData,
                'status' => 'created',
                'consigneeName' => $shipmentData['consignee_name'],
                'consigneeAddress' => $shipmentData['consignee_address'],
                'consigneePhone' => $shipmentData['consignee_phone'],
                'consigneePincode' => $shipmentData['consignee_pincode'],
                'trackingUrl' => "https://www.dtdc.in/tracking.asp?strCnno={$awbNumber}",
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
            ]);
            
            return [
                'success' => true,
                'shipment_id' => $shipmentId,
                'awb_number' => $awbNumber,
                'label_data' => $labelData,
                'tracking_url' => "https://www.dtdc.in/tracking.asp?strCnno={$awbNumber}",
                'message' => 'Shipment created successfully'
            ];
        } catch (\Exception $e) {
            error_log("Create shipment error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Shipment creation failed: ' . $e->getMessage()]
            ];
        }
    }
    
    public function trackShipment(string $awbNumber): array
    {
        try {
            $dtdcResponse = $this->makeDTDCRequest("/shipments/track/{$awbNumber}", [], 'GET');
            
            if (!$dtdcResponse['success']) {
                return $dtdcResponse;
            }
            
            $trackingData = $dtdcResponse['data'];
            
            $query = $this->shipmentsCollection->where('awbNumber', '=', $awbNumber);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $status = $trackingData['status'] ?? $trackingData['shipment_status'] ?? 'in_transit';
                    
                    $this->shipmentsCollection->document($document->id())->update([
                        ['path' => 'status', 'value' => $status],
                        ['path' => 'trackingHistory', 'value' => $trackingData['tracking_history'] ?? []],
                        ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
                    ]);
                }
            }
            
            return [
                'success' => true,
                'tracking_data' => $trackingData
            ];
        } catch (\Exception $e) {
            error_log("Track shipment error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Shipment tracking failed']
            ];
        }
    }
    
    public function getShipmentByOrderId(string $orderId): ?array
    {
        try {
            $query = $this->shipmentsCollection->where('orderId', '=', $orderId);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    return $document->data();
                }
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Get shipment error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getShipmentByAwb(string $awbNumber): ?array
    {
        try {
            $query = $this->shipmentsCollection->where('awbNumber', '=', $awbNumber);
            $documents = $query->documents();
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    return $document->data();
                }
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Get shipment error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getUserShipments(string $userId): array
    {
        try {
            $query = $this->shipmentsCollection->where('userId', '=', $userId);
            $shipments = [];
            foreach ($query->documents() as $document) {
                if ($document->exists()) {
                    $shipments[] = $document->data();
                }
            }
            return $shipments;
        } catch (\Exception $e) {
            error_log("Get user shipments error: " . $e->getMessage());
            return [];
        }
    }
}
