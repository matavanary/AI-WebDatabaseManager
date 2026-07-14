<?php

namespace App\Modules\Database\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class ExplorerController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        return $this->render('Database.index');
    }

    public function getTree()
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        header('Content-Type: application/json');

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            // Get all databases
            $sql = \App\Core\SchemaBuilder::getDatabasesQuery($driver);
            $stmt = $db->query($sql);
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $tree = [];
            foreach ($databases as $database) {
                // Skip system databases for cleaner view (optional)
                if (in_array($database, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
                    // continue;
                }

                $tree[] = [
                    'id' => 'db_' . $database,
                    'text' => $database,
                    'icon' => 'fas fa-database text-warning',
                    'state' => ['opened' => false],
                    'children' => true, // indicates AJAX loading for children or we can load tables now
                    'type' => 'database',
                    'a_attr' => ['data-db' => $database]
                ];
            }

            echo json_encode($tree);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getTables()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $dbName = isset($_GET['db']) ? $_GET['db'] : '';
        if (empty($dbName)) {
            http_response_code(400);
            return;
        }

        header('Content-Type: application/json');

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            $sql = \App\Core\SchemaBuilder::getTablesQuery($driver, $dbName);
            $stmt = $db->query($sql);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $children = [];
            foreach ($tables as $table) {
                $children[] = [
                    'id' => 'tbl_' . $dbName . '_' . $table,
                    'text' => $table,
                    'icon' => 'fas fa-table text-primary',
                    'type' => 'table',
                    'a_attr' => ['data-db' => $dbName, 'data-table' => $table]
                ];
            }

            echo json_encode($children);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getFullSchema()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $dbName = isset($_GET['db']) ? $_GET['db'] : '';
        if (empty($dbName)) {
            echo json_encode(['success' => false, 'message' => 'Database not specified']);
            return;
        }

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            $schema = [];
            
            if ($driver === 'sqlsrv') {
                $sql = "SELECT TABLE_NAME, COLUMN_NAME FROM [$dbName].INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME IN (SELECT TABLE_NAME FROM [$dbName].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE')";
                $stmt = $db->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $table = $row['TABLE_NAME'];
                    $col = $row['COLUMN_NAME'];
                    if (!isset($schema[$table])) {
                        $schema[$table] = [];
                    }
                    $schema[$table][] = $col;
                }
            } else {
                // MySQL
                $db->exec("USE `$dbName`");
                $tablesStmt = $db->query("SHOW TABLES");
                $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($tables as $table) {
                    $colsStmt = $db->query("SHOW COLUMNS FROM `$table`");
                    $cols = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
                    $schema[$table] = array_map(function($c) { return $c['Field']; }, $cols);
                }
            }

            echo json_encode(['success' => true, 'schema' => $schema]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
