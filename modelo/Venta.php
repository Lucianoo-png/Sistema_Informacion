<?php
// modelo/Venta.php
// PHP valida stock antes de insertar.
// Los triggers de BD hacen el UPDATE de stock (no el PHP).
require_once __DIR__ . '/Conexion.php';

class Venta {
    private PDO $db;

    public function __construct() { $this->db = Conexion::obtener(); }

    public function registrar(array $cabecera, array $detalle, ?string &$errorMsg = null): int|false {
        try {
            $this->db->beginTransaction();

            // ── 1. Verificar stock antes de insertar (validación PHP) ─────
            $stmtChk = $this->db->prepare(
                "SELECT stock, unidad, nombre FROM productos WHERE codigoprod = :cod FOR UPDATE"
            );
            foreach ($detalle as $it) {
                $stmtChk->execute([':cod' => $it['codigoprod']]);
                $prod = $stmtChk->fetch(PDO::FETCH_ASSOC);
                if ($prod && !in_array(strtolower($prod['unidad']), ['kg', 'litro'])) {
                    if ((float)$prod['stock'] < (float)$it['cantidad']) {
                        $this->db->rollBack();
                        $errorMsg = 'Stock insuficiente para "' . $prod['nombre']
                            . '" (disponible: ' . (int)$prod['stock']
                            . ', solicitado: ' . (int)$it['cantidad'] . ').';
                        return false;
                    }
                }
            }

            // ── 2. Insertar cabecera ──────────────────────────────────────
            $stmt = $this->db->prepare(
                "INSERT INTO ventas (fecha, total, metodo_pago, nota)
                 VALUES (:fecha, :total, :metodo, :nota) RETURNING id"
            );
            $stmt->execute([
                ':fecha'  => $cabecera['fecha'],
                ':total'  => $cabecera['total'],
                ':metodo' => $cabecera['metodo_pago'],
                ':nota'   => $cabecera['nota'] ?? null,
            ]);
            $ventaId = (int) $stmt->fetchColumn();

            // ── 3. Insertar detalle (trigger descontará stock en BD) ──────
            $stmtDet = $this->db->prepare(
                "INSERT INTO venta_detalle (venta_id, codigoprod, cantidad, precio_unitario, subtotal)
                 VALUES (:vid, :cod, :cant, :pu, :sub)"
            );
            foreach ($detalle as $it) {
                $stmtDet->execute([
                    ':vid'  => $ventaId,
                    ':cod'  => $it['codigoprod'],
                    ':cant' => $it['cantidad'],
                    ':pu'   => $it['precio_unitario'],
                    ':sub'  => $it['subtotal'],
                ]);
            }

            $this->db->commit();
            return $ventaId;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            // Extraer mensaje del trigger de BD (viene en el mensaje de excepción)
            $msg = $e->getMessage();
            // PostgreSQL pone el RAISE EXCEPTION message en el formato:
            // "SQLSTATE[P0001]: Raise exception: 7 ERROR:  Stock insuficiente..."
            if (preg_match('/ERROR:\s+(.+?)(?:
|$)/i', $msg, $m)) {
                $errorMsg = trim($m[1]);
            } else {
                $errorMsg = 'Error al procesar la venta. Intenta de nuevo.';
            }
            error_log('Venta::registrar — ' . $msg);
            return false;
        }
    }

