<?php
// =====================================================
// vista/admin/transferencias.php — Transferencias
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Transferencia.php';

$paginaActual  = 'transferencias';
$modelo        = new Transferencia();
$hoy           = date('Y-m-d');
$transferencias= $modelo->obtenerDelDia($hoy);
$total         = $modelo->totalDelDia($hoy);

abrirLayout('Transferencias', 'transferencias');
?>
<div class="pag-wrap">

<div class="page-header"><h1>Transferencias</h1><p>Ingresos por transferencia bancaria</p></div>
</div>

<div class="two-col" style="margin-bottom:20px">
    <!-- Formulario -->
    <div class="card">
        <div class="card-title">Registrar Transferencia</div>

        <div class="form-group">
            <label>Monto</label>
            <div style="display:flex;align-items:center;gap:6px;border:1.5px solid var(--border);border-radius:8px;padding:8px 14px;background:#fff">
                <span style="color:#888">$</span>
                <input type="number" id="tf-monto" step="0.01" min="0.01" value="0.00"
                       style="border:none;outline:none;flex:1;font-size:14px" placeholder="0.00">
            </div>
        </div>

        <div class="form-group">
            <label>Concepto (opcional)</label>
            <input type="text" class="form-control" id="tf-concepto" placeholder="Ej: Pago de cliente...">
        </div>

        <div class="form-group">
            <label>Referencia (opcional)</label>
            <input type="text" class="form-control" id="tf-referencia" placeholder="Numero de referencia...">
        </div>

        <button class="btn btn-primary" style="width:100%;justify-content:center"
                onclick="Transferencias.registrar()">+ Registrar</button>
    </div>

    <!-- Resumen del día -->
    <div class="card" style="display:flex;align-items:center;justify-content:center">
        <div class="transfer-summary">
            <div style="font-size:32px;margin-bottom:8px"><i class="fa-solid fa-right-left fa-2x" style="color:var(--primary)"></i></div>
            <div class="transfer-total-label">Total Transferencias</div>
            <div class="transfer-total-val"><?= formatMXN($total) ?></div>
            <div style="color:#888;font-size:13px;margin-top:4px">
                <?= count($transferencias) ?> transferencia<?= count($transferencias) !== 1 ? 's' : '' ?> registrada<?= count($transferencias) !== 1 ? 's' : '' ?>
            </div>
        </div>
    </div>
</div>

<!-- Historial del día -->
<div class="card">
    <div class="card-title">Historial de Transferencias</div>
    <?php if (empty($transferencias)): ?>
        <div class="empty-state">Sin transferencias registradas</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Monto</th>
                        <th>Concepto</th>
                        <th>Referencia</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transferencias as $tf): ?>
                    <tr>
                        <td style="color:#888;font-size:13px">
                            <?= date('H:i', strtotime($tf['created_at'])) ?>
                        </td>
                        <td class="price"><?= formatMXN($tf['monto']) ?></td>
                        <td><?= htmlspecialchars($tf['concepto'] ?? '—') ?></td>
                        <td style="color:#888"><?= htmlspecialchars($tf['referencia'] ?? '—') ?></td>
                        <td>
                            <button class="btn-icon del"
                                    onclick="eliminarTransferencia(<?= $tf['id'] ?>)"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
async function eliminarTransferencia(id) {
    if (!confirm('¿Eliminar esta transferencia?')) return;
    const res  = await fetch(BASE + 'transferencias/eliminar/' + id);
    const resp = await res.json();
    mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
    if (resp.ok) setTimeout(() => location.reload(), 900);
}
</script>

</div>
<?php cerrarLayout(); ?>
