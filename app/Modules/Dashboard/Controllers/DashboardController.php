<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use PDOException;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }

        $stats = [
            'db_count' => 0,
            'table_count' => 0,
            'user_count' => 0,
            'version' => 'Unknown',
            'uptime' => 'Unknown'
        ];

        try {
            $sysDb = Database::getSystemConnection();
            $targetDb = Database::getConnection();
            $driver = \App\Core\Session::get('active_driver');
            
            // Get version
            if ($driver === 'sqlsrv') {
                $versionStmt = $targetDb->query("SELECT @@VERSION as v");
                $vStr = $versionStmt->fetch()['v'];
                $parts = explode(' - ', $vStr);
                $stats['version'] = count($parts) > 1 ? trim($parts[0]) : $vStr;
            } else {
                $versionStmt = $targetDb->query('SELECT VERSION() as v');
                $stats['version'] = $versionStmt->fetch()['v'];
            }

            // Get DB count
            $sql = \App\Core\SchemaBuilder::getDatabasesQuery($driver);
            $dbStmt = $targetDb->query($sql);
            $stats['db_count'] = count($dbStmt->fetchAll(\PDO::FETCH_COLUMN));

            // Count users in system db
            $userStmt = $sysDb->query('SELECT COUNT(*) as c FROM users');
            $stats['user_count'] = $userStmt->fetch()['c'];

        } catch (PDOException $e) {
            // Handle error silently for dashboard stats if not set up yet
        }

        return $this->render('Dashboard.index', ['stats' => $stats]);
    }
}
