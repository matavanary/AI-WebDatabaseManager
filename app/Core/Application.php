<?php

namespace App\Core;

class Application
{
    public static $ROOT_DIR;
    public $router;
    public static $app;
    
    public function __construct($rootPath)
    {
        self::$ROOT_DIR = $rootPath;
        self::$app = $this;
        
        // Load environment variables manually to avoid composer platform reqs issues for now
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
        
        Session::init();
        
        $this->router = new Router();
    }
    
    public function run()
    {
        echo $this->router->resolve();
    }
    
    public static function asset($path)
    {
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        
        // If REQUEST_URI doesn't contain $base (e.g. public hidden by .htaccess)
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        if (stripos($requestUri, $base) !== 0) {
            $base = dirname($base); // go up one level
        }
        
        $base = rtrim(str_replace('\\', '/', $base), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
