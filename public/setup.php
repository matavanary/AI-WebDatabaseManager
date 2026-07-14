<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables manually
$rootPath = dirname(__DIR__);
if (file_exists($rootPath . '/.env')) {
    $lines = file($rootPath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

use App\Core\Database;

try {
    $pdo = Database::getSystemConnection();
    $sqlFile = dirname(__DIR__) . '/database/schema/initial_setup.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        echo "Database schema initialized successfully.";
    } else {
        echo "Error: initial_setup.sql not found.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
