<?php

namespace App\Modules\SQL\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class SQLEditorController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        return $this->render('SQL.editor');
    }

    public function execute()
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $query = isset($_POST['query']) ? $_POST['query'] : '';
        $dbName = isset($_POST['db']) ? $_POST['db'] : ''; // Optional: specific DB to run against

        if (empty(trim($query))) {
            echo json_encode(['error' => 'Query is empty']);
            return;
        }

        header('Content-Type: application/json');

        try {
            $db = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            if (!empty($dbName)) {
                if ($driver === 'sqlsrv') {
                    $db->exec("USE [$dbName]");
                } else {
                    $db->exec("USE `$dbName`");
                }
            }

            // Start timer
            $startTime = microtime(true);

            // Using PDO::query for basic execution to support multiple types of statements
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000, 2); // in ms
            
            // Affected rows
            $affectedRows = $stmt->rowCount();

            // Try to fetch data if it's a SELECT statement or similar
            $data = [];
            $columns = [];
            
            if ($stmt->columnCount() > 0) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($data) > 0) {
                    $columns = array_keys($data[0]);
                } else {
                    // Fetch column metadata if empty result
                    for ($i = 0; $i < $stmt->columnCount(); $i++) {
                        $col = $stmt->getColumnMeta($i);
                        $columns[] = $col['name'];
                    }
                }
            }
            
            \App\Core\Logger::log('Execute SQL', $dbName, null, "Executed query. Affected: $affectedRows");

            echo json_encode([
                'success' => true,
                'data' => $data,
                'columns' => $columns,
                'affectedRows' => $affectedRows,
                'executionTime' => $executionTime,
                'type' => $stmt->columnCount() > 0 ? 'select' : 'update'
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}
