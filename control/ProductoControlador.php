<?php
// =====================================================
// control/ProductoControlador.php
// CAMBIO: PK es codigoprod VARCHAR(15), no INT id
//         Registra acciones en bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Producto.php';
require_once BASE_PATH . 'modelo/Bitacora.php';
require_once BASE_PATH . 'helpers/Csrf.php';
require_once BASE_PATH . 'helpers/Validar.php';

class ProductoControlador {
    private Producto $modelo;
    private Bitacora $bitacora;

    public function __construct() {
        $this->modelo   = new Producto();
        $this->bitacora = new Bitacora();
    }

    public function listarTodos(): array {
        $buscar = trim($_GET['buscar'] ?? '');
        return $buscar ? $this->modelo->buscar($buscar) : $this->modelo->obtenerTodos();
    }

    public function stockBajo(): array  { return $this->modelo->stockBajo(); }
    public function contarStockBajo(): int { return $this->modelo->contarStockBajo(); }

    // CREATE
    public function crear(): void {
        Csrf::requerir(true); // formulario POST normal
        try {
            $d = [
                'codigoprod'    => Validar::codigoProd($_POST['codigoprod'] ?? ''),
                'nombre'        => Validar::texto($_POST['nombre'] ?? '', 1, 100),
                'categoria'     => Validar::texto($_POST['categoria'] ?? '', 0, 50, false),
                'precio_compra' => Validar::monto($_POST['precio_compra'] ?? 0, 0, 1000.0),
                'precio_venta'  => Validar::monto($_POST['precio_venta']  ?? 0, 0, 500.0),
                'stock'         => Validar::enteroPositivo($_POST['stock'] ?? 0, 0, 20),
                'stock_minimo'  => Validar::enteroPositivo($_POST['stock_minimo'] ?? 3, 0, 20),
                'unidad'        => Validar::unidad($_POST['unidad'] ?? 'pieza'),
                'proveedor_sugerido'  => !empty($_POST['proveedor_sugerido']) ? (int)$_POST['proveedor_sugerido'] : null,
                'proveedor_exclusivo' => !empty($_POST['proveedor_exclusivo']),
            ];
        } catch (\InvalidArgumentException $e) {
            $this->json(['ok'=>false,'mensaje'=>$e->getMessage()]);
            return;
        }

        $ok = $this->modelo->crear($d);
        $this->logBitacora($ok, "Producto creado: {$d['codigoprod']} - {$d['nombre']}", "Error al crear producto {$d['codigoprod']}");
        $this->json(['ok'=>$ok, 'mensaje'=> $ok ? 'Producto creado.' : 'Error al crear. Verifica que el código no esté duplicado o ejecuta el SQL de proveedor_exclusivo.']);
    }

    // UPDATE
    public function actualizar(): void {
        Csrf::requerir(true);
        try {
            $codigo = Validar::codigoProd($_POST['codigoprod'] ?? '');
            $d = [
                'nombre'        => Validar::texto($_POST['nombre'] ?? '', 1, 100),
                'categoria'     => Validar::texto($_POST['categoria'] ?? '', 0, 50, false),
                'precio_compra' => Validar::monto($_POST['precio_compra'] ?? 0, 0, 1000.0),
                'precio_venta'  => Validar::monto($_POST['precio_venta']  ?? 0, 0, 500.0),
                'stock'         => Validar::enteroPositivo($_POST['stock'] ?? 0, 0, 20),
                'stock_minimo'  => Validar::enteroPositivo($_POST['stock_minimo'] ?? 3, 0, 20),
                'unidad'        => Validar::unidad($_POST['unidad'] ?? 'pieza'),
                'proveedor_sugerido'  => !empty($_POST['proveedor_sugerido']) ? (int)$_POST['proveedor_sugerido'] : null,
                'proveedor_exclusivo' => !empty($_POST['proveedor_exclusivo']),
            ];
        } catch (\InvalidArgumentException $e) {
            $this->json(['ok'=>false,'mensaje'=>$e->getMessage()]);
            return;
        }

        $ok = $this->modelo->actualizar($codigo, $d);
        $this->logBitacora($ok, "Producto actualizado: $codigo", "Error al actualizar producto $codigo");
        $this->json(['ok'=>$ok, 'mensaje'=> $ok ? 'Producto actualizado.' : 'Error al actualizar.']);
    }

    // DELETE
    public function eliminar(string $codigo): void {
        $ok = $this->modelo->eliminar($codigo);
        $this->logBitacora($ok, "Producto eliminado: $codigo", "Error al eliminar producto $codigo");
        $this->json(['ok'=>$ok, 'mensaje'=> $ok ? 'Producto eliminado.' : 'Error al eliminar.']);
    }

    // ── helpers ──────────────────────────────────────
    private function logBitacora(bool $ok, string $descOk, string $descErr): void {
        $clave = $_SESSION['usuario'] ?? null;
        try {
            $this->bitacora->registrar($clave, $ok ? $descOk : $descErr, $ok ? 'C' : 'E');
        } catch (\Exception) {}
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
