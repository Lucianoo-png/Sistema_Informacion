<?php
// =====================================================
// control/VentaControlador.php
// CAMBIO: codigoprod en lugar de producto_id INT
//         Registra en bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Venta.php';
require_once BASE_PATH . 'modelo/Bitacora.php';

class VentaControlador {
    private Venta    $modelo;
    private Bitacora $bitacora;

    public function __construct() {
        $this->modelo   = new Venta();
        $this->bitacora = new Bitacora();
    }

    public function obtenerDelDia(string $fecha = ''): array {
        return $this->modelo->obtenerDelDia($fecha ?: date('Y-m-d'));
    }

    public function totalDelDia(string $fecha = ''): array {
        return $this->modelo->totalDelDia($fecha ?: date('Y-m-d'));
    }

    public function masVendidos(string $fecha = ''): array {
        return $this->modelo->masVendidos($fecha ?: date('Y-m-d'));
    }

    // CREATE — recibe JSON con {detalle:[{codigoprod,cantidad,precio_unitario,subtotal}], metodo_pago, nota}
    public function registrar(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['detalle']) || !is_array($input['detalle'])) {
            $this->json(['ok'=>false,'mensaje'=>'El carrito está vacío.']);
            return;
        }

        $total   = 0;
        $detalle = [];

        foreach ($input['detalle'] as $it) {
            $subtotal = (float)$it['precio_unitario'] * (int)$it['cantidad'];
            $total   += $subtotal;
            $detalle[] = [
                'codigoprod'      => $it['codigoprod'],
                'cantidad'        => (int)$it['cantidad'],
                'precio_unitario' => (float)$it['precio_unitario'],
                'subtotal'        => $subtotal,
            ];
        }

        $cabecera = [
            'fecha'       => date('Y-m-d'),
            'total'       => $total,
            'metodo_pago' => $input['metodo_pago'] ?? 'efectivo',
            'nota'        => $input['nota'] ?? null,
        ];

        $ventaId = $this->modelo->registrar($cabecera, $detalle);
        $clave   = $_SESSION['usuario'] ?? 'SIST';

        if ($ventaId) {
            $this->bitacora->registrar($clave, "Venta registrada ID:{$ventaId} total:\${$total}", 'C');
            $this->json(['ok'=>true, 'venta_id'=>$ventaId, 'mensaje'=>'Venta registrada correctamente.']);
        } else {
            $this->bitacora->registrar($clave, "Error al registrar venta (total:\${$total})", 'E');
            $this->json(['ok'=>false,'mensaje'=>'Error al registrar la venta.']);
        }
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
