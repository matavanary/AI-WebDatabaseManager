<?php

namespace App\Core;

class Controller
{
    protected $layout = 'main';

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function render($view, $params = [])
    {
        return View::render($view, $params, $this->layout);
    }
}
