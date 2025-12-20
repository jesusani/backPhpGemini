<?php

 /**
     * Obtiene el listado completo de asientos registrados en la tabla vts_ledger.
     * @return array Arreglo de registros.
     */
    function invoicesList(): array
    {
        global $db;
        $stmt =  $db->query("SELECT * FROM vts_ledger ORDER BY id ASC");
        // echo "Listing all VTS ledger entries:\n";
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>
