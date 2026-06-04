<?php
// modelo/Compra.php
// PHP inserta; triggers de BD reponen/revierten stock.
require_once __DIR__ . '/Conexion.php';

class Compra {
    private PDO $db;

    public function __construct() { $this->db = Conexion::obtener(); }

    public function registrar(array $cabecera, array $detalle): int|false {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO compras (fecha, proveedor_id, tipo, total, nota)
                 VALUES (:fecha, :prov, :tipo, :total, :nota) RETURNING id"
            );
            $stmt->execute([
                ':fecha'  => $cabecera['fecha'],
                ':prov'   => $cabecera['proveedor_id'] ?: null,
                ':tipo'   => $cabecera['tipo'],
                ':total'  => $cabecera['total'],
                ':nota'   => $cabecera['nota'] ?? null,
            ]);
            $compraId = (int) $stmt->fetchColumn();

            // Inserta detalle — el trigger trg_reponer_stock_compra suma el stock.
            $stmtDet = $this->db->prepare(
                "INSERT INTO compra_detalle
                     (compra_id, codigoprod, cantidad, precio_unitario, subtotal)
                 VALUES (:cid, :cod, :cant, :pu, :sub)"
            );

            // Actualiza precio_compra en productos con el precio pagado en esta compra
            // Solo para productos no-pesables que tienen precio por unidad real
            $stmtPrecio = $this->db->prepare(
                "UPDATE productos
                    SET precio_compra = :pc
                  WHERE codigoprod = :cod
                    AND unidad NOT IN ('kg', 'litro')"
            );

            foreach ($detalle as $it) {
                $cant     = (float)$it['cantidad'];
                $pu       = (float)$it['precio_unitario'];
                $subtotal = round($cant * $pu, 2);

                $stmtDet->execute([
                    ':cid'  => $compraId,
                    ':cod'  => $it['codigoprod'],
                    ':cant' => $cant,
                    ':pu'   => $pu,
                    ':sub'  => $subtotal,
                ]);

                // Actualizar precio_compra si el precio ingresado es mayor a 0
                if ($pu > 0) {
                    $stmtPrecio->execute([
                        ':pc'  => $pu,
                        ':cod' => $it['codigoprod'],
                    ]);
                }
            }

            $this->db->commit();
            return $compraId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Compra::registrar — ' . $e->getMessage());
            return false;
        }
    }

    // READ — compras del día con productos y cantidades
    public function obtenerDelDia(string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    COALESCE(prov.nombre, 'Compra Directa') AS proveedor_nombre,
                    STRING_AGG(
                        prod.nombre || ' x' || TRIM(TO_CHAR(cd.cantidad,'FM999999990.999')),
                        ', ' ORDER BY prod.nombre
                    ) AS productos
               FROM compras c
               LEFT JOIN proveedores    prov ON c.proveedor_id = prov.id
               LEFT JOIN compra_detalle cd   ON c.id           = cd.compra_id
               LEFT JOIN productos      prod ON cd.codigoprod  = prod.codigoprod
              WHERE c.fecha = :fecha
              GROUP BY c.id, prov.nombre
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

    // READ — total en rango de fechas
    public function totalEnRango(string $desde, string $hasta): float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM compras
              WHERE fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
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

    // READ — todas las compras (historial)
    public function obtenerTodas(int $limite = 200): array {
        $stmt = $this->db->prepare(
            "SELECT c.*, COALESCE(p.nombre, 'Compra Directa') AS proveedor_nombre
               FROM compras c
               LEFT JOIN proveedores p ON c.proveedor_id = p.id
              ORDER BY c.created_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // READ — historial filtrado por rango
    public function comprasPorFiltro(string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT c.*, COALESCE(p.nombre, 'Compra Directa') AS proveedor_nombre
               FROM compras c
               LEFT JOIN proveedores p ON c.proveedor_id = p.id
              WHERE c.fecha BETWEEN :desde AND :hasta
              ORDER BY c.created_at DESC"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }
}
?>
