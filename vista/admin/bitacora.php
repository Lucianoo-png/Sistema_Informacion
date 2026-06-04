<?php
// =====================================================
// vista/admin/bitacora.php — Bitácora del sistema
// MODIFICADO: Solo muestra inicios de sesión y detalles de ventas/compras
//             Sin sección de errores, fechas limitadas al día actual
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'control/BitacoraControlador.php';
require_once BASE_PATH . 'modelo/Cuenta.php';

$paginaActual = 'bitacora';

$hoy       = date('Y-m-d');

// Filtros — fecha no puede ser futura
$filtFecha  = $_GET['fecha']  ?? '';
$filtCuenta = $_GET['cuenta'] ?? '';

// Validar que la fecha no sea futura
if ($filtFecha && $filtFecha > $hoy) {
    $filtFecha = $hoy;
}

$ctrl     = new BitacoraControlador();
// Solo traer registros de tipo login y venta/compra (sin errores)
$registros= $ctrl->obtenerRelevantes($filtFecha, $filtCuenta);
$cuentas  = (new Cuenta())->obtenerTodas();
$totalHoy = $ctrl->totalHoy();

abrirLayout('Bitácora', 'bitacora');
?>
<div class="pag-wrap-lg">

<div class="page-header" style="margin-bottom:28px">
    <h1>Bitácora</h1>
    <p>Historial de sesiones y operaciones — <?= $filtFecha ? "Filtrando: " . date('d/m/Y', strtotime($filtFecha)) : "Todo el historial" ?></p>
</div>

