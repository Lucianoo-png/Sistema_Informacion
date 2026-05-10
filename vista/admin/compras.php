<?php
// =====================================================
// vista/admin/compras.php — Registrar Compra
// CAMBIO: usa codigoprod VARCHAR(15) como identificador
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Producto.php';
require_once BASE_PATH . 'modelo/Proveedor.php';

$paginaActual = 'compras';
$productos    = (new Producto())->obtenerTodos();
$proveedores  = (new Proveedor())->obtenerTodos();

$prodJS = array_values(array_map(fn($p) => [
    'codigoprod'    => $p['codigoprod'],
    'nombre'        => $p['nombre'],
    'precio_compra' => (float)$p['precio_compra'],
], $productos));

abrirLayout('Compras', 'compras');
?>
<div class="pag-wrap">

<div style="max-width:860px;margin:0 auto">

<div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:20px">
        <div>
            <h1><i class="fa-solid fa-box-open" style="color:var(--primary)"></i> Registrar Compra</h1>
            <p style="font-size:13px;color:var(--text-muted)">Mercancía recibida de proveedor o compra directa</p>
        </div>
        <a href="compras/historial" class="btn btn-outline btn-sm" style="white-space:nowrap">
            <i class="fa-solid fa-clock-rotate-left"></i> Ver historial
        </a>
    </div>
</div>

<div class="card">
    <div class="form-group">
        <label>Tipo de Compra</label>
        <div class="tipo-btns">
            <button class="tipo-btn active" data-tipo="proveedor"
                    onclick="ComprasUI.setTipo('proveedor')">De Proveedor</button>
            <button class="tipo-btn" data-tipo="directa"
                    onclick="ComprasUI.setTipo('directa')">Compra Directa</button>
        </div>
    </div>

    <div class="form-group" id="row-proveedor">
        <label>Proveedor</label>
        <select class="form-control" id="sel-proveedor">
            <option value="">Seleccionar proveedor...</option>
            <?php foreach ($proveedores as $pv): ?>
            <option value="<?= $pv['id'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Buscar Producto</label>
        <div class="searchbar">
            <span class="searchbar-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" placeholder="Filtrar productos..."
                   oninput="ComprasUI.filtrar(this.value)">
        </div>
    </div>

    <div class="form-group">
        <label>Productos Recibidos</label>
        <div id="lineas-compra"></div>
        <button class="btn btn-outline btn-sm" style="margin-top:10px"
                onclick="ComprasUI.agregarLinea()">+ Agregar producto</button>
    </div>

    <div class="form-group">
        <label>Nota (opcional)</label>
        <textarea id="compra-nota" class="form-control" placeholder="Observaciones..."></textarea>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
        <div>
            <div style="font-size:13px;color:#888">Total de compra</div>
            <div class="price" style="font-size:22px" id="total-compra">$0.00</div>
        </div>
        <button class="btn btn-primary" onclick="ComprasUI.registrar()">Registrar Compra</button>
    </div>
</div>

<script>
const PRODUCTOS = <?= json_encode($prodJS) ?>;

