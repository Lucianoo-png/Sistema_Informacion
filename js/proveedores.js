// proveedores.js — JavaScript específico de la página proveedores

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
