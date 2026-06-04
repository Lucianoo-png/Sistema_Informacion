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
    public function registrar(?string $claveCuenta, string $descripcion, string $estado = 'C'): bool {
        // CORRECCIÓN: try/catch para que una FK violation (usuario no existe en cuenta)
        // o cualquier otro error de BD no lance una excepción no capturada que rompa
        // la respuesta JSON en VentaControlador / CompraControlador.
        // Causa real del "no puedo hacer ventas": ALE01 no existía en tabla cuenta →
        // INSERT bitacora violaba FK fk_bitacora_cuenta → PHP devolvía HTML de error
        // en lugar de JSON → el frontend atrapaba "Error de conexión".
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO bitacora (clave_cuenta, descripcion, estado)
                 VALUES (:clave, :desc, :estado)"
            );
            return $stmt->execute([
                ':clave'  => $claveCuenta,
                ':desc'   => $descripcion,
                ':estado' => $estado,
            ]);
        } catch (\Exception $e) {
            // Fallo silencioso: loguea el error pero no interrumpe el flujo
            error_log('Bitacora::registrar — ' . $e->getMessage());
            return false;
        }
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
                       c.nombre || ' ' || c.apellidos AS usuario,
                       b.descripcion,
                       b.fechayhora,
                       b.estado
                  FROM bitacora b
                  LEFT JOIN cuenta c ON b.clave_cuenta = c.clavecuenta"
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

    /** Total de registros relevantes hoy (login + venta + compra, sin errores) */
    public function totalHoyRelevantes(): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bitacora
              WHERE DATE(fechayhora) = CURRENT_DATE
                AND estado = 'C'
                AND (descripcion ILIKE 'Inicio de sesión%'
                  OR descripcion ILIKE 'Venta registrada%'
                  OR descripcion ILIKE 'Compra registrada%'
                  OR descripcion ILIKE 'Cierre de sesión%')"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Solo registros relevantes: inicios/cierres de sesión y ventas/compras registradas.
     * Excluye errores y operaciones internas del sistema.
     */
    public function obtenerRelevantes(
        string $fecha  = '',
        string $cuenta = '',
        int    $limite = 200
    ): array {
        $where  = [
            "estado = 'C'",
            "(descripcion ILIKE 'Inicio de sesión%'
              OR descripcion ILIKE 'Venta registrada%'
              OR descripcion ILIKE 'Compra registrada%'
              OR descripcion ILIKE 'Cierre de sesión%')"
        ];
        $params = [];

        if ($fecha) {
            $where[]          = "DATE(fechayhora) = :fecha";
            $params[':fecha'] = $fecha;
        }
        if ($cuenta) {
            $where[]           = "clave_cuenta = :cuenta";
            $params[':cuenta'] = $cuenta;
        }

        $sql = "SELECT b.no_bitacora,
                       b.clave_cuenta,
                       c.nombre || ' ' || c.apellidos AS usuario,
                       b.descripcion,
                       b.fechayhora,
                       b.estado
                  FROM bitacora b
                  LEFT JOIN cuenta c ON b.clave_cuenta = c.clavecuenta"
             . ' WHERE ' . implode(' AND ', $where)
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
}
?>
