<?php
// =====================================================
// vista/admin/compras.php — Registrar Compra (simplificada)
// Solo: Proveedor, monto total pagado, nota
// No requiere detallar los productos recibidos
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Proveedor.php';
require_once BASE_PATH . 'control/CompraControlador.php';

$paginaActual = 'compras';
$proveedores  = (new Proveedor())->obtenerTodos();

abrirLayout('Compras', 'compras');
?>

<div class="pag-wrap">

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-box-open" style="color:var(--primary)"></i> Registrar Compra</h1>
        <p>Registra el pago realizado a proveedor o compra directa</p>
    </div>
    <a href="compras/historial" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-clock-rotate-left"></i> Ver historial
    </a>
</div>

<div class="card">

    <!-- Tipo de compra -->
    <div class="form-group">
        <label style="font-weight:600">Tipo de Compra</label>
        <div class="tipo-btns" style="margin-bottom:0">
            <button class="tipo-btn active" id="btn-tipo-prov" onclick="setTipo('proveedor')">
                <i class="fa-solid fa-truck"></i> De Proveedor
            </button>
            <button class="tipo-btn" id="btn-tipo-dir" onclick="setTipo('directa')">
                <i class="fa-solid fa-store"></i> Compra Directa
            </button>
        </div>
    </div>

    <!-- Selector proveedor (solo cuando tipo = proveedor) -->
    <div class="form-group" id="row-proveedor">
        <label><i class="fa-solid fa-truck" style="color:var(--primary)"></i> Proveedor</label>
        <select class="form-control" id="sel-proveedor">
            <option value="">Seleccionar proveedor...</option>
            <?php foreach ($proveedores as $pv): ?>
            <option value="<?= $pv['id'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Monto total pagado -->
    <div class="form-group">
        <label><i class="fa-solid fa-money-bill-wave" style="color:var(--primary)"></i>
            Monto total pagado
        </label>
        <div style="display:flex;align-items:center;gap:8px;
                    border:1.5px solid var(--border);border-radius:8px;
                    padding:10px 14px;background:#fff" id="wrap-monto">
            <span style="color:var(--text-muted);font-weight:600">$</span>
            <input type="number" id="inp-monto" step="0.01" min="0.01"
                   placeholder="0.00"
                   style="border:none;outline:none;flex:1;font-size:16px;font-weight:600"
                   oninput="actualizarTotal()">
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
            <i class="fa-solid fa-circle-info"></i>
            Ingresa el monto exacto que pagaste por la mercancía recibida.
        </div>
    </div>

    <!-- Nota -->
    <div class="form-group">
        <label><i class="fa-solid fa-note-sticky" style="color:var(--primary)"></i>
            Descripción / Nota (opcional)
        </label>
        <textarea id="inp-nota" class="form-control"
                  placeholder="Ej: Pago de refresco Coca-Cola semana 20, factura #123..."
                  rows="3"></textarea>
    </div>

    <!-- Total + botón -->
    <div style="display:flex;justify-content:space-between;align-items:center;
                border-top:1px solid var(--border);padding-top:16px;flex-wrap:wrap;gap:12px">
        <div>
            <div style="font-size:12px;color:var(--text-muted)">Total de compra</div>
            <div class="price" style="font-size:24px;font-weight:800" id="lbl-total">$0.00</div>
        </div>
        <button class="btn btn-primary" style="height:48px;padding:0 28px" onclick="registrarCompra()">
            <i class="fa-solid fa-circle-check"></i> Registrar Compra
        </button>
    </div>
</div>

</div><!-- /pag-wrap -->

<script>
let tipoActual = 'proveedor';

function setTipo(t) {
    tipoActual = t;
    document.getElementById('btn-tipo-prov').classList.toggle('active', t === 'proveedor');
    document.getElementById('btn-tipo-dir').classList.toggle('active',  t === 'directa');
    document.getElementById('row-proveedor').style.display = t === 'proveedor' ? '' : 'none';
}

function actualizarTotal() {
    const v = parseFloat(document.getElementById('inp-monto').value) || 0;
    document.getElementById('lbl-total').textContent =
        '$' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    const wrap = document.getElementById('wrap-monto');
    wrap.style.borderColor = v > 0 ? 'var(--primary)' : 'var(--border)';
}

async function registrarCompra() {
    const monto       = parseFloat(document.getElementById('inp-monto').value)     || 0;
    const nota        = document.getElementById('inp-nota').value.trim();
    const proveedorId = document.getElementById('sel-proveedor')?.value || null;

    if (monto <= 0) {
        mostrarToast('Ingresa un monto mayor a cero.', 'err');
        document.getElementById('inp-monto').focus();
        return;
    }
    if (tipoActual === 'proveedor' && !proveedorId) {
        mostrarToast('Selecciona un proveedor.', 'err');
        return;
    }

    const body = {
        tipo         : tipoActual,
        proveedor_id : proveedorId ? parseInt(proveedorId) : null,
        total        : monto,
        nota         : nota,
        detalle      : [],   // sin detalle de productos
    };

    const btn = document.querySelector('button[onclick="registrarCompra()"]');
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const res  = await fetch(BASE + 'compras/registrar', {
            method  : 'POST',
            headers : { 'Content-Type': 'application/json' },
            body    : JSON.stringify(body),
        });
        const resp = await res.json();
        if (resp.ok) {
            mostrarToast('Compra registrada correctamente');
            document.getElementById('inp-monto').value = '';
            document.getElementById('inp-nota').value  = '';
            if (document.getElementById('sel-proveedor'))
                document.getElementById('sel-proveedor').value = '';
            actualizarTotal();
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarToast(resp.mensaje || 'Error al registrar', 'err');
        }
    } catch(e) {
        mostrarToast('Error de conexión', 'err');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrar Compra';
    }
}
</script>

<?php cerrarLayout(); ?>
