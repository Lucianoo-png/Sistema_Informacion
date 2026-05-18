<?php
// vista/404.php
require_once BASE_PATH . 'helpers/layout.php';
$paginaActual = '';
abrirLayout('Página no encontrada', '');
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center">
    <div style="font-size:80px;margin-bottom:16px">🏪</div>
    <h1 style="font-size:32px;margin-bottom:8px">404 — Página no encontrada</h1>
    <p style="color:#888;margin-bottom:24px">La sección que buscas no existe.</p>
    <a href="<?= BASE_URL ?>panel" class="btn btn-primary">Ir al Panel Principal</a>
</div>
<?php cerrarLayout(); ?>
