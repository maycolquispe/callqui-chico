<?php
/**
 * Database Connection - Singleton Pattern
 * Sistema Callqui Chico - Profesional
 */

class Database {
    private static $instance = null;
    private $conn;
    
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db   = "callqui_chico";
    
    private function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
        
        if ($this->conn->connect_error) {
            error_log("Database Connection Error: " . $this->conn->connect_error);
            die("Error de conexión a la base de datos.");
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Previene clonación
    private function __clone() {}
    
    // Previene deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Función helper para compatibilidad
function getDB() {
    return Database::getInstance()->getConnection();
}
