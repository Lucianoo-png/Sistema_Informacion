<?php
// =====================================================
// control/CompraControlador.php
// SIMPLIFICADO: acepta total directo (sin detalle de productos)
// Compatible con la tabla compras que ya NO tiene compra_detalle
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

    public function obtenerDelDia(string $fecha = ''): array {
        return $this->modelo->obtenerDelDia($fecha ?: date('Y-m-d'));
    }

    public function totalDelDia(string $fecha = ''): float {
        return $this->modelo->totalDelDia($fecha ?: date('Y-m-d'));
    }

    // CREATE — acepta {tipo, proveedor_id, total, nota/descripcion}
    public function registrar(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $clave = $_SESSION['usuario'] ?? 'SIST';

        // Monto directo (formulario simplificado)
        $total = (float)($input['total'] ?? 0);
        if ($total <= 0) {
            $this->json(['ok' => false, 'mensaje' => 'El monto debe ser mayor a cero.']);
            return;
        }

        $cabecera = [
            'fecha'        => date('Y-m-d'),
            'proveedor_id' => !empty($input['proveedor_id']) ? (int)$input['proveedor_id'] : null,
            'tipo'         => $input['tipo'] ?? 'directa',
            'total'        => $total,
            // Usar descripcion si existe, sino nota
            'descripcion'  => trim($input['nota'] ?? $input['descripcion'] ?? ''),
        ];

        $id = $this->modelo->registrarSimple($cabecera);

        if ($id) {
            $prov = $input['tipo'] === 'proveedor' ? " (proveedor ID:" . ($cabecera['proveedor_id'] ?? '?') . ")" : '';
            $this->bitacora->registrar($clave,
                "Compra registrada ID:{$id} total:\${$total}{$prov}", 'C');
            $this->json(['ok' => true, 'compra_id' => $id,
                         'mensaje' => 'Compra registrada correctamente.']);
        } else {
            $this->bitacora->registrar($clave,
                "Error al registrar compra total:\${$total}", 'E');
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
