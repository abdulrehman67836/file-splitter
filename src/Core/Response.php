<?php
namespace App\Core;

class Response {
    /**
     * Return JSON response and terminate.
     *
     * @param array $data
     * @param int $status
     * @return void
     */
    public static function json(array $data, int $status = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to another URL and terminate.
     *
     * @param string $url
     * @return void
     */
    public static function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }

    /**
     * Render an HTML view inside a layout.
     *
     * @param string $view Name of view file under src/Views/ (excluding .php)
     * @param array $data Variables to extract to view scope
     * @param string $layout Name of layout under src/Views/layouts/ (excluding .php)
     * @return void
     */
    public static function render(string $view, array $data = [], string $layout = 'main'): void {
        $root = dirname(dirname(__DIR__));
        $viewFile = $root . "/src/Views/{$view}.php";
        
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "Error: View file '{$view}.php' not found in {$viewFile}.";
            exit;
        }

        // Extract variables to local scope of view
        extract($data);

        // Start output buffering for view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render base layout
        $layoutFile = $root . "/src/Views/layouts/{$layout}.php";
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
        exit;
    }

    /**
     * Output a standard error response view.
     *
     * @param string $message
     * @param int $status
     * @return void
     */
    public static function error(string $message, int $status = 400): void {
        http_response_code($status);
        self::render('error', [
            'message' => $message,
            'status'  => $status,
            'title'   => 'Error Occurred'
        ], 'main');
    }
}
