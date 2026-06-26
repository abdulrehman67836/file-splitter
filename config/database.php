<?php
use App\Core\Env;

// Make sure the env is loaded relative to this file
Env::load(dirname(__DIR__) . '/.env');

return [
    'host'     => Env::get('DB_HOST'),
    'port'     => Env::get('DB_PORT'),
    'database' => Env::get('DB_DATABASE'),
    'username' => Env::get('DB_USERNAME'),
    'password' => Env::get('DB_PASSWORD'),
];
