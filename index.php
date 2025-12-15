<?php
// index.php - MVC Entry Point
require_once __DIR__ . '/config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

require_once __DIR__ . '/utils/Security.php';
Security::startSession();

// 1. Headers & CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Autoloading (Simple manual require for now)
require_once __DIR__ . '/controllers/InvoiceController.php';
require_once __DIR__ . '/services/initializeDataBaseSchema.php'; // Legacy schema init for now

// 3. Initialize DB Schema (Ensures valid state)
initializeDatabaseSchema();

// 4. Routing
$action = $_GET['action'] ?? 'test';

$controller = new InvoiceController();
$controller->handleRequest($action);

?>