<?php

namespace App\Core;

class Router
{
    protected $routes = [];

    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));
        
        // If accessed via root .htaccess, REQUEST_URI doesn't have /public
        if (stripos($path, $basePath) !== 0) {
            $basePath = dirname($basePath); // go up one level
        }

        if ($basePath !== '/' && $basePath !== '\\' && stripos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        if (empty($path)) {
            $path = '/';
        }

        $callback = isset($this->routes[$method][$path]) ? $this->routes[$method][$path] : false;

        if ($callback === false) {
            http_response_code(404);
            return View::render('404');
        }

        if (is_string($callback)) {
            return View::render($callback);
        }

        if (is_array($callback)) {
            $controller = new $callback[0]();
            $callback[0] = $controller;
        }

        return call_user_func($callback);
    }
}
