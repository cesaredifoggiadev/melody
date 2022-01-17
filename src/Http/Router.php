<?php

namespace Melody\Http;

use Melody\Exceptions\PageNotFoundException;

class Route {
    private static $routes = ['GET' => [], 'POST' => []];

    private $controller;
    private $function;
    private $parameters;
    private $name;

    public function __construct($attributes) {
        $this->controller = $attributes['controller'];
        $this->function = $attributes['function'];
        if (isset($attributes['parameters'])) {
            $this->parameters = $attributes['parameters'];
        } else {
            $this->parameters = [];
        }
        
    }

    public function getController() {
        return $this->controller;
    }

    public function getFunction() {
        return $this->function;
    }

    public function name($name) {
        $this->name = $name;

        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function getParameters() {
        return $this->parameters;
    }

    public static function url($name, $parameters = []) {
        foreach (static::$routes['GET'] as $uri => $route) {
            if ($route->getName() == $name) {
                return 'http://' .$_SERVER['HTTP_HOST'] .'/genovese' .stripslashes($uri);
            }
        }

        foreach (static::$routes['POST'] as $uri => $route) {
            if ($route->getName() == $name) {
                return 'http://' .$_SERVER['HTTP_HOST'] .'/genovese' .stripslashes($uri);
            }
        }

        return '';
    }

    private static function register($url, $verb, $attributes) {
        $sanitizedVerb = strtoupper($verb);
        $urlRe = '/{(.*?)?(\?)*}/mi';
        $parameters = [];
        $regUrl = preg_replace_callback($urlRe, function($matches) use($url, &$parameters) {
            // echo $url;
            $parameter = $matches[1];
            $parameters[] = $parameter;
            return "(?'$parameter'[a-zA-Z0-9]*)";
        }, $url);

        $regUrl = str_replace('/', '\/', $regUrl);

        $attributes[] = $parameters;

        $route = new Route(static::convertAttributes($attributes));

        static::$routes[$sanitizedVerb][$regUrl] = $route;

        return $route;
    }

    private static function convertAttributes($attributes) {
        return [
            'controller' => $attributes[0],
            'function' => $attributes[1],
            'parameters' => $attributes[2]
        ];
    }

    public static function get($url, $attributes) {
        return static::register($url, 'GET', $attributes);
    }

    public static function post($url, $attributes) {
        return static::register($url, 'POST', $attributes);
    }

    public static function resolve() {
        $verb = strtoupper($_SERVER['REQUEST_METHOD']);
        $uri = str_replace('/genovese', '', $_SERVER['REQUEST_URI']);
        $parameters = [];
        $matchedRoute = null;
        foreach (self::$routes[$verb] as $regUrl => $route) {
            $parameters = [];
            $matches = null;
            if (stripslashes($regUrl) == $uri) {
                $matchedRoute = $route;
                break;
            } else {
                preg_match_all("/$regUrl/mi", $uri, $matches);
                $routeParameters = $route->getParameters();
                $routeIsMatched = false;
                foreach ($routeParameters as $parameter) {
                    if (isset($matches[$parameter]) && count($matches[$parameter]) > 0) {
                        $routeIsMatched = true;
                        $parameters[$parameter] = $matches[$parameter][0];
                    } else {
                        $routeIsMatched = false;
                        break;
                    }
                }
                if ($routeIsMatched) {
                    $matchedRoute = $route;
                    break;
                }
            }
        }
        
        if ($matchedRoute) {
            $controllerName = $matchedRoute->getController();
            $function = $matchedRoute->getFunction();
            $controller = new $controllerName;

            return $controller->$function(...array_values($parameters));
        }

        throw new PageNotFoundException($uri);
    }
}