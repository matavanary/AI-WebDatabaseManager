<?php

use App\Core\Application;
use App\Modules\Auth\Controllers\AuthController;

$router = Application::$app->router;

$router->get('/', function() {
    if (\App\Core\Auth::check()) {
        header('Location: ' . \App\Core\Application::asset('dashboard'));
        return;
    }
    header('Location: ' . \App\Core\Application::asset('login'));
});

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [\App\Modules\Dashboard\Controllers\DashboardController::class, 'index']);

$router->get('/explorer', [\App\Modules\Database\Controllers\ExplorerController::class, 'index']);
$router->get('/api/explorer/databases', [\App\Modules\Database\Controllers\ExplorerController::class, 'getTree']);
$router->get('/api/explorer/tables', [\App\Modules\Database\Controllers\ExplorerController::class, 'getTables']);
$router->get('/api/schema/full', [\App\Modules\Database\Controllers\ExplorerController::class, 'getFullSchema']);

$router->get('/table/view', [\App\Modules\Table\Controllers\TableController::class, 'view']);
$router->get('/api/table/data', [\App\Modules\Table\Controllers\TableController::class, 'data']);
$router->post('/api/table/data', [\App\Modules\Table\Controllers\TableController::class, 'data']);
$router->post('/api/table/update', [\App\Modules\Table\Controllers\TableController::class, 'update']);
$router->post('/api/table/insert', [\App\Modules\Table\Controllers\TableController::class, 'insert']);
$router->post('/api/table/delete', [\App\Modules\Table\Controllers\TableController::class, 'delete']);

$router->get('/sql', [\App\Modules\SQL\Controllers\SQLEditorController::class, 'index']);
$router->post('/api/sql/execute', [\App\Modules\SQL\Controllers\SQLEditorController::class, 'execute']);

// Phase 3 Routes
$router->get('/users', [\App\Modules\User\Controllers\UserController::class, 'index']);
$router->post('/api/users/create', [\App\Modules\User\Controllers\UserController::class, 'create']);
$router->post('/api/users/update', [\App\Modules\User\Controllers\UserController::class, 'update']);
$router->post('/api/users/delete', [\App\Modules\User\Controllers\UserController::class, 'delete']);

$router->get('/activity', [\App\Modules\Activity\Controllers\LogController::class, 'index']);
$router->get('/api/logs/data', [\App\Modules\Activity\Controllers\LogController::class, 'data']);

// Phase 4 Routes
$router->get('/builder', [\App\Modules\SQL\Controllers\BuilderController::class, 'index']);
$router->post('/api/builder/generate', [\App\Modules\SQL\Controllers\BuilderController::class, 'generate']);

$router->get('/search', [\App\Modules\Database\Controllers\SearchController::class, 'index']);
$router->post('/api/search/execute', [\App\Modules\Database\Controllers\SearchController::class, 'search']);

// Phase 5 Routes
$router->get('/connections', [\App\Modules\Connection\Controllers\ConnectionController::class, 'index']);
$router->post('/api/connection/create', [\App\Modules\Connection\Controllers\ConnectionController::class, 'create']);
$router->post('/api/connection/update', [\App\Modules\Connection\Controllers\ConnectionController::class, 'update']);
$router->post('/api/connection/test', [\App\Modules\Connection\Controllers\ConnectionController::class, 'test']);
$router->post('/api/connection/delete', [\App\Modules\Connection\Controllers\ConnectionController::class, 'delete']);
$router->get('/connection/switch', [\App\Modules\Connection\Controllers\ConnectionController::class, 'switchConnection']);

$router->get('/structure', [\App\Modules\Structure\Controllers\StructureController::class, 'index']);
$router->post('/api/structure/create', [\App\Modules\Structure\Controllers\StructureController::class, 'createTable']);

$router->get('/monitor', [\App\Modules\Monitor\Controllers\MonitorController::class, 'index']);
$router->get('/api/monitor/processes', [\App\Modules\Monitor\Controllers\MonitorController::class, 'processList']);
$router->post('/api/monitor/kill', [\App\Modules\Monitor\Controllers\MonitorController::class, 'killProcess']);
