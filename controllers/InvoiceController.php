<?php
// controllers/InvoiceController.php
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../services/ChainService.php';
require_once __DIR__ . '/../utils/Security.php';

class InvoiceController {
    private $service;
    private $injectedInput = null;

    public function __construct() {
        $this->service = new ChainService();
    }

    public function handleRequest($action, $input = null) {
        $this->injectedInput = $input; // Allow injecting data for internal calls
        
        // Enrutamiento simple interno del controlador
        switch ($action) {
            case 'register':
                $this->register();
                break;
            case 'list':
            case 'fetch_ledger':
                $this->list();
                break;
            case 'get':
                $this->get();
                break;
            case 'qr':
                $this->qr();
                break;
            case 'download':
                $this->download();
                break;
            case 'test':
                $this->test();
                break;
            case 'verify':
                // Nota: podemos mover la lógica de verify.php al servicio también refactorizando
                // Por ahora retornamos mensaje placeholder si no migramos todo verify.php
                echo json_encode(['message' => 'Endpoint en refactorización (MVC). Use el endpoint clásico por ahora si falla.']);
                break;
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Acción no encontrada']);
                break;
        }
    }

    private function register() {
        // Validation: If no injected input, require POST
        if ($this->injectedInput === null && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $input = $this->injectedInput ?? json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // VALIDACIÓN DE SEGURIDAD (CSRF & Sanitización)
        // 1. Sanitizar entrada
        $input = Security::sanitizeInput($input);

        // 2. Verificar CSRF (Si estamos en contexto web o se envía token)
        // Si hay una sesión activa con token, exigimos que coincida.
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['csrf_token'])) {
            if (empty($input['csrf_token']) || !Security::validateCsrfToken($input['csrf_token'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Error de seguridad: Token CSRF inválido o faltante.']);
                return;
            }
        }

        // VALIDACIÓN LÓGICA (Campos)
        $errors = Validator::validateInvoiceInput($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => 'Validación fallida', 'details' => $errors]);
            return;
        }

        // Si pasa validación, procesar
        try {
            // Simulamos autenticación
            $userId = $_SERVER['HTTP_X_USER_ID'] ?? 'anonymous';
            if ($userId === 'anonymous') {
                 http_response_code(401);
                 echo json_encode(['error' => 'Usuario no autenticado']);
                 return;
            }

            $meta = ['authenticatedUserId' => $userId];
            $result = $this->service->registerInvoice($input, $meta);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function list() {
        $ledger = $this->service->getLedger();
        echo json_encode(['message' => 'Listado MVC', 'records' => $ledger]);
    }

    private function test() {
         echo json_encode(['message' => 'VTS API (MVC) Operational']);
    }

    private function get() {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or Invalid ID']);
            return;
        }

        $invoice = $this->service->getInvoiceById((int)$id);
        if ($invoice) {
            echo json_encode(['success' => true, 'invoice' => $invoice]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Invoice Not Found']);
        }
    }

    private function qr() {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            die("ID inválido");
        }

        $invoice = $this->service->getInvoiceById((int)$id);
        if (!$invoice) {
            http_response_code(404);
            die("Factura no encontrada");
        }

        $meta = json_decode($invoice['entry_data'], true);
        
        // Generar enlace al Portal Público con firma de seguridad
        $signature = Security::generatePublicSignature($id, $invoice['current_hash']);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        
        // Asumimos que portal.php está en la raíz de /facturas/
        $qrData = $protocol . $host . dirname($_SERVER['SCRIPT_NAME']) . "/portal.php?id=" . $id . "&s=" . $signature;
        
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qrData) . "&qzone=1";
        
        // Limpiamos cualquier salida previa (espacios en blanco, etc)
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: image/png');
        header('Content-Length: ' . $this->getRemoteFileSize($qrUrl));
        readfile($qrUrl);
        exit;
    }

    private function getRemoteFileSize($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        return $size;
    }

    private function download() {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
             http_response_code(400);
             echo "ID inválido"; 
             return;
        }

        $invoice = $this->service->getInvoiceById((int)$id);
        if (!$invoice) {
            http_response_code(404);
            echo "Factura no encontrada";
            return;
        }

        // Decode entry_data to avoid double-encoding
        if (isset($invoice['entry_data']) && is_string($invoice['entry_data'])) {
            $invoice['entry_data'] = json_decode($invoice['entry_data'], true);
        }

        // Force Download Headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="factura_vts_' . $id . '.json"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo json_encode($invoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
