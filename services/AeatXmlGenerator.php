<?php

class AeatXmlGenerator {

    /**
     * Genera el XML de "Alta de Factura" según el formato VeriFactu (borrador técnico).
     * @param array $records Lista de registros de la tabla vts_ledger.
     * @param string $nifEmisor NIF de la entidad emisora (obligatorio en cabecera).
     * @return string XML formateado.
     */
    public function generateAltaFactuXml(array $records, string $nifEmisor = 'A12345678') {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $issuer_name = $_ENV['ISSUER_NAME'];
        $issuer_cif = $_ENV['ISSUER_CIF'];
        $issuer_machine_id = $_ENV['ISSUER_MACHINE_ID'];
        $issuer_version = $_ENV['ISSUER_VERSION'];

        // Namespace placeholder (estos cambian según la versión final de la orden ministerial)
        $nsSii = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
        $nsSiiL = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

        // Elemento Raíz: AltaFactu
        $root = $dom->createElementNS($nsSii, 'psi:AltaFactu');
        $root->setAttribute('xmlns:psi', $nsSii);
        $root->setAttribute('xmlns:sii', $nsSiiL);
        $dom->appendChild($root);

        // --- 1. CABECERA ---
        $cabecera = $dom->createElement('Cabecera');
        
        // IDVersion (Ej: 1.0)
        $cabecera->appendChild($dom->createElement('IDVersion', $issuer_version));
    
        // Titular (Emisor)
        $titular = $dom->createElement('Titular');
        $titular->appendChild($dom->createElement('NombreRazon', $issuer_name));
        $titular->appendChild($dom->createElement('NIF', $issuer_cif));
        $cabecera->appendChild($titular);

        // TipoComunicacion (A0: Alta de Factura)
        $cabecera->appendChild($dom->createElement('TipoComunicacion', 'A0'));
        $root->appendChild($cabecera);

        // --- 2. LISTA DE REGISTROS DE FACTURA ---
        foreach ($records as $record) {
            $registroAlta = $dom->createElement('RegistroAlta');

            // --- 2.1 ID Factura ---
            $idFactura = $dom->createElement('IDFactura');
            // NumSerieFactura: Usamos el ID o invoice_id
            $numSerie = $record['invoice_id'] ?? $record['id'];
            $idFactura->appendChild($dom->createElement('NumSerieFactura', $numSerie));
            // FechaExpedicionFactura: Fecha de emisión (YYYY-MM-DD)
            // La fecha en DB es ISO8601, ej: 2026-01-14T10:00:00...
            $fechaEmision = substr($record['timestamp'], 0, 10); 
            $idFactura->appendChild($dom->createElement('FechaExpedicionFactura', $fechaEmision));
            $registroAlta->appendChild($idFactura);

            // --- 2.2 Datos Factura ---
            // Decodificar entry_data JSON para obtener detalles
            $entryData = json_decode($record['entry_data'], true);
            
            $importeTotal = $entryData['amount'] ?? 0.00;
            $descripcion = $entryData['concept'] ?? 'Servicios prestados';

            // Datos Generales
            $registroAlta->appendChild($dom->createElement('DescripcionOperacion', substr($descripcion, 0, 250)));
            $registroAlta->appendChild($dom->createElement('FacturaSimplificada', 'S')); // S/N simplificada
            $registroAlta->appendChild($dom->createElement('ImporteTotal', $importeTotal));
            
            // --- 2.3 Destinatario (Si existe) ---
            if (!empty($record['recipient_nif'])) {
                $contraparte = $dom->createElement('Contraparte');
                $contraparte->appendChild($dom->createElement('NombreRazon', $record['recipient_name'] ?? 'Cliente'));
                $contraparte->appendChild($dom->createElement('NIF', $record['recipient_nif']));
                $registroAlta->appendChild($contraparte);
            }

            // --- 2.4 Encadenamiento (VeriFactu Core) ---
            $encadenamiento = $dom->createElement('EncadenamientoRegistroAnterior');
            
            // Si hay hash previo (no es la primera factura)
            if (!empty($record['previous_hash'])) {
                // Se debería incluir ID y Fecha de la anterior, aquí simplificamos con el Hash
                // En especificaciones reales piden: IDEmisor, NumSerie, Fecha del anterior.
                // Usamos el hash como placeholder de integridad.
                $encadenamiento->appendChild($dom->createElement('HuellaRegistroAnterior', $record['previous_hash']));
            } else {
                 $encadenamiento->appendChild($dom->createElement('PrimerRegistro', 'S'));
            }
            $registroAlta->appendChild($encadenamiento);

            // --- 2.5 Sistema Informático ---
            $sistema = $dom->createElement('SistemaInformatico');
            $sistema->appendChild($dom->createElement('NombreRazon', $issuer_name));
            $sistema->appendChild($dom->createElement('ID', $record['machine_id'] ?? $issuer_machine_id));
            $sistema->appendChild($dom->createElement('Version', $issuer_version));
             // NumeroRegistro (si aplica)
            $registroAlta->appendChild($sistema);

            // --- 2.6 Huella del Registro (Hash actual) ---
            $registroAlta->appendChild($dom->createElement('Huella', $record['current_hash']));

            // --- 2.7 Firma (Si aplica) ---
            $registroAlta->appendChild($dom->createElement('Signature', $record['signature_proof']));

            $root->appendChild($registroAlta);
        }

        return $dom->saveXML();
    }
}
?>
