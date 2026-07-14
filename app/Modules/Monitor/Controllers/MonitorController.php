<?php

namespace App\Modules\Monitor\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use PDO;
use PDOException;

class MonitorController extends Controller
{
    public function index()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            header('Location: ' . \App\Core\Application::asset('dashboard'));
            return;
        }

        try {
            $pdo = Database::getTargetConnection();
            $driver = Session::get('active_driver');
            
            // Get Server Info
            $versionStmt = $pdo->query("SELECT @@VERSION");
            $version = $versionStmt->fetchColumn();
            
            if ($driver === 'sqlsrv') {
                // Shorten SQL Server's excessively long version string
                $parts = explode(' - ', $version);
                if (count($parts) > 1) {
                    $version = trim($parts[0]);
                }
            }

            // Get Uptime (MySQL only for now, fallback for SQL Server)
            if ($driver === 'sqlsrv') {
                $uptimeStmt = $pdo->query("SELECT sqlserver_start_time FROM sys.dm_os_sys_info");
                $startTime = $uptimeStmt->fetchColumn();
                if ($startTime) {
                    $uptime = time() - strtotime($startTime);
                    $days = floor($uptime / (3600*24));
                    $hours = floor(($uptime % (3600*24)) / 3600);
                    $uptimeStr = "{$days}d {$hours}h";
                } else {
                    $uptimeStr = 'N/A';
                }

                $threadsStmt = $pdo->query("SELECT COUNT(*) FROM sys.dm_exec_sessions WHERE is_user_process = 1");
                $threads = $threadsStmt->fetchColumn();
            } else {
                $uptimeStmt = $pdo->query("SHOW GLOBAL STATUS LIKE 'Uptime'");
                $uptimeRow = $uptimeStmt->fetch();
                $uptime = $uptimeRow ? intval($uptimeRow['Value']) : 0;
                
                $days = floor($uptime / (3600*24));
                $hours = floor(($uptime % (3600*24)) / 3600);
                $uptimeStr = "{$days}d {$hours}h";

                // Get Threads
                $threadsStmt = $pdo->query("SHOW GLOBAL STATUS LIKE 'Threads_connected'");
                $threadsRow = $threadsStmt->fetch();
                $threads = $threadsRow ? $threadsRow['Value'] : 0;
            }

        } catch (PDOException $e) {
            $version = 'Unknown';
            $uptimeStr = 'Unknown';
            $threads = 'Unknown';
        }

        return $this->render('Monitor.index', [
            'version' => $version,
            'uptime' => $uptimeStr,
            'threads' => $threads
        ]);
    }

    public function processList()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            http_response_code(401);
            return;
        }

        try {
            $pdo = Database::getTargetConnection();
            $driver = Session::get('active_driver');
            
            $sql = \App\Core\SchemaBuilder::getProcessListQuery($driver);
            $stmt = $pdo->query($sql);
            $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $processes]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function killProcess()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? $_POST['id'] : null;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Process ID required.']);
            return;
        }

        try {
            $pdo = Database::getTargetConnection();
            $driver = Session::get('active_driver');
            
            $sql = \App\Core\SchemaBuilder::getKillProcessQuery($driver, $id);
            $pdo->exec($sql);
            
            \App\Core\Logger::log('Kill Process', null, null, "Killed process ID: $id");

            echo json_encode(['success' => true, 'message' => "Process $id killed successfully."]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to kill process: ' . $e->getMessage()]);
        }
    }
}
