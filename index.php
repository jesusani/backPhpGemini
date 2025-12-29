<?php
// index.php - MVC Entry Point
require_once __DIR__ . '/config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

require_once __DIR__ . '/utils/Security.php';
Security::startSession();

// 1. Headers & CORS
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Controladores y enrutamiento
require_once __DIR__ . '/controllers/InvoiceController.php';
require_once __DIR__ . '/services/initializeDataBaseSchema.php';
initializeDatabaseSchema();

$action = $_GET['action'] ?? 'test';

// Si no es una imagen QR, enviamos JSON por defecto
if ($action !== 'qr') {
    header('Content-Type: application/json');
}

$controller = new InvoiceController();
$controller->handleRequest($action);
