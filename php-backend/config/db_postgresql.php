<?php
require_once __DIR__ . '/../vendor/autoload.php';

class PostgreSQLConnection 
{
    private static ?PDO $connection = null;
    
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $databaseUrl = $_ENV['DATABASE_URL'] ?? '';
            
            if (empty($databaseUrl)) {
                throw new Exception('DATABASE_URL not configured');
            }
            
            try {
                self::$connection = new PDO($databaseUrl, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => true
                ]);
            } catch (PDOException $e) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    public static function testConnection(): bool
    {
        try {
            $conn = self::getConnection();
            $stmt = $conn->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("PostgreSQL connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function initializeTables(): void
    {
        $conn = self::getConnection();
        
        // Create users table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                phone VARCHAR(20),
                address TEXT,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create products table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS products (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                category VARCHAR(100),
                image_url VARCHAR(500),
                stock_quantity INTEGER DEFAULT 0,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create orders table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id VARCHAR(255) PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                payment_status VARCHAR(50) DEFAULT 'pending',
                shipping_address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Create cart table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS cart (
                id SERIAL PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                product_id VARCHAR(255) NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (product_id) REFERENCES products(id),
                UNIQUE(user_id, product_id)
            )
        ");
        
        echo "PostgreSQL tables initialized successfully!\n";
    }
}
?>