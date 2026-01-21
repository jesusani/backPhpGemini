<?php

require_once __DIR__ . '/../config/Database.php';

class EventService {
    
    /**
     * Registra un nuevo evento en el Registro de Eventos (vts_events).
     * Garantiza el encadenamiento criptográfico con el evento anterior.
     * 
     * @param string $eventType Tipo de evento (BOOT, SHUTDOWN, ERROR, ALTA_FACTURA, ETC)
     * @param string $description Descripción legible humana.
     * @param array $details Detalles técnicos opcionales (array que se convertirá a JSON).
     * @param string $userId Usuario que origina el evento (o SYSTEM).
     * @return int ID del evento registrado.
     */
    public function logEvent(string $eventType, string $description, array $details = [], string $userId = 'SYSTEM'): int {
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Obtener Hash Anterior
            $stmt = $db->query("SELECT current_hash FROM vts_events ORDER BY id DESC LIMIT 1");
            $lastHash = $stmt->fetchColumn();
            
            // Si es el primer evento, el hash previo es ceros
            $previousHash = $lastHash ? $lastHash : str_repeat('0', 64);
            
            // 2. Preparar Datos
            $timestamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
            $detailsJson = json_encode($details);
            
            // 3. Calcular Hash Actual
            // Payload para hash: PreviousHash + EventType + Timestamp + UserId + Details
            $dataToHash = $previousHash . $eventType . $timestamp . $userId . $detailsJson;
            $currentHash = hash('sha256', $dataToHash);
            
            // 4. Insertar
            $sql = "INSERT INTO vts_events (event_type, description, details, timestamp, user_id, previous_hash, current_hash) 
                    VALUES (:type, :desc, :det, :ts, :uid, :ph, :ch)";
            
            $insert = $db->prepare($sql);
            $insert->execute([
                ':type' => $eventType,
                ':desc' => $description,
                ':det' => $detailsJson,
                ':ts' => $timestamp,
                ':uid' => $userId,
                ':ph' => $previousHash,
                ':ch' => $currentHash
            ]);
            
            $id = $db->lastInsertId();
            $db->commit();
            
            return (int)$id;

        } catch (Exception $e) {
            $db->rollBack();
            // En un sistema real, si falla el log de eventos, el sistema debería detenerse (PANIC).
            error_log("CRITICAL: FAILED TO LOG VERIFACTU EVENT: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene los últimos eventos.
     */
    public function getRecentEvents($limit = 50) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM vts_events ORDER BY id DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
