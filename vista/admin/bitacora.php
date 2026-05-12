<?php
// =====================================================
// vista/admin/bitacora.php — Bitácora del sistema
// NUEVO: Muestra auditoría con filtros
//        Estructura basada en imagen de referencia
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'control/BitacoraControlador.php';
require_once BASE_PATH . 'modelo/Cuenta.php';

$paginaActual = 'bitacora';

// Filtros
$filtFecha  = $_GET['fecha']  ?? '';
$filtCuenta = $_GET['cuenta'] ?? '';
$filtEstado = $_GET['estado'] ?? '';

$ctrl     = new BitacoraControlador();
$registros= $ctrl->obtener($filtFecha, $filtCuenta, $filtEstado);
$cuentas  = (new Cuenta())->obtenerTodas();
$totalHoy = $ctrl->totalHoy();
$errHoy   = $ctrl->erroresHoy();

abrirLayout('Bitácora', 'bitacora');
?>
<div class="pag-wrap-lg">

<div class="page-header"><h1>Bitácora</h1><p>Auditoría de operaciones del sistema — <?= $filtFecha ? "Filtrando por: " . $filtFecha : "Todo el historial" ?></p>

<!-- Resumen rápido -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
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
            <div class="stat-value"><?= $totalHoy - $errHoy ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff5f5"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="stat-label">Errores hoy</div>
            <div class="stat-value" style="color:var(--danger)"><?= $errHoy ?></div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px">
    <form method="GET" action="<?= BASE_URL ?>bitacora"
          style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">

        <div class="form-group" style="margin:0;flex:1;min-width:150px">
            <label>Fecha</label>
            <input type="date" name="fecha" class="form-control"
                   value="<?= htmlspecialchars($filtFecha) ?>" placeholder="Todas las fechas">
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

        <div class="form-group" style="margin:0;min-width:140px">
            <label>Estado</label>
            <select name="estado" class="form-control">
                <option value="">Todos</option>
                <option value="C" <?= $filtEstado==='C'?'selected':'' ?>>C — Completado</option>
                <option value="E" <?= $filtEstado==='E'?'selected':'' ?>>E — Error</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="height:40px">Filtrar</button>
        <a href="<?= BASE_URL ?>bitacora" class="btn btn-outline" style="height:40px">Limpiar</a>
    </form>
</div>

<!-- Tabla de bitácora -->
<div class="card">
    <div class="card-title" style="margin-bottom:16px">
        <i class="fa-solid fa-book" style="color:var(--primary)"></i> Registros — <span style="font-weight:400;color:#888;font-size:13px"><?= count($registros) ?> encontrados</span>
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
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr><td colspan="6" class="empty-state">Sin registros para los filtros seleccionados</td></tr>
                <?php else: ?>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td style="color:#888;font-size:12px"><?= $r['no_bitacora'] ?></td>
                    <td>
                        <code style="background:#f5f0eb;padding:2px 6px;border-radius:4px;font-size:12px">
                            <?= htmlspecialchars($r['clave_cuenta']) ?>
                        </code>
                    </td>
                    <td style="font-size:13px"><?= htmlspecialchars($r['usuario'] ?? '—') ?></td>
                    <td style="font-size:13px;max-width:340px"><?= htmlspecialchars($r['descripcion']) ?></td>
                    <td style="color:#888;font-size:12px;white-space:nowrap">
                        <?= date('d/m/Y H:i:s', strtotime($r['fechayhora'])) ?>
                    </td>
                    <td>
                        <?php if ($r['estado'] === 'C'): ?>
                            <span class="badge-estado ok">C — Completado</span>
                        <?php else: ?>
                            <span class="badge-estado err">E — Error</span>
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
<?php cerrarLayout(); ?>
