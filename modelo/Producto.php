<?php
// =====================================================
// modelo/Producto.php — PostgreSQL
// CAMBIO: PK es codigoprod VARCHAR(15) (no INT)
//         Consultas adaptadas a pgsql (ILIKE, RETURNING)
// =====================================================

require_once __DIR__ . '/Conexion.php';

class Producto {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtener();
    }

    // READ — todos los productos
    public function obtenerTodos(): array {
        return $this->db->query(
            "SELECT * FROM productos ORDER BY codigoprod ASC"
        )->fetchAll();
    }

    // READ — búsqueda por nombre o código (ILIKE = insensible a mayúsculas en PG)
    public function buscar(string $termino): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM productos
              WHERE nombre     ILIKE :t
                 OR codigoprod ILIKE :t
              ORDER BY nombre ASC"
        );
        $stmt->execute([':t' => "%{$termino}%"]);
        return $stmt->fetchAll();
    }

    // READ — por código de producto
    public function obtenerPorCodigo(string $codigo): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM productos WHERE codigoprod = :cod"
        );
        $stmt->execute([':cod' => $codigo]);
        return $stmt->fetch() ?: null;
    }

    // READ — stock bajo (stock ≤ stock_minimo)
    public function stockBajo(): array {
        return $this->db->query(
            "SELECT * FROM productos WHERE stock <= stock_minimo ORDER BY stock ASC"
        )->fetchAll();
    }

    public function contarStockBajo(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo"
        )->fetchColumn();
    }

    // CREATE
    public function crear(array $d): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO productos
                 (codigoprod, nombre, categoria, precio_compra, precio_venta,
                  stock, stock_minimo, unidad)
             VALUES
                 (:cod, :nombre, :cat, :pc, :pv, :stock, :smin, :unidad)"
        );
        return $stmt->execute([
            ':cod'    => strtoupper(trim($d['codigoprod'])),
            ':nombre' => $d['nombre'],
            ':cat'    => $d['categoria']    ?? null,
            ':pc'     => $d['precio_compra'],
            ':pv'     => $d['precio_venta'],
            ':stock'  => $d['stock']        ?? 0,
            ':smin'   => $d['stock_minimo'] ?? 3,
            ':unidad' => $d['unidad']       ?? 'pieza',
        ]);
    }

    // UPDATE
    public function actualizar(string $codigo, array $d): bool {
        $stmt = $this->db->prepare(
            "UPDATE productos SET
                 nombre        = :nombre,
                 categoria     = :cat,
                 precio_compra = :pc,
                 precio_venta  = :pv,
                 stock         = :stock,
                 stock_minimo  = :smin,
                 unidad        = :unidad
             WHERE codigoprod  = :cod"
        );
        return $stmt->execute([
            ':cod'    => $codigo,
            ':nombre' => $d['nombre'],
            ':cat'    => $d['categoria']    ?? null,
            ':pc'     => $d['precio_compra'],
            ':pv'     => $d['precio_venta'],
            ':stock'  => $d['stock']        ?? 0,
            ':smin'   => $d['stock_minimo'] ?? 3,
            ':unidad' => $d['unidad']       ?? 'pieza',
        ]);
    }

    // UPDATE stock (ventas / compras lo usan internamente)
    public function reducirStock(string $codigo, int $cantidad): bool {
        $stmt = $this->db->prepare(
            "UPDATE productos
                SET stock = stock - :cant
              WHERE codigoprod = :cod AND stock >= :cant"
        );
        return $stmt->execute([':cant' => $cantidad, ':cod' => $codigo]);
    }

    public function aumentarStock(string $codigo, int $cantidad): bool {
        $stmt = $this->db->prepare(
            "UPDATE productos SET stock = stock + :cant WHERE codigoprod = :cod"
        );
        return $stmt->execute([':cant' => $cantidad, ':cod' => $codigo]);
    }

    // DELETE
    public function eliminar(string $codigo): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM productos WHERE codigoprod = :cod"
        );
        return $stmt->execute([':cod' => $codigo]);
    }

    // ── Auto-generar siguiente código ─────────────
    public function siguienteCodigoprod(): string {
        $stmt = $this->db->query(
            "SELECT codigoprod FROM productos
              WHERE codigoprod ~ '^PROD[0-9]+$'
              ORDER BY CAST(SUBSTRING(codigoprod FROM 5) AS INTEGER) DESC
              LIMIT 1"
        );
        $ultimo = $stmt->fetchColumn();
        $num    = $ultimo ? ((int) substr($ultimo, 4) + 1) : 1;
        return 'PROD' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
?>
