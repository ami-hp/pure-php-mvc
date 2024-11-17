<?php

namespace App\Core;

class Route
{
    private static array $routes = [];

    /**
     * @param  string  $uri
     * @param  array<string, string>  $controllerAction
     * @return void
     */
    public static function get(string $uri = '/', array $controllerAction = []): void
    {
        if (empty($controllerAction) || empty($controllerAction[0]) || empty($controllerAction[1])) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }
        self::$routes['GET'][$uri] = $controllerAction;
    }

    public static function post($uri, $controllerAction)
    {
        self::$routes['POST'][$uri] = $controllerAction;
    }

    public static function dispatch()
    {
        $uri = self::parseUrl();
        $method = $_SERVER['REQUEST_METHOD'];
        if (isset(self::$routes[$method][$uri])) {
            $controllerAction = self::$routes[$method][$uri];
            if (is_array($controllerAction) && class_exists($controllerAction[0])) {
                $controller = $controllerAction[0];
                $action = $controllerAction[1];
                $controllerObject = new $controller();
                $controllerObject->$action();
            } else {
                http_response_code(404);
                echo "Invalid controller or method";
            }
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }

    private static function parseUrl()
    {
        if (isset($_GET['url'])) {
            return trim($_GET['url'], '/');
        }
        return '/';
    }
}