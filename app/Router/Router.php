<?php

namespace App\Router;


class Router
{
    private $uri;

    private $routes;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function dispatch()
    {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $this->uri = $uri === '/' ? 'home' : ltrim($uri, '/');
    }

    public function addRoute($method, $path, $handler)
    {
        $this->routes[$method][$path] = $handler;
    }

    public function loadRoutes(array $routes)
    {
        foreach ($routes as $route) {
            [$method, $path, $handler] = $route;
            $this->addRoute($method, $path, $handler);
        }
    }

    public function route()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = $this->uri;


        if (isset($this->routes[$requestMethod][$requestPath])) {
            $handler = $this->routes[$requestMethod][$requestPath];

            $filePath = dirname(__DIR__, 2) . '/views/pages/' . $handler . '.php';
            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
        http_response_code(404);
        echo '404 | Page Not Found';
        die();
    }
}