<!-- Resumen rápido — solo registros -->
<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-book"></i></div>
        <div>
            <div class="stat-label">Registros hoy</div>
            <div class="stat-value"><?= $totalHoy ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i></div>
        <div>
            <div class="stat-label">Completados hoy</div>
            <div class="stat-value"><?= $totalHoy ?></div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px">
    <form method="GET" action="<?= BASE_URL ?>bitacora"
          style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">

        <div class="form-group" style="margin:0;flex:1;min-width:150px">
            <label>Fecha (no puede ser futura)</label>
            <input type="date" name="fecha" class="form-control"
                   value="<?= htmlspecialchars($filtFecha) ?>"
                   max="<?= $hoy ?>">
        </div>

        <div class="form-group" style="margin:0;flex:1;min-width:160px">
            <label>Cuenta</label>
            <select name="cuenta" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($cuentas as $c): ?>
                <option value="<?= $c['clavecuenta'] ?? $c['ClaveCuenta'] ?>"
                    <?= $filtCuenta === ($c['clavecuenta'] ?? $c['ClaveCuenta']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(($c['clavecuenta']??$c['ClaveCuenta']) . ' — ' . ($c['nombre']??$c['Nombre'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="height:40px">Filtrar</button>
        <a href="<?= BASE_URL ?>bitacora" class="btn btn-outline" style="height:40px">Limpiar</a>
    </form>
</div>

<!-- Tabla de bitácora -->
<div class="card">
    <div class="card-title" style="margin-bottom:16px">
        <i class="fa-solid fa-book" style="color:var(--primary)"></i> Registros —
        <span style="font-weight:400;color:#888;font-size:13px"><?= count($registros) ?> encontrados</span>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cuenta</th>
                    <th>Usuario</th>
                    <th>Descripción</th>
                    <th>Fecha y Hora</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr><td colspan="6" class="empty-state">Sin registros para los filtros seleccionados</td></tr>
                <?php else: ?>
                <?php foreach ($registros as $r): ?>
                <?php
                    $desc = $r['descripcion'] ?? '';
                    $detallLink = '';
                    $detallTipo = '';
                    $detallId   = 0;
                    if (preg_match('/^Venta registrada ID:(\d+)/i', $desc, $m)) {
                        $detallTipo = 'venta';
                        $detallId   = (int)$m[1];
                    } elseif (preg_match('/^Compra registrada ID:(\d+)/i', $desc, $m)) {
                        $detallTipo = 'compra';
                        $detallId   = (int)$m[1];
                    }
                ?>
                <tr>
                    <td style="color:#888;font-size:12px"><?= $r['no_bitacora'] ?></td>
                    <td>
                        <code style="background:#f5f0eb;padding:2px 6px;border-radius:4px;font-size:12px">
                            <?php if (!empty($r['clave_cuenta'])): ?>
                                <?= htmlspecialchars($r['clave_cuenta']) ?>
                            <?php else: ?>
                                <span style="color:#aaa;font-style:italic;font-size:11px">Sistema</span>
                            <?php endif; ?>
                        </code>
                    </td>
                    <td style="font-size:13px"><?= htmlspecialchars($r['usuario'] ?? '—') ?></td>
                    <td style="font-size:13px;max-width:280px"><?= htmlspecialchars($desc) ?></td>
                    <td style="color:#888;font-size:12px;white-space:nowrap">
                        <?= date('d/m/Y H:i:s', strtotime($r['fechayhora'])) ?>
                    </td>
                    <td>
                        <?php if ($detallTipo && $detallId): ?>
                        <button onclick="verDetalleBitacora('<?= $detallTipo ?>', <?= $detallId ?>)"
                                style="background:var(--primary);color:#fff;border:none;border-radius:7px;
                                       padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer;
                                       white-space:nowrap">
                            <i class="fa-solid fa-eye"></i> Ver
                        </button>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:11px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- ═══ MODAL DETALLE BITÁCORA ═══ -->
<div id="modal-bit" style="
    display:none;position:fixed;inset:0;z-index:9999;
    background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;max-width:540px;width:92%;
              max-height:82vh;overflow:hidden;display:flex;flex-direction:column;
              box-shadow:0 8px 40px rgba(0,0,0,.25);">
    <div style="display:flex;justify-content:space-between;align-items:center;
                padding:18px 22px 14px;border-bottom:1px solid #eee;flex-shrink:0">
      <div style="font-size:16px;font-weight:700;color:var(--text-dark)">
        <i id="bit-icon" class="fa-solid fa-receipt" style="color:var(--primary);margin-right:6px"></i>
        <span id="bit-titulo">Detalle de operación</span>
      </div>
      <button onclick="cerrarBit()"
              style="background:none;border:1.5px solid var(--border);border-radius:8px;
                     width:34px;height:34px;cursor:pointer;font-size:16px;
                     display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div id="bit-body" style="overflow-y:auto;padding:18px 22px;flex:1"></div>
    <div id="bit-footer" style="border-top:1px solid #eee;padding:14px 22px;flex-shrink:0;
                                 display:flex;justify-content:flex-end">
      <strong style="font-size:16px;color:var(--primary)" id="bit-total"></strong>
    </div>
  </div>
</div>

<script>
const modalBit = document.getElementById('modal-bit');

function verDetalleBitacora(tipo, id) {
    const esVenta = tipo === 'venta';
    document.getElementById('bit-titulo').textContent = esVenta ? 'Detalle de Venta' : 'Detalle de Compra';
    document.getElementById('bit-icon').className = 'fa-solid ' + (esVenta ? 'fa-receipt' : 'fa-box-open');
    document.getElementById('bit-body').innerHTML =
        '<div style="text-align:center;padding:30px;color:var(--text-muted)">' +
        '<i class="fa-solid fa-spinner fa-spin fa-lg"></i><br>Cargando...</div>';
    document.getElementById('bit-total').textContent = '';
    modalBit.style.display = 'flex';

    const endpoint = esVenta ? 'venta-detalle' : 'compra-detalle';
    fetch(BASE + 'api/' + endpoint + '?id=' + id)
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                document.getElementById('bit-body').innerHTML =
                    '<p style="color:var(--text-muted);text-align:center;padding:20px">Sin detalle de productos</p>';
                return;
            }
            let total = 0;
            let html = '<table style="width:100%;border-collapse:collapse;font-size:13px">' +
                '<thead><tr style="background:#f5f0eb">' +
                '<th style="padding:8px 10px;text-align:left">Producto</th>' +
                '<th style="padding:8px 10px;text-align:right">Cant.</th>' +
                '<th style="padding:8px 10px;text-align:right">P.Unit.</th>' +
                '<th style="padding:8px 10px;text-align:right">Subtotal</th>' +
                '</tr></thead><tbody>';
            items.forEach((it, i) => {
                total += parseFloat(it.subtotal) || 0;
                const bg = i % 2 === 0 ? '#fff' : '#fafafa';
                html += '<tr style="background:' + bg + '">' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;font-weight:500">' +
                        (it.nombre || it.codigoprod) + '</td>' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;text-align:right;color:var(--text-muted)">' +
                        parseFloat(it.cantidad).toLocaleString('es-MX', {maximumFractionDigits:3}) + '</td>' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;text-align:right">' +
                        formatMXN(it.precio_unitario) + '</td>' +
                    '<td style="padding:9px 10px;border-bottom:1px solid #eee;text-align:right;font-weight:700;color:var(--primary)">' +
                        formatMXN(it.subtotal) + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('bit-body').innerHTML = html;
            document.getElementById('bit-total').textContent = 'Total: ' + formatMXN(total);
        })
        .catch(() => {
            document.getElementById('bit-body').innerHTML =
                '<p style="color:var(--danger);text-align:center;padding:20px">Error al cargar el detalle</p>';
        });
}
function cerrarBit() { modalBit.style.display = 'none'; }
modalBit.addEventListener('click', function(e) { if (e.target === this) cerrarBit(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarBit(); });
</script>

</div>
<?php cerrarLayout(); ?>
