<?php

namespace App\Core;

class View
{
    public static function render($view, $params = [], $layout = 'main')
    {
        $viewContent = self::renderOnlyView($view, $params);
        if ($layout === false || $layout === null) {
            return $viewContent;
        }
        $layoutContent = self::layoutContent($layout);
        if ($view === '404' && empty($layoutContent)) {
            return $viewContent; // Fallback if no layout
        }
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    protected static function layoutContent($layout)
    {
        $layoutPath = Application::$ROOT_DIR . "/app/Shared/Components/Layouts/$layout.php";
        if (file_exists($layoutPath)) {
            ob_start();
            include_once $layoutPath;
            return ob_get_clean();
        }
        return '{{content}}'; // Return placeholder if layout not found
    }

    protected static function renderOnlyView($view, $params)
    {
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        
        $viewParts = explode('.', $view);
        if (count($viewParts) == 2) {
            $module = ucfirst($viewParts[0]);
            $viewName = $viewParts[1];
            $viewPath = Application::$ROOT_DIR . "/app/Modules/$module/Views/$viewName.php";
        } else {
            // Default to root views if any (like 404)
            $viewPath = Application::$ROOT_DIR . "/app/Shared/Components/Views/$view.php";
        }

        if (file_exists($viewPath)) {
            ob_start();
            include_once $viewPath;
            return ob_get_clean();
        }
        
        return "View $view not found";
    }
}
