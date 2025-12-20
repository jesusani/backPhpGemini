<?php
   require './services/initializeDataBaseSchema.php';
   require './services/hashes.php';
   require './services/invoiceRegister.php';
    require './services/exporAEAT.php';
    require './services/invoicesList.php';
    require './services/verify.php';


/**
 * VTS API Service (PHP)
 * Backend actualizado para usar una base de datos SQLite persistente (no temporal)
 * para el registro inmutable de facturas.
 */

// Constantes de Configuración
const INTERNAL_SIGNATURE_KEY = "VTS_AEAT_SECRET_KEY_2024";
// MODIFICACIÓN: Conexión a DB persistente en archivo "vts_ledger.sqlite"
const DB_CONNECTION_STRING = "sqlite:" . __DIR__ . "/vts_ledger.sqlite"; 
     $db = new PDO(DB_CONNECTION_STRING);
class VtsService
{
 
    // SIMULACIÓN: Rutas a las claves de firma del sistema (requerido por AEAT)
    private $privateKeyPath = '/path/to/system/private_key.pem'; 
    private $publicKeyPath = '/path/to/system/public_key.pem';

    public function __construct()    { initializeDatabaseSchema();}
   // Antes de cualquier salida

}

// ----------------------------------------------------------------------------------
// Lógica de Enrutamiento (Simulación de un Endpoint POST)
// ----------------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permitir CORS para desarrollo local
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID');



// Manejo de preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simular la recepción de datos POST desde React
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Determinar la acción y el tipo de asiento
$action = $_GET['action'] ?? null;
$type = $data['type'] ?? 'INITIAL';

// --- LÓGICA DE AUTENTICACIÓN (Simulación de certificado) ---
$authenticatedUserId = $_SERVER['HTTP_X_USER_ID'] ?? 'user-certificate-001'; 
// ----------------------------------------------------------------------


$vtsService = new VtsService();
$response = [];

switch ($action) {
    case 'register': 
        $originalId = $data['originalInvoiceId'] ?? null;
     
        $invoiceData = [
            'concept' => $data['concept'] ?? '',
            'amount' => $data['amount'] ?? 0,
            'reason' => $data['reason'] ?? null,
            'recipientNIF' => $data['recipientNIF'] ?? null,
            'recipientName' => $data['recipientName'] ?? null,
        ];
        echo "Registering new entry of type {$type} by user {$authenticatedUserId}\n";
        $response = invoiceRegister($invoiceData, $type, $authenticatedUserId, $originalId);
        break;
        
    case 'export':
        $response = [ 'message' => 'Exportación completada.',
            'exportData' => exportForAEAT()];
        break;

    case 'test':
        $response = ['message' => 'VTS API Service is operational.', 
                    'userId' => $authenticatedUserId];
        break;
    case 'hash':
        $dataToHash = $data['dataToHash'] ?? '';
        $response = ['message' => 'Hash generado.',
            'hash' => generateHash($dataToHash)];
        break;
    case 'verify':
       
        $isValid = verifyChainIntegrity();
        $response = ['message' => 'Verificación completada.',
            'isValid' => $isValid];
        break;
    
    case 'list':
        $response = ['message' => 'listado list completada.',
            'records' => invoicesList()];
        break;
    
    case 'fetch_ledger':
        $response = ['message' => 'listado ledger completada.',
            'ledger' => invoicesList()];
        break;

    default:
        $response = ['error' => 'Acción no válida o datos incompletos.'];
        break;
}

// Devolver la respuesta al cliente React
echo json_encode($response);


?>
