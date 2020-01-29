<?php

namespace Sowe\Framework\HTTP;

class Router
{
    protected $url;
    protected $routes;
    protected $arguments;
    protected $noroute;

    public function __construct($base, ...$arguments)
    {
        $this->routes = [];
        $this->arguments = $arguments;
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
        $this->noroute = $controller;
        return $this;
    }

    public function route()
    {
        $urlArgs = explode("/", $this->url);

        foreach ($this->routes as $path => $route) {
            $argv = [];
            $routeArgs = array_filter(explode("/", $path));

            if (sizeof($routeArgs) !== sizeof($urlArgs)) {
                continue;
            }

            for ($i = 0; $i < sizeof($urlArgs); $i++) {
                if (!isset($routeArgs[$i])) {
                    // not the same argument length
                    continue 2;
                }
                if ($routeArgs[$i] === "%") {
                    // wildcard
                    $argv[] = $urlArgs[$i];
                } elseif ($routeArgs[$i] !== $urlArgs[$i]) {
                    // not matching
                    continue 2;
                }
            }
            return new $route(...array_merge($this->arguments, $argv));
        }
        if (isset($this->noroute)) {
            return new $this->noroute($this->url);
        }
        return false;
    }
}
