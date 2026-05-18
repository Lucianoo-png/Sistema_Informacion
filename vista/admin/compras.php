<?php
// =====================================================
// vista/admin/compras.php — con carrito de productos
// Registra mercancía y actualiza inventario automáticamente
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Proveedor.php';
require_once BASE_PATH . 'modelo/Producto.php';

$paginaActual = 'compras';
$proveedores  = (new Proveedor())->obtenerTodos();

$todos    = (new Producto())->obtenerTodos();
$pesables = [];
$generales= [];
foreach ($todos as $p) {
    if (strtolower(trim($p['unidad'])) === 'kg') $pesables[]  = $p;
    else                                          $generales[] = $p;
}
usort($pesables,  fn($a,$b) => strcasecmp($a['nombre'], $b['nombre']));
usort($generales, fn($a,$b) => strcasecmp($a['nombre'], $b['nombre']));

abrirLayout('Compras', 'compras');
?>
<script>document.body.classList.add('page-compras');</script>

<style>
/* ── Layout split igual que ventas ── */
body.page-compras .main-content {
    padding: 18px 308px 24px 32px !important;
    max-width: none !important;
    overflow-x: hidden !important;
}
body.page-compras .pag-wrap { max-width: none !important; margin: 0 !important; }

/* ── Header ── */
.comp-hdr-top {
    display:flex; justify-content:space-between; align-items:flex-start;
    margin-bottom:12px; flex-wrap:wrap; gap:8px;
}
.comp-hdr-top h1 { font-size:20px; font-weight:700; margin:0; }
.comp-hdr-top p  { font-size:12px; color:var(--text-muted); margin:2px 0 0; }

/* ── Tipo compra ── */
.tipo-btns  { display:flex; gap:10px; }
.tipo-btn   { flex:1; padding:10px; border-radius:8px; border:1.5px solid var(--border);
              font-size:14px; cursor:pointer; background:#fff; font-weight:600; transition:.15s; }
.tipo-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }

/* ── Config row ── */
.comp-cfg { display:grid; grid-template-columns:1fr 150px; gap:10px; margin-bottom:12px; }

/* ── Grupos catálogo ── */
.grupo-hdr { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.8px;
             color:#fff; padding:5px 14px; border-radius:20px; margin-bottom:10px; margin-top:4px;
             display:inline-flex; align-items:center; gap:8px; }
.grupo-hdr .cnt { background:rgba(255,255,255,.3); padding:1px 7px; border-radius:10px;
                  font-size:10px; letter-spacing:0; text-transform:none; }
.grupo-hdr.peso    { background:var(--success); }
.grupo-hdr.general { background:var(--primary); }
.grupo-sep { height:3px; background:var(--border); border-radius:2px; margin:10px 0 14px; }

