<?php
/**
 * VTS API Update Test Suite (PHP)
 * Ejecuta pruebas de integración sobre los servicios REALES (services/*.php).
 */

// 1. Configuración del Entorno de Prueba
const INTERNAL_SIGNATURE_KEY = "VTS_AEAT_SECRET_KEY_2024";
// Usamos DB en memoria para no afectar la producción
const DB_CONNECTION_STRING = "sqlite::memory:"; 

// 2. Inicialización de Base de Datos Global
try {
    $db = new PDO(DB_CONNECTION_STRING);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Test Error: No se pudo crear DB en memoria: " . $e->getMessage());
}

// 3. Carga de Servicios Reales
// Intentamos cargar desde la estructura de directorios relativa
$servicesDir = __DIR__ . '/services/';

require_once $servicesDir . 'hashes.php';
require_once $servicesDir . 'initializeDataBaseSchema.php';
require_once $servicesDir . 'invoiceRegister.php';
require_once $servicesDir . 'verify.php';
require_once $servicesDir . 'invoicesList.php';
require_once $servicesDir . 'exporAEAT.php';

// Inicializar tabla
initializeDatabaseSchema();

// 4. Utilerías de Test
function testResult(string $testName, bool $passed, $info = "") {
    $status = $passed ? "PASSED" : "FAILED";
    $color = $passed ? "\033[32m" : "\033[31m";
    echo "{$color}[{$status}]\033[0m {$testName} {$info}\n";
}

echo "\n================================================\n";
echo "       INICIO DE PRUEBAS DE INTEGRACIÓN       \n";
echo "================================================\n";

$userId = 'TEST_USER_001';

// ======================================================
// TEST 1: Registro de Facturas (Happy Path)
// ======================================================

// Factura 1
$data1 = [
    'concept' => 'Licencia Software', 
    'amount' => 500.00,
    'recipientNIF' => '12345678',
    'recipientName' => 'John Doe',
    'rectificationReason' => 'Factura original'
    ];
$res1 = invoiceRegister($data1, 'INITIAL', $userId);
$pass1 = isset($res1['success']) && $res1['success'] === true && $res1['id'] === 1;
testResult("1. Registro Factura #1", $pass1);

// Factura 2
$data2 = ['concept' => 'Soporte Técnico', 'amount' => 150.00];
$res2 = invoiceRegister($data2, 'INITIAL', $userId);
$pass2 = isset($res2['success']) && $res2['success'] === true && $res2['id'] === 2;
testResult("2. Registro Factura #2", $pass2);

// Factura 3 (Rectificativa de la 1)
$data3 = ['concept' => 'Devolución Parcial', 'amount' => -50.00, 'reason' => 'Descuento aplicado tarde'];
$res3 = invoiceRegister($data3, 'RECTIFICATIVA', $userId, 1);
$pass3 = isset($res3['success']) && $res3['success'] === true && $res3['id'] === 3;
testResult("3. Registro Factura #3 (Rectificativa)", $pass3);

// ======================================================
// TEST 2: Verificación de Integridad (Cadena Correcta)
// ======================================================

$isIntegrityValid = verifyChainIntegrity();
testResult("4. Verificación de Integridad (Estado Correcto)", $isIntegrityValid === true);

// ======================================================
// TEST 3: Detección de Manipulación (Tampering)
// ======================================================
echo "\n--- Simulando ataque de manipulación (Hack) ---\n";

// Intentamos modificar el importe de la Factura #2 directamente en la DB
// Esto debería romper el hash de la factura #2 O la cadena con la #3
$stmt = $db->prepare("UPDATE vts_ledger SET entry_data = REPLACE(entry_data, '150.00', '9000.00') WHERE id = 2");
$stmt->execute();

// Verificamos de nuevo
$isIntegrityValidTampered = verifyChainIntegrity();
testResult("5. Detección de Manipulación", $isIntegrityValidTampered === false, "(Debe fallar si detecta el hack)");

// Restauramos para seguir pruebas (o reiniciamos DB, pero aqui terminamos logica de hack)
// ======================================================
// TEST 4: Exportación y Listado
// ======================================================

// Limpiamos y reiniciamos para probar exportación limpia
$db->exec("DELETE FROM vts_ledger");
// Reset sequence (en sqlite::memory basta con borrar tabla o drop, pero aqui reinserteramos 1)
initializeDatabaseSchema(); // Re-create if needed or just empty
// Mejor reiniciamos inserciones validas
invoiceRegister(['concept'=>'A',
 'amount'=>10,
 'rectificationReason'=>'Factura original',
 'recipientNIF'=>'12345678',
 'recipientName'=>'John Doe',
 'rectificationReason'=>'Factura original',
 'originalInvoiceId'=>1,
 'invoiceId'=>2,
 'message'=>'Factura original',
 'machine'=>'TEST_MACHINE_001',
 'signatureKey'=>'TEST_SIGNATURE_KEY_001'],
 'INITIAL', $userId);
 
invoiceRegister(['concept'=>'B',
 'amount'=>20,
 'rectificationReason'=>'Factura original',
 'recipientNIF'=>'12345678',
 'recipientName'=>'John Doe',
 'rectificationReason'=>'Factura original',
 'originalInvoiceId'=>1,
 'invoiceId'=>2,
 'message'=>'Factura original',
 'machine'=>'TEST_MACHINE_001',
 'signatureKey'=>'TEST_SIGNATURE_KEY_001'],
 'INITIAL', $userId);

$list = invoicesList();
$passList = count($list) === 2;
testResult("6. Listado de Facturas", $passList, "Count: " . count($list));

$exportJson = exportForAEAT();
$exportData = json_decode($exportJson, true);
$passExport = isset($exportData['totalRecords']) && $exportData['totalRecords'] === 2;
testResult("7. Exportación AEAT", $passExport);


echo "\n================================================\n";
echo "          FIN DE PRUEBAS DE INTEGRACIÓN       \n";
echo "================================================\n";
?>