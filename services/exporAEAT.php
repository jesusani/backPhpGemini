<?php

 /**
     * Endpoint de la API REST para la exportación a AEAT (simulado).
     * @return string JSON de los registros.
     */
function exportForAEAT(): string
    {
        global $db;
        $stmt = $db->query("SELECT * FROM vts_ledger ORDER BY id ASC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // echo "<script>console.log('exportForAEAT called');</script>";
        // En un entorno real, aquí se formatearían los datos al estándar XML/JSON de la AEAT
        return json_encode([
            'exportDate' => (new DateTime())->format('Y-m-d H:i:s'),
            'totalRecords' => count($records),
            'vtsRecords' => $records
        ], JSON_PRETTY_PRINT); 
        
    }

?>