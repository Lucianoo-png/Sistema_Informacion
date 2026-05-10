<?php
// =====================================================
// helpers/layout.php
// CSS cargado desde abarrotes.css (ruta relativa al servidor)
// =====================================================

// ── Zona horaria CDMX (respaldo por si no viene de index.php) ──
if (date_default_timezone_get() !== 'America/Mexico_City') {
    date_default_timezone_set('America/Mexico_City');
}

function abrirLayout(string $titulo, string $paginaActual): void {
    $base     = defined('BASE_URL') ? BASE_URL : './';
    $usuario  = $_SESSION['nombre']    ?? '';
    $apellido = $_SESSION['apellidos'] ?? '';
    $clave    = $_SESSION['usuario']   ?? '';

    // Ruta al CSS: relativa al servidor web, siempre funciona en XAMPP
    // Detecta automáticamente el subfolder del proyecto
    $script    = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']); // /AbarrotesAngy/index.php
    $folder    = rtrim(dirname($script), '/');                    // /AbarrotesAngy
    $cssUrl    = $folder . '/estilos/abarrotes.css';              // /AbarrotesAngy/estilos/abarrotes.css
    $jsUrl     = $folder . '/js/abarrotes.js';

    echo '<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="base-url" content="' . $base . '">
  <title>' . htmlspecialchars($titulo) . ' — Abarrotes Angy</title>
  <link rel="stylesheet" href="' . $cssUrl . '">
  <!-- Font Awesome 6 Free — CSS CDN (gratis, sin atribución en UI) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>';

    require_once BASE_PATH . 'control/navbar.php';

    echo '<div class="main-content">
  <div class="topbar-user">
    <span class="topbar-welcome"><i class="fa-solid fa-circle-user" style="color:var(--primary)"></i> '
        . htmlspecialchars($usuario . ' ' . $apellido)
        . ' <small>(' . htmlspecialchars($clave) . ')</small></span>
    <a href="' . $base . 'logout" class="btn-logout">Cerrar sesión</a>
  </div>';

    // Guardar la ruta JS para cerrarLayout()
    $GLOBALS['_layout_js'] = $jsUrl;
}

function cerrarLayout(): void {
    $jsUrl = $GLOBALS['_layout_js'] ?? '/AbarrotesAngy/js/abarrotes.js';
    $base  = defined('BASE_URL') ? BASE_URL : './';

    echo '</div>
  <div id="toast" class="toast"></div>
  <script>
    const BASE = document.querySelector(\'meta[name="base-url"]\')?.content ?? "./";
    function mostrarToast(msg, tipo = "ok") {
      let t = document.getElementById("toast");
      t.textContent = msg;
      t.className = "toast " + tipo + " show";
      clearTimeout(t._tmr);
      t._tmr = setTimeout(() => t.classList.remove("show"), 3200);
    }
    function formatMXN(n) {
      return "$" + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
  </script>
  <script src="' . $jsUrl . '"></script>
</body>
</html>';
}

function formatMXN(float $n): string {
    return '$' . number_format($n, 2);
}

function fechaEspanol(string $fecha = ''): string {
    $ts    = $fecha ? strtotime($fecha) : time();
    $dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
              'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    return $dias[date('w',$ts)] . ', ' . date('j',$ts)
         . ' De ' . $meses[(int)date('n',$ts)]
         . ' De ' . date('Y',$ts);
}
?>
