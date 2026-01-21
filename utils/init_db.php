<?php
require_once __DIR__ . '/../services/initializeDataBaseSchema.php';

// Force initialization
echo "Inicializando esquema de base de datos...\n";
initializeDatabaseSchema();
echo "Esquema verificado. Tabla 'vts_events' debería existir ahora.\n";

// Test Event Logging
require_once __DIR__ . '/../services/EventService.php';
$logger = new EventService();
$id = $logger->logEvent('BOOT', 'Sistema Iniciado', ['version' => '1.0.0']);
echo "Evento de prueba registrado con ID: $id\n";
?>
