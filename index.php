<?php
// =====================================================
// index.php — Router principal Abarrotes Angy
// =====================================================

// ── Zona horaria CDMX ─────────────────────────────
date_default_timezone_set('America/Mexico_City');

session_start();

define('BASE_PATH', __DIR__ . '/');
define('BASE_URL',  'http://' . $_SERVER['HTTP_HOST'] . '/AbarrotesAngy/');

// ── Helpers de seguridad ──────────────────────────
require_once BASE_PATH . 'helpers/Csrf.php';
require_once BASE_PATH . 'helpers/Validar.php';

// ── Carga modelos y controladores ─────────────────
require_once BASE_PATH . 'modelo/Conexion.php';
require_once BASE_PATH . 'control/ProductoControlador.php';
require_once BASE_PATH . 'control/VentaControlador.php';
require_once BASE_PATH . 'control/CompraControlador.php';
require_once BASE_PATH . 'control/ProveedorControlador.php';
require_once BASE_PATH . 'control/TransferenciaControlador.php';
require_once BASE_PATH . 'control/ReporteControlador.php';
require_once BASE_PATH . 'control/CuentaControlador.php';
require_once BASE_PATH . 'control/BitacoraControlador.php';

// ── Parsear URL ────────────────────────────────────
$url    = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$partes = array_filter(explode('/', $url));
$partes = array_values($partes);

$seccion = $partes[0] ?? 'panel';
$accion  = $partes[1] ?? 'index';
$param   = $partes[2] ?? '';

// ── Rutas públicas (sin autenticación) ────────────
if ($seccion === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl = new CuentaControlador();
        $ctrl->login();
    } else {
        require_once BASE_PATH . 'vista/login.php';
    }
    exit;
}

if ($seccion === 'logout') {
    $ctrl = new CuentaControlador();
    $ctrl->logout();
    exit;
}

// ── Verificar sesión activa ────────────────────────
if (empty($_SESSION['usuario'])) {
    header('Location: ' . BASE_URL . 'login');
    exit;
}

// ── Generar/mantener token CSRF ──────────────────────
Csrf::generar();

// ── Validar sesión única (como WhatsApp) ─────────────
// $_SESSION['session_token'] = session_id() raw
// BD guarda hash('sha256', session_id())
// Si otro perfil/dispositivo inició sesión, el hash en BD cambió
// y esta sesión queda inválida automáticamente.
if (!empty($_SESSION['usuario']) && !empty($_SESSION['session_token'])) {
    try {
        require_once BASE_PATH . 'modelo/Cuenta.php';
        $cuentaModel = new Cuenta();
        $hashEnBD    = $cuentaModel->obtenerTokenSesion($_SESSION['usuario']);
        // Calcular hash del session_id guardado en esta sesión
        $hashActual  = hash('sha256', $_SESSION['session_token']);

        if ($hashEnBD !== null && $hashEnBD !== '' && !hash_equals($hashEnBD, $hashActual)) {
            // Otro dispositivo/perfil inició sesión — esta sesión fue desplazada
            session_destroy();
            header('Location: ' . BASE_URL . 'login?err=sesion_desplazada');
            exit;
        }
    } catch (\Throwable $e) {
        // Si falla la consulta de BD, no bloquear (fail-open)
        error_log('Session validation error: ' . $e->getMessage());
    }
}

