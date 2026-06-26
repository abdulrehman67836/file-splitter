<?php
// Front Controller & Routing Entry Point
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path !== '/' && is_file(__DIR__ . $path)) {
        return false; // maujood static file ko server khud serve kare
    }
}
$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Router;
use App\Core\Response;
use App\Core\Request;

// Load environment variables
Env::load($root . '/.env');

// Configure environment error handling
if (Env::get('APP_ENV', 'local') === 'local') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Instantiate router
$router = new Router();

// Register application routes defined in the SRS (7.2)
$router->get('/', 'ViewController@index');
$router->post('/uploads', 'FileUploadController@upload');
$router->post('/jobs/{uuid}/split', 'JobController@startSplit');
$router->get('/jobs/{uuid}/status', 'JobController@status');
$router->get('/jobs/{uuid}/download', 'JobController@download');

// Dispatch incoming request with global exception shielding
try {
    $router->dispatch();
} catch (\Exception $e) {
    // If request accepts JSON or is a POST call, return json response
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (Request::getMethod() === 'POST' || strpos($acceptHeader, 'application/json') !== false) {
        Response::json([
            'error'   => 'Server Error',
            'details' => $e->getMessage()
        ], 500);
    } else {
        Response::error($e->getMessage(), 500);
    }
}
