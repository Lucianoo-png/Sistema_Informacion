<?php
// =====================================================
// modelo/Compra.php — PostgreSQL
// CAMBIO: RETURNING id, codigoprod VARCHAR(15),
//         ENUM → CHECK, esquema "Abarrotes."
// =====================================================

require_once __DIR__ . '/Conexion.php';

class Compra {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtener();
    }

    // CREATE
    public function registrar(array $cabecera, array $detalle): int|false {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO compras (fecha, proveedor_id, tipo, total, nota)
                 VALUES (:fecha, :prov, :tipo, :total, :nota)
                 RETURNING id"
            );
            $stmt->execute([
                ':fecha'  => $cabecera['fecha'],
                ':prov'   => $cabecera['proveedor_id'] ?: null,
                ':tipo'   => $cabecera['tipo'],
                ':total'  => $cabecera['total'],
                ':nota'   => $cabecera['nota'] ?? null,
            ]);
            $compraId = (int) $stmt->fetchColumn();

            $stmtDet = $this->db->prepare(
                "INSERT INTO compra_detalle
                     (compra_id, codigoprod, cantidad, precio_unitario, subtotal)
                 VALUES (:cid, :cod, :cant, :pu, :sub)"
            );
            $stmtStk = $this->db->prepare(
                "UPDATE productos SET stock = stock + :cant WHERE codigoprod = :cod"
            );

            foreach ($detalle as $it) {
                $subtotal = (float)$it['cantidad'] * (float)$it['precio_unitario'];
                $stmtDet->execute([
                    ':cid'  => $compraId,
                    ':cod'  => $it['codigoprod'],
                    ':cant' => $it['cantidad'],
                    ':pu'   => $it['precio_unitario'],
                    ':sub'  => $subtotal,
                ]);
                $stmtStk->execute([
                    ':cant' => $it['cantidad'],
                    ':cod'  => $it['codigoprod'],
                ]);
            }

            $this->db->commit();
            return $compraId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Compra::registrar — ' . $e->getMessage());
            return false;
        }
    }

    // READ — compras del día
    public function obtenerDelDia(string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    COALESCE(p.nombre, 'Compra Directa') AS proveedor_nombre
               FROM compras c
               LEFT JOIN proveedores p ON c.proveedor_id = p.id
              WHERE c.fecha = :fecha
              ORDER BY c.created_at DESC"
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    // READ — total del día
    public function totalDelDia(string $fecha): float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM compras WHERE fecha = :fecha"
        );
        $stmt->execute([':fecha' => $fecha]);
        return (float) $stmt->fetchColumn();
    }

    // READ — detalle de una compra
    public function obtenerDetalle(int $compraId): array {
        $stmt = $this->db->prepare(
            "SELECT cd.*, p.nombre
               FROM compra_detalle cd
               JOIN productos p ON cd.codigoprod = p.codigoprod
              WHERE cd.compra_id = :cid"
        );
        $stmt->execute([':cid' => $compraId]);
        return $stmt->fetchAll();
    }
    // READ — total en rango de fechas
    public function totalEnRango(string $desde, string $hasta): float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM compras WHERE fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return (float) $stmt->fetchColumn();
    }

    // READ — todas las compras (historial completo)
    public function obtenerTodas(int $limite = 200): array {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    COALESCE(p.nombre, 'Compra Directa') AS proveedor_nombre
               FROM compras c
               LEFT JOIN proveedores p ON c.proveedor_id = p.id
              ORDER BY c.created_at DESC
              LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

}
?>
