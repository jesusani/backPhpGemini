<?php
// services/ChainService.php
require_once __DIR__ . '/../models/LedgerModel.php';

class ChainService {
    private $model;

    public function __construct() {
        $this->model = new LedgerModel();
    }

    public function registerInvoice($inputData, $requestMeta) {
        // requestMeta: { authenticatedUserId, entryType }
        
        // 1. Prepare Data
        $nextId = $this->model->getNextId();
        $previousHash = $this->getPreviousHash();
        $timestamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
        
        $userId = $requestMeta['authenticatedUserId'];
        $entryType = $inputData['type'];

        // Trazabilidad y Metadatos
        $metadata = [
            'id' => $nextId,
            'date' => $timestamp,
            'type' => $entryType,
            'concept' => $inputData['concept'],
            'amount' => number_format((float)($inputData['amount']), 2, '.', ''),
            'originalInvoiceId' => $inputData['originalInvoiceId'] ?? null,
            'rectificationReason' => $inputData['reason'] ?? ($inputData['rectificationReason'] ?? null),
            'userId' => $userId, 
            'recipientNIF' => $inputData['recipientNIF'] ?? null,
            'recipientName' => $inputData['recipientName'] ?? null,
            'machine' => 'PHP_VTS_SERVER_1',
            'previousHash' => $previousHash,
            'machine' => 'PHP_VTS_SERVER_1',
            'previousHash' => $previousHash,
            'signatureKey' => $_ENV['VTS_SECRET_KEY'] ?? 'DEV_KEY_DEFAULT',
            // Agregamos campos extra si se requieren en el JSON hasheado
        ];

        $dataToHash = json_encode($metadata);
        $currentHash = $this->generateHash($dataToHash);
        $signatureProof = $currentHash; 
        
        // 2. Persist to DB
        $dbData = [
            'id' => $nextId,
            'current_hash' => $currentHash,
            'previous_hash' => $previousHash,
            'entry_data' => $dataToHash,
            'timestamp' => $timestamp,
            'invoice_id' => $nextId,
            'user_id' => $userId,
            'recipient_nif' => $metadata['recipientNIF'],
            'recipient_name' => $metadata['recipientName'],
            'machine_id' => $metadata['machine'],
            'signature_proof' => $signatureProof,
            'rectification_reason' => $metadata['rectificationReason'],
            'original_invoice_id' => $metadata['originalInvoiceId'],
            'message' => 'Registro Exitoso',
            'machine' => $metadata['machine'],
            'signature_key' => $metadata['signatureKey']
        ];

        if ($this->model->createEntry($dbData)) {
            return [
                'success' => true,
                'id' => $nextId,
                'invoiceId' => $nextId,
                'currentHash' => $currentHash,
                'message' => "Asiento registrado y encadenado con éxito.",
                'userId' => $userId
            ];
        } else {
            throw new Exception("Error al guardar en base de datos.");
        }
    }

    public function generateHash($data) {
        return hash('sha256', $data);
    }

    private function getPreviousHash() {
        $last = $this->model->getLastEntry();
        return $last ? $last['current_hash'] : str_repeat('0', 64);
    }
    
    public function getLedger() {
        return $this->model->getAll();
    }

    public function getInvoiceById($id) {
        return $this->model->getById($id);
    }
}
?>
