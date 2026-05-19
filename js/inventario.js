// inventario.js — JavaScript específico de la página inventario

function filtrarTabla(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tabla-productos tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Listener de botones "Editar" — lee JSON del data-prod
document.querySelectorAll('.btn-editar-prod').forEach(btn => {
    btn.addEventListener('click', function() {
        try {
            const prod = JSON.parse(this.dataset.prod);
            Inventario.abrirModal('editar', prod);
        } catch(e) {
            mostrarToast('Error al cargar producto', 'err');
        }
    });
});

const Inventario = (() => {
    function abrirModal(modo, datos = {}) {
        const modal = document.getElementById('modal-producto');
        document.getElementById('modal-prod-titulo').innerHTML =
            '<i class="fas fa-' + (modo==='crear'?'plus':'edit') + '" style="color:var(--primary)"></i> '
            + (modo==='crear' ? 'Nuevo Producto' : 'Editar Producto');

        const esEdicion = modo === 'editar';
        const cod = datos.codigoprod ?? '';
        document.getElementById('prod-codigoprod').value        = cod;
        document.getElementById('prod-codigo-visible').value    = cod;
        document.getElementById('prod-codigo-visible').readOnly = esEdicion;
        document.getElementById('btn-gen-cod').style.display    = esEdicion ? 'none' : '';
        document.getElementById('lbl-auto').style.display       = esEdicion ? 'none' : '';
        document.getElementById('prod-nombre').value            = datos.nombre       ?? '';
        document.getElementById('prod-categoria').value         = datos.categoria    ?? '';
        document.getElementById('prod-p-compra').value          = datos.precio_compra?? '';
        document.getElementById('prod-p-venta').value           = datos.precio_venta ?? '';
        document.getElementById('prod-stock').value             = datos.stock        ?? '';
        document.getElementById('prod-stock-min').value         = datos.stock_minimo ?? 3;
        document.getElementById('prod-unidad').value            = datos.unidad       ?? 'pieza';
        modal.classList.add('open');

        // Auto-generar código si es creación y no tiene uno aún
        if (!esEdicion && !cod) generarCodigo();
    }

    async function generarCodigo() {
        const inp = document.getElementById('prod-codigo-visible');
        inp.value = 'Generando...';
        try {
            const res  = await fetch(BASE + 'api/siguiente-codigo');
            const data = await res.json();
            inp.value  = data.codigo ?? '';
            document.getElementById('prod-codigoprod').value = data.codigo ?? '';
        } catch(e) {
            inp.value = '';
            inp.placeholder = 'Escribe un código';
        }
    }
    function cerrarModal() { document.getElementById('modal-producto')?.classList.remove('open'); }

    async function guardar() {
        const cod    = document.getElementById('prod-codigoprod').value.trim();
        const nombre = document.getElementById('prod-nombre').value.trim();
        const pComp  = parseFloat(document.getElementById('prod-p-compra').value) || 0;
        const pVenta = parseFloat(document.getElementById('prod-p-venta').value)  || 0;

        // ── Validaciones ────────────────────────────
        if (!cod) {
            mostrarToast('El código del producto es obligatorio.', 'err');
            document.getElementById('prod-codigo-visible').focus();
            return;
        }
        if (!nombre) {
            mostrarToast('El nombre del producto es obligatorio.', 'err');
            document.getElementById('prod-nombre').focus();
            return;
        }
        const unidad = document.getElementById('prod-unidad').value.toLowerCase().trim();
        const esKg   = unidad === 'kg';
        const stock  = parseFloat(document.getElementById('prod-stock').value);

        // Para productos en kg: stock puede ser null/0 (se actualiza al vender)
        // Para otros productos: stock debe ser >= 1
        if (!esKg && (isNaN(stock) || stock < 1)) {
            mostrarToast('El stock debe ser al menos 1 unidad.', 'err');
            document.getElementById('prod-stock').focus();
            return;
        }
        if (pComp > pVenta && pVenta > 0) {
            mostrarToast('⚠️ El precio de compra no puede ser mayor al precio de venta.', 'err');
            return;
        }

        const accion = document.getElementById('prod-codigo-visible').readOnly
            ? 'actualizar'   // campo bloqueado = modo editar
            : 'crear';

        const data = new FormData(document.getElementById('form-producto'));

        try {
            const res  = await fetch(BASE + 'inventario/' + accion, { method:'POST', body:data });
            const resp = await res.json();
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) { cerrarModal(); setTimeout(() => location.reload(), 900); }
        } catch(e) { mostrarToast('Error de conexión', 'err'); }
    }

    async function eliminar(codigo, nombre) {
        if (!confirm(`¿Eliminar "${nombre}"?`)) return;
        try {
            const res  = await fetch(BASE + 'inventario/eliminar/' + encodeURIComponent(codigo));
            const resp = await res.json();
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) setTimeout(()=>location.reload(), 900);
        } catch(e) { mostrarToast('Error de conexión','err'); }
    }
    return { abrirModal, cerrarModal, guardar, eliminar };
})();

function actualizarHintStock(unidad) {
    const hint  = document.getElementById('lbl-stock-hint');
    const input = document.getElementById('prod-stock');
    const smInput = document.getElementById('prod-stock-min');
    if (!hint) return;
    if (unidad.toLowerCase() === 'kg') {
        hint.textContent = '(kg, decimal permitido — puede ser 0)';
        input.step = '0.001'; input.placeholder = 'ej: 5.500';
        smInput.step = '0.001'; smInput.placeholder = 'ej: 1.000 (opcional)';
    } else {
        hint.textContent = '(unidades enteras)';
        input.step = '1'; input.placeholder = '0';
        smInput.step = '1'; smInput.placeholder = '3';
    }
}
