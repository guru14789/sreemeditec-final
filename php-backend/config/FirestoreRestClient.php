<?php

/**
 * Firestore REST API Client
 * This is a simple wrapper around Firestore REST API since gRPC extension is not available
 */

class FirestoreRestClient {
    private $projectId;
    private $accessToken;
    private $baseUrl;

    public function __construct($serviceAccountPathOrJson) {
        if (is_array($serviceAccountPathOrJson)) {
            $serviceAccount = $serviceAccountPathOrJson;
        } elseif (file_exists($serviceAccountPathOrJson)) {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPathOrJson), true);
        } else {
            $serviceAccount = json_decode($serviceAccountPathOrJson, true);
        }
        
        $this->projectId = $serviceAccount['project_id'];
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
        $this->accessToken = $this->getAccessToken($serviceAccount);
    }

    private function getAccessToken($serviceAccount) {
        try {
            $auth = new \Google\Auth\Credentials\ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/datastore', 'https://www.googleapis.com/auth/cloud-platform'],
                $serviceAccount
            );
            $token = $auth->fetchAuthToken();
            
            if (!isset($token['access_token'])) {
                error_log("Access token not found in response. Token response: " . json_encode($token));
                throw new \Exception("Failed to obtain access_token from Firebase. Got: " . json_encode(array_keys($token)));
            }
            
            return $token['access_token'];
        } catch (\Exception $e) {
            error_log("Failed to get access token: " . $e->getMessage());
            error_log("Service account project_id: " . ($serviceAccount['project_id'] ?? 'NOT SET'));
            throw $e;
        }
    }

    public function collection($collectionName) {
        return new FirestoreCollection($this, $collectionName);
    }

    public function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            error_log("Firestore API error: " . $response);
            return null;
        }

        return json_decode($response, true);
    }

    public function getBaseUrl() {
        return $this->baseUrl;
    }
}

class FirestoreCollection {
    private $client;
    private $collectionName;
    private $wheres = [];
    private $limitCount = null;
    private $orderByField = null;
    private $orderByDirection = 'ASCENDING';

    public function __construct($client, $collectionName) {
        $this->client = $client;
        $this->collectionName = $collectionName;
    }

    public function document($documentId) {
        return new FirestoreDocument($this->client, $this->collectionName, $documentId);
    }

    public function where($field, $operator, $value) {
        $this->wheres[] = ['field' => $field, 'operator' => $operator, 'value' => $value];
        return $this;
    }

    public function limit($count) {
        $this->limitCount = $count;
        return $this;
    }

    public function orderBy($field, $direction = 'ASCENDING') {
        $this->orderByField = $field;
        $this->orderByDirection = strtoupper($direction) === 'DESC' ? 'DESCENDING' : 'ASCENDING';
        return $this;
    }

    public function documents() {
        $queryUrl = $this->client->getBaseUrl() . ':runQuery';
        
        $collectionId = basename($this->collectionName);
        
        $structuredQuery = [
            'from' => [['collectionId' => $collectionId]]
        ];
        
        if (!empty($this->wheres)) {
            $filters = [];
            foreach ($this->wheres as $where) {
                $filters[] = [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => $where['field']],
                        'op' => $this->convertOperator($where['operator']),
                        'value' => $this->convertValueForQuery($where['value'])
                    ]
                ];
            }
            
