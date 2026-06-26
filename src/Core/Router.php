<?php
namespace App\Core;

class Router {
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string $path
     * @param string $handler Format: 'ViewController@index'
     * @return void
     */
    public function get(string $path, string $handler): void {
        $this->routes['GET'][$this->convertToRegex($path)] = $handler;
    }

    /**
     * Register a POST route.
     *
     * @param string $path
     * @param string $handler Format: 'JobController@split'
     * @return void
     */
    public function post(string $path, string $handler): void {
        $this->routes['POST'][$this->convertToRegex($path)] = $handler;
    }

    /**
     * Convert standard URI parameters (e.g. /jobs/{uuid}/status) into a regex pattern.
     *
     * @param string $path
     * @return string
     */
    private function convertToRegex(string $path): string {
        $path = trim($path, '/');
        if ($path === '') {
            return '#^/$#';
        }
        
        // Convert route parameters (e.g. {uuid}) into regex named capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9\-]+)', $path);
        return '#^/' . $pattern . '/?$#';
    }

    /**
     * Match current request against registered routes and execute controller handler.
     *
     * @return void
     */
    public function dispatch(): void {
    $method = Request::getMethod();

    $uri = Request::getUri();
    $uri = parse_url($uri, PHP_URL_PATH);          
    if ($uri === '' || $uri === false || $uri === null) {
        $uri = '/';                                 
    }
    

        foreach ($this->routes[$method] as $pattern => $handler) {
            if (preg_match($pattern, $uri, $matches)) {
                // Keep only the named matches (string keys) as route variables
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Handler template: 'ViewController@index'
                list($controllerName, $action) = explode('@', $handler);
                $controllerClass = "App\\Controllers\\" . $controllerName;

                if (!class_exists($controllerClass)) {
                    Response::error("Internal Server Error: Controller '{$controllerClass}' not found", 500);
                }

                $controller = new $controllerClass();
                if (!method_exists($controller, $action)) {
                    Response::error("Internal Server Error: Action '{$action}' not found in '{$controllerClass}'", 500);
                }

                // Call the controller action, passing route variables as arguments
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        Response::error("Not Found: The requested URL '{$uri}' was not found on this server.", 404);
    }
}
