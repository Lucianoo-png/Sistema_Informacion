<?php
// =====================================================
// control/CompraControlador.php
// Acepta detalle de productos y actualiza inventario
// =====================================================

require_once BASE_PATH . 'modelo/Compra.php';
require_once BASE_PATH . 'modelo/Bitacora.php';
require_once BASE_PATH . 'helpers/Csrf.php';
require_once BASE_PATH . 'helpers/Validar.php';

class CompraControlador {
    private Compra   $modelo;
    private Bitacora $bitacora;

    public function __construct() {
        $this->modelo   = new Compra();
        $this->bitacora = new Bitacora();
    }

    public function obtenerDelDia(string $fecha = ''): array {
        return $this->modelo->obtenerDelDia($fecha ?: date('Y-m-d'));
    }

    public function totalDelDia(string $fecha = ''): float {
        return $this->modelo->totalDelDia($fecha ?: date('Y-m-d'));
    }

    // POST /compras/registrar
    // Payload: { tipo, proveedor_id, total, nota, detalle:[{codigoprod,cantidad,precio_unitario,subtotal}] }
    public function registrar(): void {
        Csrf::requerir(true);
        $input = json_decode(file_get_contents('php://input'), true);
        $clave = $_SESSION['usuario'] ?? null;

        $total   = (float)($input['total']  ?? 0);
        $detalle = $input['detalle'] ?? [];

        if ($total <= 0) {
            $this->json(['ok' => false, 'mensaje' => 'El monto debe ser mayor a cero.']);
            return;
        }
        if (($input['tipo'] ?? '') === 'proveedor' && empty($input['proveedor_id'])) {
            $this->json(['ok' => false, 'mensaje' => 'Selecciona un proveedor.']);
            return;
        }

        $cabecera = [
            'fecha'        => date('Y-m-d'),
            'proveedor_id' => !empty($input['proveedor_id']) ? (int)$input['proveedor_id'] : null,
            'tipo'         => $input['tipo'] ?? 'directa',
            'total'        => $total,
            'nota'         => mb_substr(trim($input['nota'] ?? ''), 0, 200),
        ];

        // Con detalle de productos: transacción que actualiza stock
        // Sin detalle: registro simple (compatibilidad)
        $id = !empty($detalle)
            ? $this->modelo->registrar($cabecera, $detalle)
            : $this->modelo->registrarSimple($cabecera);

        if ($id) {
            $nProd = count($detalle);
            $prov  = ($cabecera['tipo'] === 'proveedor')
                   ? ' proveedor ID:' . ($cabecera['proveedor_id'] ?? '?')
                   : ' directa';
            $this->bitacora->registrar(
                $clave,
                "Compra registrada ID:{$id} total:\${$total}{$prov}"
                    . ($nProd > 0 ? " ({$nProd} producto(s))" : ''),
                'C'
            );
            $this->json(['ok' => true, 'compra_id' => $id,
                         'mensaje' => 'Compra registrada correctamente.']);
        } else {
            $this->bitacora->registrar(
                $clave, "Error al registrar compra total:\${$total}", 'E'
            );
            $this->json(['ok' => false, 'mensaje' => 'Error al registrar la compra.']);
        }
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