// ── Rutas protegidas ──────────────────────────────
switch ($seccion) {

    case '':
    case 'panel':
        require_once BASE_PATH . 'vista/admin/panel.php';
        break;

    case 'ventas':
        $ctrl = new VentaControlador();
        if ($accion === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->registrar();
        } elseif ($accion === 'historial') {
            require_once BASE_PATH . 'vista/vendedor/historial_ventas.php';
        } else {
            require_once BASE_PATH . 'vista/vendedor/ventas.php';
        }
        break;

    case 'compras':
        $ctrl = new CompraControlador();
        if ($accion === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->registrar();
        } elseif ($accion === 'historial') {
            require_once BASE_PATH . 'vista/admin/historial_compras.php';
        } else {
            require_once BASE_PATH . 'vista/admin/compras.php';
        }
        break;

    case 'inventario':
        $ctrl = new ProductoControlador();
        if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->crear();
        } elseif ($accion === 'actualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->actualizar();
        } elseif ($accion === 'eliminar' && $param) {
            $ctrl->eliminar(urldecode($param));
        } else {
            require_once BASE_PATH . 'vista/admin/inventario.php';
        }
        break;

    case 'proveedores':
        $ctrl = new ProveedorControlador();
        if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->crear();
        } elseif ($accion === 'actualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->actualizar();
        } elseif ($accion === 'eliminar' && $param) {
            $ctrl->eliminar((int)$param);
        } else {
            require_once BASE_PATH . 'vista/admin/proveedores.php';
        }
        break;

    case 'transferencias':
        $ctrl = new TransferenciaControlador();
        if ($accion === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ctrl->registrar();
        } elseif ($accion === 'eliminar' && $param) {
            $ctrl->eliminar((int)$param);
        } else {
            require_once BASE_PATH . 'vista/admin/transferencias.php';
        }
        break;

    case 'reporte':
        require_once BASE_PATH . 'vista/admin/reporte_diario.php';
        break;

    case 'corte':
        require_once BASE_PATH . 'vista/admin/corte_caja.php';
        break;

    case 'bitacora':
        require_once BASE_PATH . 'vista/admin/bitacora.php';
        break;

    // ── API JSON (AJAX interno) ───────────────────
    case 'api':
        header('Content-Type: application/json');
        require_once BASE_PATH . 'modelo/Venta.php';
        require_once BASE_PATH . 'modelo/Compra.php';
        switch ($accion) {
            case 'productos':
                $ctrl = new ProductoControlador();
                echo json_encode($ctrl->listarTodos());
                break;
            // Stock en tiempo real — endpoint ligero para polling
            case 'stock':
                require_once BASE_PATH . 'modelo/Producto.php';
                $modelo = new Producto();
                $rows   = $modelo->obtenerTodos();
                $stock  = [];
                foreach ($rows as $p) {
                    $stock[$p['codigoprod']] = [
                        'stock' => $p['stock'],
                        'unidad'=> $p['unidad'],
                    ];
                }
                header('Cache-Control: no-store');
                echo json_encode($stock);
                break;
            case 'proveedores':
                $ctrl = new ProveedorControlador();
                echo json_encode($ctrl->listarTodos());
                break;
            case 'panel':
                $ctrl = new ReporteControlador();
                echo json_encode($ctrl->resumenPanel());
                break;
            // Auto-code para nuevo producto
            case 'siguiente-codigo':
                require_once BASE_PATH . 'modelo/Producto.php';
                $modelo = new Producto();
                echo json_encode(['codigo' => $modelo->siguienteCodigoprod()]);
                break;
            // Reporte por rango de fechas
            case 'reporte-rango':
                $ctrl  = new ReporteControlador();
                $desde = $_GET['desde'] ?? date('Y-m-d');
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                echo json_encode($ctrl->reporteRango($desde, $hasta));
                break;
            // Detalle de una venta
            case 'venta-detalle':
                $vid = (int)($_GET['id'] ?? 0);
                if ($vid > 0) {
                    $modelo = new \Venta();
                    echo json_encode($modelo->obtenerDetalle($vid));
                } else {
                    echo json_encode([]);
                }
                break;
            // Detalle de una compra
            case 'compra-detalle':
                $cid = (int)($_GET['id'] ?? 0);
                if ($cid > 0) {
                    $modelo = new \Compra();
                    echo json_encode($modelo->obtenerDetalle($cid));
                } else {
                    echo json_encode([]);
                }
                break;
            default:
                echo json_encode(['error' => 'Endpoint no encontrado']);
        }
        exit;

    default:
        http_response_code(404);
        require_once BASE_PATH . 'vista/404.php';
        break;
}
?>