            if (count($filters) === 1) {
                $structuredQuery['where'] = $filters[0];
            } else {
                $structuredQuery['where'] = [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $filters
                    ]
                ];
            }
        }
        
        if ($this->limitCount) {
            $structuredQuery['limit'] = $this->limitCount;
        }
        
        if ($this->orderByField) {
            $structuredQuery['orderBy'] = [
                [
                    'field' => ['fieldPath' => $this->orderByField],
                    'direction' => $this->orderByDirection
                ]
            ];
        }
        
        $response = $this->client->makeRequest('POST', $queryUrl, ['structuredQuery' => $structuredQuery]);
        
        $this->wheres = [];
        $this->limitCount = null;
        $this->orderByField = null;
        $this->orderByDirection = 'ASCENDING';
        
        $documents = [];
        if ($response && is_array($response)) {
            foreach ($response as $result) {
                if (isset($result['document'])) {
                    $docPath = $result['document']['name'];
                    $docId = basename($docPath);
                    $documents[] = new FirestoreQueryDocument($result['document'], $docId);
                }
            }
        }
        
        return $documents;
    }

    private function convertOperator($operator) {
        $operatorMap = [
            '=' => 'EQUAL',
            '==' => 'EQUAL',
            '!=' => 'NOT_EQUAL',
            '<' => 'LESS_THAN',
            '<=' => 'LESS_THAN_OR_EQUAL',
            '>' => 'GREATER_THAN',
            '>=' => 'GREATER_THAN_OR_EQUAL',
        ];
        
        return $operatorMap[$operator] ?? 'EQUAL';
    }

    private function convertValueForQuery($value) {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string)$value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif ($value === null) {
            return ['nullValue' => null];
        }
        return ['stringValue' => (string)$value];
    }

    public function add($data) {
        $url = $this->client->getBaseUrl() . '/' . $this->collectionName;
        $firestoreData = $this->convertToFirestoreFormat($data);
        
        $response = $this->client->makeRequest('POST', $url, ['fields' => $firestoreData]);
        return $response;
    }

    private function convertToFirestoreFormat($data) {
        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[$key] = $this->convertValue($value);
        }
        return $formatted;
    }

    private function convertValue($value) {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string)$value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_array($value)) {
            if ($this->isAssociativeArray($value)) {
                $mapFields = [];
                foreach ($value as $key => $val) {
                    $mapFields[$key] = $this->convertValue($val);
                }
                return ['mapValue' => ['fields' => $mapFields]];
            } else {
                $arrayValues = [];
                foreach ($value as $item) {
                    $arrayValues[] = $this->convertValue($item);
                }
                return ['arrayValue' => ['values' => $arrayValues]];
            }
        } elseif ($value instanceof \DateTime) {
            return ['timestampValue' => $value->format('Y-m-d\TH:i:s\Z')];
        } elseif ($value === null) {
            return ['nullValue' => null];
        }
        return ['stringValue' => (string)$value];
    }

    private function isAssociativeArray($array) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

class FirestoreDocument {
    private $client;
    private $collectionName;
    private $documentId;

    public function __construct($client, $collectionName, $documentId) {
        $this->client = $client;
        $this->collectionName = $collectionName;
        $this->documentId = $documentId;
    }

    public function set($data, $options = []) {
        $url = $this->client->getBaseUrl() . '/' . $this->collectionName . '/' . $this->documentId;
        $firestoreData = $this->convertToFirestoreFormat($data);
        
        $response = $this->client->makeRequest('PATCH', $url . '?updateMask.fieldPaths=' . implode('&updateMask.fieldPaths=', array_keys($data)), ['fields' => $firestoreData]);
        return $response;
    }

    public function update($data) {
        if (isset($data[0]) && is_array($data[0]) && isset($data[0]['path'])) {
            $updateData = [];
            $paths = [];
            foreach ($data as $update) {
                $path = $update['path'];
                $paths[] = $path;
                $this->setNestedValue($updateData, $path, $update['value']);
            }
            
            $url = $this->client->getBaseUrl() . '/' . $this->collectionName . '/' . $this->documentId;
            $firestoreData = $this->convertToFirestoreFormat($updateData);
            $updateMask = implode('&updateMask.fieldPaths=', $paths);
            
            $response = $this->client->makeRequest('PATCH', $url . '?updateMask.fieldPaths=' . $updateMask, ['fields' => $firestoreData]);
            return $response;
        }
        return $this->set($data);
    }

    private function setNestedValue(&$array, $path, $value) {
        $keys = explode('.', $path);
        $current = &$array;
        
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        
        $current = $value;
    }

    public function snapshot() {
        $url = $this->client->getBaseUrl() . '/' . $this->collectionName . '/' . $this->documentId;
        $response = $this->client->makeRequest('GET', $url);
        
        return new FirestoreSnapshot($response);
    }

