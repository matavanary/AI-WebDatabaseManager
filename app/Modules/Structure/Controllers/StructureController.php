<?php

namespace App\Modules\Structure\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class StructureController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        try {
            $pdo = Database::getTargetConnection();
            $driver = \App\Core\Session::get('active_driver');
            $sql = \App\Core\SchemaBuilder::getDatabasesQuery($driver);
            $stmt = $pdo->query($sql);
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $databases = [];
        }

        return $this->render('Structure.index', ['databases' => $databases]);
    }

    public function createTable()
    {
        if (!Auth::check()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $input = $input ? $input : $_POST;
        
        $dbName = isset($input['db']) ? $input['db'] : '';
        $tableName = isset($input['table']) ? $input['table'] : '';
        $columns = isset($input['columns']) ? $input['columns'] : [];

        if (empty($dbName) || empty($tableName) || empty($columns)) {
            echo json_encode(['success' => false, 'message' => 'Database, Table Name, and at least 1 column are required.']);
            return;
        }

        try {
            $pdo = Database::getTargetConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            if ($driver === 'sqlsrv') {
                $pdo->exec("USE [$dbName]");
                $q = function($str) { return "[$str]"; };
            } else {
                $pdo->exec("USE `$dbName`");
                $q = function($str) { return "`$str`"; };
            }

            $sql = "CREATE TABLE " . $q($tableName) . " (\n";
            $colDefs = [];
            $primaryKeys = [];

            foreach ($columns as $col) {
                if (empty($col['name'])) continue;

                $def = $q($col['name']) . " {$col['type']}";
                
                if (!empty($col['length'])) {
                    $def .= "({$col['length']})";
                }
                
                if (empty($col['nullable'])) {
                    $def .= " NOT NULL";
                }

                if (!empty($col['ai'])) {
                    $def .= ($driver === 'sqlsrv') ? " IDENTITY(1,1)" : " AUTO_INCREMENT";
                }

                if (!empty($col['pk'])) {
                    $primaryKeys[] = $q($col['name']);
                }

                $colDefs[] = $def;
            }

            if (!empty($primaryKeys)) {
                $colDefs[] = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
            }

            $sql .= implode(",\n", $colDefs);
            $sql .= "\n)";
            
            if ($driver !== 'sqlsrv') {
                $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            }

            $pdo->exec($sql);
            
            \App\Core\Logger::log('Create Table', $dbName, $tableName, "Created table via Structure Manager");

            echo json_encode(['success' => true, 'message' => "Table '$tableName' created successfully.", 'sql' => $sql]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
