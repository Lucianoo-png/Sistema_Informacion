<?php
// =====================================================
// vista/vendedor/historial_ventas.php — Historial completo
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Venta.php';

$paginaActual = 'ventas';
$modelo = new Venta();

// Filtros
$desde  = $_GET['desde']  ?? '';
$hasta  = $_GET['hasta']  ?? '';
$metodo = $_GET['metodo'] ?? '';

if ($desde || $hasta || $metodo) {
    $desde  = $desde  ?: '2000-01-01';
    $hasta  = $hasta  ?: date('Y-m-d');
    $ventas = $modelo->ventasPorFiltro($desde, $hasta, $metodo);
} else {
    $ventas = $modelo->obtenerTodas(500);
}

// Total general filtrado
$totalFiltrado = array_sum(array_column($ventas, 'total'));
$numVentas     = count($ventas);

abrirLayout('Historial de Ventas', 'ventas');
?>

<div class="pag-wrap-lg">

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Historial de Ventas</h1>
        <p>Todas las ventas registradas en el sistema</p>
    </div>
    <a href="ventas" class="btn btn-primary">
        <i class="fa-solid fa-cart-plus"></i> Nueva Venta
    </a>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px">
    <form method="GET" action="ventas/historial">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="margin:0;flex:1;min-width:140px">
                <label>Desde</label>
                <input type="date" class="form-control" name="desde" value="<?= $desde ?>">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:140px">
                <label>Hasta</label>
                <input type="date" class="form-control" name="hasta" value="<?= $hasta ?>">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:130px">
                <label>Método</label>
                <select class="form-control" name="metodo">
                    <option value="">Todos</option>
                    <option value="efectivo"      <?= $metodo==='efectivo'?'selected':'' ?>>Efectivo</option>
                    <option value="transferencia" <?= $metodo==='transferencia'?'selected':'' ?>>Transferencia</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <a href="ventas/historial" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i> Limpiar
            </a>
        </div>
    </form>
</div>

<!-- Resumen -->
<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <div class="stat-card" style="flex:1;min-width:160px">
        <div class="stat-icon orange"><i class="fa-solid fa-list" style="color:var(--primary)"></i></div>
        <div>
            <div class="stat-label">Registros</div>
            <div class="stat-value"><?= $numVentas ?></div>
        </div>
    </div>
    <div class="stat-card" style="flex:1;min-width:160px">
        <div class="stat-icon green"><i class="fa-solid fa-chart-line" style="color:var(--success)"></i></div>
        <div>
            <div class="stat-label">Total periodo</div>
            <div class="stat-value"><?= formatMXN($totalFiltrado) ?></div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Productos</th>
                    <th>Método</th>
                    <th>Total</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventas)): ?>
                <tr><td colspan="6" class="empty-state">Sin ventas registradas</td></tr>
                <?php else: ?>
                <?php foreach ($ventas as $v): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:12px"><?= $v['id'] ?></td>
                    <td>
                        <div style="font-weight:600"><?= fechaEspanol($v['fecha']) ?></div>
                    </td>
                    <td style="font-size:13px;color:var(--text-muted);max-width:280px">
                        <?= htmlspecialchars($v['productos'] ?? '—') ?>
                    </td>
                    <td>
                        <?php if ($v['metodo_pago'] === 'efectivo'): ?>
                        <span style="background:#e8f5ee;color:var(--success);padding:2px 8px;border-radius:6px;font-size:12px;font-weight:600">
                            <i class="fa-solid fa-money-bill-wave"></i> Efectivo
                        </span>
                        <?php else: ?>
                        <span style="background:#ebf8ff;color:#2b6cb0;padding:2px 8px;border-radius:6px;font-size:12px;font-weight:600">
                            <i class="fa-solid fa-right-left"></i> Transferencia
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="price"><?= formatMXN($v['total']) ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($v['nota'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
<?php cerrarLayout(); ?>
