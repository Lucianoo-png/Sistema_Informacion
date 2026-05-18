<?php
// =====================================================
// control/CuentaControlador.php — Login / Logout
// =====================================================

require_once BASE_PATH . 'modelo/Cuenta.php';
require_once BASE_PATH . 'modelo/Bitacora.php';

class CuentaControlador {
    private Cuenta   $modelo;
    private Bitacora $bitacora;

    public function __construct() {
        $this->modelo   = new Cuenta();
        $this->bitacora = new Bitacora();
    }

    /** Procesa el formulario de login (POST) */
    public function login(): void {
        $clave  = trim($_POST['clave']       ?? '');
        $pass   = trim($_POST['contrasena']  ?? '');

        if (!$clave || !$pass) {
            $this->redirigirConError('Completa todos los campos.');
            return;
        }

        $cuenta = $this->modelo->autenticar($clave, $pass);

        if ($cuenta) {
            // Iniciar sesión
            $_SESSION['usuario']  = $cuenta['ClaveCuenta'] ?? $cuenta['clavecuenta'];
            $_SESSION['nombre']   = $cuenta['Nombre']      ?? $cuenta['nombre'];
            $_SESSION['apellidos']= $cuenta['Apellidos']   ?? $cuenta['apellidos'];

            // Registrar en bitácora
            $this->bitacora->registrar(
                $_SESSION['usuario'],
                "Inicio de sesión exitoso desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'),
                'C'
            );

            header('Location: ' . BASE_URL . 'panel');
            exit;
        }

        // Credenciales incorrectas — intentar registrar error en bitácora
        // (puede fallar si la clave no existe, lo ignoramos)
        try {
            $this->bitacora->registrar(
                $clave,
                "Intento de inicio de sesión fallido.",
                'E'
            );
        } catch (\Exception) { /* ignorar */ }

        $this->redirigirConError('Clave o contraseña incorrecta.');
    }

    /** Cierra la sesión */
    public function logout(): void {
        if (!empty($_SESSION['usuario'])) {
            $this->bitacora->registrar(
                $_SESSION['usuario'],
                "Cierre de sesión.",
                'C'
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    /** Devuelve lista de cuentas (para gestión) */
    public function listarTodas(): array {
        return $this->modelo->obtenerTodas();
    }

    /** Crear cuenta nueva */
    public function crear(): void {
        $d = [
            'clave'      => strtoupper(trim($_POST['clave']     ?? '')),
            'contrasena' => trim($_POST['contrasena'] ?? ''),
            'nombre'     => trim($_POST['nombre']     ?? ''),
            'apellidos'  => trim($_POST['apellidos']  ?? ''),
        ];

        if (strlen($d['clave']) !== 5 || empty($d['contrasena'])) {
            $this->responderJson(['ok'=>false,'mensaje'=>'La clave debe tener exactamente 5 caracteres.']);
            return;
        }

        $ok = $this->modelo->crear($d);
        if ($ok && !empty($_SESSION['usuario'])) {
            $this->bitacora->registrar($_SESSION['usuario'], "Cuenta creada: {$d['clave']}", 'C');
        }
        $this->responderJson(['ok'=>$ok,'mensaje'=>$ok?'Cuenta creada.':'Error al crear cuenta (¿clave duplicada?).']);
    }

    private function redirigirConError(string $msg): void {
        $_SESSION['login_error'] = $msg;
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    private function responderJson(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>
