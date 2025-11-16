<?php

namespace App;

use App\Router\Router;

class App
{
    public function run()
    {
        $router = new Router('');
        $router->dispatch();

        $routes = require __DIR__ . '/routes.php';
        $router->loadRoutes($routes);

        $router->route();
    }
}