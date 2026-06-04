<?php
// =====================================================
// control/CuentaControlador.php — Login / Logout
// MODIFICADO: Previene sesiones dobles con token de sesión
// =====================================================

require_once BASE_PATH . 'modelo/Cuenta.php';
require_once BASE_PATH . 'modelo/Bitacora.php';
require_once BASE_PATH . 'helpers/Csrf.php';
require_once BASE_PATH . 'helpers/Validar.php';

class CuentaControlador {
    private Cuenta   $modelo;
    private Bitacora $bitacora;

    public function __construct() {
        $this->modelo   = new Cuenta();
        $this->bitacora = new Bitacora();
    }

    /** Procesa el formulario de login (POST) */
    public function login(): void {
        // 6.2.3 CSRF en formulario de login
        if (!Csrf::verificarPost()) {
            $this->redirigirConError('Token de seguridad inválido. Intenta de nuevo.');
            return;
        }
        Csrf::rotar();

        try {
            $clave = Validar::claveCuenta($_POST['clave'] ?? '');
        } catch (\InvalidArgumentException $e) {
            $this->redirigirConError('Clave o contraseña incorrecta.');
            return;
        }
        $pass = mb_substr(trim($_POST['contrasena'] ?? ''), 0, 72); // bcrypt max

        if (!$clave || !$pass) {
            $this->redirigirConError('Completa todos los campos.');
            return;
        }

        $cuenta = $this->modelo->autenticar($clave, $pass);

        if ($cuenta) {
            $claveSesion = $cuenta['ClaveCuenta'] ?? $cuenta['clavecuenta'];

            // Contraseña correcta → siempre permitir entrar.
            // El token nuevo sobreescribe cualquier sesión anterior (como WhatsApp).
            // El dispositivo anterior quedará invalidado en su próximo request.

            // Iniciar sesión
            $_SESSION['usuario']       = $claveSesion;
            $_SESSION['nombre']        = $cuenta['Nombre']      ?? $cuenta['nombre'];
            $_SESSION['apellidos']     = $cuenta['Apellidos']   ?? $cuenta['apellidos'];
            // Guardar session_id sin hashear en sesión
            // El modelo lo hashea al guardarlo en BD
            $_SESSION['session_token'] = session_id();

            // Guardar hash del session_id en BD
            $this->modelo->guardarTokenSesion($claveSesion, session_id());

            // Registrar en bitácora
            $this->bitacora->registrar(
                $_SESSION['usuario'],
                "Inicio de sesión exitoso desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'),
                'C'
            );

            header('Location: ' . BASE_URL . 'panel');
            exit;
        }

        // Credenciales incorrectas
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
            // Limpiar token de sesión en BD para permitir nuevo login
            $this->modelo->limpiarTokenSesion($_SESSION['usuario']);
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
        Csrf::requerir(true);
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
