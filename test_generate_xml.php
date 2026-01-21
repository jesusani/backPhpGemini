<?php

require_once './services/exporAEAT.php';

// Setup DB Connection mimicking vts_api_service.php
const DB_CONNECTION_STRING = "sqlite:" . __DIR__ . "/vts_ledger.sqlite"; 

try {
    $db = new PDO(DB_CONNECTION_STRING);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Generando XML para AEAT...\n";
    
    // Call the export function
    $xmlContent = exportForAEAT();
    
    // Save to file
    $filename = 'aeat_output_test.xml';
    file_put_contents($filename, $xmlContent);
    
    echo "XML generado correctamente en: " . __DIR__ . DIRECTORY_SEPARATOR . $filename . "\n";
    echo "---------------------------------------------------\n";
    echo "Contenido del XML (primeros 500 caracteres):\n";
    echo htmlspecialchars(substr($xmlContent, 0, 500)) . "...\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
