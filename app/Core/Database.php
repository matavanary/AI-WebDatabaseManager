<?php

namespace App\Core;

use PDO;
use PDOException;
use App\Core\Session;

class Database
{
    private static $systemConnection = null;
    private static $targetConnection = null;

    /**
     * Gets the connection to the system database (users, logs, connections)
     * This always uses the .env settings.
     */
    public static function getSystemConnection()
    {
        if (self::$systemConnection === null) {
            $connection = isset($_ENV['DB_SYSTEM_CONNECTION']) ? $_ENV['DB_SYSTEM_CONNECTION'] : (isset($_SERVER['DB_SYSTEM_CONNECTION']) ? $_SERVER['DB_SYSTEM_CONNECTION'] : (getenv('DB_SYSTEM_CONNECTION') ?: 'sqlite'));
            $host = isset($_ENV['DB_SYSTEM_HOST']) ? $_ENV['DB_SYSTEM_HOST'] : (isset($_SERVER['DB_SYSTEM_HOST']) ? $_SERVER['DB_SYSTEM_HOST'] : (getenv('DB_SYSTEM_HOST') ?: 'localhost'));
            $port = isset($_ENV['DB_SYSTEM_PORT']) ? $_ENV['DB_SYSTEM_PORT'] : (isset($_SERVER['DB_SYSTEM_PORT']) ? $_SERVER['DB_SYSTEM_PORT'] : (getenv('DB_SYSTEM_PORT') ?: '3306'));
            $dbName = isset($_ENV['DB_SYSTEM_DATABASE']) ? $_ENV['DB_SYSTEM_DATABASE'] : (isset($_SERVER['DB_SYSTEM_DATABASE']) ? $_SERVER['DB_SYSTEM_DATABASE'] : (getenv('DB_SYSTEM_DATABASE') ?: 'web_db_manager'));
            $user = isset($_ENV['DB_SYSTEM_USERNAME']) ? $_ENV['DB_SYSTEM_USERNAME'] : (isset($_SERVER['DB_SYSTEM_USERNAME']) ? $_SERVER['DB_SYSTEM_USERNAME'] : (getenv('DB_SYSTEM_USERNAME') ?: 'root'));
            $password = isset($_ENV['DB_SYSTEM_PASSWORD']) ? $_ENV['DB_SYSTEM_PASSWORD'] : (isset($_SERVER['DB_SYSTEM_PASSWORD']) ? $_SERVER['DB_SYSTEM_PASSWORD'] : (getenv('DB_SYSTEM_PASSWORD') ?: ''));

            try {
                if ($connection === 'sqlsrv') {
                    $dsn = "sqlsrv:Server=$host,$port;Database=$dbName";
                } elseif ($connection === 'sqlite') {
                    $dbPath = dirname(dirname(__DIR__)) . "/database/system.sqlite";
                    // Create empty file if not exists
                    if (!file_exists($dbPath)) {
                        touch($dbPath);
                    }
                    $dsn = "sqlite:$dbPath";
                } else {
                    $dsn = "$connection:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
                }
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ];
                
                // SQLite doesn't use username/password
                if ($connection === 'sqlite') {
                    self::$systemConnection = new PDO($dsn, null, null, $options);
                } else {
                    self::$systemConnection = new PDO($dsn, $user, $password, $options);
                }
                
                self::$systemConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("System Database Connection failed: " . $e->getMessage());
            }
        }

        return self::$systemConnection;
    }

    /**
     * Gets the connection to the target database server the user is currently managing.
     * Uses active connection from Session, or falls back to system connection if none selected.
     */
    public static function getTargetConnection()
    {
        if (self::$targetConnection === null) {
            $activeConnId = Session::get('active_connection_id');
            
            if ($activeConnId) {
                // Fetch connection details from System DB
                try {
                    $sysDb = self::getSystemConnection();
                    $stmt = $sysDb->prepare("SELECT * FROM db_connections WHERE id = ?");
                    $stmt->execute([$activeConnId]);
                    $connData = $stmt->fetch();

                    if ($connData) {
                        $driver = isset($connData['driver']) ? $connData['driver'] : 'mysql';
                        $host = $connData['host'];
                        $port = $connData['port'];
                        $user = $connData['username'];
                        $password = base64_decode($connData['password']); 
                        
                        if ($driver === 'sqlsrv') {
                            $dsn = "sqlsrv:Server=$host,$port";
                        } else {
                            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                        }
                        
                        $options = [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ];
                        
                        self::$targetConnection = new PDO($dsn, $user, $password, $options);
                        self::$targetConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                        
                        Session::set('active_driver', $driver);
                        return self::$targetConnection;
                    }
                } catch (PDOException $e) {
                    // Do NOT fallback silently. Throw so the UI can show the connection error.
                    throw new PDOException("Failed to connect to Target Database ($host): " . $e->getMessage());
                }
            }

            // Fallback: If no active connection selected, use system connection
            $connection = getenv('DB_SYSTEM_CONNECTION') ?: 'mysql';
            Session::set('active_driver', $connection);
            
            if ($connection === 'sqlite') {
                self::$targetConnection = self::getSystemConnection();
            } else {
                $host = getenv('DB_SYSTEM_HOST') ?: 'db';
                $port = getenv('DB_SYSTEM_PORT') ?: '3306';
                $user = getenv('DB_SYSTEM_USERNAME') ?: 'root';
                $password = getenv('DB_SYSTEM_PASSWORD') ?: 'root';

                try {
                    $dsn = "$connection:host=$host;port=$port;charset=utf8mb4";
                    self::$targetConnection = new PDO($dsn, $user, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    self::$targetConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die("Target Database Connection fallback failed: " . $e->getMessage());
                }
            }
        }

        return self::$targetConnection;
    }

    /**
     * For backwards compatibility during transition.
     * @deprecated Use getSystemConnection() or getTargetConnection() directly.
     */
    public static function getConnection()
    {
        return self::getTargetConnection();
    }
}
