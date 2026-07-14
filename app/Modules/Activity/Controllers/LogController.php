<?php

namespace App\Modules\Activity\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDO;

class LogController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        return $this->render('Activity.index');
    }

    public function data()
    {
        if (!Auth::check()) {
            http_response_code(401);
            return;
        }

        $pdo = Database::getSystemConnection();

        // DataTables parameters
        $draw = isset($_GET['draw']) ? $_GET['draw'] : 1;
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $length = isset($_GET['length']) ? $_GET['length'] : 10;
        $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

        $params = [];
        $whereClause = "";

        if (!empty($search)) {
            $whereClause = " WHERE l.action LIKE ? OR l.target_database LIKE ? OR l.target_table LIKE ? OR u.username LIKE ?";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        }

        $countQuery = "SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id $whereClause";
        $stmtCount = $pdo->prepare($countQuery);
        $stmtCount->execute($params);
        $totalRecords = $stmtCount->fetchColumn();

        $query = "
            SELECT l.*, u.username 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            $whereClause 
            ORDER BY l.created_at DESC 
            LIMIT " . (int)$length . " OFFSET " . (int)$start
        ;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data
        ]);
    }
}
