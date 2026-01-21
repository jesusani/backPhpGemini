<?php
// controllers/InvoiceController.php
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../services/ChainService.php';
require_once __DIR__ . '/../utils/Security.php';

// Ensure Composer autoloader is loaded
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
use Dompdf\Dompdf;
use Dompdf\Options;

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
            case 'export':
                $this->export();
                break;
            case 'test':
                $this->test();
                break;
            case 'verify':
                // Nota: podemos mover la lógica de verify.php al servicio también refactorizando
                // Por ahora retornamos mensaje placeholder si no migramos todo verify.php
                echo json_encode(['message' => 'Endpoint en refactorización (MVC). Use el endpoint clásico por ahora si falla.']);
                break;
            case 'last_invoice_number':
                $this->lastInvoiceNumber();
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
        
        file_put_contents(__DIR__ . '/../../debug_vts.log', date('Y-m-d H:i:s') . " Register Input: " . print_r($input, true) . "\n", FILE_APPEND);

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
            file_put_contents(__DIR__ . '/../../debug_vts.log', date('Y-m-d H:i:s') . " Register Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
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

    private function lastInvoiceNumber() {
        $year = $_GET['year'] ?? date('Y');
        // Simple security check if needed, but this is a read-only op mostly
        
        try {
            $lastId = $this->service->getLastInvoiceIdByYear($year);
            echo json_encode(['success' => true, 'year' => $year, 'last_invoice_id' => $lastId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function export() {
        $format = $_GET['format'] ?? 'xml';
        $ledger = $this->service->getLedger();

        if ($format === 'aeat') {
            require_once __DIR__ . '/../services/AeatXmlGenerator.php';
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename="facturas_aeat_' . date('Y-m-d') . '.xml"');
            
            $generator = new AeatXmlGenerator();
            $nif = $_ENV['ISSUER_NIT'] ?? 'B99999999'; // Fallback to placeholder
            echo $generator->generateAltaFactuXml($ledger, $nif); 
            exit;
        } elseif ($format === 'sql') {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="facturas_vts_' . date('Y-m-d') . '.sql"');
            
            echo "-- Export of vts_ledger table\n";
            echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($ledger as $row) {
                // Escape values for SQL
                $values = array_map(function($value) {
                    if ($value === null) return 'NULL';
                    // Basic escaping for SQLite/MySQL - replace single quotes
                    return "'" . str_replace("'", "''", $value) . "'";
                }, $row);
                
                // Construct INSERT statement
                $columns = implode(", ", array_keys($row));
                $vals = implode(", ", $values);
                
                echo "INSERT INTO vts_ledger ($columns) VALUES ($vals);\n";
            }
        } elseif ($format === 'xml') {
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename="facturas_vts_' . date('Y-m-d') . '.xml"');
            
            $xml = new SimpleXMLElement('<invoices/>');
            
            foreach ($ledger as $row) {
                $inv = $xml->addChild('invoice');
                foreach ($row as $key => $value) {
                    $inv->addChild($key, $value ?? '');
                }
            }
            
            echo $xml->asXML();
        } elseif ($format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="facturas_vts_' . date('Y-m-d') . '.csv"');
            
            // Add BOM for Excel UTF-8 compatibility
            echo "\xEF\xBB\xBF";
            
            $output = fopen('php://output', 'w');
            
            // Headers
            if (!empty($ledger)) {
                // Use semicolon for Spanish Excel compatibility
                fputcsv($output, array_keys($ledger[0]), ';');
            }
            
            foreach ($ledger as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        } elseif ($format === 'pdf') {
            // PDF Generation using Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Courier');
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            
            // Build HTML for PDF
            $html = '<html><head><style>
                        body { font-family: sans-serif; font-size: 10pt; }
                        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        h1 { color: #333; }
                     </style></head><body>';
            
            $html .= '<h1>Facturas VTS VeriFactu</h1>';
            $html .= '<p>Generado el: ' . date('d/m/Y H:i:s') . '</p>';
            $html .= '<table><thead><tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Importe</th>
                        <th>NIF Recip.</th>
                      </tr></thead><tbody>';
                      
            foreach ($ledger as $row) {
                $meta = json_decode($row['entry_data'], true);
                $concept = $meta['concept'] ?? 'N/A';
                $amount = number_format((float)($meta['amount'] ?? 0), 2);
                $nif = $row['recipient_nif'] ?? '';
                $date = date('d/m/Y', strtotime($row['timestamp']));
                
                // Truncate overly long concepts for PDF
                if (strlen($concept) > 50) $concept = substr($concept, 0, 47) . '...';

                $html .= "<tr>
                            <td>{$row['id']}</td>
                            <td>{$date}</td>
                            <td>" . htmlspecialchars($concept) . "</td>
                            <td style='text-align:right'>{$amount} €</td>
                            <td>" . htmlspecialchars($nif) . "</td>
                          </tr>";
            }
            $html .= '</tbody></table></body></html>';
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $dompdf->stream("facturas_vts_" . date('Y-m-d') . ".pdf", ["Attachment" => true]);
        } else {
             http_response_code(400);
             echo "Formato no soportado";
        }
        exit;
    }
}