    public function delete() {
        $url = $this->client->getBaseUrl() . '/' . $this->collectionName . '/' . $this->documentId;
        return $this->client->makeRequest('DELETE', $url);
    }

    private function convertToFirestoreFormat($data) {
        $formatted = [];
        foreach ($data as $key => $value) {
            $formatted[$key] = $this->convertValue($value);
        }
        return $formatted;
    }

    private function convertValue($value) {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['integerValue' => (string)$value];
        } elseif (is_bool($value)) {
            return ['booleanValue' => $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_array($value)) {
            if ($this->isAssociativeArray($value)) {
                $mapFields = [];
                foreach ($value as $key => $val) {
                    $mapFields[$key] = $this->convertValue($val);
                }
                return ['mapValue' => ['fields' => $mapFields]];
            } else {
                $arrayValues = [];
                foreach ($value as $item) {
                    $arrayValues[] = $this->convertValue($item);
                }
                return ['arrayValue' => ['values' => $arrayValues]];
            }
        } elseif ($value instanceof \DateTime) {
            return ['timestampValue' => $value->format('Y-m-d\TH:i:s\Z')];
        } elseif ($value === null) {
            return ['nullValue' => null];
        } elseif ($value instanceof \Google\Cloud\Core\Timestamp) {
            $dt = $value->get();
            return ['timestampValue' => $dt->format('Y-m-d\TH:i:s\Z')];
        }
        return ['stringValue' => (string)$value];
    }

    private function isAssociativeArray($array) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

class FirestoreSnapshot {
    private $data;
    private $exists;

    public function __construct($response) {
        $this->exists = $response !== null && isset($response['fields']);
        $this->data = $this->exists ? $this->parseFields($response['fields']) : [];
    }

    public function exists() {
        return $this->exists;
    }

    public function data() {
        return $this->data;
    }

    private function parseFields($fields) {
        $data = [];
        foreach ($fields as $key => $value) {
            $data[$key] = $this->parseValue($value);
        }
        return $data;
    }

    private function parseValue($value) {
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            return (int)$value['integerValue'];
        } elseif (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        } elseif (isset($value['doubleValue'])) {
            return $value['doubleValue'];
        } elseif (isset($value['arrayValue'])) {
            $array = [];
            foreach ($value['arrayValue']['values'] ?? [] as $item) {
                $array[] = $this->parseValue($item);
            }
            return $array;
        } elseif (isset($value['timestampValue'])) {
            return new \DateTime($value['timestampValue']);
        } elseif (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['mapValue'])) {
            return $this->parseFields($value['mapValue']['fields'] ?? []);
        }
        return null;
    }
}

class FirestoreQueryDocument {
    private $data;
    private $id;
    private $exists;

    public function __construct($document, $id) {
        $this->id = $id;
        $this->exists = $document !== null && isset($document['fields']);
        $this->data = $this->exists ? $this->parseFields($document['fields']) : [];
    }

    public function exists() {
        return $this->exists;
    }

    public function data() {
        return $this->data;
    }

    public function id() {
        return $this->id;
    }

    private function parseFields($fields) {
        $data = [];
        foreach ($fields as $key => $value) {
            $data[$key] = $this->parseValue($value);
        }
        return $data;
    }

    private function parseValue($value) {
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            return (int)$value['integerValue'];
        } elseif (isset($value['booleanValue'])) {
            return $value['booleanValue'];
        } elseif (isset($value['doubleValue'])) {
            return $value['doubleValue'];
        } elseif (isset($value['arrayValue'])) {
            $array = [];
            foreach ($value['arrayValue']['values'] ?? [] as $item) {
                $array[] = $this->parseValue($item);
            }
            return $array;
        } elseif (isset($value['timestampValue'])) {
            return new \DateTime($value['timestampValue']);
        } elseif (isset($value['nullValue'])) {
            return null;
        } elseif (isset($value['mapValue'])) {
            return $this->parseFields($value['mapValue']['fields'] ?? []);
        }
        return null;
    }
}

?>
