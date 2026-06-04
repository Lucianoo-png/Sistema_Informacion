<?php
// =====================================================
// control/VentaControlador.php
// CAMBIO: codigoprod en lugar de producto_id INT
//         Registra en bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Venta.php';
require_once BASE_PATH . 'modelo/Bitacora.php';
require_once BASE_PATH . 'helpers/Csrf.php';
require_once BASE_PATH . 'helpers/Validar.php';

class VentaControlador {
    private Venta         $modelo;
    private Bitacora      $bitacora;
    private Transferencia $modeloTransf;

    public function __construct() {
        $this->modelo       = new Venta();
        $this->bitacora     = new Bitacora();
        $this->modeloTransf = new Transferencia();
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
        // 6.2.3 Verificar CSRF
        Csrf::requerir(true);

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['detalle']) || !is_array($input['detalle'])) {
            $this->json(['ok'=>false,'mensaje'=>'El carrito está vacío.']);
            return;
        }

        $total   = 0;
        $detalle = [];

        foreach ($input['detalle'] as $it) {
            try {
                $cod  = Validar::codigoProd($it['codigoprod'] ?? '');
                $cant = Validar::cantidad($it['cantidad'] ?? 0);
                $pu   = Validar::monto($it['precio_unitario'] ?? 0, 0.01);
            } catch (\InvalidArgumentException $e) {
                $this->json(['ok'=>false,'mensaje'=>'Dato inválido en carrito: ' . $e->getMessage()]);
                return;
            }
            $subtotal  = round($cant * $pu, 2);
            $total    += $subtotal;
            $detalle[] = [
                'codigoprod'      => $cod,
                'cantidad'        => $cant,
                'precio_unitario' => $pu,
                'subtotal'        => $subtotal,
            ];
        }

        // Total de venta no puede superar $500
        if ($total > 500) {
            $this->json(['ok'=>false,'mensaje'=>'El total de la venta no puede superar $500.00.']);
            return;
        }

        $cabecera = [
            'fecha'       => date('Y-m-d'),
            'total'       => round($total, 2),
            'metodo_pago' => Validar::metodoPago($input['metodo_pago'] ?? 'efectivo'),
            'nota'        => mb_substr(trim($input['nota'] ?? ''), 0, 200),
        ];

        $clave   = $_SESSION['usuario'] ?? null;
        $errMsg  = null;
        $ventaId = $this->modelo->registrar($cabecera, $detalle, $errMsg);

        if ($ventaId) {
            if ($cabecera['metodo_pago'] === 'transferencia') {
                $notaTransf = !empty($cabecera['nota'])
                    ? $cabecera['nota']
                    : 'Venta ID:' . $ventaId;
                $this->modeloTransf->registrar([
                    'fecha'    => $cabecera['fecha'],
                    'monto'    => $total,
                    'concepto' => $notaTransf,
                    'clave'    => $clave,
                    'venta_id' => $ventaId,
                ]);
            }
            $this->bitacora->registrar($clave, "Venta registrada ID:{$ventaId} total:\${$total} método:{$cabecera['metodo_pago']}", 'C');
            $this->json(['ok'=>true, 'venta_id'=>$ventaId, 'mensaje'=>'Venta registrada correctamente.']);
        } else {
            // Usar mensaje del trigger/BD si está disponible
            $mensajeError = $errMsg ?: 'Error al registrar la venta. Verifica el stock.';
            $this->bitacora->registrar($clave, "Error al registrar venta: {$mensajeError}", 'E');
            $this->json(['ok'=>false, 'mensaje'=>$mensajeError]);
        }
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
