<?php
// Core PHP script to diagnose environment and install database schema.

// Set content-type if run via browser, else plain text for CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
}

echo "=== Excel/CSV File Splitter Installer ===\n\n";

// 1. Check PHP version
echo "1. Checking PHP version... ";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "OK (Current version: " . PHP_VERSION . ")\n";
} else {
    echo "FAILED\nError: PHP 8.2 or higher is required. Current: " . PHP_VERSION . "\n";
    exit(1);
}

// 2. Check extensions
echo "2. Checking required PHP extensions...\n";
$extensions = ['pdo', 'pdo_pgsql', 'zip', 'mbstring', 'openssl'];
$missing = [];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   - {$ext}: OK\n";
    } else {
        echo "   - {$ext}: MISSING\n";
        $missing[] = $ext;
    }
}
if (!empty($missing)) {
    echo "FAILED\nError: The following extensions are missing: " . implode(', ', $missing) . "\n";
    echo "Please enable them in your php.ini.\n";
    exit(1);
}
echo "Extensions check: OK\n";

// 3. Load Autoloader & Environment Config
echo "3. Loading environment configurations... ";
$root = dirname(__DIR__);

// Check if composer autoload exists
$autoloadFile = $root . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    echo "FAILED\nError: Composer autoloader not found. Please run 'composer install' first.\n";
    exit(1);
}

require_once $autoloadFile;

use App\Core\Env;
Env::load($root . '/.env');

$dbConfig = require $root . '/config/database.php';
echo "OK\n";

// 4. Connect to PostgreSQL
echo "4. Connecting to PostgreSQL... ";
$host = $dbConfig['host'];
$port = $dbConfig['port'];
$dbname = $dbConfig['database'];
$user = $dbConfig['username'];
$pass = $dbConfig['password'];

try {
    // First try connecting directly to the target database
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected to target database '{$dbname}'.\n";
} catch (PDOException $e) {
    // If target database doesn't exist, try connecting to default 'postgres' database to create it
    if (strpos($e->getMessage(), 'does not exist') !== false || $e->getCode() === '7' || strpos($e->getMessage(), 'database') !== false) {
        echo "Database '{$dbname}' does not exist. Trying to create it...\n";
        try {
            $dsnDefault = "pgsql:host=$host;port=$port;dbname=postgres";
            $pdoDefault = new PDO($dsnDefault, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $cleanDbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbname);
            $pdoDefault->exec("CREATE DATABASE \"{$cleanDbName}\"");
            echo "   - Database '{$dbname}' created successfully.\n";
            
            // Now reconnect to target
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "Connected to target database '{$dbname}'.\n";
        } catch (PDOException $ex) {
            echo "FAILED\nError: Could not connect or create database. Details: " . $ex->getMessage() . "\n";
            exit(1);
        }
    } else {
        echo "FAILED\nError: Could not connect to PostgreSQL server. Details: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// 5. Run Database Migration Schema
echo "5. Running SQL schema migrations... ";
$schemaFile = $root . '/scripts/schema.sql';
if (!file_exists($schemaFile)) {
    echo "FAILED\nError: schema.sql file not found at: {$schemaFile}\n";
    exit(1);
}

try {
    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);
    echo "OK (Tables created/verified)\n";
} catch (PDOException $e) {
    echo "FAILED\nError executing schema migration: " . $e->getMessage() . "\n";
    exit(1);
}

// 6. Create directories
echo "6. Ensuring storage directories exist...\n";
$storageDir = Env::get('STORAGE_DIR', 'storage');
$dirs = [
    $root . '/' . $storageDir,
    $root . '/' . $storageDir . '/uploads',
    $root . '/' . $storageDir . '/outputs',
    $root . '/' . $storageDir . '/archives',
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "   - Created: " . str_replace($root, '', $dir) . "\n";
        } else {
            echo "   - FAILED to create: " . str_replace($root, '', $dir) . "\n";
            exit(1);
        }
    } else {
        echo "   - Exists: " . str_replace($root, '', $dir) . "\n";
    }
}
echo "Storage directories: OK\n";

echo "\n=== Installation Completed Successfully! ===\n";
