<?php

class MongoDBAPIConnection 
{
    private static string $apiKey;
    private static string $baseUrl;
    private static string $clusterName;
    private static string $databaseName;
    
    public static function initialize(): void
    {
        // MongoDB Atlas API configuration
        self::$clusterName = 'sreemeditec';
        self::$databaseName = 'sreemeditec_db';
        self::$baseUrl = 'https://data.mongodb-api.com/app/data-fwmyz/endpoint/data/v1';
        
        // You would need to set up MongoDB Atlas App Services for this
        self::$apiKey = $_ENV['MONGODB_API_KEY'] ?? '';
    }
    
    public static function testConnection(): bool
    {
        try {
            self::initialize();
            
            if (empty(self::$apiKey)) {
                return false;
            }
            
            $response = self::makeRequest('action/findOne', [
                'collection' => 'users',
                'database' => self::$databaseName,
                'dataSource' => self::$clusterName,
                'filter' => ['_id' => ['$exists' => true]]
            ]);
            
            return isset($response['document']) || isset($response['documents']);
        } catch (Exception $e) {
            error_log("MongoDB API connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function insertDocument(string $collection, array $document): array
    {
        return self::makeRequest('action/insertOne', [
            'collection' => $collection,
            'database' => self::$databaseName,
            'dataSource' => self::$clusterName,
            'document' => $document
        ]);
    }
    
    public static function findDocuments(string $collection, array $filter = [], array $options = []): array
    {
        return self::makeRequest('action/find', [
            'collection' => $collection,
            'database' => self::$databaseName,
            'dataSource' => self::$clusterName,
            'filter' => $filter,
            ...$options
        ]);
    }
    
    public static function updateDocument(string $collection, array $filter, array $update): array
    {
        return self::makeRequest('action/updateOne', [
            'collection' => $collection,
            'database' => self::$databaseName,
            'dataSource' => self::$clusterName,
            'filter' => $filter,
            'update' => $update
        ]);
    }
    
    public static function deleteDocument(string $collection, array $filter): array
    {
        return self::makeRequest('action/deleteOne', [
            'collection' => $collection,
            'database' => self::$databaseName,
            'dataSource' => self::$clusterName,
            'filter' => $filter
        ]);
    }
    
    private static function makeRequest(string $endpoint, array $data): array
    {
        $url = self::$baseUrl . '/' . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'api-key: ' . self::$apiKey
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('MongoDB API request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("MongoDB API returned HTTP $httpCode: $response");
        }
        
        $decoded = json_decode($response, true);
        if (!$decoded) {
            throw new Exception('Invalid JSON response from MongoDB API');
        }
        
        return $decoded;
    }
}
?>