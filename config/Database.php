<?php
// config/Database.php

class Database {
    private static $instance = null;
    private $pdo;

    // Usamos la misma ruta relativa que antes pero absoluta para evitar problemas
    private $dbPath;

    private function __construct() {
        $this->dbPath = __DIR__ . '/../vts_ledger.sqlite';
        $connectionString = "sqlite:" . $this->dbPath;
        
        try {
            $this->pdo = new PDO($connectionString);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
    
    // Para tests, permitir inyectar una conexión (ej. :memory:)
    public static function setTestConnection($pdoInstance) {
        self::$instance = new Database(); // dummy init
        self::$instance->pdo = $pdoInstance;
    }
}
