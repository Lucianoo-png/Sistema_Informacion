<?php
// =====================================================
// control/TransferenciaControlador.php — con bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Transferencia.php';
require_once BASE_PATH . 'modelo/Bitacora.php';

class TransferenciaControlador {
    private Transferencia $modelo;
    private Bitacora      $bitacora;

    public function __construct() {
        $this->modelo   = new Transferencia();
        $this->bitacora = new Bitacora();
    }

    public function obtenerDelDia(string $fecha=''): array {
        return $this->modelo->obtenerDelDia($fecha ?: date('Y-m-d'));
    }
    public function totalDelDia(string $fecha=''): float {
        return $this->modelo->totalDelDia($fecha ?: date('Y-m-d'));
    }
    public function obtenerTodas(): array { return $this->modelo->obtenerTodas(); }

    // CREATE
    public function registrar(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $monto = (float)($input['monto'] ?? 0);

        if ($monto <= 0) {
            $this->json(['ok'=>false,'mensaje'=>'El monto debe ser mayor a cero.']);
            return;
        }

        $d  = [
            'fecha'      => date('Y-m-d'),
            'monto'      => $monto,
            'concepto'   => trim($input['concepto']   ?? ''),
            'referencia' => trim($input['referencia'] ?? ''),
        ];
        $ok    = $this->modelo->registrar($d);
        $clave = $_SESSION['usuario'] ?? 'SIST';

        $this->bitacora->registrar(
            $clave,
            $ok ? "Transferencia registrada: \${$monto}" : "Error al registrar transferencia \${$monto}",
            $ok ? 'C' : 'E'
        );
        $this->json(['ok'=>$ok,'mensaje'=> $ok ? 'Transferencia registrada.' : 'Error al registrar.']);
    }

    // DELETE
    public function eliminar(int $id): void {
        $ok    = $this->modelo->eliminar($id);
        $clave = $_SESSION['usuario'] ?? 'SIST';
        $this->bitacora->registrar($clave, $ok ? "Transferencia eliminada ID:{$id}" : "Error al eliminar transferencia ID:{$id}", $ok ? 'C' : 'E');
        $this->json(['ok'=>$ok,'mensaje'=> $ok ? 'Transferencia eliminada.' : 'Error.']);
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
