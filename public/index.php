<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Use /tmp for writable directories
$tmpStoragePath = '/tmp/storage';
if (!file_exists($tmpStoragePath)) {
    mkdir($tmpStoragePath, 0777, true);
}

// Modify maintenance mode check
$maintenanceFile = $tmpStoragePath . '/framework/maintenance.php';
if (file_exists($maintenanceFile)) {
    require $maintenanceFile;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request with custom storage path
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Set custom storage path
$app->useStoragePath($tmpStoragePath);

// Handle the request
$app->handleRequest(Request::capture());