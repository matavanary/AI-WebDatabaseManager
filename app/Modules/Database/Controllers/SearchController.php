<?php

namespace App\Modules\Database\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class SearchController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        try {
            $pdo = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            $sql = \App\Core\SchemaBuilder::getDatabasesQuery($driver);
            $stmt = $pdo->query($sql);
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $databases = [];
        }

        return $this->render('Database.search', ['databases' => $databases]);
    }

    public function search()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $dbName = isset($_POST['db']) ? $_POST['db'] : '';
        $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : '';
        $tables = isset($_POST['tables']) ? $_POST['tables'] : []; // Empty means all tables

        if (empty($dbName) || empty($keyword)) {
            echo json_encode(['success' => false, 'message' => 'Database and Keyword are required']);
            return;
        }

        try {
            $pdo = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            if ($driver === 'sqlsrv') {
                $pdo->exec("USE [$dbName]");
            } else {
                $pdo->exec("USE `$dbName`");
            }

            if (empty($tables)) {
                $sqlTables = \App\Core\SchemaBuilder::getTablesQuery($driver, $dbName);
                $stmtTables = $pdo->query($sqlTables);
                while ($row = $stmtTables->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
            }

            $results = [];
            $startTime = microtime(true);

            foreach ($tables as $table) {
                // Get all text/varchar columns
                if ($driver === 'sqlsrv') {
                    $stmtCols = $pdo->prepare(\App\Core\SchemaBuilder::getColumnsQuery($driver, $dbName, $table));
                    $stmtCols->execute(['table' => $table]);
                } else {
                    $stmtCols = $pdo->query(\App\Core\SchemaBuilder::getColumnsQuery($driver, $dbName, $table));
                }
                
                $columns = [];
                while ($col = $stmtCols->fetch(PDO::FETCH_ASSOC)) {
                    $type = strtolower($col['Type']);
                    if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                        $columns[] = $col['Field'];
                    }
                }

                if (empty($columns)) continue; // Skip table if no text columns

                // Build query
                $whereParts = [];
                $params = [];
                foreach ($columns as $col) {
                    $whereParts[] = ($driver === 'sqlsrv') ? "[$col] LIKE ?" : "`$col` LIKE ?";
                    $params[] = "%$keyword%";
                }

                $tableEsc = ($driver === 'sqlsrv') ? "[$table]" : "`$table`";
                $query = "SELECT * FROM $tableEsc WHERE " . implode(" OR ", $whereParts);
                $query = \App\Core\SchemaBuilder::paginate($driver, $query, 100, 0, null);
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $results[] = [
                        'table' => $table,
                        'columns' => array_keys($rows[0]),
                        'data' => $rows,
                        'count' => count($rows)
                    ];
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            \App\Core\Logger::log('Global Search', $dbName, null, "Searched for '$keyword' (Found in " . count($results) . " tables)");

            echo json_encode([
                'success' => true, 
                'results' => $results,
                'executionTime' => $executionTime,
                'tablesSearched' => count($tables)
            ]);

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
