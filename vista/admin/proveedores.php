<?php
require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Proveedor.php';

$paginaActual = 'proveedores';
$proveedores  = (new Proveedor())->obtenerTodos();
abrirLayout('Proveedores', 'proveedores');
?>
<div class="pag-wrap">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div class="page-header" style="margin-bottom:0">
        <h1>Proveedores</h1>
        <p>Gestiona tus proveedores</p>
    </div>
    <button class="btn btn-primary" onclick="Proveedores.abrirModal('crear')">
        <i class="fa-solid fa-plus"></i> Nuevo Proveedor
    </button>
</div>

<?php if (empty($proveedores)): ?>
    <div class="empty-state card">No hay proveedores registrados.</div>
<?php else: ?>
<div class="suppliers-grid">
    <?php foreach ($proveedores as $pv): ?>
    <div class="supplier-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:44px;height:44px;background:#fff3e8;border-radius:10px;
                            display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-truck fa-lg" style="color:#e87722"></i>
                </div>
                <div>
                    <div class="supplier-name"><?= htmlspecialchars($pv['nombre']) ?></div>
                    <div class="supplier-tel">
                        <i class="fa-solid fa-phone-alt" style="color:var(--primary);font-size:11px"></i>
                        <?= htmlspecialchars($pv['telefono'] ?? '—') ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:6px">
                <button class="btn-icon" title="Editar"
                        onclick='Proveedores.abrirModal("editar", <?= json_encode($pv) ?>)'>
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <button class="btn-icon del" title="Eliminar"
                        onclick="Proveedores.eliminar(<?= $pv['id'] ?>, '<?= addslashes($pv['nombre']) ?>')">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </div>
        <?php $dias = $pv['DiaVisita'] ?? $pv['diavisita'] ?? ''; ?>
        <?php if ($dias): ?>
        <div style="margin-top:14px;background:#f9f4ef;border-radius:8px;padding:10px 14px">
            <div class="supplier-days-label">
                <i class="fa-solid fa-calendar-days" style="color:var(--primary)"></i> Días de visita
            </div>
            <div class="supplier-days"><?= htmlspecialchars($dias) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal -->
<div class="modal-overlay" id="modal-proveedor">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="modal-prov-titulo">
                <i class="fa-solid fa-truck" style="color:var(--primary)"></i> Nuevo Proveedor
            </span>
            <button class="modal-close" onclick="Proveedores.cerrarModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="form-proveedor">
            <input type="hidden" name="id" id="prov-id">
            <div class="form-group">
                <label><i class="fa-solid fa-building" style="color:var(--primary)"></i> Nombre</label>
                <input type="text" class="form-control" name="nombre" id="prov-nombre" required>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-phone" style="color:var(--primary)"></i> Teléfono (10 dígitos)</label>
                <input type="tel" class="form-control" name="telefono" id="prov-telefono"
                       maxlength="10" pattern="[0-9]{10}" placeholder="Ej: 2281234567">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-calendar-day" style="color:var(--primary)"></i> Días de visita</label>
                <input type="text" class="form-control" name="DiaVisita" id="prov-dias"
                       placeholder="Ej: Lunes y Jueves">
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Proveedores.cerrarModal()">
                <i class="fa-solid fa-xmark"></i> Cancelar
            </button>
            <button class="btn btn-primary" onclick="Proveedores.guardar()">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
const Proveedores = (() => {
    function abrirModal(modo, datos = {}) {
        const modal = document.getElementById('modal-proveedor');
        document.getElementById('modal-prov-titulo').innerHTML =
            '<i class="fa-solid fa-truck" style="color:var(--primary)"></i> '
            + (modo==='crear' ? 'Nuevo Proveedor' : 'Editar Proveedor');
        document.getElementById('prov-id').value       = datos.id       ?? '';
        document.getElementById('prov-nombre').value   = datos.nombre   ?? '';
        document.getElementById('prov-telefono').value = datos.telefono ?? '';
        document.getElementById('prov-dias').value     = datos.DiaVisita ?? datos.diavisita ?? '';
        modal.classList.add('open');
    }
    function cerrarModal() { document.getElementById('modal-proveedor')?.classList.remove('open'); }

    async function guardar() {
        const tel = document.getElementById('prov-telefono').value.trim();
        if (!/^[0-9]{10}$/.test(tel)) {
            mostrarToast('El teléfono debe tener exactamente 10 dígitos.', 'err'); return;
        }
        const id     = document.getElementById('prov-id').value;
        const accion = id ? 'actualizar' : 'crear';
        const data   = new FormData(document.getElementById('form-proveedor'));
        try {
            const res  = await fetch(BASE + 'proveedores/' + accion, { method:'POST', body:data });
            const resp = await res.json();
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) { cerrarModal(); setTimeout(() => location.reload(), 900); }
        } catch(e) { mostrarToast('Error de conexión','err'); }
    }

    async function eliminar(id, nombre) {
        if (!confirm(`¿Eliminar proveedor "${nombre}"?`)) return;
        try {
            const res  = await fetch(BASE + `proveedores/eliminar/${id}`);
            const resp = await res.json();
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) setTimeout(() => location.reload(), 900);
        } catch(e) { mostrarToast('Error de conexión','err'); }
    }
    return { abrirModal, cerrarModal, guardar, eliminar };
})();
</script>

</div>
<?php cerrarLayout(); ?>
