<?php
/**
 * Verifica la integridad de la cadena VTS.
 * @return bool Verdadero si la cadena es íntegra, falso si se detectan manipulaciones.
 */

// Si este archivo se ejecuta desde services/ o index, ajustar inclusion.
// Asumimos execution desde root para los tests/index.
// include_once './services/hashes.php'; // Se asume cargado o se carga aqui

function verifyChainIntegrity(): bool { 
    global $db;
    
    // Asegurar que generateHash esté disponible
    if (!function_exists('generateHash')) {
         // Intentar cargar hashes.php si no está cargado.
         // Rutas relativas pueden variar dependiendo de quien invoca.
         if (file_exists(__DIR__ . '/hashes.php')) require_once __DIR__ . '/hashes.php';
         else if (file_exists('./services/hashes.php')) require_once './services/hashes.php';
    }

    $stmt = $db->query("SELECT * FROM vts_ledger ORDER BY id ASC");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // echo "Iniciando verificación de integridad. Asientos: " . count($invoices) . "\n";
    
    if($invoices === false) {
        error_log("Error al obtener los asientos de la base de datos.");
        return false;
    }   
    if (count($invoices) === 0) return true;

    for ($i = 0; $i < count($invoices); $i++) {
        $currentEntry = $invoices[$i];
        
        // 1. Verificar Encadenamiento (Hash Previo)
        // El primer registro (indice 0) debe tener hash previo de 64 ceros.
        // Los siguientes deben coincidir con el current_hash del anterior.
        $expectedPreviousHash = ($i === 0) ? str_repeat('0', 64) : $invoices[$i - 1]['current_hash'];
        
        if ($currentEntry['previous_hash'] !== $expectedPreviousHash) {
            error_log("ERROR: Fallo de encadenamiento en registro ID {$currentEntry['id']}. Hash previo incorrecto.");
            return false;
        }

        // 2. Recalcular Hash para verificar Inviolabilidad
        // Reconstruimos la data EXACTA que se usó para hashear.
        // NOTA: vts_ledger almacena 'entry_data' que es el JSON original hasheado. 
        // Idealmente deberíamos hashear ese entry_data directamente, pero invoiceRegister 
        // hashea json_encode($metadata).
        // Si vts_ledger guarda el entry_data TAL CUAL se hasheó, validamos con eso.
        // Verificamos 'entry_data' en invoiceRegister:
        // $stmt = ... VALUES (..., :entry_data, ...) donde :entry_data es $dataToHash.
        // Entonces podemos usar $currentEntry['entry_data'] directamente.
        
        $dataToHash = $currentEntry['entry_data'];
        $reCalculatedHash = generateHash($dataToHash); 

        if ($currentEntry['current_hash'] !== $reCalculatedHash) {
            error_log("ERROR: Fallo de hash en registro ID {$currentEntry['id']}. Dato manipulado o hash no coincide.");
            // Debug:
            // error_log("Stored: " . $currentEntry['current_hash']);
            // error_log("Calculated: " . $reCalculatedHash);
            return false;
        }
    }
    return true;
}
?>
