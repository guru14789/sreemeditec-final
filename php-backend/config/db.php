<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Database;

class DatabaseConnection 
{
    private static ?Client $client = null;
    private static ?Database $database = null;
    private static ?PDO $pdo = null;
    private static string $dbType = '';
    
    public static function getClient(): Client
    {
        if (self::$client === null) {
            $uri = $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017';
            
            self::$client = new Client($uri, [
                'maxPoolSize' => 10,
                'connectTimeoutMS' => 3000,
                'socketTimeoutMS' => 30000,
                'retryWrites' => true,
                'w' => 'majority'
            ]);
        }
        
        return self::$client;
    }
    
    public static function getDatabase(): Database
    {
        if (self::$database === null) {
            $client = self::getClient();
            $dbName = $_ENV['MONGODB_DATABASE'] ?? 'sree_meditec_db';
            self::$database = $client->selectDatabase($dbName);
        }
        
        return self::$database;
    }
    
    public static function getPDO(): ?PDO
    {
        if (self::$pdo === null && (isset($_ENV['DATABASE_URL']) || isset($_ENV['PGHOST']))) {
            try {
                // Try DATABASE_URL first, then fallback to individual components
                if (isset($_ENV['DATABASE_URL'])) {
                    // Parse the DATABASE_URL to extract components for proper DSN
                    $url = parse_url($_ENV['DATABASE_URL']);
                    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
                        $url['host'],
                        $url['port'] ?? 5432,
                        ltrim($url['path'], '/')
                    );
                    self::$pdo = new PDO($dsn, $url['user'], $url['pass']);
                } else {
                    // Use individual environment variables
                    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
                        $_ENV['PGHOST'],
                        $_ENV['PGPORT'] ?? 5432,
                        $_ENV['PGDATABASE']
                    );
                    self::$pdo = new PDO($dsn, $_ENV['PGUSER'], $_ENV['PGPASSWORD']);
                }
                
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$dbType = 'postgresql';
            } catch (Exception $e) {
                error_log("PostgreSQL connection failed: " . $e->getMessage());
                self::$pdo = null;
            }
        }
        
        return self::$pdo;
    }
    
    public static function getDbType(): string
    {
        if (self::$dbType) {
            return self::$dbType;
        }
        
        if (extension_loaded('mongodb') && self::testMongoConnection()) {
            self::$dbType = 'mongodb';
        } elseif (self::getPDO() !== null) {
            self::$dbType = 'postgresql';
        } else {
            self::$dbType = 'none';
        }
        
        return self::$dbType;
    }
    
    private static function testMongoConnection(): bool
    {
        try {
            $client = self::getClient();
            $client->selectDatabase('admin')->command(['ping' => 1]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function testConnection(): bool
    {
        try {
            $dbType = self::getDbType();
            
            switch ($dbType) {
                case 'mongodb':
                    // Check if MongoDB extension is loaded
                    if (!extension_loaded('mongodb')) {
                        error_log("MongoDB extension not loaded - falling back to PostgreSQL");
                        return self::testPostgreSQLConnection();
                    }
                    
                    $client = self::getClient();
                    $client->selectDatabase('admin')->command(['ping' => 1]);
                    return true;
                    
                case 'postgresql':
                    return self::testPostgreSQLConnection();
                    
                default:
                    error_log("No database connection available - API will run with limited functionality");
                    return false;
            }
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage() . " - trying fallback");
            return self::testPostgreSQLConnection();
        }
    }
    
    private static function testPostgreSQLConnection(): bool
    {
        try {
            $pdo = self::getPDO();
            if ($pdo === null) {
                return false;
            }
            
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("PostgreSQL test failed: " . $e->getMessage());
            return false;
        }
    }
}
?>