<?php
// =====================================================
// control/BitacoraControlador.php
// =====================================================

require_once BASE_PATH . 'modelo/Bitacora.php';

class BitacoraControlador {
    private Bitacora $modelo;

    public function __construct() {
        $this->modelo = new Bitacora();
    }

    public function obtener(string $fecha='', string $cuenta='', string $estado=''): array {
        return $this->modelo->obtener($fecha, $cuenta, $estado, 200);
    }

    /** Solo inicios de sesión y registros de venta/compra — sin errores */
    public function obtenerRelevantes(string $fecha='', string $cuenta=''): array {
        return $this->modelo->obtenerRelevantes($fecha, $cuenta, 200);
    }

    public function totalHoy(): int   { return $this->modelo->totalHoyRelevantes(); }

    /** Registra una acción (lo usan otros controladores) */
    public function registrar(string $clave, string $desc, string $estado = 'C'): void {
        $this->modelo->registrar($clave, $desc, $estado);
    }
}
?>
