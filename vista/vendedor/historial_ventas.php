<?php
// =====================================================
// vista/vendedor/historial_ventas.php — Historial completo
// ACTUALIZADO: botón cerrar, filtros mejorados, modal detalle
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

<!-- ═══ MODAL DETALLE VENTA ═══ -->
<div id="modal-detalle" style="
    display:none;position:fixed;inset:0;z-index:9999;
    background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;max-width:540px;width:92%;
              max-height:82vh;overflow:hidden;display:flex;flex-direction:column;
              box-shadow:0 8px 40px rgba(0,0,0,.25);">
    <!-- Cabecera modal -->
    <div style="display:flex;justify-content:space-between;align-items:center;
                padding:18px 22px 14px;border-bottom:1px solid #eee;flex-shrink:0">
      <div>
        <div style="font-size:16px;font-weight:700;color:var(--text-dark)">
          <i class="fa-solid fa-receipt" style="color:var(--primary);margin-right:6px"></i>
          Detalle de Venta
        </div>
        <div id="md-fecha" style="font-size:12px;color:var(--text-muted);margin-top:2px"></div>
      </div>
      <button onclick="cerrarModal()"
              style="background:none;border:1.5px solid var(--border);border-radius:8px;
                     width:34px;height:34px;cursor:pointer;font-size:16px;
                     display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <!-- Body modal -->
    <div id="md-body" style="overflow-y:auto;padding:18px 22px;flex:1">
      <div id="md-spinner" style="text-align:center;padding:30px;color:var(--text-muted)">
        <i class="fa-solid fa-spinner fa-spin fa-lg"></i><br>Cargando...
      </div>
    </div>
    <!-- Pie modal -->
    <div id="md-footer" style="border-top:1px solid #eee;padding:14px 22px;flex-shrink:0;
                                display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:12px;color:var(--text-muted)" id="md-metodo"></span>
      <strong style="font-size:16px;color:var(--primary)" id="md-total"></strong>
    </div>
  </div>
</div>

<div class="pag-wrap-lg">

<!-- Encabezado con botón cerrar -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Historial de Ventas</h1>
        <p>Todas las ventas registradas en el sistema</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="<?= BASE_URL ?>ventas" class="btn btn-primary">
            <i class="fa-solid fa-cart-plus"></i> Nueva Venta
        </a>
        <a href="<?= BASE_URL ?>ventas" title="Cerrar historial"
           style="display:inline-flex;align-items:center;justify-content:center;
                  width:40px;height:40px;border-radius:10px;
                  border:1.5px solid var(--border);background:#fff;
                  color:var(--text-muted);text-decoration:none;font-size:18px;
                  transition:.15s">
            <i class="fa-solid fa-xmark"></i>
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px">
    <form method="GET" action="<?= BASE_URL ?>ventas/historial">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="margin:0;flex:1;min-width:140px">
                <label>Desde</label>
                <input type="date" class="form-control" name="desde" value="<?= htmlspecialchars($desde) ?>">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:140px">
                <label>Hasta</label>
                <input type="date" class="form-control" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
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
            <a href="<?= BASE_URL ?>ventas/historial" class="btn btn-outline">
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventas)): ?>
                <tr><td colspan="7" class="empty-state">Sin ventas registradas</td></tr>
                <?php else: ?>
                <?php foreach ($ventas as $v): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:12px"><?= $v['id'] ?></td>
                    <td>
                        <div style="font-weight:600"><?= fechaEspanol($v['fecha']) ?></div>
                    </td>
                    <td style="font-size:13px;color:var(--text-muted);max-width:240px">
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
                    <td>
                        <button onclick="verDetalle(<?= $v['id'] ?>, '<?= addslashes(fechaEspanol($v['fecha'])) ?>', '<?= $v['metodo_pago'] ?>', <?= $v['total'] ?>)"
                                style="background:var(--primary);color:#fff;border:none;border-radius:7px;
                                       padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;
                                       white-space:nowrap">
                            <i class="fa-solid fa-eye"></i> Ver
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<script>
const modal = document.getElementById('modal-detalle');

function verDetalle(id, fecha, metodo, total) {
    document.getElementById('md-fecha').textContent = fecha;
    document.getElementById('md-metodo').textContent =
        metodo === 'efectivo' ? '💵 Efectivo' : '🔄 Transferencia';
    document.getElementById('md-total').textContent = formatMXN(total);
    document.getElementById('md-body').innerHTML =
        '<div id="md-spinner" style="text-align:center;padding:30px;color:var(--text-muted)">' +
        '<i class="fa-solid fa-spinner fa-spin fa-lg"></i><br>Cargando...</div>';
    modal.style.display = 'flex';

    fetch(BASE + 'api/venta-detalle?id=' + id)
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                document.getElementById('md-body').innerHTML =
                    '<p style="color:var(--text-muted);text-align:center;padding:20px">Sin detalle disponible</p>';
                return;
            }
            let html = '<table style="width:100%;border-collapse:collapse;font-size:13px">' +
                '<thead><tr style="background:#f5f0eb">' +
                '<th style="padding:8px 10px;text-align:left;border-radius:6px 0 0 0">Producto</th>' +
                '<th style="padding:8px 10px;text-align:right">Cant.</th>' +
                '<th style="padding:8px 10px;text-align:right">P. Unit.</th>' +
                '<th style="padding:8px 10px;text-align:right;border-radius:0 6px 0 0">Subtotal</th>' +
                '</tr></thead><tbody>';
            items.forEach((it, i) => {
                const bg = i % 2 === 0 ? '#fff' : '#fafafa';
                html += '<tr style="background:' + bg + '">' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;font-weight:500">' +
                        (it.nombre || it.codigoprod) + '</td>' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;text-align:right;color:var(--text-muted)">' +
                        parseFloat(it.cantidad).toLocaleString('es-MX', {maximumFractionDigits:3}) + '</td>' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;text-align:right">' +
                        formatMXN(it.precio_unitario) + '</td>' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;text-align:right;' +
                        'font-weight:700;color:var(--primary)">' + formatMXN(it.subtotal) + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('md-body').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('md-body').innerHTML =
                '<p style="color:var(--danger);text-align:center;padding:20px">Error al cargar el detalle</p>';
        });
}

function cerrarModal() {
    modal.style.display = 'none';
}
modal.addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModal();
});
</script>

<?php cerrarLayout(); ?>
