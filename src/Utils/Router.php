<?php

namespace Sowe\Framework\Utils;

class Router
{
    protected $url;
    protected $routes;
    protected $errorController;

    public function __construct($base)
    {
        $this->routes = [];
        $this->parse_url($_SERVER['REQUEST_URI'], $base);
    }

    private function parse_url($url, $base)
    {
        $this->url = implode("/", array_diff(
            array_filter(explode("/", explode("?", $url)[0])),
            array_filter(explode("/", $base))
        ));
    }

    public function on($route, $controller)
    {
        $this->routes[$route] = $controller;
        return $this;
    }

    public function else($controller)
    {
        $this->errorController = $controller;
        return $this;
    }

    public function route()
    {
        foreach ($this->routes as $route => $class) {
            if ($this->url == implode("/", array_filter(explode("/", $route)))) {
                return new $class();
            }
        }
        return new $this->errorController();
    }
}
