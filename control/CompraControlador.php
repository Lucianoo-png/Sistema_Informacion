<?php
// =====================================================
// control/CompraControlador.php
// CAMBIO: codigoprod VARCHAR en detalle, bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Compra.php';
require_once BASE_PATH . 'modelo/Bitacora.php';

class CompraControlador {
    private Compra   $modelo;
    private Bitacora $bitacora;

    public function __construct() {
        $this->modelo   = new Compra();
        $this->bitacora = new Bitacora();
    }

    public function obtenerDelDia(string $fecha=''): array {
        return $this->modelo->obtenerDelDia($fecha ?: date('Y-m-d'));
    }

    public function totalDelDia(string $fecha=''): float {
        return $this->modelo->totalDelDia($fecha ?: date('Y-m-d'));
    }

    // CREATE — JSON: {tipo, proveedor_id, nota, detalle:[{codigoprod,cantidad,precio_unitario}]}
    public function registrar(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['detalle'])) {
            $this->json(['ok'=>false,'mensaje'=>'Agrega al menos un producto.']);
            return;
        }

        $total   = 0;
        $detalle = [];

        foreach ($input['detalle'] as $it) {
            $sub = (float)$it['cantidad'] * (float)$it['precio_unitario'];
            $total += $sub;
            $detalle[] = [
                'codigoprod'      => $it['codigoprod'],
                'cantidad'        => (int)$it['cantidad'],
                'precio_unitario' => (float)$it['precio_unitario'],
            ];
        }

        $cabecera = [
            'fecha'        => date('Y-m-d'),
            'proveedor_id' => (int)($input['proveedor_id'] ?? 0) ?: null,
            'tipo'         => $input['tipo'] ?? 'directa',
            'total'        => $total,
            'nota'         => $input['nota'] ?? null,
        ];

        $id    = $this->modelo->registrar($cabecera, $detalle);
        $clave = $_SESSION['usuario'] ?? 'SIST';

        if ($id) {
            $this->bitacora->registrar($clave, "Compra registrada ID:{$id} total:\${$total}", 'C');
            $this->json(['ok'=>true,'compra_id'=>$id,'mensaje'=>'Compra registrada correctamente.']);
        } else {
            $this->bitacora->registrar($clave, "Error al registrar compra total:\${$total}", 'E');
            $this->json(['ok'=>false,'mensaje'=>'Error al registrar la compra.']);
        }
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
