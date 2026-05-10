<?php
// =====================================================
// vista/admin/reporte_diario.php — Reporte Multiperiodo
// Periodos: Diario | Semanal | Mensual | Anual | Personalizado
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'control/ReporteControlador.php';

$paginaActual = 'reporte';
$ctrl         = new ReporteControlador();

// Determinar rango
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

$datos = $ctrl->reporteRango($desde, $hasta);
$tot   = $datos['totales'];

// Etiqueta del período
$etiquetaPeriodo = match($periodo) {
    'semana'        => 'Esta semana',
    'mes'           => 'Este mes (' . date('F Y') . ')',
    'anio'          => 'Este año (' . date('Y') . ')',
    'personalizado' => fechaEspanol($desde) . ' — ' . fechaEspanol($hasta),
    default         => fechaEspanol($desde),
};

abrirLayout('Reporte', 'reporte');
?>

<style>
.rep-wrap { max-width:960px; margin:0 auto; }
.periodo-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
.periodo-tab {
    padding:8px 18px; border-radius:8px; border:1.5px solid var(--border);
    font-size:13px; font-weight:600; cursor:pointer; background:#fff;
    color:var(--text-dark); text-decoration:none; transition:.15s;
}
.periodo-tab:hover  { border-color:var(--primary); color:var(--primary); }
.periodo-tab.active { background:var(--primary); color:#fff; border-color:var(--primary); }

.personalizado-row {
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    background:#fff; border:1.5px solid var(--border);
    border-radius:10px; padding:12px 16px; margin-bottom:20px;
}
.personalizado-row label { font-size:12px; color:var(--text-muted); font-weight:600; }
.personalizado-row input[type=date] {
    padding:7px 12px; border:1.5px solid var(--border);
    border-radius:7px; font-size:13px; cursor:pointer;
}
.personalizado-row input[type=date]:focus { outline:none; border-color:var(--primary); }

/* Mini tabla ventas por día */
.mini-table { width:100%; border-collapse:collapse; font-size:13px; }
.mini-table th { background:#f5f0eb; padding:8px 12px; text-align:left;
                 font-size:11px; text-transform:uppercase; color:var(--text-muted); }
.mini-table td { padding:8px 12px; border-bottom:1px solid var(--border); }
.mini-table tr:last-child td { border-bottom:none; }
.mini-table .bar-col { width:100px; }
.dia-bar-wrap { background:#f5f0eb; border-radius:4px; height:8px; overflow:hidden; }
.dia-bar      { background:var(--primary); height:8px; border-radius:4px; transition:width .4s; }
</style>

<div class="rep-wrap">

<div class="page-header">
    <h1><i class="fa-solid fa-chart-bar" style="color:var(--primary)"></i> Reporte</h1>
    <p><?= $etiquetaPeriodo ?></p>
</div>

<!-- ── Tabs de periodo ─────────────────────────── -->
<div class="periodo-tabs">
    <?php foreach (['hoy'=>'Hoy','semana'=>'Esta semana','mes'=>'Este mes','anio'=>'Este año','personalizado'=>'Personalizado'] as $p => $label): ?>
    <a href="reporte?periodo=<?= $p ?><?= ($p==='personalizado')?'&desde='.$desde.'&hasta='.$hasta:'' ?>"
       class="periodo-tab <?= $periodo===$p?'active':'' ?>">
        <?php if ($p==='personalizado'): ?><i class="fa-solid fa-calendar-days"></i> <?php endif; ?>
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Rango personalizado ─────────────────────── -->
<?php if ($periodo === 'personalizado'): ?>
<div class="personalizado-row">
    <i class="fa-solid fa-calendar-days" style="color:var(--primary)"></i>
    <label>Desde:</label>
    <input type="date" id="inp-desde" value="<?= $desde ?>">
    <label>Hasta:</label>
    <input type="date" id="inp-hasta" value="<?= $hasta ?>">
    <button class="btn btn-primary btn-sm" onclick="aplicarPersonalizado()">
        <i class="fa-solid fa-magnifying-glass"></i> Consultar
    </button>
</div>
<script>
function aplicarPersonalizado() {
    const d = document.getElementById('inp-desde').value;
    const h = document.getElementById('inp-hasta').value;
    if (!d || !h) { mostrarToast('Selecciona ambas fechas', 'err'); return; }
    location.href = 'reporte?periodo=personalizado&desde=' + d + '&hasta=' + h;
}
</script>
<?php endif; ?>

<!-- ── Métricas principales ────────────────────── -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-chart-line fa-lg" style="color:var(--primary)"></i></div>
        <div>
            <div class="stat-label">Total Ventas</div>
            <div class="stat-value"><?= formatMXN($datos['total_ventas']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-cart-shopping fa-lg" style="color:var(--success)"></i></div>
        <div>
            <div class="stat-label">Transacciones</div>
            <div class="stat-value"><?= $datos['transacciones'] ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-money-bill-wave fa-lg" style="color:var(--primary)"></i></div>
        <div>
            <div class="stat-label">Vtas. Efectivo</div>
            <div class="stat-value"><?= formatMXN($datos['efectivo']) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fa-solid fa-box fa-lg" style="color:#d97706"></i></div>
        <div>
            <div class="stat-label">Total Compras</div>
            <div class="stat-value"><?= formatMXN($datos['total_compras']) ?></div>
        </div>
    </div>
</div>

<div class="two-col" style="margin-bottom:20px">
    <!-- ── Desglose de ventas ── -->
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-receipt" style="color:var(--primary)"></i> Desglose de Ventas
        </div>
        <div class="report-row">
            <span><i class="fa-solid fa-money-bill-wave" style="color:var(--success)"></i> Efectivo</span>
            <span><?= formatMXN($datos['efectivo']) ?></span>
        </div>
        <div class="report-row">
            <span><i class="fa-solid fa-right-left" style="color:var(--primary)"></i> Transferencia</span>
            <span><?= formatMXN($datos['transferencia']) ?></span>
        </div>
        <div class="report-row" style="border-top:2px solid var(--border);margin-top:4px;padding-top:12px">
            <strong>Total</strong>
            <strong style="color:var(--primary)"><?= formatMXN($datos['total_ventas']) ?></strong>
        </div>
        <div class="report-row" style="background:#fff5f5;border-radius:8px;margin-top:8px">
            <span style="color:var(--danger)"><i class="fa-solid fa-box"></i> Total Compras</span>
            <span style="color:var(--danger)">-<?= formatMXN($datos['total_compras']) ?></span>
        </div>
        <div class="report-row" style="margin-top:8px">
            <strong>Balance del período</strong>
            <strong style="color:<?= $datos['balance'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                <?= formatMXN($datos['balance']) ?>
            </strong>
        </div>
    </div>

    <!-- ── Productos más vendidos ── -->
    <div class="card">
        <div class="card-title" style="color:var(--primary)">
            <i class="fa-solid fa-trophy"></i> Top Productos Vendidos
        </div>
        <?php if (empty($datos['mas_vendidos'])): ?>
            <div class="empty-state">Sin ventas en este período</div>
        <?php else: ?>
            <?php
            $maxVendido = max(array_column($datos['mas_vendidos'], 'total_vendido')) ?: 1;
            foreach ($datos['mas_vendidos'] as $i => $mv):
                $pct = round(($mv['total_vendido'] / $maxVendido) * 100);
            ?>
            <div class="report-row" style="flex-direction:column;align-items:flex-start;gap:4px">
                <div style="display:flex;justify-content:space-between;width:100%;align-items:center">
                    <div>
                        <span style="color:var(--primary);font-weight:700;margin-right:6px">#<?= $i+1 ?></span>
                        <span style="font-weight:500"><?= htmlspecialchars($mv['nombre']) ?></span>
                    </div>
                    <div style="text-align:right">
                        <span style="font-weight:700"><?= number_format($mv['total_vendido'], 2) ?> uds.</span>
                        <span style="font-size:11px;color:var(--text-muted);margin-left:6px">
                            <?= formatMXN($mv['total_importe']) ?>
                        </span>
                    </div>
                </div>
                <div class="dia-bar-wrap" style="width:100%">
                    <div class="dia-bar" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ── Ventas por día (solo si rango > 1 día) ───── -->
<?php if (!empty($datos['ventas_por_dia']) && count($datos['ventas_por_dia']) > 1): ?>
<div class="card">
    <div class="card-title">
        <i class="fa-solid fa-calendar-week" style="color:var(--primary)"></i>
        Detalle por Día
        <span style="font-size:12px;color:var(--text-muted);margin-left:8px;font-weight:400">
            <?= $desde ?> → <?= $hasta ?>
        </span>
    </div>
    <?php
    $maxDia = max(array_column($datos['ventas_por_dia'], 'total_dia')) ?: 1;
    ?>
    <div class="table-wrapper">
        <table class="mini-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Transacciones</th>
                    <th>Total</th>
                    <th class="bar-col">Proporción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos['ventas_por_dia'] as $dia):
                    $pct = round(($dia['total_dia'] / $maxDia) * 100);
                ?>
                <tr>
                    <td><strong><?= fechaEspanol($dia['fecha']) ?></strong></td>
                    <td><?= $dia['transacciones'] ?></td>
                    <td><span style="color:var(--primary);font-weight:700">
                        <?= formatMXN($dia['total_dia']) ?>
                    </span></td>
                    <td>
                        <div class="dia-bar-wrap">
                            <div class="dia-bar" style="width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div><!-- /rep-wrap -->
<?php cerrarLayout(); ?>
