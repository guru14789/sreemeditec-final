<?php

/**
 * Health Checker Class
 * Validates system configuration and requirements
 */

class HealthChecker {
    
    /**
     * Check if running in production mode
     * @return bool
     */
    public static function isProduction() {
        return defined('IS_PRODUCTION') && IS_PRODUCTION === true;
    }

    /**
     * Fail if production is misconfigured
     * This ensures critical services are available in production
     */
    public static function failIfProductionMisconfigured() {
        $errors = [];

        if (!extension_loaded('curl')) {
            $errors[] = 'CURL extension is required in production';
        }

        if (!extension_loaded('json')) {
            $errors[] = 'JSON extension is required in production';
        }

        if (!file_exists(__DIR__ . '/sreemeditec-final-firebase-adminsdk-fbsvc-6184119249.json')) {
            $errors[] = 'Firebase service account file is missing';
        }

        if (!empty($errors)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'errors' => $errors,
                'message' => 'Production environment is misconfigured'
            ]);
            exit;
        }
    }

    /**
     * Get system health status
     * @return array
     */
    public static function getHealthStatus() {
        return [
            'status' => 'healthy',
            'php_version' => PHP_VERSION,
            'extensions' => [
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
                'firebase' => class_exists('Kreait\Firebase\Factory')
            ],
            'firebase_config' => file_exists(__DIR__ . '/sreemeditec-final-firebase-adminsdk-fbsvc-6184119249.json'),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

?>
