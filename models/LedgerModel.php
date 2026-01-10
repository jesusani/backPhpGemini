<?php
// models/LedgerModel.php
require_once __DIR__ . '/../config/Database.php';

class LedgerModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getNextId() {
        $stmt = $this->conn->query("SELECT MAX(id) AS max_id FROM vts_ledger");
        return (int)$stmt->fetchColumn() + 1;
    }

    public function getLastEntry() {
        $stmt = $this->conn->query("SELECT current_hash FROM vts_ledger ORDER BY id DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createEntry($data) {
        $sql = "INSERT INTO vts_ledger 
                (id, current_hash, previous_hash, entry_data, timestamp, invoice_id, user_id, recipient_nif, recipient_name, machine_id, signature_proof,
                 rectification_reason, original_invoice_id, message, machine, signature_key)
                VALUES (:id, :current_hash, :previous_hash, :entry_data, :timestamp, :invoice_id, :user_id, :recipient_nif, :recipient_name, :machine_id, :signature_proof,
                 :rectification_reason, :original_invoice_id, :message, :machine, :signature_key)";
        
        $stmt = $this->conn->prepare($sql);
        
        // Asignamos valores default para evitar el error de parámetro faltante
        $params = [
            ':id' => $data['id'],
            ':current_hash' => $data['current_hash'],
            ':previous_hash' => $data['previous_hash'],
            ':entry_data' => $data['entry_data'],
            ':timestamp' => $data['timestamp'],
            ':invoice_id' => $data['invoice_id'],
            ':user_id' => $data['user_id'],
            ':recipient_nif' => $data['recipient_nif'] ?? 'UNKNOWN',
            ':recipient_name' => $data['recipient_name'] ?? 'UNKNOWN',
            ':machine_id' => $data['machine_id'],
            ':signature_proof' => $data['signature_proof'],
            ':rectification_reason' => $data['rectification_reason'] ?? 'N/A',
            ':original_invoice_id' => $data['original_invoice_id'] ?? 0,
            ':message' => $data['message'] ?? 'Registro API',
            ':machine' => $data['machine'], 
            ':signature_key' => $data['signature_key']
        ];

        return $stmt->execute($params);
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM vts_ledger ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM vts_ledger WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLastIdByYear($year) {
        // SQLite uses strftime or substr for dates stored as strings
        // timestamp format is likely 'Y-m-d ...'
        // We use LIKE for simplicity if format is YYYY-MM-DD...
        $pattern = $year . '%';
        $stmt = $this->conn->prepare("SELECT MAX(id) as max_id FROM vts_ledger WHERE timestamp LIKE :pattern");
        $stmt->execute([':pattern' => $pattern]);
        $result = $stmt->fetchColumn();
        return $result ? (int)$result : 0;
    }
}