/* ── Filas producto ── */
.prod-row {
    display:grid;
    grid-template-columns: 1fr 80px 90px 36px;
    align-items:center; gap:10px;
    padding:9px 12px; background:var(--card-bg);
    border:1.5px solid var(--border); border-radius:10px;
    margin-bottom:6px; transition:border-color .15s;
}
.prod-row:hover { border-color:var(--primary); box-shadow:0 0 0 2px #e8772215; }
.prow-name  { font-weight:600; font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.prow-code  { font-size:11px; color:var(--text-muted); }
.prow-prec  { font-weight:700; color:var(--primary); font-size:13px; text-align:right; white-space:nowrap; }
.prow-prec small { display:block; font-size:10px; color:var(--text-muted); font-weight:400; }
.prow-stk   { font-size:11px; text-align:right; color:var(--text-muted); white-space:nowrap; }
.btn-add { width:34px; height:34px; border-radius:8px; border:none; cursor:pointer; font-size:13px;
           display:flex; align-items:center; justify-content:center; color:#fff;
           transition:opacity .15s, transform .1s; justify-self:end; }
.btn-add:hover  { opacity:.85; }
.btn-add:active { transform:scale(.92); }
.btn-add.normal { background:var(--primary); }
.btn-add.peso   { background:var(--success); }

/* ── Panel derecho fijo ── */
.cart-fixed {
    position:fixed; top:0; right:0; width:300px; height:100vh;
    background:var(--card-bg); border-left:1px solid var(--border);
    display:flex; flex-direction:column; z-index:200;
    box-shadow:-2px 0 12px rgba(0,0,0,.07);
}
.cart-header { padding:14px 16px 10px; border-bottom:1px solid var(--border);
               flex-shrink:0; display:flex; align-items:center; gap:8px;
               font-size:15px; font-weight:700; }
.cart-count  { background:var(--primary); color:#fff; font-size:11px; font-weight:700;
               padding:1px 7px; border-radius:10px; display:none; margin-left:auto; }
.cart-items  { flex:1; overflow-y:auto; padding:8px 14px; min-height:0; }
.cart-empty  { color:var(--text-muted); text-align:center; padding:32px 0; font-size:13px; }
.cart-footer { flex-shrink:0; padding:10px 14px 14px; border-top:1px solid var(--border); }
.cart-total-row { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:8px; }
.cart-total-label { font-size:14px; font-weight:700; }
.cart-total-val   { font-size:20px; font-weight:800; color:var(--primary); }

/* ── Items del carrito de compra ── */
.cart-item  { padding:10px 0; border-bottom:1px solid var(--border); }
.ci-top     { display:flex; justify-content:space-between; align-items:flex-start; gap:6px; margin-bottom:7px; }
.ci-name    { font-size:12px; font-weight:600; flex:1; line-height:1.3; }
.ci-total   { font-size:14px; font-weight:800; color:var(--primary); white-space:nowrap; }
.ci-fields  { display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:5px; }
.ci-field   { display:flex; flex-direction:column; gap:3px; }
.ci-field label { font-size:10px; color:var(--text-muted); font-weight:600;
                  text-transform:uppercase; letter-spacing:.4px; }
.ci-field input {
    width:100%; padding:5px 7px; font-size:12px; font-weight:600;
    border:1.5px solid var(--border); border-radius:6px; background:#fff;
    text-align:right; box-sizing:border-box;
}
.ci-field input:focus { outline:none; border-color:var(--primary); }
.ci-actions { display:flex; justify-content:flex-end; }
.ci-quitar  { background:none; border:none; color:var(--danger); cursor:pointer;
              font-size:11px; padding:0 2px; }

/* ── Modal KG ── */
.mp-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
              z-index:9000; align-items:center; justify-content:center; }
.mp-overlay.open { display:flex; }
.mp-box { background:#fff; border-radius:14px; padding:24px; width:320px; max-width:95vw;
          box-shadow:0 12px 40px rgba(0,0,0,.22); animation:fadeUp .17s ease; }
@keyframes fadeUp { from{transform:translateY(10px);opacity:0} to{transform:translateY(0);opacity:1} }
.mp-box h3  { font-size:16px; font-weight:700; margin-bottom:4px; }
.mp-tag     { font-size:11px; background:#e8f5ee; color:var(--success);
              padding:2px 8px; border-radius:5px; font-weight:600;
              margin-bottom:14px; display:inline-block; }
.mp-group   { display:flex; flex-direction:column; gap:4px; margin-bottom:12px; }
.mp-group label { font-size:11px; font-weight:600; color:var(--text-muted); }
.mp-group input { padding:10px 12px; border:1.5px solid var(--border); border-radius:8px;
                  font-size:18px; text-align:right; width:100%; box-sizing:border-box; }
.mp-group input:focus { outline:none; border-color:var(--primary); }
.mp-sub-box { background:#f5f0eb; border-radius:8px; padding:10px 14px;
              margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; }
.mp-sub-box span   { font-size:13px; color:var(--text-muted); }
.mp-sub-box strong { font-size:20px; font-weight:800; color:var(--primary); }
.mp-btns { display:flex; gap:10px; }
.mp-btns button { flex:1; padding:10px; border-radius:8px; font-size:13px;
                  font-weight:600; cursor:pointer; border:none; }
.mp-cancel  { background:var(--border); color:var(--text-dark); }
.mp-confirm { background:var(--success); color:#fff; }
</style>

<!-- ── Modal KG compra ── -->
<div class="mp-overlay" id="mp-overlay">
  <div class="mp-box">
    <h3 id="mp-nombre">Producto</h3>
    <span class="mp-tag"><i class="fa-solid fa-scale-balanced"></i> Por peso (kg) — Compra</span>
    <div class="mp-group">
      <label>Cantidad comprada (kg)</label>
      <input type="number" id="mp-kg" min="0.001" step="0.001"
             placeholder="0.000" oninput="calcModalKg()">
    </div>
    <div class="mp-group">
      <label>Precio pagado por kg ($)</label>
      <input type="number" id="mp-prec-kg" min="0.01" step="0.5"
             placeholder="0.00" oninput="calcModalKg()">
    </div>
    <div class="mp-sub-box">
      <span>Subtotal</span>
      <strong id="mp-sub">$0.00</strong>
    </div>
    <div class="mp-btns">
      <button class="mp-cancel"  onclick="cerrarMP()">
        <i class="fa-solid fa-xmark"></i> Cancelar
      </button>
      <button class="mp-confirm" onclick="confirmarMP()">
        <i class="fa-solid fa-plus"></i> Agregar
      </button>
    </div>
  </div>
</div>

<!-- ══ CONTENIDO ══ -->
<div class="comp-hdr-top">
  <div>
    <h1><i class="fa-solid fa-box-open" style="color:var(--primary)"></i> Registrar Compra</h1>
    <p>Agrega los productos recibidos — el inventario se actualiza automáticamente</p>
  </div>
  <a href="compras/historial" class="btn btn-outline btn-sm">
    <i class="fa-solid fa-clock-rotate-left"></i> Ver historial
  </a>
</div>

<!-- Configuración superior -->
<div class="card" style="margin-bottom:12px">

  <!-- Tipo de compra -->
  <div class="form-group" style="margin-bottom:12px">
    <label style="font-weight:600;font-size:13px">Tipo de Compra</label>
    <div class="tipo-btns" style="margin-top:6px">
      <button class="tipo-btn active" id="btn-tipo-prov" onclick="setTipo('proveedor')">
        <i class="fa-solid fa-truck"></i> De Proveedor
      </button>
      <button class="tipo-btn" id="btn-tipo-dir" onclick="setTipo('directa')">
        <i class="fa-solid fa-store"></i> Compra Directa
      </button>
    </div>
  </div>

  <!-- Proveedor + Fecha -->
  <div class="comp-cfg">
    <div id="row-proveedor">
      <label style="font-size:13px;font-weight:500;display:block;margin-bottom:4px">
        <i class="fa-solid fa-truck" style="color:var(--primary)"></i> Proveedor
      </label>
      <select class="form-control" id="sel-proveedor">
        <option value="">Seleccionar proveedor...</option>
        <?php foreach ($proveedores as $pv): ?>
        <option value="<?= $pv['id'] ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="font-size:13px;font-weight:500;display:block;margin-bottom:4px">
        <i class="fa-solid fa-calendar" style="color:var(--primary)"></i> Fecha
      </label>
      <input type="date" class="form-control" id="inp-fecha" value="<?= date('Y-m-d') ?>">
    </div>
  </div>

  <!-- Buscador -->
  <div class="searchbar" style="margin-bottom:0">
    <span class="searchbar-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
    <input type="text" id="buscador"
           placeholder="Buscar producto por nombre o código..."
           oninput="filtrar(this.value)">
  </div>

</div><!-- /card config -->

<!-- Catálogo de productos -->
<div id="catalogo-body">

  <?php if (!empty($pesables)): ?>
  <div class="grupo-hdr peso">
    <i class="fa-solid fa-scale-balanced"></i> Pesables — precio por kg
    <span class="cnt"><?= count($pesables) ?></span>
  </div>
  <?php foreach ($pesables as $p):
    $dj = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
  ?>
  <div class="prod-row"
       data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
       data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">
    <div>
      <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
      <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
    </div>
    <div class="prow-prec">
      <?= formatMXN($p['precio_compra']) ?>
      <small>ref./kg</small>
    </div>
    <div class="prow-stk">
      <i class="fa-solid fa-scale-balanced" style="color:var(--success)"></i>
      <?= number_format((float)$p['stock'], 2) ?> kg
    </div>
    <button class="btn-add peso" data-prod="<?= $dj ?>" title="Ingresar kg y precio">
      <i class="fa-solid fa-scale-balanced"></i>
    </button>
  </div>
  <?php endforeach; ?>
  <?php if (!empty($generales)): ?><div class="grupo-sep"></div><?php endif; ?>
  <?php endif; ?>

  <?php if (!empty($generales)): ?>
  <div class="grupo-hdr general">
    <i class="fa-solid fa-box"></i> Productos generales
    <span class="cnt"><?= count($generales) ?></span>
  </div>
  <?php foreach ($generales as $p):
    $dj = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
  ?>
  <div class="prod-row"
       data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
       data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">
    <div>
      <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
      <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
    </div>
    <div class="prow-prec"><?= formatMXN($p['precio_compra']) ?></div>
    <div class="prow-stk">
      <i class="fa-solid fa-boxes-stacked" style="color:var(--text-muted)"></i>
      <?= number_format((float)$p['stock'], 0) ?> <?= htmlspecialchars($p['unidad']) ?>
    </div>
    <button class="btn-add normal" data-prod="<?= $dj ?>" title="Agregar a la compra">
      <i class="fa-solid fa-plus"></i>
    </button>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div><!-- /catalogo-body -->


<!-- ─── PANEL DERECHO: carrito de compra ─── -->
<div class="cart-fixed">

  <div class="cart-header">
    <i class="fa-solid fa-box-open" style="color:var(--primary)"></i>
    Productos comprados
    <span class="cart-count" id="cart-count">0</span>
  </div>

  <div class="cart-items" id="cart-items">
    <p class="cart-empty">Agrega productos de la lista</p>
  </div>

  <div class="cart-footer">
    <div class="cart-total-row">
      <span class="cart-total-label">Total compra</span>
      <span class="cart-total-val" id="cart-total">$0.00</span>
    </div>

    <textarea id="inp-nota" class="nota-input"
              style="min-height:44px;max-height:68px;margin-bottom:10px"
              placeholder="Nota / descripción (opcional)..."></textarea>

    <button class="btn btn-primary"
            style="width:100%;justify-content:center;height:44px"
            id="btn-registrar" disabled>
      <i class="fa-solid fa-circle-check"></i> Registrar Compra
    </button>
  </div>
</div>


<script>
// ═══════════════════════════════════════════════
//  ESTADO
// ═══════════════════════════════════════════════
const items = {};          // { codigoprod: {...} }
let tipoActual  = 'proveedor';
let _prodModal  = null;

function fmt(n) {
    return '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── RENDER ─────────────────────────────────────
function render() {
    const el  = document.getElementById('cart-items');
    const tot = document.getElementById('cart-total');
    const btn = document.getElementById('btn-registrar');
    const cnt = document.getElementById('cart-count');

    const keys = Object.keys(items);
    cnt.style.display = keys.length ? 'inline-block' : 'none';
    cnt.textContent   = keys.length;

    if (!keys.length) {
        el.innerHTML    = '<p class="cart-empty">Agrega productos de la lista</p>';
        tot.textContent = '$0.00';
        btn.disabled    = true;
        return;
    }

    const total = keys.reduce((s, k) => s + items[k].subtotal, 0);

    el.innerHTML = keys.map(cod => {
        const it = items[cod];
        return `
        <div class="cart-item">
          <div class="ci-top">
            <span class="ci-name">
              ${it.tipo === 'peso'
                ? '<i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px"></i> '
                : ''}${esc(it.nombre)}
            </span>
            <span class="ci-total">${fmt(it.subtotal)}</span>
          </div>
          <div class="ci-fields">
            <div class="ci-field">
              <label>${it.tipo === 'peso' ? 'Cantidad (kg)' : 'Cantidad'}</label>
              <input type="number"
                     value="${it.tipo === 'peso' ? it.cantidad.toFixed(3) : it.cantidad}"
                     min="${it.tipo === 'peso' ? '0.001' : '1'}"
                     step="${it.tipo === 'peso' ? '0.001' : '1'}"
                     oninput="updCampo('${cod}','cantidad',this.value)">
            </div>
            <div class="ci-field">
              <label>${it.tipo === 'peso' ? 'Precio/kg ($)' : 'Precio unit. ($)'}</label>
              <input type="number" value="${it.precio_unitario.toFixed(2)}"
                     min="0" step="0.01"
                     oninput="updCampo('${cod}','precio_unitario',this.value)">
            </div>
          </div>
          <div class="ci-actions">
            <button class="ci-quitar" onclick="quitar('${cod}')">
              <i class="fa-solid fa-trash-can"></i> Quitar
            </button>
          </div>
        </div>`;
    }).join('');

    tot.textContent = fmt(total);
    btn.disabled    = false;
}

function updCampo(cod, campo, val) {
    const v = parseFloat(val);
    if (isNaN(v) || v <= 0) return;
    items[cod][campo] = v;
    items[cod].subtotal = parseFloat(
        (items[cod].cantidad * items[cod].precio_unitario).toFixed(2)
    );
    render();
}
function quitar(cod) { delete items[cod]; render(); }

// ── Agregar producto general ─────────────────────
function agregarNormal(p) {
    const cod = p.codigoprod;
    if (items[cod]) {
        items[cod].cantidad++;
        items[cod].subtotal = parseFloat(
            (items[cod].cantidad * items[cod].precio_unitario).toFixed(2)
        );
    } else {
        const pu = parseFloat(p.precio_compra) || 0;
        items[cod] = {
            codigoprod: cod, nombre: p.nombre, tipo: 'normal',
            cantidad: 1, precio_unitario: pu,
            subtotal: parseFloat(pu.toFixed(2))
        };
    }
    render();
}

// ── Modal KG ───────────────────────────────────
function abrirMP(p) {
    _prodModal = p;
    document.getElementById('mp-nombre').textContent = p.nombre;
    document.getElementById('mp-kg').value      = '';
    document.getElementById('mp-prec-kg').value = p.precio_compra || '';
    document.getElementById('mp-sub').textContent = '$0.00';
    document.getElementById('mp-overlay').classList.add('open');
    setTimeout(() => document.getElementById('mp-kg').focus(), 80);
}
function cerrarMP() {
    document.getElementById('mp-overlay').classList.remove('open');
    _prodModal = null;
}
function calcModalKg() {
    const kg   = parseFloat(document.getElementById('mp-kg').value)      || 0;
    const prec = parseFloat(document.getElementById('mp-prec-kg').value) || 0;
    const sub  = kg * prec;
    document.getElementById('mp-sub').textContent = fmt(sub);
    document.getElementById('mp-sub').style.color =
        sub > 0 ? 'var(--primary)' : 'var(--text-muted)';
}
function confirmarMP() {
    const kg   = parseFloat(document.getElementById('mp-kg').value);
    const prec = parseFloat(document.getElementById('mp-prec-kg').value);
    if (!kg   || kg   <= 0) { mostrarToast('Ingresa la cantidad en kg', 'err'); return; }
    if (!prec || prec <= 0) { mostrarToast('Ingresa el precio por kg',  'err'); return; }

    const p   = _prodModal;
    const cod = p.codigoprod;
    items[cod] = {
        codigoprod: cod, nombre: p.nombre, tipo: 'peso',
        cantidad: kg, precio_unitario: prec,
        subtotal: parseFloat((kg * prec).toFixed(2))
    };
    mostrarToast('✓ ' + p.nombre + ' — ' + fmt(kg * prec));
    cerrarMP(); render();
}

// ── Tipo de compra ───────────────────────────────
function setTipo(t) {
    tipoActual = t;
    document.getElementById('btn-tipo-prov').classList.toggle('active', t === 'proveedor');
    document.getElementById('btn-tipo-dir').classList.toggle('active',  t === 'directa');
    document.getElementById('row-proveedor').style.display =
        t === 'proveedor' ? '' : 'none';
}

// ── Filtrar catálogo ─────────────────────────────
function filtrar(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.prod-row').forEach(row => {
        const ok = !q
            || row.dataset.nombre.includes(q)
            || row.dataset.codigo.includes(q);
        row.style.display = ok ? '' : 'none';
    });
}

// ── Init ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Botones catálogo
    document.querySelectorAll('.btn-add.normal').forEach(btn => {
        btn.addEventListener('click', function() {
            try { agregarNormal(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto', 'err'); }
        });
    });
    document.querySelectorAll('.btn-add.peso').forEach(btn => {
        btn.addEventListener('click', function() {
            try { abrirMP(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto', 'err'); }
        });
    });

    // Modal: teclado
    document.getElementById('mp-kg').addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('mp-prec-kg').focus();
    });
    document.getElementById('mp-prec-kg').addEventListener('keydown', e => {
        if (e.key === 'Enter') confirmarMP();
    });
    document.getElementById('mp-overlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarMP();
    });

    // Registrar compra
    document.getElementById('btn-registrar').addEventListener('click', async () => {
        const btn = document.getElementById('btn-registrar');

        if (tipoActual === 'proveedor') {
            const prov = document.getElementById('sel-proveedor')?.value;
            if (!prov) { mostrarToast('Selecciona un proveedor.', 'err'); return; }
        }

        const keys = Object.keys(items);
        if (!keys.length) {
            mostrarToast('Agrega al menos un producto.', 'err'); return;
        }

        const detalle = keys.map(cod => ({
            codigoprod      : cod,
            cantidad        : items[cod].cantidad,
            precio_unitario : items[cod].precio_unitario,
            subtotal        : items[cod].subtotal,
        }));
        const total = parseFloat(
            detalle.reduce((s, d) => s + d.subtotal, 0).toFixed(2)
        );

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        try {
            const r = await fetch(BASE + 'compras/registrar', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    tipo         : tipoActual,
                    proveedor_id : document.getElementById('sel-proveedor')?.value
                                   ? parseInt(document.getElementById('sel-proveedor').value)
                                   : null,
                    total,
                    nota   : document.getElementById('inp-nota').value.trim(),
                    detalle,
                }),
            });
            const data = await r.json();
            if (data.ok) {
                mostrarToast('Compra registrada — inventario actualizado');
                Object.keys(items).forEach(k => delete items[k]);
                render();
                document.getElementById('inp-nota').value = '';
                if (document.getElementById('sel-proveedor'))
                    document.getElementById('sel-proveedor').value = '';
                setTimeout(() => location.reload(), 1300);
            } else {
                mostrarToast(data.mensaje || 'Error al registrar', 'err');
            }
        } catch(e) {
            mostrarToast('Error de conexión', 'err');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrar Compra';
        }
    });
});
</script>

<?php cerrarLayout(); ?>
