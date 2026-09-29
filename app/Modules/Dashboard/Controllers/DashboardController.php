<?php
namespace App\Modules\Dashboard\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\SchemaBuilder;
use PDO;
use PDOException;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('login'));
            return;
        }
        $stats = ['db_count' => null, 'connection_count' => null, 'user_count' => null, 'version' => null];
        $recentActivity = [];
        $activityAvailable = true;
        $connectionName = 'Local System DB';
        $connectionState = 'local';
        try {
            $sysDb = Database::getSystemConnection();
            $stats['user_count'] = (int)$sysDb->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $stats['connection_count'] = (int)$sysDb->query('SELECT COUNT(*) FROM db_connections')->fetchColumn();
        } catch (PDOException $e) {
            // Missing setup is represented by an unavailable metric, not a false zero.
        }
        try {
            $driver = $sysDb->getAttribute(PDO::ATTR_DRIVER_NAME);
            $sql = 'SELECT ' . ($driver === 'sqlsrv' ? 'TOP 5 ' : '') . 'action, target_database, details, created_at FROM activity_logs ORDER BY id DESC';
            if ($driver !== 'sqlsrv') $sql .= ' LIMIT 5';
            $recentActivity = $sysDb->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $activityAvailable = false;
        }
        // The internal SQLite store is not a managed database server.
        if (Session::get('active_connection_id') || $sysDb->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            try {
                if (Session::get('active_connection_id')) {
                    $statement = $sysDb->prepare('SELECT name FROM db_connections WHERE id = ?');
                    $statement->execute([Session::get('active_connection_id')]);
                    $connectionName = $statement->fetchColumn();
                    if (!$connectionName) throw new PDOException('Connection is no longer available');
                }
                $targetDb = Database::getTargetConnection();
                $driver = $targetDb->getAttribute(PDO::ATTR_DRIVER_NAME);
                $stats['version'] = $targetDb->query($driver === 'sqlsrv' ? "SELECT CAST(SERVERPROPERTY('ProductVersion') AS VARCHAR(50))" : 'SELECT VERSION()')->fetchColumn();
                $stats['db_count'] = count($targetDb->query(SchemaBuilder::getDatabasesQuery($driver))->fetchAll(PDO::FETCH_COLUMN));
                $connectionState = 'connected';
            } catch (PDOException $e) {
                $connectionName = $connectionName ?: 'Unavailable connection';
                $connectionState = 'unavailable';
            }
        }
        return $this->render('Dashboard.index', compact('stats', 'recentActivity', 'activityAvailable', 'connectionName', 'connectionState'));
    }
}
