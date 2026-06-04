// inventario.js

function filtrarTabla(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tabla-productos tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// ── Llenar select de proveedor sugerido ────────────────────
// Lee los datos ya embebidos en la página (sin fetch, sin async)
function cargarProveedoresEnSelect(valorActual) {
    const sel = document.getElementById('prod-proveedor');
    if (!sel) return;

    const lista = window.PROVEEDORES_INIT || [];
    sel.innerHTML = '<option value="">Sin sugerencia</option>';
    lista.forEach(pv => {
        const op       = document.createElement('option');
        op.value       = pv.id;
        op.textContent = pv.nombre;
        sel.appendChild(op);
    });
    if (valorActual) sel.value = String(valorActual);
}

// ── Generar código automático ─────────────────────────────
async function generarCodigo() {
    const inp = document.getElementById('prod-codigo-visible');
    inp.value = 'Generando...';
    try {
        const res  = await apiFetch(BASE + 'api/siguiente-codigo');
        const data = await res.json();
        inp.value  = data.codigo ?? '';
        document.getElementById('prod-codigoprod').value = data.codigo ?? '';
    } catch(e) {
        inp.value = '';
        inp.placeholder = 'Escribe un código';
    }
}

// ── Objeto Inventario ──────────────────────────────────────
const Inventario = (() => {

    function abrirModal(modo, datos = {}) {
        const modal = document.getElementById('modal-producto');
        if (!modal) return;

        document.getElementById('modal-prod-titulo').innerHTML =
            '<i class="fas fa-' + (modo === 'crear' ? 'plus' : 'edit') +
            '" style="color:var(--primary)"></i> ' +
            (modo === 'crear' ? 'Nuevo Producto' : 'Editar Producto');

        const esEdicion = modo === 'editar';
        const cod = datos.codigoprod ?? '';

        document.getElementById('prod-codigoprod').value        = cod;
        document.getElementById('prod-codigo-visible').value    = cod;
        document.getElementById('prod-codigo-visible').readOnly = esEdicion;
        document.getElementById('btn-gen-cod').style.display    = esEdicion ? 'none' : '';
        document.getElementById('lbl-auto').style.display       = esEdicion ? 'none' : '';
        document.getElementById('prod-nombre').value            = datos.nombre        ?? '';
        document.getElementById('prod-categoria').value         = datos.categoria     ?? '';
        document.getElementById('prod-p-compra').value          = datos.precio_compra ?? '';
        document.getElementById('prod-p-venta').value           = datos.precio_venta  ?? '';
        document.getElementById('prod-unidad').value            = datos.unidad        ?? 'pieza';

        // Aplicar reglas de kg ANTES de poner los valores de stock
        actualizarHintStock(datos.unidad || 'pieza');

        document.getElementById('prod-stock').value     = datos.stock        ?? '';
        document.getElementById('prod-stock-min').value = datos.stock_minimo ?? 3;

        // Llenar proveedores desde datos ya en página (síncrono, sin fetch)
        cargarProveedoresEnSelect(datos.proveedor_sugerido ?? null);

        // Proveedor exclusivo: mostrar checkbox y su estado
        const exclusivo = datos.proveedor_exclusivo == 1 || datos.proveedor_exclusivo === true;
        const chkWrap   = document.getElementById('chk-exclusivo-wrap');
        const chk       = document.getElementById('prod-exclusivo');
        if (chk) chk.checked = exclusivo;
        if (chkWrap) chkWrap.style.display = datos.proveedor_sugerido ? 'flex' : 'none';

        modal.classList.add('open');

        if (!esEdicion && !cod) generarCodigo();
    }

    function cerrarModal() {
        document.getElementById('modal-producto')?.classList.remove('open');
    }

    async function guardar() {
        const cod      = document.getElementById('prod-codigoprod').value.trim();
        const nombre   = document.getElementById('prod-nombre').value.trim();
        const unidad   = document.getElementById('prod-unidad').value;
        const esPesable = unidad.toLowerCase() === 'kg';
        const pc       = parseFloat(document.getElementById('prod-p-compra').value) || 0;
        const pVenta   = parseFloat(document.getElementById('prod-p-venta').value)  || 0;
        const stock    = parseFloat(document.getElementById('prod-stock').value);

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
        if (!esPesable && (isNaN(stock) || stock < 0)) {
            mostrarToast('El stock no puede ser negativo.', 'err');
            document.getElementById('prod-stock').focus();
            return;
        }
        if (!esPesable && stock > 20) {
            mostrarToast('El stock no puede superar 20 unidades.', 'err');
            document.getElementById('prod-stock').focus();
            return;
        }
        if (pc > 1000) {
            mostrarToast('El precio de compra no puede superar $1,000.', 'err');
            document.getElementById('prod-p-compra').focus();
            return;
        }
        if (pVenta > 500) {
            mostrarToast('El precio de venta no puede superar $500 (límite del ticket).', 'err');
            document.getElementById('prod-p-venta').focus();
            return;
        }
        if (pc > pVenta && pVenta > 0) {
            mostrarToast('⚠ El precio de compra no puede superar el precio de venta.', 'err');
            return;
        }

        const accion = document.getElementById('prod-codigo-visible').readOnly
            ? 'actualizar' : 'crear';

        const data = new FormData(document.getElementById('form-producto'));
        data.append('csrf_token', CSRF_TOKEN);

        if (esPesable) {
            data.set('stock',        document.getElementById('prod-stock').value     || 0);
            data.set('stock_minimo', document.getElementById('prod-stock-min').value || 0);
        }

        const btnGuardar = document.querySelector('#modal-producto .btn-primary');
        if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.textContent = 'Guardando...'; }
        let exito = false;

        try {
            const res  = await apiFetch(BASE + 'inventario/' + accion, { method: 'POST', body: data });
            let resp;
            try { resp = await res.json(); }
            catch(_) { mostrarToast('Respuesta inválida del servidor', 'err'); return; }
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) { exito = true; cerrarModal(); setTimeout(() => location.reload(), 900); }
        } catch(e) {
            mostrarToast('Error de conexión', 'err');
        } finally {
            if (!exito && btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
            }
        }
    }

    async function eliminar(codigo, nombre) {
        if (!confirm(`¿Eliminar "${nombre}"?`)) return;
        try {
            const res  = await apiFetch(BASE + 'inventario/eliminar/' + encodeURIComponent(codigo));
            const resp = await res.json();
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) setTimeout(() => location.reload(), 900);
        } catch(e) {
            mostrarToast('Error de conexión', 'err');
        }
    }

    return { abrirModal, cerrarModal, guardar, eliminar };
})();