    public function obtenerDelDia(string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT v.*,
                    STRING_AGG(
                        p.nombre || ' x' || TRIM(TO_CHAR(vd.cantidad, 'FM999999990.999')),
                        ', ' ORDER BY p.nombre
                    ) AS productos
               FROM ventas v
               LEFT JOIN venta_detalle vd ON v.id          = vd.venta_id
               LEFT JOIN productos     p  ON vd.codigoprod = p.codigoprod
              WHERE v.fecha = :fecha
              GROUP BY v.id
              ORDER BY v.created_at DESC"
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    public function obtenerDetalle(int $ventaId): array {
        $stmt = $this->db->prepare(
            "SELECT vd.*, p.nombre, p.codigoprod
               FROM venta_detalle vd
               JOIN productos p ON vd.codigoprod = p.codigoprod
              WHERE vd.venta_id = :vid"
        );
        $stmt->execute([':vid' => $ventaId]);
        return $stmt->fetchAll();
    }

    public function totalDelDia(string $fecha): array {
        $stmt = $this->db->prepare(
            "SELECT
                 COALESCE(SUM(total), 0) AS total_ventas,
                 COUNT(*)               AS num_transacciones,
                 COALESCE(SUM(CASE WHEN metodo_pago='efectivo'      THEN total END), 0) AS efectivo,
                 COALESCE(SUM(CASE WHEN metodo_pago='transferencia' THEN total END), 0) AS transferencia
               FROM ventas WHERE fecha = :fecha"
        );
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetch();
    }

    public function masVendidos(string $fecha, int $limite = 5): array {
        $stmt = $this->db->prepare(
            "SELECT p.nombre, SUM(vd.cantidad) AS total_vendido, SUM(vd.subtotal) AS total_importe
               FROM venta_detalle vd
               JOIN ventas    v ON vd.venta_id   = v.id
               JOIN productos p ON vd.codigoprod = p.codigoprod
              WHERE v.fecha = :fecha
              GROUP BY p.codigoprod, p.nombre
              ORDER BY total_vendido DESC LIMIT :lim"
        );
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function totalEnRango(string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT
                 COALESCE(SUM(total), 0) AS total_ventas,
                 COUNT(*)               AS num_transacciones,
                 COALESCE(SUM(CASE WHEN metodo_pago='efectivo'      THEN total END), 0) AS efectivo,
                 COALESCE(SUM(CASE WHEN metodo_pago='transferencia' THEN total END), 0) AS transferencia
               FROM ventas WHERE fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetch();
    }

    public function masVendidosRango(string $desde, string $hasta, int $limite = 10): array {
        $stmt = $this->db->prepare(
            "SELECT p.nombre, SUM(vd.cantidad) AS total_vendido, SUM(vd.subtotal) AS total_importe
               FROM venta_detalle vd
               JOIN ventas    v ON vd.venta_id   = v.id
               JOIN productos p ON vd.codigoprod = p.codigoprod
              WHERE v.fecha BETWEEN :desde AND :hasta
              GROUP BY p.codigoprod, p.nombre
              ORDER BY total_vendido DESC LIMIT :lim"
        );
        $stmt->bindValue(':desde', $desde);
        $stmt->bindValue(':hasta', $hasta);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function ventasPorDia(string $desde, string $hasta): array {
        $stmt = $this->db->prepare(
            "SELECT fecha, COALESCE(SUM(total), 0) AS total_dia, COUNT(*) AS transacciones
               FROM ventas WHERE fecha BETWEEN :desde AND :hasta
              GROUP BY fecha ORDER BY fecha ASC"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    public function obtenerTodas(int $limite = 200): array {
        $stmt = $this->db->prepare(
            "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.nota,
                    STRING_AGG(p.nombre || ' x' || TRIM(TO_CHAR(vd.cantidad,'FM999999990.999')),
                               ', ' ORDER BY p.nombre) AS productos
               FROM ventas v
               LEFT JOIN venta_detalle vd ON v.id          = vd.venta_id
               LEFT JOIN productos     p  ON vd.codigoprod = p.codigoprod
              GROUP BY v.id, v.fecha, v.total, v.metodo_pago, v.nota
              ORDER BY v.created_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function ventasPorFiltro(string $desde, string $hasta, string $metodo = ''): array {
        $where  = ["v.fecha BETWEEN :desde AND :hasta"];
        $params = [':desde' => $desde, ':hasta' => $hasta];
        if ($metodo) { $where[] = "v.metodo_pago = :metodo"; $params[':metodo'] = $metodo; }
        $sql = "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.nota,
                       STRING_AGG(p.nombre || ' x' || TRIM(TO_CHAR(vd.cantidad,'FM999999990.999')),
                                  ', ' ORDER BY p.nombre) AS productos
                  FROM ventas v
                  LEFT JOIN venta_detalle vd ON v.id          = vd.venta_id
                  LEFT JOIN productos     p  ON vd.codigoprod = p.codigoprod
                 WHERE " . implode(' AND ', $where) . "
                 GROUP BY v.id, v.fecha, v.total, v.metodo_pago, v.nota
                 ORDER BY v.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
