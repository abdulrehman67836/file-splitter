<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Database;

try {
    Env::load(dirname(__DIR__) . '/.env');
    
    echo "Connecting to PostgreSQL database...\n";
    $conn = Database::getConnection();
    echo "Connection: SUCCESSFUL\n\n";
    
    echo "Querying public schema tables...\n";
    $tables = Database::fetchAll("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name ASC
    ");
    
    if (empty($tables)) {
        echo "RESULT: No tables found in the database. Schema has NOT migrated yet.\n";
    } else {
        echo "RESULT: Schema migration is SUCCESSFUL. Found " . count($tables) . " tables:\n";
        foreach ($tables as $index => $table) {
            echo "   [" . ($index + 1) . "] Table Name: " . $table['table_name'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "RESULT: CONNECTION FAILED\n";
    echo "Error Details: " . $e->getMessage() . "\n";
}
