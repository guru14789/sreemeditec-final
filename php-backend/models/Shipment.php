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
        
        // Shipsy API Configuration
        $this->dtdcBaseUrl = getenv('DTDC_BASE_URL') ?: 'https://app.shipsy.in';
        $this->dtdcApiKey = getenv('DTDC_API_KEY');
        $this->dtdcUsername = getenv('DTDC_CUSTOMER_CODE'); // Customer code for Shipsy
        $this->dtdcPassword = getenv('DTDC_PASSWORD');
    }
    
    private function makeDTDCRequest(string $endpoint, array $data = [], string $method = 'POST', array $queryParams = []): array
    {
        if (!$this->dtdcBaseUrl || !$this->dtdcApiKey) {
            return [
                'success' => false,
                'errors' => ['DTDC/Shipsy credentials not configured']
            ];
        }
        
        $url = rtrim($this->dtdcBaseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        // Add query parameters for GET requests
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        // Shipsy uses api-key header for authentication
        $headers = [
            'Content-Type: application/json',
            'api-key: ' . $this->dtdcApiKey,
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            return [
                'success' => false,
                'errors' => ['Shipsy API request failed']
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
            'errors' => [$responseData['message'] ?? 'Shipsy API error'],
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
            $customerCode = $this->dtdcUsername ?: 'CUSTOMER';
            
            // Shipsy Softdata Upload API v2 Payload
            $dtdcPayload = [
                'action_type' => 'single_pickup',
                'consignment_type' => 'forward',
                'movement_type' => 'forward',
                'load_type' => $shipmentData['product_type'] === 'document' ? 'DOCUMENT' : 'NON-DOCUMENT',
                'description' => $shipmentData['description'] ?? 'Medical Equipment',
                'customer_code' => $customerCode,
                'reference_number' => $orderId,
                'service_type_id' => $shipmentData['service_type'] ?? 'PREMIUM',
                'dimension_unit' => 'cm',
                'length' => (string)($shipmentData['length'] ?? '10'),
                'width' => (string)($shipmentData['width'] ?? '10'),
                'height' => (string)($shipmentData['height'] ?? '10'),
                'weight_unit' => 'kg',
                'weight' => (string)((float)$shipmentData['weight']),
                'declared_value' => (float)($shipmentData['declared_value'] ?? 0),
                'invoice_amount' => (float)($shipmentData['invoice_amount'] ?? $shipmentData['declared_value'] ?? 0),
                'invoice_number' => $shipmentData['invoice_number'] ?? $orderId,
                'invoice_date' => $shipmentData['invoice_date'] ?? date('Y-m-d'),
                'num_pieces' => (int)($shipmentData['pieces'] ?? 1),
                'customer_reference_number' => $orderId,
                'is_cod' => isset($shipmentData['cod_amount']) && $shipmentData['cod_amount'] > 0,
                'cod_amount' => (string)($shipmentData['cod_amount'] ?? '0'),
                'destination_details' => [
                    'name' => $shipmentData['consignee_name'],
                    'phone' => $shipmentData['consignee_phone'],
                    'alternate_phone' => $shipmentData['consignee_alternate_phone'] ?? '',
                    'address_line_1' => $shipmentData['consignee_address'],
                    'address_line_2' => $shipmentData['consignee_address_line_2'] ?? '',
                    'pincode' => $shipmentData['consignee_pincode'],
                    'city' => $shipmentData['consignee_city'] ?? '',
                    'state' => $shipmentData['consignee_state'] ?? '',
                    'country' => 'India'
                ]
            ];
            
            // Add origin details if provided
            if (isset($shipmentData['origin_name'])) {
                $dtdcPayload['origin_details'] = [
                    'name' => $shipmentData['origin_name'],
                    'phone' => $shipmentData['origin_phone'] ?? '',
                    'address_line_1' => $shipmentData['origin_address'] ?? '',
                    'pincode' => $shipmentData['origin_pincode'] ?? '',
                    'city' => $shipmentData['origin_city'] ?? '',
                    'state' => $shipmentData['origin_state'] ?? '',
                    'country' => 'India'
                ];
            }
            
            $dtdcResponse = $this->makeDTDCRequest('/api/customer/integration/consignment/upload/softdata/v2', $dtdcPayload, 'POST');
            
            if (!$dtdcResponse['success']) {
                return $dtdcResponse;
            }
            
            $responseData = $dtdcResponse['data'];
            $referenceNumber = $responseData['reference_number'] ?? $orderId;
            $courierPartnerRefNumber = $responseData['courier_partner_reference_number'] ?? '';
            
            $shipmentId = uniqid('shipment_', true);
            
            $this->shipmentsCollection->document($shipmentId)->set([
                'shipmentId' => $shipmentId,
                'orderId' => $orderId,
                'userId' => $userId,
                'referenceNumber' => $referenceNumber,
                'courierPartnerReferenceNumber' => $courierPartnerRefNumber,
                'courierPartner' => $responseData['courier_partner'] ?? 'DTDC',
                'courierAccount' => $responseData['courier_account'] ?? '',
                'status' => 'pickup_awaited',
                'consigneeName' => $shipmentData['consignee_name'],
                'consigneeAddress' => $shipmentData['consignee_address'],
                'consigneePhone' => $shipmentData['consignee_phone'],
                'consigneePincode' => $shipmentData['consignee_pincode'],
                'trackingUrl' => "https://www.dtdc.in/tracking.asp?strCnno={$referenceNumber}",
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTime())
            ]);
            
            return [
                'success' => true,
                'shipment_id' => $shipmentId,
                'reference_number' => $referenceNumber,
                'courier_partner_reference_number' => $courierPartnerRefNumber,
                'courier_partner' => $responseData['courier_partner'] ?? 'DTDC',
                'tracking_url' => "https://www.dtdc.in/tracking.asp?strCnno={$referenceNumber}",
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
    
    public function trackShipment(string $referenceNumber): array
    {
        try {
            // Shipsy tracking API: GET /api/customer/integration/consignment/track?reference_number={reference_number}
            $dtdcResponse = $this->makeDTDCRequest(
                '/api/customer/integration/consignment/track', 
                [], 
                'GET',
                ['reference_number' => $referenceNumber]
            );
            
            if (!$dtdcResponse['success']) {
                return $dtdcResponse;
            }
            
            $trackingData = $dtdcResponse['data'];
            
            // Update shipment status in Firestore - query by referenceNumber OR orderId
            $query = $this->shipmentsCollection->where('referenceNumber', '=', $referenceNumber);
            $documents = $query->documents();
            
            // If not found by referenceNumber, try orderId (for backward compatibility)
            $documents = $documents->isEmpty() ? 
                $this->shipmentsCollection->where('orderId', '=', $referenceNumber)->documents() : 
                $documents;
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $status = $trackingData['status'] ?? 'in_transit';
                    $events = $trackingData['events'] ?? [];
                    
                    $this->shipmentsCollection->document($document->id())->update([
                        ['path' => 'status', 'value' => $status],
                        ['path' => 'trackingEvents', 'value' => $events],
                        ['path' => 'attemptCount', 'value' => $trackingData['attempt_count'] ?? 0],
                        ['path' => 'hubCode', 'value' => $trackingData['hub_code'] ?? ''],
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
                'errors' => ['Shipment tracking failed: ' . $e->getMessage()]
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
    
    public function generateLabel(string $referenceNumber): array
    {
        try {
            // Shipsy Label Generation API: GET /api/customer/integration/consignment/shippinglabel/stream?reference_number={reference_number}
            $dtdcResponse = $this->makeDTDCRequest(
                '/api/customer/integration/consignment/shippinglabel/stream', 
                [], 
                'GET',
                ['reference_number' => $referenceNumber]
            );
            
            if (!$dtdcResponse['success']) {
                return $dtdcResponse;
            }
            
            return [
                'success' => true,
                'label_url' => $this->dtdcBaseUrl . '/api/customer/integration/consignment/shippinglabel/stream?reference_number=' . urlencode($referenceNumber),
                'message' => 'Label generated successfully'
            ];
        } catch (\Exception $e) {
            error_log("Generate label error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Label generation failed: ' . $e->getMessage()]
            ];
        }
    }
    
    public function cancelShipment(string $referenceNumber): array
    {
        try {
            $customerCode = $this->dtdcUsername ?: 'CUSTOMER';
            
            // Shipsy Cancel Order API
            $payload = [
                'AWBNo' => [$referenceNumber],
                'customerCode' => $customerCode
            ];
            
            $dtdcResponse = $this->makeDTDCRequest(
                '/api/customer/integration/consignment/cancel', 
                $payload, 
                'POST'
            );
            
            if (!$dtdcResponse['success']) {
                return $dtdcResponse;
            }
            
            // Update shipment status in Firestore - query by referenceNumber OR orderId
            $query = $this->shipmentsCollection->where('referenceNumber', '=', $referenceNumber);
            $documents = $query->documents();
            
            // If not found by referenceNumber, try orderId (for backward compatibility)
            $documents = $documents->isEmpty() ? 
                $this->shipmentsCollection->where('orderId', '=', $referenceNumber)->documents() : 
                $documents;
            
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $this->shipmentsCollection->document($document->id())->update([
                        ['path' => 'status', 'value' => 'cancelled'],
                        ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
                    ]);
                }
            }
            
            return [
                'success' => true,
                'message' => 'Shipment cancelled successfully'
            ];
        } catch (\Exception $e) {
            error_log("Cancel shipment error: " . $e->getMessage());
            return [
                'success' => false, 
                'errors' => ['Shipment cancellation failed: ' . $e->getMessage()]
            ];
        }
    }
}
