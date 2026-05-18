<?php
// =====================================================
// vista/admin/historial_compras.php — Historial completo
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Compra.php';

$paginaActual = 'compras';
$modelo = new Compra();
$compras = $modelo->obtenerTodas(500);

$totalGeneral = array_sum(array_column($compras, 'total'));

abrirLayout('Historial de Compras', 'compras');
?>

<div class="pag-wrap-lg">

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Historial de Compras</h1>
        <p>Todas las compras registradas en el sistema</p>
    </div>
    <a href="compras" class="btn btn-primary">
        <i class="fa-solid fa-box-open"></i> Nueva Compra
    </a>
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($compras)): ?>
                <tr><td colspan="6" class="empty-state">Sin compras registradas</td></tr>
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
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
<?php cerrarLayout(); ?>
