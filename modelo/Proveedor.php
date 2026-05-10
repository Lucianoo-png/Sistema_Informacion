<?php
// =====================================================
// modelo/Proveedor.php — PostgreSQL
// CAMBIO: SERIAL PK, CHECK de teléfono '^[0-9]{10}$'
// =====================================================

require_once __DIR__ . '/Conexion.php';

class Proveedor {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtener();
    }

    public function obtenerTodos(): array {
        return $this->db->query(
            "SELECT * FROM proveedores ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // CREATE — RETURNING para obtener el nuevo id
    public function crear(array $d): int|false {
        // Validar teléfono antes de enviar a la BD (la BD también lo valida)
        if (!preg_match('/^[0-9]{10}$/', $d['telefono'] ?? '')) {
            return false;
        }
        $stmt = $this->db->prepare(
            "INSERT INTO proveedores (nombre, telefono, DiaVisita)
             VALUES (:nombre, :tel, :dias)
             RETURNING id"
        );
        $stmt->execute([
            ':nombre' => $d['nombre'],
            ':tel'    => $d['telefono'],
            ':dias'   => $d['DiaVisita'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    // UPDATE
    public function actualizar(int $id, array $d): bool {
        if (!preg_match('/^[0-9]{10}$/', $d['telefono'] ?? '')) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE proveedores SET
                 nombre    = :nombre,
                 telefono  = :tel,
                 DiaVisita = :dias
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'     => $id,
            ':nombre' => $d['nombre'],
            ':tel'    => $d['telefono'],
            ':dias'   => $d['DiaVisita'] ?? null,
        ]);
    }

    // DELETE
    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM proveedores WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
?>
