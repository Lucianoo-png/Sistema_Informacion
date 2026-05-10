<?php
// =====================================================
// modelo/Conexion.php — Conexión PDO a PostgreSQL
// CAMBIO: MySQL → PostgreSQL (pgsql driver)
//         Esquema "Abarrotes" configurado en search_path
// =====================================================

class Conexion {
    private static $instancia = null;

    private string $host     = 'localhost';
    private string $port     = '5432';
    private string $dbname   = 'Tienda';
    private string $schema   = 'Abarrotes';
    private string $usuario  = 'postgres';
    private string $password = '12345678';   // ← ajusta tu contraseña

    private PDO $pdo;

    private function __construct() {
        // DSN PostgreSQL: sin charset (se maneja con options)
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->usuario, $this->password, $opciones);

            // Establecer search_path al esquema y encoding UTF-8
            $this->pdo->exec("SET search_path TO \"{$this->schema}\", public");
            $this->pdo->exec("SET client_encoding TO 'UTF8'");

        } catch (PDOException $e) {
            // En producción evitar exponer el mensaje al cliente
            error_log('DB Error: ' . $e->getMessage());
            die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
        }
    }

    /** Singleton — devuelve siempre la misma instancia de PDO */
    public static function obtener(): PDO {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia->pdo;
    }
}
?>
