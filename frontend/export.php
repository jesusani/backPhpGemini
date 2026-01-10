<?php
// frontend/export.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Environment
require_once __DIR__ . '/../config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/../.env');

require_once __DIR__ . '/../controllers/InvoiceController.php';

session_start();
if (!isset($_SESSION['registrado']) || $_SESSION['registrado'] != 'true') {
    die("No autorizado");
}

// Instantiate Controller directly
$controller = new InvoiceController();

// Simulate parameters if needed
$_SERVER['REQUEST_METHOD'] = 'GET';

// Handle Export Request
$controller->handleRequest('export');
?>
