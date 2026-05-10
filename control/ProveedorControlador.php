<?php
// =====================================================
// control/ProveedorControlador.php
// CAMBIO: valida teléfono 10 dígitos, bitácora
// =====================================================

require_once BASE_PATH . 'modelo/Proveedor.php';
require_once BASE_PATH . 'modelo/Bitacora.php';

class ProveedorControlador {
    private Proveedor $modelo;
    private Bitacora  $bitacora;

    public function __construct() {
        $this->modelo   = new Proveedor();
        $this->bitacora = new Bitacora();
    }

    public function listarTodos(): array { return $this->modelo->obtenerTodos(); }

    // CREATE
    public function crear(): void {
        $d = [
            'nombre'   => trim($_POST['nombre']    ?? ''),
            'telefono' => trim($_POST['telefono']  ?? ''),
            'DiaVisita'=> trim($_POST['DiaVisita'] ?? ''),
        ];

        if (empty($d['nombre'])) {
            $this->json(['ok'=>false,'mensaje'=>'El nombre es obligatorio.']);
            return;
        }
        if (!preg_match('/^[0-9]{10}$/', $d['telefono'])) {
            $this->json(['ok'=>false,'mensaje'=>'El teléfono debe tener exactamente 10 dígitos numéricos.']);
            return;
        }

        $id = $this->modelo->crear($d);
        $ok = $id !== false;
        $this->log($ok, "Proveedor creado: {$d['nombre']} (ID:{$id})", "Error al crear proveedor {$d['nombre']}");
        $this->json(['ok'=>$ok,'mensaje'=> $ok ? 'Proveedor creado.' : 'Error al guardar.']);
    }

    // UPDATE
    public function actualizar(): void {
        $id = (int)($_POST['id'] ?? 0);
        $d  = [
            'nombre'   => trim($_POST['nombre']    ?? ''),
            'telefono' => trim($_POST['telefono']  ?? ''),
            'DiaVisita'=> trim($_POST['DiaVisita'] ?? ''),
        ];

        if (!preg_match('/^[0-9]{10}$/', $d['telefono'])) {
            $this->json(['ok'=>false,'mensaje'=>'El teléfono debe tener exactamente 10 dígitos numéricos.']);
            return;
        }

        $ok = $this->modelo->actualizar($id, $d);
        $this->log($ok, "Proveedor actualizado ID:{$id}", "Error al actualizar proveedor ID:{$id}");
        $this->json(['ok'=>$ok,'mensaje'=> $ok ? 'Proveedor actualizado.' : 'Error al actualizar.']);
    }

    // DELETE
    public function eliminar(int $id): void {
        $ok = $this->modelo->eliminar($id);
        $this->log($ok, "Proveedor eliminado ID:{$id}", "Error al eliminar proveedor ID:{$id}");
        $this->json(['ok'=>$ok,'mensaje'=> $ok ? 'Proveedor eliminado.' : 'Error al eliminar.']);
    }

    private function log(bool $ok, string $desc, string $err): void {
        $clave = $_SESSION['usuario'] ?? 'SIST';
        try { $this->bitacora->registrar($clave, $ok ? $desc : $err, $ok ? 'C' : 'E'); }
        catch (\Exception) {}
    }

    private function json(array $d): void {
        header('Content-Type: application/json');
        echo json_encode($d);
        exit;
    }
}
?>
