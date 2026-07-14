<?php

namespace App\Core;

use App\Core\Database;
use App\Core\Session;
use PDOException;

class Logger
{
    public static function log($action, $db = null, $table = null, $details = null)
    {
        if (!Session::has('user_id')) {
            return false;
        }

        try {
            $pdo = Database::getSystemConnection(); 
            // System connection automatically connects to web_db_manager, no need to USE

            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, target_database, target_table, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $userId = Session::get('user_id');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            return $stmt->execute([$userId, $action, $db, $table, $details, $ip, $ua]);
        } catch (PDOException $e) {
            // Silently fail logging so it doesn't break the app
            error_log("Failed to write activity log: " . $e->getMessage());
            return false;
        }
    }
}
