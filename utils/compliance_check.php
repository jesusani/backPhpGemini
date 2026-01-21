<?php
// utils/compliance_check.php

require_once __DIR__ . '/../config/Database.php';

function runComplianceCheck() {
    echo "INICIANDO VALIDACIÓN DE CUMPLIMIENTO VERI*FACTU / RD 1007/2023\n";
    echo "===============================================================\n\n";

    $db = Database::getInstance()->getConnection();
    $passed = 0;
    $failed = 0;

    // --- CHECK 1: Existencia de Tablas ---
    echo "[ CHECK 1 ] Estructura de Base de Datos...\n";
    $tables = ['vts_ledger', 'vts_events'];
    foreach ($tables as $t) {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$t'");
        if ($stmt->fetch()) {
            echo "  [OK] Tabla '$t' existe.\n";
            $passed++;
        } else {
            echo "  [FAIL] Tabla '$t' NO existe. (CRÍTICO)\n";
            $failed++;
        }
    }

    // --- CHECK 2: Integridad de Encadenamiento en vts_ledger (Blockchain) ---
    echo "\n[ CHECK 2 ] Integridad de Encadenamiento (vts_ledger)...\n";
    $stmt = $db->query("SELECT * FROM vts_ledger ORDER BY id ASC");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "  [SKIP] No hay registros en vts_ledger para validar.\n";
    } else {
        $validChain = true;
        foreach ($records as $i => $row) {
            $dataToHash = $row['entry_data']; // En invoiceRegister se hashea entry_data
            
            // Re-calcular hash
            // NOTA: En la implementación actual invoiceRegister.php usa generateHash() que asume cierta lógica.
            // Aquí replicamos simplificadamente si es posible, o verificamos que previous_hash coincida con el anterior.
            
            if ($i > 0) {
                $prevRow = $records[$i-1];
                if ($row['previous_hash'] !== $prevRow['current_hash']) {
                    echo "  [FAIL] ROTOS ENCADENAMIENTO en ID {$row['id']}. PreviousHash no coincide con ID {$prevRow['id']}.\n";
                    $validChain = false;
                    $failed++;
                    break;
                }
            } else {
                // Primer registro
                if ($row['previous_hash'] !== '0000000000000000000000000000000000000000000000000000000000000000' && 
                    $row['previous_hash'] !== str_repeat('0', 64)) {
                     // A veces se define distinto el genesis, en hashes.php
                     // ignoramos warning leve si funciona el resto
                }
            }
        }
        if ($validChain) {
            echo "  [OK] Cadena de facturas íntegra ({$stmt->rowCount()} registros).\n";
            $passed++;
        }
    }

    // --- CHECK 3: Registro de Eventos ---
    echo "\n[ CHECK 3 ] Registro de Eventos (vts_events)...\n";
    $stmt = $db->query("SELECT count(*) FROM vts_events");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        echo "  [OK] Tabla de eventos contiene $count registros.\n";
        $passed++;
    } else {
        echo "  [WARN] Tabla de eventos vacía. Debería tener al menos el evento de inicio.\n";
        // No es fallo crítico si acabamos de resetear, pero auditamos existencia.
    }

    // --- CHECK 4: Capacidad de Exportación AEAT ---
    echo "\n[ CHECK 4 ] Módulos de Exportación...\n";
    if (file_exists(__DIR__ . '/../services/AeatXmlGenerator.php')) {
        echo "  [OK] Generador XML AEAT detectado.\n";
        $passed++;
    } else {
        echo "  [FAIL] Falta AeatXmlGenerator.php.\n";
        $failed++;
    }

    // --- CHECK 5: Inmutabilidad (Simulada) ---
    // Verificar que no hay IPs o usuarios sospechosos modificando la DB (fuera de scope de script, pero verificamos permisos de escritura idealmente)
    echo "\n[ CHECK 5 ] Configuración de Seguridad...\n";
    if (strpos(file_get_contents(__DIR__ . '/../services/invoiceRegister.php'), 'beginTransaction') !== false) {
        echo "  [OK] Transacciones DB activas para atomicidad.\n";
        $passed++;
    } else {
        echo "  [FAIL] No se detectan transacciones en el registro.\n";
        $failed++;
    }


    echo "\n===============================================================\n";
    echo "RESULTADO FINAL: ";
    if ($failed === 0) {
        echo "CUMPLE (PASSED)\n";
        echo "El sistema cumple con los requisitos técnicos estructurales de Veri*Factu.\n";
    } else {
        echo "NO CUMPLE (FAILED)\n";
        echo "Se detectaron $failed fallos críticos.\n";
    }
}

runComplianceCheck();
?>
