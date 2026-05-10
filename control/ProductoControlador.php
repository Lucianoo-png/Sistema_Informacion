<?php
// =====================================================
// control/ProductoControlador.php
// CAMBIO: PK es codigoprod VARCHAR(15), no INT id
//         Registra acciones en bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Producto.php';
require_once BASE_PATH . 'modelo/Bitacora.php';

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
        $d = [
            'codigoprod'    => strtoupper(trim($_POST['codigoprod']   ?? '')),
            'nombre'        => trim($_POST['nombre']       ?? ''),
            'categoria'     => trim($_POST['categoria']    ?? ''),
            'precio_compra' => (float)($_POST['precio_compra'] ?? 0),
            'precio_venta'  => (float)($_POST['precio_venta']  ?? 0),
            'stock'         => (int)($_POST['stock']       ?? 0),
            'stock_minimo'  => (int)($_POST['stock_minimo']?? 3),
            'unidad'        => trim($_POST['unidad']       ?? 'pieza'),
        ];

        if (empty($d['codigoprod']) || empty($d['nombre'])) {
            $this->json(['ok'=>false,'mensaje'=>'Código y nombre son obligatorios.']);
            return;
        }

        $ok = $this->modelo->crear($d);
        $this->logBitacora($ok, "Producto creado: {$d['codigoprod']} - {$d['nombre']}", "Error al crear producto {$d['codigoprod']}");
        $this->json(['ok'=>$ok, 'mensaje'=> $ok ? 'Producto creado.' : 'Error: ¿código duplicado?']);
    }

    // UPDATE
    public function actualizar(): void {
        $codigo = strtoupper(trim($_POST['codigoprod'] ?? ''));
        $d = [
            'nombre'        => trim($_POST['nombre']       ?? ''),
            'categoria'     => trim($_POST['categoria']    ?? ''),
            'precio_compra' => (float)($_POST['precio_compra'] ?? 0),
            'precio_venta'  => (float)($_POST['precio_venta']  ?? 0),
            'stock'         => (int)($_POST['stock']       ?? 0),
            'stock_minimo'  => (int)($_POST['stock_minimo']?? 3),
            'unidad'        => trim($_POST['unidad']       ?? 'pieza'),
        ];

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
        $clave = $_SESSION['usuario'] ?? 'SIST';
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
