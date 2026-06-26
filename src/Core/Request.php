<?php
namespace App\Core;

class Request {
    /**
     * Get current HTTP request method.
     *
     * @return string (e.g. GET, POST, DELETE)
     */
    public static function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get clean request URI without query parameters.
     *
     * @return string
     */
    public static function getUri(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Strip out query string
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        
        return '/' . trim($uri, '/');
    }

    /**
     * Get specific input key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function input(string $key, mixed $default = null): mixed {
        $body = self::all();
        return $body[$key] ?? $default;
    }

    /**
     * Get all inputs from GET, POST, or JSON body.
     *
     * @return array
     */
    public static function all(): array {
        $results = [];

        // Sanitize and fetch GET params
        foreach ($_GET as $key => $value) {
            $results[$key] = filter_var($value, FILTER_DEFAULT);
        }

        if (self::getMethod() === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            
            // Check if JSON request
            if (strpos($contentType, 'application/json') !== false) {
                $json = json_decode(file_get_contents('php://input'), true);
                if (is_array($json)) {
                    $results = array_merge($results, $json);
                }
            } else {
                // Sanitize and fetch POST params
                foreach ($_POST as $key => $value) {
                    $results[$key] = filter_var($value, FILTER_DEFAULT);
                }
            }
        }
        
        return $results;
    }

    /**
     * Get uploaded file parameters.
     *
     * @param string $key
     * @return array|null
     */
    public static function file(string $key): ?array {
        return $_FILES[$key] ?? null;
    }
}