// ── Reglas visuales según unidad ──────────────────────────
function actualizarHintStock(unidad) {
    const hint    = document.getElementById('lbl-stock-hint');
    const input   = document.getElementById('prod-stock');
    const smInput = document.getElementById('prod-stock-min');
    const pcInput = document.getElementById('prod-p-compra');
    if (!hint) return;

    const esPesable = unidad.toLowerCase() === 'kg';

    if (esPesable) {
        hint.textContent         = '(manejado por compras — no editable)';
        hint.style.display       = 'block';
        input.disabled           = true;
        input.style.background   = '#f5f5f5';
        input.style.cursor       = 'not-allowed';
        smInput.disabled         = true;
        smInput.style.background = '#f5f5f5';
        smInput.style.cursor     = 'not-allowed';
        smInput.value            = 0;
        input.removeAttribute('max');
        input.step               = '0.001';
    } else {
        hint.textContent         = '(unidades — máx. 20)';
        hint.style.display       = 'inline';
        input.disabled           = false;
        input.style.background   = '';
        input.style.cursor       = '';
        smInput.disabled         = false;
        smInput.style.background = '';
        smInput.style.cursor     = '';
        input.max                = 20;
        input.step               = '1';
        input.placeholder        = '0';
        smInput.max              = 20;
        smInput.step             = '1';
        smInput.placeholder      = '3';
    }
    if (pcInput) pcInput.max = 1000;
}

// ── Listeners de botones Editar ────────────────────────────
// Scripts cargan al final del body: DOM ya está listo, sin necesidad de DOMContentLoaded
document.querySelectorAll('.btn-editar-prod').forEach(btn => {
    btn.addEventListener('click', function () {
        try {
            const prod = JSON.parse(this.dataset.prod);
            Inventario.abrirModal('editar', prod);
        } catch (e) {
            console.error('Error al abrir modal:', e);
            mostrarToast('Error al cargar producto', 'err');
        }
    });
});
