 <?php
// services/initializeDataBaseSchema.php

 /**
     * Inicializa la tabla de asientos (registros inmutables).
     * Los campos id, current_hash y previous_hash son críticos.
     */

    require_once __DIR__ . '/../config/Database.php';

    function initializeDatabaseSchema()
    {  
        // Establecer la conexión a la base de datos persistente usando el Singleton
        try {
            $db = Database::getInstance()->getConnection();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Usamos Transaction solo si no está activa ya (aunque en sqlite DDL commitea implicito a veces)
            // Para simplificar y evitar conflictos en tests, ejecutamos directo
            
            // NOTA: En producción real NO se debe borrar la tabla en cada inicio.
            // Mantenemos la lógica "DROP" solo porque este script parece actuar como un "Reset" en los tests.
            // Si esto corre en index.php, borrará datos!
            // CAMBIO MVC: Solo CREAR si no existe. ELIMINAR el DROP para index.php.
            // Si los tests necesitan reset, deben hacerlo explicitamente.
            
            $db->exec("
                CREATE TABLE IF NOT EXISTS vts_ledger (
                    id INTEGER PRIMARY KEY,
                    current_hash CHAR(64) NOT NULL,
                    previous_hash CHAR(64) NOT NULL,
                    entry_data TEXT NOT NULL,
                    timestamp TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    machine_id TEXT NOT NULL,
                    signature_proof TEXT NOT NULL,
                    recipient_nif TEXT NOT NULL,
                    recipient_name TEXT NOT NULL,
                    rectification_reason TEXT NOT NULL,
                    original_invoice_id INTEGER NOT NULL,
                    invoice_id INTEGER NOT NULL,
                    message TEXT NOT NULL,
                    machine TEXT NOT NULL,
                    signature_key TEXT NOT NULL
                );
            ");
            
            // echo " Tabla vts_ledger verificada/inicializada.";

         } catch (PDOException $e) {
            die("Error de conexión/inicialización de DB: " . $e->getMessage());
        }
    }

    function resetDatabaseSchema()
    {
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("DROP TABLE IF EXISTS vts_ledger");
            initializeDatabaseSchema();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
