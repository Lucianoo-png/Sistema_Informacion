<?php
// =====================================================
// vista/admin/corte_caja.php — Corte de Caja Multiperiodo
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'control/ReporteControlador.php';

$paginaActual = 'corte';
$ctrl         = new ReporteControlador();

$periodo = $_GET['periodo'] ?? 'hoy';
$desde   = $_GET['desde']   ?? date('Y-m-d');
$hasta   = $_GET['hasta']   ?? date('Y-m-d');

if ($periodo !== 'personalizado') {
    [$desde, $hasta] = match($periodo) {
        'semana' => ReporteControlador::rangoDelPeriodo('semana'),
        'mes'    => ReporteControlador::rangoDelPeriodo('mes'),
        'anio'   => ReporteControlador::rangoDelPeriodo('anio'),
        default  => [date('Y-m-d'), date('Y-m-d')],
    };
}

$datos = $ctrl->corteRango($desde, $hasta);

$etiqueta = match($periodo) {
    'semana'        => 'Esta semana',
    'mes'           => 'Este mes (' . date('F Y') . ')',
    'anio'          => 'Este año (' . date('Y') . ')',
    'personalizado' => $desde . ' — ' . $hasta,
    default         => fechaEspanol($desde),
};

abrirLayout('Corte de Caja', 'corte', BASE_URL . 'estilos/corte_caja.css');
?>


<div class="corte-wrap">

<div class="page-header">
    <h1><i class="fa-solid fa-cash-register" style="color:var(--primary)"></i> Corte de Caja</h1>
    <p><?= $etiqueta ?></p>
</div>

<!-- ── Tabs ─────────────────────────────────────── -->
<div class="periodo-tabs">
    <?php foreach (['hoy'=>'Hoy','semana'=>'Esta semana','mes'=>'Este mes','anio'=>'Este año','personalizado'=>'Personalizado'] as $p => $label): ?>
    <a href="corte?periodo=<?= $p ?><?= ($p==='personalizado')?'&desde='.$desde.'&hasta='.$hasta:'' ?>"
       class="periodo-tab <?= $periodo===$p?'active':'' ?>">
        <?php if ($p==='personalizado'): ?><i class="fa-solid fa-calendar-days"></i> <?php endif; ?>
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Personalizado ─────────────────────────────── -->
<?php if ($periodo === 'personalizado'): ?>
<div class="personalizado-row">
    <i class="fa-solid fa-calendar-days" style="color:var(--primary)"></i>
    <label style="font-size:12px;color:var(--text-muted);font-weight:600">Desde:</label>
    <input type="date" id="inp-desde" value="<?= $desde ?>">
    <label style="font-size:12px;color:var(--text-muted);font-weight:600">Hasta:</label>
    <input type="date" id="inp-hasta" value="<?= $hasta ?>">
    <button class="btn btn-primary btn-sm" onclick="aplicarPersonalizado()">
        <i class="fa-solid fa-magnifying-glass"></i> Consultar
    </button>
</div>
<?php endif; ?>

<!-- ── Ingresos ───────────────────────────────────── -->
<div class="card" style="margin-bottom:14px;border-left:4px solid var(--success)">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <i class="fa-solid fa-circle-arrow-up fa-lg" style="color:var(--success)"></i>
        <span style="font-size:15px;font-weight:700;color:var(--success)">Ingresos</span>
    </div>
    <div class="report-row">
        <div><i class="fa-solid fa-money-bill-wave" style="color:var(--success)"></i>&ensp;Ventas en Efectivo</div>
        <span><?= formatMXN($datos['efectivo']) ?></span>
    </div>
    <div class="report-row">
        <div><i class="fa-solid fa-right-left" style="color:var(--primary)"></i>&ensp;Ventas cobradas por Transferencia</div>
        <span><?= formatMXN($datos['transferencia']) ?></span>
    </div>
    <!-- CORRECCIÓN: línea nueva — transferencias bancarias de la tabla transferencias -->
    <div class="report-row">
        <div><i class="fa-solid fa-building-columns" style="color:var(--primary)"></i>&ensp;Transferencias Bancarias Recibidas</div>
        <span><?= formatMXN($datos['transferencias_banco']) ?></span>
    </div>
    <div class="report-row total" style="border-top:2px solid var(--border);margin-top:4px;padding-top:12px">
        <strong>Total Ingresos</strong>
        <strong class="amount-pos"><?= formatMXN($datos['total_ingresos']) ?></strong>
    </div>
</div>

<!-- ── Egresos ───────────────────────────────────── -->
<div class="card" style="margin-bottom:14px;border-left:4px solid var(--danger)">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <i class="fa-solid fa-circle-arrow-down fa-lg" style="color:var(--danger)"></i>
        <span style="font-size:15px;font-weight:700;color:var(--danger)">Egresos</span>
    </div>
    <div class="report-row">
        <div><i class="fa-solid fa-box" style="color:var(--danger)"></i>&ensp;Pagos a Proveedores / Compras</div>
        <span class="amount-neg">-<?= formatMXN($datos['total_compras']) ?></span>
    </div>
</div>

<!-- ── Balance final ─────────────────────────────── -->
<?php $balance = $datos['balance_final']; ?>
<div class="balance-card card">
    <div>
        <div class="balance-label">
            <i class="fa-solid fa-cash-register"></i>&ensp;BALANCE FINAL
        </div>
        <div class="balance-sub">
            <?= $datos['num_ventas'] ?> ventas en el período
        </div>
        <?php if ($periodo !== 'hoy'): ?>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px">
            <?= $desde ?> → <?= $hasta ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="balance-val" style="color:<?= $balance >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
        <?= formatMXN($balance) ?>
    </div>
</div>

</div><!-- /corte-wrap -->
<?php cerrarLayout(BASE_URL . 'js/corte_caja.js'); ?>
