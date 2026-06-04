<?php
require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'control/ReporteControlador.php';

$paginaActual = 'panel';
$ctrl  = new ReporteControlador();
$datos = $ctrl->resumenPanel();
$hoy   = fechaEspanol();

abrirLayout('Panel Principal', 'panel');
?>
<div class="pag-wrap-lg">

<div class="page-header">
    <h1>Panel Principal</h1>
    <p><?= $hoy ?></p>
</div>

<!-- Estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fa-solid fa-chart-line fa-lg" style="color:#e87722"></i>
        </div>
        <div>
            <div class="stat-label">Ventas del día</div>
            <div class="stat-value"><?= formatMXN($datos['ventas_dia']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-cart-shopping fa-lg" style="color:#38a169"></i>
        </div>
        <div>
            <div class="stat-label">Transacciones</div>
            <div class="stat-value"><?= $datos['transacciones'] ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fa-solid fa-box fa-lg" style="color:#d97706"></i>
        </div>
        <div>
            <div class="stat-label">Compras del día</div>
            <div class="stat-value"><?= formatMXN($datos['compras_dia']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-wallet fa-lg" style="color:#38a169"></i>
        </div>
        <div>
            <div class="stat-label">Balance en caja</div>
            <div class="stat-value"><?= formatMXN($datos['balance']) ?></div>
        </div>
    </div>
</div>

<!-- Alerta stock bajo -->
<?php if ($datos['stock_bajo'] > 0): ?>
<div class="alert-stock">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <?= $datos['stock_bajo'] ?> producto<?= $datos['stock_bajo'] > 1 ? 's' : '' ?> con stock bajo. Revisa el inventario.
</div>
<?php endif; ?>

<!-- Recientes -->
<div class="two-col">
    <div class="card">
        <div class="card-title">
            <i class="fa-regular fa-clock" style="color:var(--primary)"></i> Ventas Recientes
        </div>
        <?php if (empty($datos['ventas_recientes'])): ?>
            <div class="empty-state">Sin ventas hoy</div>
        <?php else: ?>
            <?php foreach (array_slice($datos['ventas_recientes'], 0, 5) as $v): ?>
            <div class="report-row">
                <div>
                    <!-- Productos con cantidad: "cerveza x7, Cigarros x16" -->
                    <div style="font-weight:500;font-size:13px;line-height:1.4">
                        <?= htmlspecialchars($v['productos'] ?? 'Venta') ?>
                    </div>
                    <div style="font-size:12px;color:#888;margin-top:2px">
                        <i class="fas fa-<?= $v['metodo_pago']==='efectivo'?'money-bill-wave':'exchange-alt' ?>"
                           style="color:var(--primary)"></i>
                        <?= ucfirst($v['metodo_pago']) ?>
                    </div>
                </div>
                <div class="price" style="white-space:nowrap"><?= formatMXN($v['total']) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-box" style="color:var(--primary)"></i> Compras Recientes
        </div>
        <?php if (empty($datos['compras_recientes'])): ?>
            <div class="empty-state">Sin compras registradas</div>
        <?php else: ?>
            <?php foreach (array_slice($datos['compras_recientes'], 0, 5) as $c): ?>
            <div class="report-row">
                <div>
                    <!-- Productos comprados con cantidad -->
                    <?php if (!empty($c['productos'])): ?>
                    <div style="font-weight:500;font-size:13px;line-height:1.4">
                        <?= htmlspecialchars($c['productos']) ?>
                    </div>
                    <?php endif; ?>
                    <div style="font-size:12px;color:#888;margin-top:2px">
                        <i class="fa-solid fa-truck" style="color:var(--primary)"></i>
                        <?= htmlspecialchars($c['proveedor_nombre']) ?>
                    </div>
                </div>
                <div class="price" style="white-space:nowrap"><?= formatMXN($c['total']) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</div>
<?php cerrarLayout(); ?>
