<?php
// =====================================================
// modelo/Bitacora.php — Auditoría del sistema
// NUEVO: estructura basada en imagen de referencia
//   no_bitacora SERIAL PK
//   clave_cuenta CHAR(5) FK → cuenta
//   descripcion  TEXT NOT NULL
//   fechayhora   TIMESTAMP DEFAULT NOW()
//   estado       CHAR(1) DEFAULT 'C'  ('C'=Completado, 'E'=Error)
// =====================================================

require_once __DIR__ . '/Conexion.php';

class Bitacora {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtener();
    }

    /**
     * Registra una entrada en la bitácora.
     *
     * @param string $claveCuenta   Quien realiza la acción
     * @param string $descripcion   Texto libre de la operación
     * @param string $estado        'C' completado | 'E' error
     */
    public function registrar(string $claveCuenta, string $descripcion, string $estado = 'C'): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO bitacora (clave_cuenta, descripcion, estado)
             VALUES (:clave, :desc, :estado)"
        );
        return $stmt->execute([
            ':clave'  => $claveCuenta,
            ':desc'   => $descripcion,
            ':estado' => $estado,
        ]);
    }

    /**
     * Obtiene registros con filtros opcionales.
     *
     * @param string $fecha      'YYYY-MM-DD' o '' para todos
     * @param string $cuenta     ClaveCuenta o '' para todas
     * @param string $estado     'C','E' o '' para todos
     * @param int    $limite     Máximo de filas
     */
    public function obtener(
        string $fecha   = '',
        string $cuenta  = '',
        string $estado  = '',
        int    $limite  = 100
    ): array {
        $where  = [];
        $params = [];

        if ($fecha) {
            $where[]          = "DATE(fechayhora) = :fecha";
            $params[':fecha'] = $fecha;
        }
        if ($cuenta) {
            $where[]           = "clave_cuenta = :cuenta";
            $params[':cuenta'] = $cuenta;
        }
        if ($estado) {
            $where[]           = "estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql = "SELECT b.no_bitacora,
                       b.clave_cuenta,
                       c.Nombre || ' ' || c.Apellidos AS usuario,
                       b.descripcion,
                       b.fechayhora,
                       b.estado
                  FROM bitacora b
                  LEFT JOIN cuenta c ON b.clave_cuenta = c.ClaveCuenta"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY b.fechayhora DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Total de registros hoy */
    public function totalHoy(): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bitacora WHERE DATE(fechayhora) = CURRENT_DATE"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Total de errores hoy */
    public function erroresHoy(): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bitacora
              WHERE DATE(fechayhora) = CURRENT_DATE AND estado = 'E'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
?>
