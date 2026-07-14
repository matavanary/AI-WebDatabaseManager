<?php

namespace App\Modules\Table\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class TableController extends Controller
{
    public function view()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }
        
        $dbName = isset($_REQUEST['db']) ? $_REQUEST['db'] : '';
        $tableName = isset($_REQUEST['table']) ? $_REQUEST['table'] : '';
        
        if (empty($dbName) || empty($tableName)) {
            echo '<div class="alert alert-danger w-75 m-auto">Invalid database or table.</div>';
            return;
        }

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            if ($driver === 'sqlsrv') {
                $db->exec("USE [$dbName]");
                $stmt = $db->prepare(\App\Core\SchemaBuilder::getColumnsQuery($driver, $dbName, $tableName));
                $stmt->execute(['table' => $tableName]);
            } else {
                $db->exec("USE `$dbName`");
                $stmt = $db->query(\App\Core\SchemaBuilder::getColumnsQuery($driver, $dbName, $tableName));
            }
            
            $columns = $stmt->fetchAll();

            $primaryKey = null;
            foreach ($columns as $col) {
                if ($col['Key'] === 'PRI') {
                    $primaryKey = $col['Field'];
                    break;
                }
            }
            if (!$primaryKey && count($columns) > 0) {
                $primaryKey = $columns[0]['Field']; // Fallback
            }

            // We are using a partial view without layout here since it's loaded via AJAX
            $this->setLayout(false);
            return $this->render('Table.view', [
                'dbName' => $dbName, 
                'tableName' => $tableName, 
                'columns' => $columns,
                'primaryKey' => $primaryKey
            ]);
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger w-75 m-auto">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }
    }

    public function data()
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $dbName = isset($_REQUEST['db']) ? $_REQUEST['db'] : '';
        $tableName = isset($_REQUEST['table']) ? $_REQUEST['table'] : '';
        
        if (empty($dbName) || empty($tableName)) {
            echo json_encode(['data' => []]);
            return;
        }

        header('Content-Type: application/json');

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');

            if ($driver === 'sqlsrv') {
                $db->exec("USE [$dbName]");
            } else {
                $db->exec("USE `$dbName`");
            }

            // Base parameters for DataTables
            $start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
            $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
            $searchValue = isset($_REQUEST['search']['value']) ? $_REQUEST['search']['value'] : '';

            $whereClause = "";
            $params = [];
            
            if (!empty($searchValue)) {
                if ($driver === 'sqlsrv') {
                    $colsStmt = $db->prepare(\App\Core\SchemaBuilder::getColumnsQuery($driver, $dbName, $tableName));
                    $colsStmt->execute(['table' => $tableName]);
                } else {
                    $colsStmt = $db->query(\App\Core\SchemaBuilder::getColumnsQuery($driver, $dbName, $tableName));
                }
                $cols = $colsStmt->fetchAll();
                
                $searchConditions = [];
                foreach ($cols as $col) {
                    if (strpos(strtolower($col['Type']), 'char') !== false || strpos(strtolower($col['Type']), 'text') !== false || strpos(strtolower($col['Type']), 'int') !== false) {
                        $searchConditions[] = ($driver === 'sqlsrv') ? "[{$col['Field']}] LIKE ?" : "`{$col['Field']}` LIKE ?";
                        $params[] = "%$searchValue%";
                    }
                }
                if (!empty($searchConditions)) {
                    $whereClause = "WHERE " . implode(' OR ', $searchConditions);
                }
            }

            // Order
            $orderClause = "";
            if (isset($_REQUEST['order'][0]['column'])) {
                // Simplified for Phase 6. Real implementation would map index to column name
                // $colIdx = (int)$_REQUEST['order'][0]['column'];
                // $dir = $_REQUEST['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
            }

            // Count total records
            $tableEsc = ($driver === 'sqlsrv') ? "[$tableName]" : "`$tableName`";
            $totalStmt = $db->query("SELECT COUNT(*) FROM $tableEsc");
            $recordsTotal = $totalStmt->fetchColumn();

            // Count filtered records
            $recordsFiltered = $recordsTotal;
            if (!empty($whereClause)) {
                $filterStmt = $db->prepare("SELECT COUNT(*) FROM $tableEsc $whereClause");
                $filterStmt->execute($params);
                $recordsFiltered = $filterStmt->fetchColumn();
            }

            // Fetch data (use SchemaBuilder pagination)
            $sql = "SELECT * FROM $tableEsc $whereClause";
            $sql = \App\Core\SchemaBuilder::paginate($driver, $sql, $length, $start, null);
            
            $dataStmt = $db->prepare($sql);
            $dataStmt->execute($params);
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            $json = json_encode([
                'draw' => isset($_REQUEST['draw']) ? (int)$_REQUEST['draw'] : 1,
                'recordsTotal' => (int)$recordsTotal,
                'recordsFiltered' => (int)$recordsFiltered,
                'data' => $data
            ]);
            
            if ($json === false) {
                echo json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg(), 'data' => []]);
            } else {
                echo $json;
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage(), 'data' => []]);
        }
    }

    public function delete()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $dbName = isset($_POST['db']) ? $_POST['db'] : '';
        $tableName = isset($_POST['table']) ? $_POST['table'] : '';
        $pk = isset($_POST['pk']) ? $_POST['pk'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';

        if (empty($dbName) || empty($tableName) || empty($pk) || empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');

            if ($driver === 'sqlsrv') {
                $stmt = $db->prepare("DELETE FROM [$dbName].dbo.[$tableName] WHERE [$pk] = ?");
            } else {
                $stmt = $db->prepare("DELETE FROM `$dbName`.`$tableName` WHERE `$pk` = ?");
            }
            
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function insert()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $dbName = isset($_POST['db']) ? $_POST['db'] : '';
        $tableName = isset($_POST['table']) ? $_POST['table'] : '';
        $data = isset($_POST['data']) ? $_POST['data'] : [];

        if (empty($dbName) || empty($tableName) || empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing parameters or data']);
            return;
        }

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');

            $columns = [];
            $placeholders = [];
            $values = [];

            foreach ($data as $col => $val) {
                if ($val === '') $val = null;
                if ($driver === 'sqlsrv') {
                    $columns[] = "[$col]";
                } else {
                    $columns[] = "`$col`";
                }
                $placeholders[] = "?";
                $values[] = $val;
            }

            $colStr = implode(", ", $columns);
            $valStr = implode(", ", $placeholders);

            if ($driver === 'sqlsrv') {
                $sql = "INSERT INTO [$dbName].dbo.[$tableName] ($colStr) VALUES ($valStr)";
            } else {
                $sql = "INSERT INTO `$dbName`.`$tableName` ($colStr) VALUES ($valStr)";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $dbName = isset($_POST['db']) ? $_POST['db'] : '';
        $tableName = isset($_POST['table']) ? $_POST['table'] : '';
        $pk = isset($_POST['pk']) ? $_POST['pk'] : '';
        $pkValue = isset($_POST['pk_value']) ? $_POST['pk_value'] : '';
        $data = isset($_POST['data']) ? $_POST['data'] : [];

        if (empty($dbName) || empty($tableName) || empty($pk) || empty($pkValue) || empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing parameters or data']);
            return;
        }

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');

            $setClauses = [];
            $values = [];

            foreach ($data as $col => $val) {
                if ($val === '') $val = null;
                if ($driver === 'sqlsrv') {
                    $setClauses[] = "[$col] = ?";
                } else {
                    $setClauses[] = "`$col` = ?";
                }
                $values[] = $val;
            }
            
            // Add primary key value at the end for WHERE clause
            $values[] = $pkValue;

            $setStr = implode(", ", $setClauses);

            if ($driver === 'sqlsrv') {
                $sql = "UPDATE [$dbName].dbo.[$tableName] SET $setStr WHERE [$pk] = ?";
            } else {
                $sql = "UPDATE `$dbName`.`$tableName` SET $setStr WHERE `$pk` = ?";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