const ComprasUI = (() => {
    const lineas = {};
    let nextIdx = 0;
    let tipoActual = 'proveedor';

    function buildOpts(filtro = '') {
        const f = filtro.toLowerCase();
        return '<option value="">Seleccionar...</option>'
            + PRODUCTOS.filter(p => !f
                || p.nombre.toLowerCase().includes(f)
                || p.codigoprod.toLowerCase().includes(f))
            .map(p => `<option value="${p.codigoprod}" data-precio="${p.precio_compra}">${p.nombre} [${p.codigoprod}]</option>`)
            .join('');
    }

    function agregarLinea(filtro = '') {
        const idx = nextIdx++;
        lineas[idx] = { codigoprod: '', cantidad: 1, precio: 0 };
        const div = document.createElement('div');
        div.className = 'compra-line';
        div.id = `linea-${idx}`;
        div.innerHTML = `
          <select class="form-control" id="sel-prod-${idx}"
                  onchange="ComprasUI.cambiarProducto(${idx},this)">
              ${buildOpts(filtro)}
          </select>
          <input type="number" class="form-control" min="1" value="1" placeholder="Cant."
                 onchange="ComprasUI.cambiarCampo(${idx},'cantidad',this.value)">
          <input type="number" class="form-control" step="0.01" min="0"
                 id="precio-${idx}" placeholder="P. Unit."
                 onchange="ComprasUI.cambiarCampo(${idx},'precio',this.value)">
          <span class="price" id="sub-${idx}">$0.00</span>
          <button class="btn-icon del" onclick="ComprasUI.eliminarLinea(${idx})"><i class="fa-solid fa-trash-can"></i></button>`;
        document.getElementById('lineas-compra').appendChild(div);
    }

    function cambiarProducto(idx, sel) {
        const opt   = sel.options[sel.selectedIndex];
        const precio= parseFloat(opt.dataset.precio || 0);
        lineas[idx].codigoprod = sel.value;
        lineas[idx].precio     = precio;
        const pEl = document.getElementById(`precio-${idx}`);
        if (pEl) pEl.value = precio.toFixed(2);
        actualizarSubtotal(idx);
        actualizarTotal();
    }

    function cambiarCampo(idx, campo, val) {
        lineas[idx][campo] = campo === 'cantidad' ? parseInt(val)||0 : parseFloat(val)||0;
        actualizarSubtotal(idx);
        actualizarTotal();
    }

    function actualizarSubtotal(idx) {
        const sub = (lineas[idx].cantidad||0) * (lineas[idx].precio||0);
        const el  = document.getElementById(`sub-${idx}`);
        if (el) el.textContent = '$' + sub.toFixed(2);
    }

    function actualizarTotal() {
        const tot = Object.values(lineas).reduce((s,l) => s + (l.cantidad||0)*(l.precio||0), 0);
        document.getElementById('total-compra').textContent = '$' + tot.toFixed(2);
    }

    function eliminarLinea(idx) {
        if (Object.keys(lineas).length <= 1) {
            mostrarToast('Debe haber al menos una línea.','err'); return;
        }
        delete lineas[idx];
        document.getElementById(`linea-${idx}`)?.remove();
        actualizarTotal();
    }

    function filtrar(q) {
        Object.keys(lineas).forEach(idx => {
            const sel = document.getElementById(`sel-prod-${idx}`);
            if (!sel) return;
            const cur = sel.value;
            sel.innerHTML = buildOpts(q);
            if (cur) sel.value = cur;
        });
    }

    function setTipo(t) {
        tipoActual = t;
        document.querySelectorAll('.tipo-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.tipo === t));
        const row = document.getElementById('row-proveedor');
        if (row) row.style.display = t === 'proveedor' ? '' : 'none';
    }

    async function registrar() {
        const detalle = Object.values(lineas)
            .filter(l => l.codigoprod && l.cantidad > 0 && l.precio > 0)
            .map(l => ({ codigoprod: l.codigoprod, cantidad: l.cantidad, precio_unitario: l.precio }));

        if (!detalle.length) {
            mostrarToast('Agrega al menos un producto con cantidad y precio.','err'); return;
        }
        const body = {
            tipo:         tipoActual,
            proveedor_id: document.getElementById('sel-proveedor')?.value ?? 0,
            nota:         document.getElementById('compra-nota')?.value ?? '',
            detalle,
        };
        try {
            const res  = await fetch(BASE + 'compras/registrar', {
                method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)
            });
            const resp = await res.json();
            mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
            if (resp.ok) setTimeout(() => location.reload(), 900);
        } catch(e) { mostrarToast('Error de conexión','err'); }
    }

    return { agregarLinea, cambiarProducto, cambiarCampo, eliminarLinea,
             filtrar, setTipo, registrar };
})();

document.addEventListener('DOMContentLoaded', () => ComprasUI.agregarLinea());
</script>

</div>
</div>
<?php cerrarLayout(); ?>
