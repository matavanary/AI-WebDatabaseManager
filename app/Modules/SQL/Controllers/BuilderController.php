<?php

namespace App\Modules\SQL\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class BuilderController extends Controller
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

        return $this->render('SQL.builder', ['databases' => $databases]);
    }

    public function generate()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $input = $input ? $input : $_POST;
        
        $action = isset($input['action']) ? $input['action'] : 'SELECT';
        $table = isset($input['table']) ? $input['table'] : '';
        $columns = isset($input['columns']) ? $input['columns'] : ['*'];
        $conditions = isset($input['conditions']) ? $input['conditions'] : [];

        if (empty($table)) {
            echo json_encode(['success' => false, 'message' => 'Table is required']);
            return;
        }

        $driver = \App\Core\Session::get('active_driver');
        $q = ($driver === 'sqlsrv') ? '"' : '`'; // Use double quotes or brackets for SQL Server (actually, [] is safer for SQL server, but standard SQL is "). Let's use [ ] for sqlsrv.
        
        $op = ($driver === 'sqlsrv') ? '[' : '`';
        $cp = ($driver === 'sqlsrv') ? ']' : '`';

        $sql = "";
        
        // Basic Query Builder implementation for Phase 4
        switch (strtoupper($action)) {
            case 'SELECT':
                if (is_array($columns) && !empty($columns)) {
                    if ($columns[0] === '*') {
                        $cols = '*';
                    } else {
                        $cols = $op . implode($cp . ', ' . $op, $columns) . $cp;
                    }
                } else {
                    $cols = '*';
                }
                $sql = "SELECT $cols FROM $op$table$cp";
                break;
            case 'UPDATE':
                $sql = "UPDATE $op$table$cp SET ";
                // For builder UI, we might just put placeholders
                $setParts = [];
                if (is_array($columns) && !empty($columns) && $columns[0] !== '*') {
                    foreach ($columns as $col) {
                        $setParts[] = "$op$col$cp = 'value'";
                    }
                    $sql .= implode(', ', $setParts);
                } else {
                    $sql .= "{$op}column_name{$cp} = 'value'";
                }
                break;
            case 'DELETE':
                $sql = "DELETE FROM $op$table$cp";
                break;
            case 'INSERT':
                $sql = "INSERT INTO $op$table$cp ";
                if (is_array($columns) && !empty($columns) && $columns[0] !== '*') {
                    $sql .= "($op" . implode("$cp, $op", $columns) . "$cp) VALUES ('" . implode("', '", array_fill(0, count($columns), 'value')) . "')";
                } else {
                    $sql .= "(column1, column2) VALUES ('value1', 'value2')";
                }
                break;
        }

        // Add WHERE clause if conditions exist
        if (!empty($conditions) && in_array(strtoupper($action), ['SELECT', 'UPDATE', 'DELETE'])) {
            $whereParts = [];
            foreach ($conditions as $cond) {
                if (isset($cond['column'], $cond['operator'], $cond['value'])) {
                    $val = is_numeric($cond['value']) ? $cond['value'] : "'{$cond['value']}'";
                    $whereParts[] = "$op{$cond['column']}$cp {$cond['operator']} $val";
                }
            }
            if (!empty($whereParts)) {
                $sql .= " WHERE " . implode(' AND ', $whereParts);
            }
        }

        $sql .= ";";

        echo json_encode(['success' => true, 'sql' => $sql]);
    }
}
