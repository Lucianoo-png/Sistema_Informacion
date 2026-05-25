<?php
// =====================================================
// vista/admin/historial_compras.php — Historial completo
// ACTUALIZADO: botón cerrar, filtros, modal detalle
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Compra.php';

$paginaActual = 'compras';
$modelo = new Compra();

// Filtros
$desde  = $_GET['desde']  ?? '';
$hasta  = $_GET['hasta']  ?? '';
$tipo   = $_GET['tipo']   ?? '';

// Obtener compras con filtro o todas
if ($desde || $hasta || $tipo) {
    // Aplicar filtros manualmente sobre el conjunto completo
    $todas = $modelo->obtenerTodas(500);
    $compras = array_filter($todas, function($c) use ($desde, $hasta, $tipo) {
        $fecha = $c['fecha'] ?? '';
        if ($desde && $fecha < $desde) return false;
        if ($hasta && $fecha > $hasta) return false;
        if ($tipo  && ($c['tipo'] ?? '') !== $tipo) return false;
        return true;
    });
    $compras = array_values($compras);
} else {
    $compras = $modelo->obtenerTodas(500);
}

$totalGeneral = array_sum(array_column($compras, 'total'));

abrirLayout('Historial de Compras', 'compras');
?>

<!-- ═══ MODAL DETALLE COMPRA ═══ -->
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
          <i class="fa-solid fa-box-open" style="color:var(--primary);margin-right:6px"></i>
          Detalle de Compra
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
      <span style="font-size:12px;color:var(--text-muted)" id="md-tipo"></span>
      <strong style="font-size:16px;color:var(--primary)" id="md-total"></strong>
    </div>
  </div>
</div>

<div class="pag-wrap-lg">

<!-- Encabezado con botón cerrar -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Historial de Compras</h1>
        <p>Todas las compras registradas en el sistema</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="<?= BASE_URL ?>compras" class="btn btn-primary">
            <i class="fa-solid fa-box-open"></i> Nueva Compra
        </a>
        <a href="<?= BASE_URL ?>compras" title="Cerrar historial"
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
    <form method="GET" action="<?= BASE_URL ?>compras/historial">
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
                <label>Tipo</label>
                <select class="form-control" name="tipo">
                    <option value="">Todos</option>
                    <option value="proveedor" <?= $tipo==='proveedor'?'selected':'' ?>>Proveedor</option>
                    <option value="directa"   <?= $tipo==='directa'?'selected':'' ?>>Directa</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <a href="<?= BASE_URL ?>compras/historial" class="btn btn-outline">
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
            <div class="stat-label">Total registros</div>
            <div class="stat-value"><?= count($compras) ?></div>
        </div>
    </div>
    <div class="stat-card" style="flex:1;min-width:160px">
        <div class="stat-icon yellow"><i class="fa-solid fa-box" style="color:#d97706"></i></div>
        <div>
            <div class="stat-label">Total invertido</div>
            <div class="stat-value"><?= formatMXN($totalGeneral) ?></div>
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
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Total</th>
                    <th>Nota</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($compras)): ?>
                <tr><td colspan="7" class="empty-state">Sin compras registradas</td></tr>
                <?php else: ?>
                <?php foreach ($compras as $c): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:12px"><?= $c['id'] ?></td>
                    <td style="font-weight:600"><?= fechaEspanol($c['fecha']) ?></td>
                    <td><?= htmlspecialchars($c['proveedor_nombre']) ?></td>
                    <td>
                        <?php if ($c['tipo'] === 'proveedor'): ?>
                        <span style="background:#fff3e8;color:var(--primary);padding:2px 8px;border-radius:6px;font-size:12px;font-weight:600">
                            <i class="fa-solid fa-truck"></i> Proveedor
                        </span>
                        <?php else: ?>
                        <span style="background:#f5f0eb;color:var(--text-muted);padding:2px 8px;border-radius:6px;font-size:12px;font-weight:600">
                            <i class="fa-solid fa-store"></i> Directa
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="price"><?= formatMXN($c['total']) ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($c['nota'] ?? '—') ?></td>
                    <td>
                        <button onclick="verDetalle(<?= $c['id'] ?>, '<?= addslashes(fechaEspanol($c['fecha'])) ?>', '<?= addslashes(htmlspecialchars($c['tipo'])) ?>', <?= $c['total'] ?>)"
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

function verDetalle(id, fecha, tipo, total) {
    document.getElementById('md-fecha').textContent = fecha;
    document.getElementById('md-tipo').textContent =
        tipo === 'proveedor' ? '🚚 De Proveedor' : '🏪 Compra Directa';
    document.getElementById('md-total').textContent = formatMXN(total);
    document.getElementById('md-body').innerHTML =
        '<div id="md-spinner" style="text-align:center;padding:30px;color:var(--text-muted)">' +
        '<i class="fa-solid fa-spinner fa-spin fa-lg"></i><br>Cargando...</div>';
    modal.style.display = 'flex';

    fetch(BASE + 'api/compra-detalle?id=' + id)
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                document.getElementById('md-body').innerHTML =
                    '<p style="color:var(--text-muted);text-align:center;padding:20px">Sin detalle de productos disponible</p>';
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
