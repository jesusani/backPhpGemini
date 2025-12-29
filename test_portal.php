<?php
// facturas/test_portal.php
require_once __DIR__ . '/config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

require_once __DIR__ . '/services/ChainService.php';
require_once __DIR__ . '/utils/Security.php';

echo "<h1>Test de Generación de Portal de Paciente</h1>";

$service = new ChainService();

// 1. Crear una factura de prueba con los nuevos campos de sesión
$testData = [
    'type' => 'INITIAL',
    'concept' => 'Sesión de Fisioterapia (Test Portal)',
    'amount' => 47.00,
    'recipientNIF' => '12345678Z',
    'recipientName' => 'PACIENTE DE PRUEBA PORTAL',
    'fechacita' => '2025/12/29',
    'horacita' => '11:30'
];

$meta = [
    'authenticatedUserId' => 'test-admin',
    'entryType' => 'INITIAL'
];

try {
    echo "<p>Registrando factura de prueba con datos de sesión...</p>";
    $result = $service->registerInvoice($testData, $meta);
    
    if ($result['success']) {
        $id = $result['id'];
        $currentHash = $result['currentHash'];
        
        // 2. Generar la firma de seguridad para el enlace público
        $signature = Security::generatePublicSignature($id, $currentHash);
        
        $portalUrl = "portal.php?id=$id&s=$signature";
        
        echo "<div style='background:#d4edda; color:#155724; padding:20px; border-radius:8px; border:1px solid #c3e6cb;'>";
        echo "<h3>¡Éxito! Factura #" . $id . " creada.</h3>";
        echo "<p>Hash Generado: <small><code>$currentHash</code></small></p>";
        echo "<hr>";
        echo "<p><b>Enlace que el paciente vería al escanear el QR:</b></p>";
        echo "<a href='$portalUrl' target='_blank' style='display:inline-block; background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Abrir Portal del Paciente (Simulación QR)</a>";
        echo "<p style='margin-top:10px; font-size:12px; color:#666;'>URL: " . $portalUrl . "</p>";
        echo "</div>";
        
        echo "<p style='margin-top:20px;'><b>Verificación de campos nuevos:</b><br>";
        $checkInvoice = $service->getInvoiceById($id);
        $entryData = json_decode($checkInvoice['entry_data'], true);
        echo "Fecha Sesión en metadatos: " . ($entryData['fechacita'] ?? '<span style="color:red">No encontrada</span>') . "<br>";
        echo "Hora Sesión en metadatos: " . ($entryData['horacita'] ?? '<span style="color:red">No encontrada</span>') . "</p>";
        
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>Error en el test: " . $e->getMessage() . "</div>";
}
