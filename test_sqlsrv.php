<?php
try {
    $pdo = new PDO('sqlsrv:Server=127.0.0.1;LoginTimeout=1', 'sa', 'pwd', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    echo $e->getMessage();
}
