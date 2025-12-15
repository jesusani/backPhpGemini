<?php
// tests/IntegrationTest.php

// Setup environment for testing
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/initializeDataBaseSchema.php';
require_once __DIR__ . '/../controllers/InvoiceController.php';

// Mock DB Connection for Testing (In-Memory)
$memoryDb = new PDO('sqlite::memory:');
$memoryDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setTestConnection($memoryDb);

// Explicit Reset for Clean State
$memoryDb->exec("DROP TABLE IF EXISTS vts_ledger");

// Initialize Schema
initializeDatabaseSchema();

echo "\nRunning MVC Integration Tests...\n";

// --- Helper Functions to simulate HTTP Requests ---
function simulatePostRequest($action, $data, $userId = 'TEST_USER_MVC') {
    $_GET['action'] = $action;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_USER_ID'] = $userId;
    
    // Mock input stream by intercepting file_get_contents('php://input')
    // Nota: Como file_get_contents solo lee input stream real, en un script CLI 
    // necesitamos modificar el controlador para aceptar datos inyectados O 
    // usar un wrapper. 
    // Para simplificar, instanciamos el Servicio directamente para pruebas de integración lógica
    // O re-estructuramos el Controller para ser testable sin php://input.
    // --> Haremos test directo al SERVICIO (ChainService) que es el core lógico.
}

// Direct Service Testing (More reliable in CLI than mocking php://input)
require_once __DIR__ . '/../services/ChainService.php';
$service = new ChainService();

// TEST 1: Register Normal Invoice
$input1 = [
    'type' => 'INITIAL',
    'concept' => 'MVC Test Invoice',
    'amount' => 1000.00,
    'recipientNIF' => 'A12345678',
    'recipientName' => 'Empresa Test S.L.'
];
$meta1 = ['authenticatedUserId' => 'TEST_USER_MVC'];

try {
    $result1 = $service->registerInvoice($input1, $meta1);
    echo ($result1['success'] === true && $result1['id'] === 1) ? "[PASS] Register Normal Invoice\n" : "[FAIL] Register Normal Invoice\n";
} catch (Exception $e) {
    echo "[FAIL] Exception: " . $e->getMessage() . "\n";
}

// TEST 2: Register Rectificative
$input2 = [
    'type' => 'RECTIFICATIVA',
    'concept' => 'MVC Rectification',
    'amount' => -100.00,
    'originalInvoiceId' => 1,
    'reason' => 'Error Correction' // or rectificationReason
];

try {
    $result2 = $service->registerInvoice($input2, $meta1);
    echo ($result2['success'] === true && $result2['id'] === 2) ? "[PASS] Register Rectificative Invoice\n" : "[FAIL] Register Rectificative\n";
} catch (Exception $e) {
    echo "[FAIL] Exception: " . $e->getMessage() . "\n";
}

// TEST 2.5: Get Invoice By ID
$fetchedInvoice = $service->getInvoiceById(1);
echo ($fetchedInvoice && $fetchedInvoice['id'] === 1 && $fetchedInvoice['previous_hash'] === str_repeat('0', 64)) 
    ? "[PASS] Get Invoice By ID (Model/Service)\n" 
    : "[FAIL] Get Invoice By ID\n";

// TEST 3: Integrity Check (Verify hash chaining)
$ledger = $service->getLedger();
$entry1 = $ledger[0];
$entry2 = $ledger[1];

$hashLinkValid = ($entry2['previous_hash'] === $entry1['current_hash']);
echo $hashLinkValid ? "[PASS] Chain Integrity (Hash Linking)\n" : "[FAIL] Chain Integrity Broken\n";

?>
