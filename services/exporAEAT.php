<?php

 /**
     * Endpoint de la API REST para la exportación a AEAT (simulado).
     * @return string JSON de los registros.
     */
function exportForAEAT(): string
    {

        require_once __DIR__ . '/AeatXmlGenerator.php';
        
        global $db;
        $stmt = $db->query("SELECT * FROM vts_ledger ORDER BY id ASC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $generator = new AeatXmlGenerator();
        // Generamos el XML real
        return $generator->generateAltaFactuXml($records, 'B99999999'); 

    }

?>
