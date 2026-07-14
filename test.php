<?php
$path = '/enomban/REPO.GITHUB/AI-WebDatabaseManager/';
$scriptName = '/enomban/REPO.GITHUB/AI-WebDatabaseManager/public/index.php';
$basePath = str_replace('\\', '/', dirname($scriptName));
if (strpos($path, $basePath) !== 0) {
    $basePath = dirname($basePath);
}
if ($basePath !== '/' && $basePath !== '\\' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
if (empty($path)) {
    $path = '/';
}
echo "Path is: '" . $path . "'\n";
echo "BasePath was: '" . $basePath . "'\n";
