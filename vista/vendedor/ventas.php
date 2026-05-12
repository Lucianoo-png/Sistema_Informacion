<?php
// =====================================================
// vista/vendedor/ventas.php
// Agrupación: PESABLES (unidad = kg) vs GENERALES (resto)
// Carrito: panel fijo con scroll interno
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Producto.php';

$paginaActual = 'ventas';

$todos = (new Producto())->obtenerTodos();

// Separar por UNIDAD: kg → pesable, resto → general
$pesables = [];
$generales = [];
foreach ($todos as $p) {
    if (strtolower(trim($p['unidad'])) === 'kg') {
        $pesables[] = $p;
    } else {
        $generales[] = $p;
    }
}

// Ordenar cada grupo por nombre
usort($pesables,  fn($a,$b) => strcasecmp($a['nombre'], $b['nombre']));
usort($generales, fn($a,$b) => strcasecmp($a['nombre'], $b['nombre']));

abrirLayout('Nueva Venta', 'ventas');
?>
<script>document.body.classList.add('page-ventas');</script>
<?php
?>

<style>
body.page-ventas .main-content {
    padding: 18px 302px 24px 32px !important;
    max-width: none !important;
    overflow-x: hidden !important;
}
body.page-ventas .pag-wrap,
body.page-ventas .pag-wrap-lg { max-width: none !important; margin: 0 !important; }
.catalogo-header { margin-bottom: 16px; }
.catalogo-header-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px; }
.catalogo-header h1 { font-size:20px; font-weight:700; margin:0; }
.catalogo-header p  { font-size:12px; color:var(--text-muted); margin:2px 0 0; }
.grupo-hdr { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; color:#fff; padding:5px 14px; border-radius:20px; margin-bottom:10px; margin-top:4px; display:inline-flex; align-items:center; gap:8px; }
.grupo-hdr .cnt { background:rgba(255,255,255,.3); padding:1px 7px; border-radius:10px; font-size:10px; letter-spacing:0; text-transform:none; }
.grupo-hdr.peso    { background: var(--success); }
.grupo-hdr.general { background: var(--primary); }
.grupo-sep { height:3px; background:var(--border); border-radius:2px; margin:10px 0 14px; }
.prod-row {
    display: grid;
    grid-template-columns: 1fr 90px 100px 40px;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: var(--card-bg);
    border: 1.5px solid var(--border);
    border-radius: 10px;
    margin-bottom: 7px;
    transition: border-color .15s;
    width: 100%;
    box-sizing: border-box;
}
.prod-row:hover { border-color:var(--primary); box-shadow:0 0 0 2px #e8772215; }
.prod-row.sin-stock { opacity:.45; }
.prod-info  { min-width:0; overflow:hidden; }
.prow-name  { font-weight:600; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.prow-code  { font-size:11px; color:var(--text-muted); }
.prow-precio { font-weight:700; color:var(--primary); font-size:14px; text-align:right; white-space:nowrap; }
.prow-precio small { display:block; font-size:10px; color:var(--text-muted); font-weight:400; }
.prow-stock { font-size:12px; white-space:nowrap; text-align:right; }
.prow-stock.ok  { color:var(--success); }
.prow-stock.low { color:var(--danger); font-weight:700; }
.btn-add { width:36px; height:36px; border-radius:8px; border:none; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; color:#fff; transition:opacity .15s,transform .1s; justify-self:end; }
.btn-add:hover  { opacity:.85; }
.btn-add:active { transform:scale(.92); }
.btn-add:disabled { opacity:.3; cursor:not-allowed; }
.btn-add.normal { background:var(--primary); }
.btn-add.peso   { background:var(--success); }
.cart-fixed { position:fixed; top:0; right:0; width:290px; height:100vh; background:var(--card-bg); border-left:1px solid var(--border); display:flex; flex-direction:column; z-index:200; box-shadow:-2px 0 12px rgba(0,0,0,.07); }
.cart-header { padding:16px 16px 10px; border-bottom:1px solid var(--border); flex-shrink:0; display:flex; align-items:center; gap:8px; font-size:15px; font-weight:700; }
.cart-count  { background:var(--primary); color:#fff; font-size:11px; font-weight:700; padding:1px 7px; border-radius:10px; display:none; margin-left:auto; }
.cart-items  { flex:1; overflow-y:auto; padding:8px 14px; min-height:0; }
.cart-empty  { color:var(--text-muted); text-align:center; padding:32px 0; font-size:13px; }
.cart-footer { flex-shrink:0; padding:10px 14px 14px; border-top:1px solid var(--border); }
.cart-total-row { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:10px; }
.cart-total-label { font-size:14px; font-weight:700; }
.cart-total-val   { font-size:20px; font-weight:800; color:var(--primary); }
.cart-item { padding:8px 0; border-bottom:1px solid var(--border); }
.ci-top { display:flex; justify-content:space-between; align-items:flex-start; gap:4px; margin-bottom:4px; }
.ci-name  { font-size:12px; font-weight:600; flex:1; line-height:1.3; }
.ci-total { font-size:14px; font-weight:800; color:var(--primary); white-space:nowrap; }
.ci-bot   { display:flex; justify-content:space-between; align-items:center; }
.ci-price { font-size:11px; color:var(--text-muted); }
.qty-ctrl { display:flex; align-items:center; gap:2px; }
.qty-ctrl button { width:22px; height:22px; border-radius:5px; border:1.5px solid var(--border); background:#fff; cursor:pointer; font-size:14px; color:var(--primary); display:flex; align-items:center; justify-content:center; }
.qty-ctrl span { font-size:12px; font-weight:700; min-width:24px; text-align:center; }
.peso-inputs { display:flex; gap:6px; margin-top:5px; }
.pi-group { flex:1; display:flex; flex-direction:column; gap:2px; }
.pi-group label { font-size:10px; color:var(--text-muted); font-weight:600; }
.pi-group input { width:100%; padding:4px 6px; font-size:12px; text-align:right; border:1.5px solid var(--border); border-radius:5px; background:#fff; }
.pi-group input:focus { outline:none; border-color:var(--primary); }
.btn-quitar { background:none; border:none; color:var(--danger); cursor:pointer; font-size:11px; padding:0 2px; }
.mp-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9000; align-items:center; justify-content:center; }
.mp-overlay.open { display:flex; }
.mp-box { background:#fff; border-radius:14px; padding:24px; width:330px; max-width:95vw; box-shadow:0 12px 40px rgba(0,0,0,.22); animation:fadeUp .17s ease; }
@keyframes fadeUp { from{transform:translateY(10px);opacity:0} to{transform:translateY(0);opacity:1} }
.mp-box h3 { font-size:16px; font-weight:700; margin-bottom:3px; }
.mp-tag { font-size:11px; background:#e8f5ee; color:var(--success); padding:2px 8px; border-radius:5px; font-weight:600; margin-bottom:12px; display:inline-block; }
.mp-ref { font-size:11px; color:var(--text-muted); margin-left:6px; }
.mp-fields { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; }
.mp-group { display:flex; flex-direction:column; gap:4px; }
.mp-group label { font-size:11px; font-weight:600; color:var(--text-muted); }
.mp-group input { padding:10px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:15px; text-align:right; width:100%; }
.mp-group input:focus { outline:none; border-color:var(--primary); }
.mp-total-box { background:#f5f0eb; border-radius:8px; padding:10px 14px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; }
.mp-total-box span   { font-size:13px; color:var(--text-muted); }
.mp-total-box strong { font-size:20px; font-weight:800; color:var(--primary); }
.mp-btns { display:flex; gap:10px; }
.mp-btns button { flex:1; padding:10px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; }
.mp-cancel  { background:var(--border); color:var(--text-dark); }
.mp-confirm { background:var(--success); color:#fff; }
</style>

<!-- ── Modal peso ── -->
<div class="mp-overlay" id="mp-overlay">
    <div class="mp-box">
        <h3 id="mp-nombre">Producto</h3>
        <div>
            <span class="mp-tag"><i class="fa-solid fa-scale-balanced"></i> Por peso (kg)</span>
            <span class="mp-ref" id="mp-ref"></span>
        </div>
        <div class="mp-fields">
            <div class="mp-group">
                <label>Cantidad (kg)</label>
                <input type="number" id="mp-cant" min="0.001" step="0.001" placeholder="0.000">
            </div>
            <div class="mp-group">
                <label>Precio a cobrar ($)</label>
                <input type="number" id="mp-prec" min="0.01" step="0.5" placeholder="0.00">
            </div>
        </div>
        <div class="mp-total-box">
            <span>Total</span>
            <strong id="mp-total">$0.00</strong>
        </div>
        <div class="mp-btns">
            <button class="mp-cancel" onclick="cerrarMP()">
                <i class="fa-solid fa-xmark"></i> Cancelar
            </button>
            <button class="mp-confirm" onclick="confirmarMP()">
                <i class="fa-solid fa-plus"></i> Agregar
            </button>
        </div>
    </div>
</div>

<!-- ══ ESTRUCTURA ══ -->

    <div class="catalogo-header">
        <div class="catalogo-header-top">
            <div>
                <h1>Nueva Venta</h1>
                <p>Selecciona productos para agregar al carrito</p>
            </div>
            <a href="ventas/historial" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-clock-rotate-left"></i> Historial
            </a>
        </div>
        <div class="searchbar">
            <span class="searchbar-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="buscador"
                   placeholder="Buscar por nombre o código..."
                   oninput="filtrar(this.value)">
        </div>
    </div>

    <div class="catalogo-body" id="catalogo-body">

    <?php if (!empty($pesables)): ?>
        <!-- GRUPO PESABLES (kg) -->
        <div class="grupo-hdr peso">
            <i class="fa-solid fa-scale-balanced"></i>
            Pesables — precio por kg
            <span style="background:rgba(255,255,255,.3);padding:1px 8px;border-radius:10px;font-size:10px"><?= count($pesables) ?></span>
        </div>

        <?php foreach ($pesables as $p):
            // Para productos por kg: el stock es referencial, NUNCA se deshabilita el botón
            $stockKg  = (float)$p['stock'];
            $dj       = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="prod-row"
             data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
             data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">
            <div class="prod-info">
                <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
                <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
            </div>
            <div class="prow-precio">
                <?= formatMXN($p['precio_venta']) ?>
                <small>ref. / kg</small>
            </div>
            <div class="prow-stock ok" style="font-size:11px;color:var(--text-muted)">
                <?php if ($stockKg > 0): ?>
                <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
                <?= number_format($stockKg, 3, '.', '') ?> kg
                <?php else: ?>
                <i class="fa-solid fa-scale-balanced" style="color:var(--success)"></i>
                siempre disponible
                <?php endif; ?>
            </div>
            <button class="btn-add peso"
                    data-prod="<?= $dj ?>" title="Ingresar peso y precio">
                <i class="fa-solid fa-scale-balanced"></i>
            </button>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($generales)): ?>
        <div class="grupo-sep"></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($generales)): ?>
        <!-- GRUPO GENERALES -->
        <div class="grupo-hdr general">
            <i class="fa-solid fa-box"></i>
            Productos generales
            <span style="background:rgba(255,255,255,.3);padding:1px 8px;border-radius:10px;font-size:10px"><?= count($generales) ?></span>
        </div>

        <?php foreach ($generales as $p):
            $sinStock = (float)$p['stock'] <= 0;
            $low      = (float)$p['stock'] <= (float)$p['stock_minimo'];
            $dj       = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="prod-row <?= $sinStock?'sin-stock':'' ?>"
             data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
             data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">
            <div class="prod-info">
                <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
                <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
            </div>
            <div class="prow-precio"><?= formatMXN($p['precio_venta']) ?></div>
            <div class="prow-stock <?= $sinStock?'low':($low?'low':'ok') ?>">
                <?php if ($sinStock): ?>
                    <i class="fa-solid fa-circle-xmark"></i> Sin stock
                <?php else: ?>
                    <i class="fa-solid fa-circle-check"></i>
                    <?= (int)$p['stock'] ?> <?= htmlspecialchars($p['unidad']) ?>
                <?php endif; ?>
            </div>
            <button class="btn-add normal" <?= $sinStock?'disabled':'' ?>
                    data-prod="<?= $dj ?>" title="Agregar al carrito">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    </div><!-- /catalogo-body -->


<!-- ─── CARRITO ─── -->
<div class="cart-fixed">

    <!-- Cabecera fija -->
    <div class="cart-header">
        <i class="fa-solid fa-cart-shopping" style="color:var(--primary)"></i>
        Carrito
        <span class="cart-count" id="cart-count">0</span>
    </div>

    <!-- Zona scrollable de items -->
    <div class="cart-items" id="cart-items">
        <p class="cart-empty">Agrega productos al carrito</p>
    </div>

    <!-- Pie fijo -->
    <div class="cart-footer">
        <div class="cart-total-row">
            <span class="cart-total-label">Total</span>
            <span class="cart-total-val" id="cart-total">$0.00</span>
        </div>

        <!-- Método de pago -->
        <div style="margin-bottom:10px">
            <div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:5px">MÉTODO DE PAGO</div>
            <div class="metodo-btns">
                <button class="metodo-btn active" id="btn-efectivo">
                    <i class="fa-solid fa-money-bill-wave"></i> Efectivo
                </button>
                <button class="metodo-btn" id="btn-transf">
                    <i class="fa-solid fa-right-left"></i> Transferencia
                </button>
            </div>
        </div>

        <!-- Nota -->
        <textarea id="venta-nota" class="nota-input"
                  style="min-height:52px;max-height:80px;margin-bottom:10px"
                  placeholder="Comentario..."></textarea>

        <button class="btn btn-primary" style="width:100%;justify-content:center;height:42px"
                id="btn-registrar" disabled>
            <i class="fa-solid fa-circle-check"></i> Registrar Venta
        </button>
    </div>
</div>



<script>
// ═══════════════════════════════════════════════
//  ESTADO DEL CARRITO
// ═══════════════════════════════════════════════
const items       = {};        // { codigoprod → {...} }
let   metodoPago  = 'efectivo';
let   _prodModal  = null;

// ── Helpers ──────────────────────────────────────
function fmt(n) {
    return '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── RENDER CARRITO ────────────────────────────────
function render() {
    const el    = document.getElementById('cart-items');
    const tot   = document.getElementById('cart-total');
    const btn   = document.getElementById('btn-registrar');
    const cnt   = document.getElementById('cart-count');
    if (!el) return;

    const keys = Object.keys(items);
    cnt.style.display = keys.length ? 'inline-block' : 'none';
    cnt.textContent   = keys.length;

    if (!keys.length) {
        el.innerHTML     = '<p class="cart-empty">Agrega productos al carrito</p>';
        tot.textContent  = '$0.00';
        btn.disabled     = true;
        return;
    }

    const total = keys.reduce((s, k) => s + items[k].precio * items[k].cantidad, 0);

    el.innerHTML = keys.map(cod => {
        const it  = items[cod];
        const sub = it.precio * it.cantidad;

        if (it.tipo === 'peso') {
            return `
            <div class="cart-item">
              <div class="ci-top">
                <span class="ci-name">
                  <i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px"></i>
                  ${esc(it.nombre)}
                </span>
                <span class="ci-total">${fmt(sub)}</span>
              </div>
              <div class="peso-inputs">
                <div class="pi-group">
                  <label>Cantidad (kg)</label>
                  <input type="number" value="${it.cantidad}" min="0.001" step="0.001"
                         oninput="updateCampo('${cod}','cantidad',this.value)">
                </div>
                <div class="pi-group">
                  <label>Precio ($)</label>
                  <input type="number" value="${it.precio.toFixed(2)}" min="0.01" step="0.5"
                         oninput="updateCampo('${cod}','precio',this.value)">
                </div>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
                <span style="font-size:11px;color:var(--text-muted)">
                  ${it.cantidad} kg × ${fmt(it.precio)}
                </span>
                <button class="btn-quitar" onclick="quitar('${cod}')">
                  <i class="fa-solid fa-trash-can"></i> Quitar
                </button>
              </div>
            </div>`;
        } else {
            return `
            <div class="cart-item">
              <div class="ci-top">
                <span class="ci-name">${esc(it.nombre)}</span>
                <span class="ci-total">${fmt(sub)}</span>
              </div>
              <div class="ci-bot">
                <span class="ci-unitprice">${fmt(it.precio)} c/u</span>
                <div style="display:flex;align-items:center;gap:4px">
                  <div class="qty-ctrl">
                    <button onclick="cambiarCant('${cod}',-1)">−</button>
                    <span>${it.cantidad}</span>
                    <button onclick="cambiarCant('${cod}',+1)">+</button>
                  </div>
                  <button class="btn-quitar" onclick="quitar('${cod}')">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
              </div>
            </div>`;
        }
    }).join('');

    tot.textContent = fmt(total);
    btn.disabled    = false;
}

// ── Agregar producto normal ───────────────────────
function agregarNormal(p) {
    const cod = p.codigoprod;
    if (items[cod]) {
        if (items[cod].cantidad < parseInt(p.stock)) items[cod].cantidad++;
        else { mostrarToast('Sin más stock disponible', 'err'); return; }
    } else {
        items[cod] = { codigoprod:cod, nombre:p.nombre, tipo:'normal',
                       precio:parseFloat(p.precio_venta), cantidad:1,
                       stock:parseInt(p.stock) };
    }
    render();
}

// ── Controles carrito ─────────────────────────────
function cambiarCant(cod, d) {
    if (!items[cod]) return;
    items[cod].cantidad += d;
    if (items[cod].cantidad <= 0) delete items[cod];
    render();
}
function updateCampo(cod, campo, val) {
    if (!items[cod]) return;
    const v = parseFloat(val);
    if (!isNaN(v) && v > 0) items[cod][campo] = v;
    render();
}
function quitar(cod) { delete items[cod]; render(); }

// ── Modal peso ────────────────────────────────────
function abrirMP(p) {
    _prodModal = p;
    document.getElementById('mp-nombre').textContent = p.nombre;
    document.getElementById('mp-ref').textContent =
        '(ref: ' + fmt(p.precio_venta) + '/kg)';
    document.getElementById('mp-cant').value  = '';
    document.getElementById('mp-prec').value  = '';
    document.getElementById('mp-total').textContent = '$0.00';
    document.getElementById('mp-overlay').classList.add('open');
    setTimeout(() => document.getElementById('mp-cant').focus(), 80);
}
function cerrarMP() {
    document.getElementById('mp-overlay').classList.remove('open');
    _prodModal = null;
}
function calcMP() {
    const c = parseFloat(document.getElementById('mp-cant').value) || 0;
    const p = parseFloat(document.getElementById('mp-prec').value) || 0;
    document.getElementById('mp-total').textContent = fmt(c * p);
    document.getElementById('mp-total').style.color =
        (c > 0 && p > 0) ? 'var(--success)' : 'var(--primary)';
}
function confirmarMP() {
    const cant = parseFloat(document.getElementById('mp-cant').value);
    const prec = parseFloat(document.getElementById('mp-prec').value);
    if (!cant || cant <= 0) { mostrarToast('Cantidad inválida','err'); return; }
    if (!prec || prec <= 0) { mostrarToast('Precio inválido','err'); return; }

    const p   = _prodModal;
    const cod = p.codigoprod;
    if (items[cod] && items[cod].tipo === 'peso') {
        items[cod].cantidad = parseFloat((items[cod].cantidad + cant).toFixed(3));
        items[cod].precio   = prec;
    } else {
        items[cod] = { codigoprod:cod, nombre:p.nombre, tipo:'peso',
                       precio:prec, cantidad:parseFloat(cant.toFixed(3)),
                       stock:parseFloat(p.stock), unidad:'kg' };
    }
    mostrarToast('✓ ' + p.nombre + ' — ' + fmt(cant * prec));
    cerrarMP(); render();
}

// ── Método de pago ────────────────────────────────
document.getElementById('btn-efectivo').addEventListener('click', () => {
    metodoPago = 'efectivo';
    document.getElementById('btn-efectivo').classList.add('active');
    document.getElementById('btn-transf').classList.remove('active');
});
document.getElementById('btn-transf').addEventListener('click', () => {
    metodoPago = 'transferencia';
    document.getElementById('btn-transf').classList.add('active');
    document.getElementById('btn-efectivo').classList.remove('active');
});

// ── Registrar venta ───────────────────────────────
document.getElementById('btn-registrar').addEventListener('click', async () => {
    const btn  = document.getElementById('btn-registrar');
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const detalle = Object.values(items).map(i => ({
        codigoprod      : i.codigoprod,
        cantidad        : i.cantidad,
        precio_unitario : i.precio,
        subtotal        : parseFloat((i.precio * i.cantidad).toFixed(2)),
    }));

    try {
        const r    = await fetch(BASE + 'ventas/registrar', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                detalle, metodo_pago: metodoPago,
                nota: document.getElementById('venta-nota').value
            })
        });
        const data = await r.json();
        if (data.ok) {
            mostrarToast('Venta registrada correctamente');
            Object.keys(items).forEach(k => delete items[k]);
            render();
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarToast(data.mensaje || 'Error', 'err');
        }
    } catch(e) { mostrarToast('Error de conexión','err'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrar Venta';
    }
});

// ── Filtrar catálogo ──────────────────────────────
function filtrar(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.prod-row').forEach(row => {
        const ok = !q || row.dataset.nombre.includes(q) || row.dataset.codigo.includes(q);
        row.style.display = ok ? '' : 'none';
    });
}

// ── Listeners en botones (DOMContentLoaded) ───────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-add.normal').forEach(btn => {
        btn.addEventListener('click', function() {
            try { agregarNormal(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto','err'); }
        });
    });
    document.querySelectorAll('.btn-add.peso').forEach(btn => {
        btn.addEventListener('click', function() {
            try { abrirMP(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto','err'); }
        });
    });

    // Modal: inputs en tiempo real
    document.getElementById('mp-cant').addEventListener('input', calcMP);
    document.getElementById('mp-prec').addEventListener('input', calcMP);
    document.getElementById('mp-cant').addEventListener('keydown', e => { if (e.key==='Enter') document.getElementById('mp-prec').focus(); });
    document.getElementById('mp-prec').addEventListener('keydown', e => { if (e.key==='Enter') confirmarMP(); });

    // Cerrar modal al click fuera
    document.getElementById('mp-overlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarMP();
    });
});
</script>

<?php cerrarLayout(); ?>
