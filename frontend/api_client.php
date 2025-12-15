<?php
// frontend/api_client.php

// MODO DIRECTO: Evita el bloqueo (deadlock) de PHP Built-in Server
// En lugar de hacer una petición HTTP real a localhost (que bloquearía),
// instanciamos el controlador directamente.
require_once __DIR__ . '/../controllers/InvoiceController.php';

class ApiClient {
    private $baseUrl;
    
    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    private function request($method, $action, $data = [], $params = []) {
        // Simulamos la llamada a la API capturando la salida (Output Buffering)
        ob_start();
        
        try {
            // Setup entorno simulado para el controlador
            $_SERVER['HTTP_X_USER_ID'] = $_ENV['FRONTEND_USER_ID'] ?? 'frontend_admin_fallback'; // Simular usuario autenticado
            $_SERVER['REQUEST_METHOD'] = $method;

            if ($method === 'GET') {
                $_GET['action'] = $action;
                foreach ($params as $k => $v) { $_GET[$k] = $v; }
            }
            
            // Instanciar y ejecutar
            $controller = new InvoiceController();
            
            // Pasamos $data directamente para evitar leer php://input
            $controller->handleRequest($action, $data);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        $responseJson = ob_get_clean();
        $responseArgs = json_decode($responseJson, true);
        
        // Asumimos éxito si devuelve JSON válido, sino error server
        $code = ($responseArgs) ? 200 : 500;
        
        return [
            'code' => $code,
            'body' => $responseArgs ?? ['error' => 'Respuesta vacía o inválida del servidor interno.', 'raw' => $responseJson]
        ];
    }

    public function getList() {
        return $this->request('GET', 'list');
    }

    public function getInvoice($id) {
        return $this->request('GET', 'get', [], ['id' => $id]);
    }

    public function register($data) {
        // En el modo interno, pasamos data para que el controller no lea php://input
        return $this->request('POST', 'register', $data);
    }
}

// Configuración
// Nota: en modo directo no usamos la URL realmente, pero la dejamos por compatibilidad
// Cargamos .env si aún no está cargado (por si se llama standalone, aunque index.php lo carga)
if (!class_exists('EnvLoader')) {
    // Si se usa desde front, index.php NO ha cargado esto necesariamente si entramos directo a create.php -> pero create.php hace require api_client.php
    // El frontend necesita cargar el .env tambien si quiere usar variables.
    // create.php/index.php del FRONTEND deberían cargar el .env.
    // Pero api_client.php es el punto común.
    // Vamos a asumir que el ENTRY POINT (index/create) carga el env, O api_client lo hace.
    // Hagamos que api_client lo cargue si $_ENV no está poblado?
    // Mejor: require EnvLoader y load aquí también por seguridad? No, require once evita re-load.
    require_once __DIR__ . '/../config/EnvLoader.php';
    EnvLoader::load(__DIR__ . '/../.env');
}

$apiUrl = $_ENV['API_BASE_URL'] ?? "http://localhost:8001/index.php"; 
$api = new ApiClient($apiUrl);
?>
