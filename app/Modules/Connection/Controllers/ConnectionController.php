<?php

namespace App\Modules\Connection\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use PDO;
use PDOException;

class ConnectionController extends Controller
{
    public function index()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            header('Location: ' . \App\Core\Application::asset('dashboard'));
            return;
        }

        $pdo = Database::getSystemConnection();
        
        // Ensure table exists (Auto Migration for Phase 5 & 6)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS db_connections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                driver VARCHAR(50) DEFAULT 'mysql',
                host VARCHAR(255) NOT NULL,
                port INTEGER DEFAULT 3306,
                username VARCHAR(100) NOT NULL,
                password TEXT NOT NULL,
                created_by INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        
        // Add driver column if it doesn't exist (for existing tables)
        try {
            $pdo->exec("ALTER TABLE db_connections ADD COLUMN driver VARCHAR(50) DEFAULT 'mysql' AFTER name");
        } catch (\PDOException $e) {
            // Column likely already exists, ignore
        }

        $stmt = $pdo->query("SELECT * FROM db_connections ORDER BY created_at DESC");
        $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('Connection.index', ['connections' => $connections]);
    }

    public function create()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $driver = trim(isset($_POST['driver']) ? $_POST['driver'] : 'mysql');
        $host = trim(isset($_POST['host']) ? $_POST['host'] : '');
        $port = trim(isset($_POST['port']) ? $_POST['port'] : '3306');
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($name) || empty($host) || empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Name, Host, and Username are required.']);
            return;
        }

        try {
            $pdo = Database::getSystemConnection();
            
            // Simple encryption for Phase 5 demo. In prod, use openssl_encrypt.
            $encryptedPassword = base64_encode($password);
            
            $stmt = $pdo->prepare("INSERT INTO db_connections (name, driver, host, port, username, password, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $driver, $host, $port, $username, $encryptedPassword, Session::get('user_id')]);

            \App\Core\Logger::log('Add Connection', null, 'db_connections', "Added connection: $name");

            echo json_encode(['success' => true, 'message' => 'Connection added successfully.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
        }
    }

    public function update()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $driver = trim(isset($_POST['driver']) ? $_POST['driver'] : 'mysql');
        $host = trim(isset($_POST['host']) ? $_POST['host'] : '');
        $port = trim(isset($_POST['port']) ? $_POST['port'] : '3306');
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (!$id || empty($name) || empty($host) || empty($username)) {
            echo json_encode(['success' => false, 'message' => 'ID, Name, Host, and Username are required.']);
            return;
        }

        try {
            $pdo = Database::getSystemConnection();
            
            if (!empty($password)) {
                $encryptedPassword = base64_encode($password);
                $stmt = $pdo->prepare("UPDATE db_connections SET name = ?, driver = ?, host = ?, port = ?, username = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $driver, $host, $port, $username, $encryptedPassword, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE db_connections SET name = ?, driver = ?, host = ?, port = ?, username = ? WHERE id = ?");
                $stmt->execute([$name, $driver, $host, $port, $username, $id]);
            }

            \App\Core\Logger::log('Update Connection', null, 'db_connections', "Updated connection: $name");

            echo json_encode(['success' => true, 'message' => 'Connection updated successfully.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
        }
    }

    public function test()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $driver = trim(isset($_POST['driver']) ? $_POST['driver'] : 'mysql');
        $host = trim(isset($_POST['host']) ? $_POST['host'] : '');
        $port = trim(isset($_POST['port']) ? $_POST['port'] : '3306');
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($host) || empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Host and Username are required for testing.']);
            return;
        }

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
            
            if ($driver === 'sqlsrv') {
                $dsn = "sqlsrv:Server=$host,$port;LoginTimeout=3";
            } else {
                $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                $options[PDO::ATTR_TIMEOUT] = 3;
            }
            
            $pdo = new PDO($dsn, $username, $password, $options);

            echo json_encode(['success' => true, 'message' => 'Connection successful!']);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? $_POST['id'] : null;

        try {
            $pdo = Database::getSystemConnection();
            
            $stmtGet = $pdo->prepare("SELECT name FROM db_connections WHERE id = ?");
            $stmtGet->execute([$id]);
            $connName = $stmtGet->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM db_connections WHERE id = ?");
            if ($stmt->execute([$id])) {
                if (Session::get('active_connection_id') == $id) {
                    Session::remove('active_connection_id');
                }
                \App\Core\Logger::log('Delete Connection', null, 'db_connections', "Deleted connection: $connName");
                echo json_encode(['success' => true, 'message' => 'Connection deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete connection.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function switchConnection()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if ($id) {
            Session::set('active_connection_id', $id);
        } else {
            Session::remove('active_connection_id');
        }

        // Redirect back
        header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/dashboard'));
    }

    public static function getActiveConnections()
    {
        try {
            $pdo = Database::getSystemConnection();
            $stmt = $pdo->query("SELECT id, name FROM db_connections ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
