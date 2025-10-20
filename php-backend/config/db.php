<?php

/**
 * Database Connection Class
 * Handles Firebase Firestore connection for the application
 */

require_once __DIR__ . '/firebase.php';

class DatabaseConnection {
    private static $firestore = null;
    private static $auth = null;

    /**
     * Get Firestore database instance
     */
    public static function getFirestore() {
        if (self::$firestore === null) {
            self::$firestore = get_firebase_firestore();
        }
        return self::$firestore;
    }

    /**
     * Get Firebase Auth instance
     */
    public static function getAuth() {
        if (self::$auth === null) {
            self::$auth = get_firebase_auth();
        }
        return self::$auth;
    }

    /**
     * Test database connection
     * @return bool
     */
    public static function testConnection() {
        try {
            $firestore = self::getFirestore();
            if ($firestore) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Database connection test failed: " . $e->getMessage());
            return false;
        }
    }
}

?>
