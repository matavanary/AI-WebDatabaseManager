<?php
require 'vendor/autoload.php';
$app = new App\Core\Application(__DIR__);
\App\Core\Session::init();
\App\Core\Session::set('user_id', 1);

// We need a target connection to simulate the real request
$systemDb = \App\Core\Database::getSystemConnection();
$stmt = $systemDb->query('SELECT id, driver FROM db_connections LIMIT 1');
$conn = $stmt->fetch();
if ($conn) {
    \App\Core\Session::set('active_connection_id', $conn['id']);
    \App\Core\Session::set('active_driver', $conn['driver']);
}

$_GET['db'] = 'TEST_EMS2_203';
$_GET['table'] = 'MENU';
$controller = new \App\Modules\Table\Controllers\TableController();
$controller->data();
