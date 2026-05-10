<?php
// =====================================================
// modelo/Transferencia.php — PostgreSQL
// CAMBIO: SERIAL PK, RETURNING id, esquema "Abarrotes."
// =====================================================

require_once __DIR__ . '/Conexion.php';

class Transferencia {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtener();
    }

    // CREATE
    public function registrar(array $d): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO transferencias (fecha, monto, concepto, referencia)
             VALUES (:fecha, :monto, :concepto, :ref)"
        );
        return $stmt->execute([
            ':fecha'   => $d['fecha'],
            ':monto'   => $d['monto'],
            ':concepto'=> $d['concepto']   ?? null,
            ':ref'     => $d['referencia'] ?? null,
        ]);
    }

    // READ — del día
    public function obtenerDelDia(string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM transferencias WHERE fecha = :fecha ORDER BY created_at DESC"
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    // READ — total del día
    public function totalDelDia(string $fecha): float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM transferencias WHERE fecha = :fecha"
        );
        $stmt->execute([':fecha' => $fecha]);
        return (float) $stmt->fetchColumn();
    }

    // READ — historial completo
    public function obtenerTodas(): array {
        return $this->db->query(
            "SELECT * FROM transferencias ORDER BY fecha DESC, created_at DESC"
        )->fetchAll();
    }

    // DELETE
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM transferencias WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
?>
