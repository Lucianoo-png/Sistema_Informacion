<?php
// =====================================================
// vista/vendedor/ventas.php — Nueva Venta
// MEJORAS:
//   • Layout: lista agrupada por categoría (más legible)
//   • Frutas y verduras: modal con peso (decimal) y precio editable
//   • Resto de productos: botón + directo con cantidad entera
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Producto.php';

$paginaActual = 'ventas';

// Obtener productos y agrupar por categoría
$todosProd   = (new Producto())->obtenerTodos();
$categorias  = [];
foreach ($todosProd as $p) {
    $cat = $p['categoria'] ?: 'Sin categoría';
    $categorias[$cat][] = $p;
}
ksort($categorias);

// Categorías con precio variable (peso/granel)
define('CAT_PESO', ['Frutas y verduras', 'Frutas y Verduras', 'frutas y verduras',
                    'Verduras', 'Frutas', 'Granel', 'A granel']);

abrirLayout('Nueva Venta', 'ventas');
?>

<style>
/* ── Layout ventas ───────────────────────────────── */
.ventas-wrapper { display:flex; gap:0; margin:-32px; margin-left:0; min-height:calc(100vh - 0px); }
.catalogo       { flex:1; display:flex; flex-direction:column; overflow:hidden; }
.catalogo-inner { flex:1; overflow-y:auto; padding:24px 28px; }

/* ── Searchbar en la cabecera del catálogo ──────── */
.catalogo-header { padding:20px 28px 0; background:var(--bg); }
.catalogo-header h1 { font-size:22px; font-weight:700; }
.catalogo-header p  { font-size:13px; color:var(--text-muted); margin-bottom:12px; }

/* ── Sección de categoría ───────────────────────── */
.cat-seccion { margin-bottom:24px; }
.cat-titulo  {
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.8px; color:var(--text-muted);
    padding:6px 0; border-bottom:2px solid var(--border);
    margin-bottom:10px;
    display:flex; align-items:center; gap:8px;
}
.cat-titulo .cat-badge {
    font-size:10px; padding:2px 7px; border-radius:10px;
    background:var(--primary); color:#fff; font-weight:700;
    text-transform:none; letter-spacing:0;
}
.cat-titulo .peso-badge {
    font-size:10px; padding:2px 7px; border-radius:10px;
    background:#e8f5ee; color:var(--success); font-weight:700;
    text-transform:none; letter-spacing:0;
}

/* ── Fila de producto ───────────────────────────── */
.prod-row {
    display:grid;
    grid-template-columns: 1fr 90px 80px 80px auto;
    align-items:center;
    gap:12px;
    padding:10px 14px;
    background:var(--card-bg);
    border:1.5px solid var(--border);
    border-radius:10px;
    margin-bottom:8px;
    transition:border-color .15s, box-shadow .15s;
}
.prod-row:hover { border-color:var(--primary); box-shadow:0 0 0 2px #e8772215; }
.prod-row.sin-stock { opacity:.45; }

.prod-row .prow-name  { font-weight:600; font-size:14px; line-height:1.2; }
.prod-row .prow-code  { font-size:11px; color:var(--text-muted); margin-top:2px; }
.prod-row .prow-cat   { font-size:11px; color:var(--text-muted); }
.prod-row .prow-precio { font-weight:700; color:var(--primary); font-size:14px; text-align:right; }
.prod-row .prow-stock  { font-size:12px; text-align:center; }
.prod-row .prow-stock.low { color:var(--danger); font-weight:700; }
.prod-row .prow-stock.ok  { color:var(--success); }

/* Botón añadir en fila */
.btn-add-prod {
    width:34px; height:34px; border-radius:8px;
    background:var(--primary); color:#fff;
    border:none; cursor:pointer; font-size:18px;
    display:flex; align-items:center; justify-content:center;
    transition:opacity .15s, transform .1s;
}
.btn-add-prod:hover  { opacity:.85; }
.btn-add-prod:active { transform:scale(.92); }
.btn-add-prod.peso   { background:var(--success); }
.btn-add-prod:disabled { opacity:.35; cursor:not-allowed; }

/* ── Carrito ────────────────────────────────────── */
.cart-panel {
    width:310px; min-height:100vh;
    background:var(--card-bg);
    border-left:1px solid var(--border);
    padding:20px 18px;
    display:flex; flex-direction:column;
    position:sticky; top:0;
}
.cart-title { font-size:16px; font-weight:700; margin-bottom:14px;
              display:flex; align-items:center; gap:8px; }
.cart-items { flex:1; overflow-y:auto; }
.cart-empty { color:var(--text-muted); text-align:center;
              margin-top:40px; font-size:13px; }

/* Item normal en carrito */
.cart-item {
    padding:10px 0; border-bottom:1px solid var(--border);
    font-size:13px;
}
.cart-item-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px; }
.cart-item-name   { font-weight:600; font-size:13px; flex:1; margin-right:6px; }
.cart-item-sub    { font-weight:700; font-size:13px; color:var(--primary); white-space:nowrap; }

/* Controles qty normales */
.cart-qty { display:flex; align-items:center; gap:4px; }
.cart-qty button {
    width:24px; height:24px; border-radius:5px; border:1.5px solid var(--border);
    background:#fff; cursor:pointer; font-size:15px; line-height:1;
    display:flex; align-items:center; justify-content:center; color:var(--primary);
}
.cart-qty span { font-weight:700; min-width:28px; text-align:center; font-size:13px; }
.cart-item-precio { font-size:11px; color:var(--text-muted); }

/* Item tipo peso (frutas) */
.cart-item-peso .peso-inputs {
    display:grid; grid-template-columns:1fr 1fr;
    gap:6px; margin-top:6px;
}
.peso-input-group { display:flex; flex-direction:column; gap:2px; }
.peso-input-group label { font-size:10px; color:var(--text-muted); font-weight:600; }
.peso-input-group input {
    padding:5px 8px; border:1.5px solid var(--border);
    border-radius:6px; font-size:13px; width:100%;
    text-align:right;
}
.peso-input-group input:focus { outline:none; border-color:var(--primary); }
.btn-quitar {
    background:none; border:none; color:var(--danger);
    cursor:pointer; font-size:12px; padding:0 2px;
}

/* ── Modal peso ──────────────────────────────────── */
.modal-peso-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.45); z-index:2000;
    align-items:center; justify-content:center;
}
.modal-peso-overlay.open { display:flex; }
.modal-peso {
    background:#fff; border-radius:14px;
    padding:28px; width:340px; max-width:95vw;
    box-shadow:0 12px 40px rgba(0,0,0,.22);
    animation:slideUp .18s ease;
}
@keyframes slideUp { from { transform:translateY(14px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.modal-peso h3 { font-size:17px; font-weight:700; margin-bottom:4px; }
.modal-peso .mp-cat { font-size:12px; color:var(--success); font-weight:600;
    background:#e8f5ee; padding:2px 8px; border-radius:6px; display:inline-block; margin-bottom:16px; }
.mp-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.mp-group { display:flex; flex-direction:column; gap:5px; }
.mp-group label { font-size:12px; font-weight:600; color:var(--text-muted); }
.mp-group input {
    padding:10px 12px; border:1.5px solid var(--border);
    border-radius:8px; font-size:15px; text-align:right;
}
.mp-group input:focus { outline:none; border-color:var(--primary); }
.mp-total { background:#f5f0eb; border-radius:8px; padding:12px 14px;
            margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; }
.mp-total span:first-child { font-size:13px; color:var(--text-muted); }
.mp-total strong { font-size:20px; font-weight:800; color:var(--primary); }
.mp-btns { display:flex; gap:10px; }
.mp-btns button { flex:1; padding:10px; border-radius:8px; font-size:14px;
                  font-weight:600; cursor:pointer; border:none; }
.mp-cancel { background:var(--border); color:var(--text-dark); }
.mp-confirm { background:var(--primary); color:#fff; }
</style>

<!-- ── Modal para Frutas y Verduras ───────────────── -->
<div class="modal-peso-overlay" id="modal-peso">
    <div class="modal-peso">
        <h3 id="mp-nombre">Producto</h3>
        <span class="mp-cat"><i class="fa-solid fa-leaf"></i> Frutas y Verduras — precio por peso</span>
        <div class="mp-row">
            <div class="mp-group">
                <label><i class="fa-solid fa-scale-balanced" style="color:var(--success)"></i> Cantidad / Peso</label>
                <input type="number" id="mp-cantidad" min="0.01" step="0.01"
                       placeholder="0.00" oninput="mpActualizar()">
            </div>
            <div class="mp-group">
                <label><i class="fa-solid fa-tag" style="color:var(--primary)"></i> Precio unitario ($)</label>
                <input type="number" id="mp-precio" min="0" step="0.5"
                       placeholder="0.00" oninput="mpActualizar()">
            </div>
        </div>
        <div class="mp-total">
            <span>Total a cobrar</span>
            <strong id="mp-total-val">$0.00</strong>
        </div>
        <div class="mp-btns">
            <button class="mp-cancel" onclick="cerrarModalPeso()">Cancelar</button>
            <button class="mp-confirm" id="mp-confirm-btn" onclick="mpConfirmar()">
                <i class="fa-solid fa-plus"></i> Agregar al carrito
            </button>
        </div>
    </div>
</div>

<!-- ── Estructura principal ───────────────────────── -->
<div class="ventas-wrapper">

<!-- ══ CATÁLOGO ══════════════════════════════════── -->
<div class="catalogo">
    <div class="catalogo-header">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <div>
                <h1>Nueva Venta</h1>
                <p style="font-size:13px;color:var(--text-muted)">Selecciona productos para agregar al carrito</p>
            </div>
            <a href="ventas/historial" class="btn btn-outline btn-sm" style="white-space:nowrap">
                <i class="fa-solid fa-clock-rotate-left"></i> Ver historial
            </a>
        </div>
        <div class="searchbar" style="margin-bottom:16px">
            <span class="searchbar-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="input-buscar"
                   placeholder="Buscar por nombre o código..."
                   oninput="filtrarProductos(this.value)">
        </div>
    </div>

    <div class="catalogo-inner" id="catalogo-inner">
        <?php
        // Iconos por categoría
        $catIcons = [
            'Bebidas'          => 'fa-solid fa-whiskey-glass',
            'Botanas'          => 'fa-solid fa-cookie',
            'Lacteos'          => 'fa-solid fa-cheese',
            'Panaderia'        => 'fa-solid fa-bread-slice',
            'Limpieza'         => 'fa-solid fa-soap',
            'Granos'           => 'fa-solid fa-seedling',
            'Aceites'          => 'fa-solid fa-flask',
            'Abarrotes'        => 'fa-solid fa-box',
            'Higiene'          => 'fa-solid fa-pump-soap',
            'Frutas y verduras'=> 'fa-solid fa-carrot',
            'Frutas y Verduras'=> 'fa-solid fa-carrot',
            'Verduras'         => 'fa-solid fa-leaf',
            'Frutas'           => 'fa-solid fa-apple-whole',
        ];

        foreach ($categorias as $cat => $prods):
            $esPeso   = in_array($cat, CAT_PESO);
            $icon     = $catIcons[$cat] ?? 'fa-solid fa-tag';
            $totalCat = count($prods);
        ?>
        <div class="cat-seccion" data-cat="<?= htmlspecialchars($cat) ?>">
            <div class="cat-titulo">
                <i class="<?= $icon ?>"></i>
                <?= htmlspecialchars($cat) ?>
                <span class="cat-badge"><?= $totalCat ?></span>
                <?php if ($esPeso): ?>
                <span class="peso-badge"><i class="fa-solid fa-scale-balanced"></i> precio por peso</span>
                <?php endif; ?>
            </div>

            <?php foreach ($prods as $p):
                $sinStock = (int)$p['stock'] <= 0;
                $low      = (int)$p['stock'] <= (int)$p['stock_minimo'];
                $dataJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="prod-row <?= $sinStock ? 'sin-stock' : '' ?>"
                 data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
                 data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">

                <!-- Nombre + código -->
                <div>
                    <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
                    <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
                </div>

                <!-- Precio referencia -->
                <div class="prow-precio">
                    <?= formatMXN($p['precio_venta']) ?>
                    <?php if ($esPeso): ?>
                    <div style="font-size:10px;color:var(--text-muted);font-weight:400">/<?= $p['unidad'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Stock -->
                <div class="prow-stock <?= $low ? 'low' : 'ok' ?>">
                    <?= $sinStock ? '<i class="fa-solid fa-xmark-circle"></i> Sin stock'
                        : ($low ? '<i class="fa-solid fa-circle-exclamation"></i> ' . $p['stock']
                                : '<i class="fa-solid fa-circle-check"></i> ' . $p['stock']) ?>
                    <?php if (!$sinStock): ?>
                    <div style="font-size:10px;color:var(--text-muted)"><?= $p['unidad'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Unidad -->
                <div style="font-size:11px;color:var(--text-muted);text-align:center">
                    <?= htmlspecialchars($p['unidad']) ?>
                </div>

                <!-- Botón añadir -->
                <?php if ($esPeso): ?>
                <button class="btn-add-prod peso"
                        <?= $sinStock ? 'disabled' : '' ?>
                        data-producto="<?= $dataJson ?>"
                        onclick="abrirModalPeso(this)"
                        title="Ingresar cantidad y precio">
                    <i class="fa-solid fa-scale-balanced" style="font-size:14px"></i>
                </button>
                <?php else: ?>
                <button class="btn-add-prod"
                        <?= $sinStock ? 'disabled' : '' ?>
                        data-producto="<?= $dataJson ?>"
                        onclick="Carrito.agregar(JSON.parse(this.dataset.producto))"
                        title="Agregar al carrito">
                    <i class="fa-solid fa-plus"></i>
                </button>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div><!-- /catalogo-inner -->
</div><!-- /catalogo -->

<!-- ══ CARRITO ═══════════════════════════════════── -->
<div class="cart-panel">
    <div class="cart-title">
        <i class="fa-solid fa-cart-shopping" style="color:var(--primary)"></i>
        Carrito
        <span id="cart-count" style="margin-left:auto;background:var(--primary);color:#fff;
              font-size:11px;padding:2px 8px;border-radius:10px;display:none">0</span>
    </div>

    <div class="cart-items" id="cart-items">
        <p class="cart-empty">Agrega productos al carrito</p>
    </div>

    <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:8px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <span style="font-size:15px;font-weight:700">Total</span>
            <span style="font-size:20px;font-weight:800;color:var(--primary)" id="cart-total">$0.00</span>
        </div>

        <div class="metodo-pago">
            <label>Método de Pago</label>
            <div class="metodo-btns">
                <button class="metodo-btn active" data-metodo="efectivo"
                        onclick="Carrito.setMetodo('efectivo')">
                    <i class="fa-solid fa-money-bill-wave"></i> Efectivo
                </button>
                <button class="metodo-btn" data-metodo="transferencia"
                        onclick="Carrito.setMetodo('transferencia')">
                    <i class="fa-solid fa-right-left"></i> Transferencia
                </button>
            </div>
        </div>

        <div style="margin-top:10px">
            <label style="font-size:11px;color:var(--text-muted);font-weight:600">NOTA (opcional)</label>
            <textarea id="venta-nota" class="nota-input"
                      style="margin-top:4px" placeholder="Comentario..."></textarea>
        </div>

        <button class="btn btn-primary"
                style="width:100%;margin-top:12px;justify-content:center;height:44px"
                id="btn-registrar-venta" disabled onclick="Carrito.registrar()">
            <i class="fa-solid fa-circle-check"></i> Registrar Venta
        </button>
    </div>
</div>

</div><!-- /ventas-wrapper -->

<script>
// ══════════════════════════════════════════════════
//  FILTRAR CATÁLOGO
// ══════════════════════════════════════════════════
function filtrarProductos(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.cat-seccion').forEach(sec => {
        let visibles = 0;
        sec.querySelectorAll('.prod-row').forEach(row => {
            const match = !q
                || row.dataset.nombre.includes(q)
                || row.dataset.codigo.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visibles++;
        });
        // Ocultar toda la sección si no hay resultados
        sec.style.display = visibles > 0 ? '' : 'none';
    });
}

// ══════════════════════════════════════════════════
//  MODAL FRUTAS Y VERDURAS
// ══════════════════════════════════════════════════
let _prodPeso = null;   // producto activo en el modal

function abrirModalPeso(btn) {
    try {
        _prodPeso = JSON.parse(btn.dataset.producto);
    } catch(e) { mostrarToast('Error al leer producto','err'); return; }

    const precioRef = parseFloat(_prodPeso.precio_venta).toFixed(2);
    const unidad    = _prodPeso.unidad || 'kg';

    document.getElementById('mp-nombre').textContent    = _prodPeso.nombre;
    document.getElementById('mp-precio-ref').textContent =
        '(referencia: $' + precioRef + '/' + unidad + ')';
    document.getElementById('mp-unidad-lbl').textContent = '(' + unidad + ')';

    // Limpiar campos — el cajero ingresa los valores manualmente
    document.getElementById('mp-cantidad').value        = '';
    document.getElementById('mp-precio').value          = '';
    document.getElementById('mp-total-val').textContent = '$0.00';

    document.getElementById('modal-peso').classList.add('open');
    setTimeout(() => document.getElementById('mp-cantidad').focus(), 80);
}

function cerrarModalPeso() {
    document.getElementById('modal-peso').classList.remove('open');
    _prodPeso = null;
}

// Cerrar modal al click fuera
document.getElementById('modal-peso').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPeso();
});

function mpActualizar() {
    const cant  = parseFloat(document.getElementById('mp-cantidad').value) || 0;
    const prec  = parseFloat(document.getElementById('mp-precio').value)   || 0;
    const total = cant * prec;

    const el = document.getElementById('mp-total-val');
    el.textContent = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    // Highlight total when both fields have values
    el.style.color = (cant > 0 && prec > 0) ? 'var(--success)' : 'var(--primary)';
}

function mpConfirmar() {
    if (!_prodPeso) return;
    const cant = parseFloat(document.getElementById('mp-cantidad').value);
    const prec = parseFloat(document.getElementById('mp-precio').value);

    if (!cant || cant <= 0) {
        mostrarToast('Ingresa una cantidad mayor a cero.', 'err');
        document.getElementById('mp-cantidad').focus();
        return;
    }
    if (!prec || prec <= 0) {
        mostrarToast('Ingresa un precio mayor a cero.', 'err');
        document.getElementById('mp-precio').focus();
        return;
    }

    Carrito.agregarPeso(_prodPeso, cant, prec);
    cerrarModalPeso();
}

// ══════════════════════════════════════════════════
//  MÓDULO CARRITO
// ══════════════════════════════════════════════════
const Carrito = (() => {
    // items[cod] = { codigoprod, nombre, tipo:'normal'|'peso', precio, cantidad, stock }
    const items = {};
    let metodoPago = 'efectivo';

    // ── Agregar producto normal ────────────────────
    function agregar(p) {
        const cod = p.codigoprod;
        if (items[cod]) {
            if (items[cod].tipo === 'peso') {
                mostrarToast('Este producto se vende por peso — usa el botón ⚖️', 'err');
                return;
            }
            if (items[cod].cantidad < parseInt(p.stock)) {
                items[cod].cantidad++;
            } else {
                mostrarToast('No hay más stock disponible', 'err');
                return;
            }
        } else {
            items[cod] = {
                codigoprod : cod,
                nombre     : p.nombre,
                tipo       : 'normal',
                precio     : parseFloat(p.precio_venta),
                cantidad   : 1,
                stock      : parseInt(p.stock),
            };
        }
        render();
    }

    // ── Agregar producto por peso ──────────────────
    function agregarPeso(p, cantidad, precio) {
        const cod = p.codigoprod;
        // Si ya está, suma la cantidad al existente
        if (items[cod] && items[cod].tipo === 'peso') {
            items[cod].cantidad = parseFloat((items[cod].cantidad + cantidad).toFixed(3));
            items[cod].precio   = precio;   // actualiza precio al último ingresado
        } else {
            items[cod] = {
                codigoprod : cod,
                nombre     : p.nombre,
                tipo       : 'peso',
                precio     : precio,
                cantidad   : parseFloat(cantidad.toFixed(3)),
                stock      : parseInt(p.stock),
                unidad     : p.unidad || 'kg',
            };
        }
        mostrarToast(`✓ ${p.nombre} — $${(cantidad * precio).toFixed(2)}`);
        render();
    }

    // ── Cambiar cantidad (solo normal) ─────────────
    function cambiar(cod, delta) {
        if (!items[cod] || items[cod].tipo !== 'normal') return;
        items[cod].cantidad += delta;
        if (items[cod].cantidad <= 0) delete items[cod];
        render();
    }

    // ── Actualizar campo del item peso ─────────────
    function actualizarPeso(cod, campo, val) {
        if (!items[cod] || items[cod].tipo !== 'peso') return;
        const v = parseFloat(val);
        if (!isNaN(v) && v > 0) items[cod][campo] = v;
        render();
    }

    // ── Quitar item ────────────────────────────────
    function quitar(cod) {
        delete items[cod];
        render();
    }

    // ── Total ──────────────────────────────────────
    function total() {
        return Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
    }

    // ── Render carrito ─────────────────────────────
    function render() {
        const el    = document.getElementById('cart-items');
        const tot   = document.getElementById('cart-total');
        const btn   = document.getElementById('btn-registrar-venta');
        const count = document.getElementById('cart-count');
        if (!el) return;

        const keys = Object.keys(items);
        count.style.display = keys.length ? 'inline-block' : 'none';
        count.textContent   = keys.length;

        if (!keys.length) {
            el.innerHTML = '<p class="cart-empty">Agrega productos al carrito</p>';
            tot.textContent = '$0.00';
            btn.disabled    = true;
            return;
        }

        el.innerHTML = keys.map(cod => {
            const it = items[cod];
            const sub = (it.precio * it.cantidad).toFixed(2);

            if (it.tipo === 'peso') {
                // ── Item tipo peso: campos editables ──
                return `
                <div class="cart-item cart-item-peso">
                  <div class="cart-item-header">
                    <span class="cart-item-name">
                      <i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px"></i>
                      ${escHtml(it.nombre)}
                    </span>
                    <span class="cart-item-sub">$${sub}</span>
                  </div>
                  <div class="peso-inputs">
                    <div class="peso-input-group">
                      <label><i class="fa-solid fa-scale-balanced"></i> Cantidad (${escHtml(it.unidad||'kg')})</label>
                      <input type="number" value="${it.cantidad}" min="0.01" step="0.01"
                             onchange="Carrito.actualizarPeso('${cod}','cantidad',this.value)"
                             oninput="this.style.borderColor='var(--primary)'">
                    </div>
                    <div class="peso-input-group">
                      <label><i class="fa-solid fa-tag"></i> Precio / ${escHtml(it.unidad||'kg')}</label>
                      <input type="number" value="${it.precio.toFixed(2)}" min="0" step="0.5"
                             onchange="Carrito.actualizarPeso('${cod}','precio',this.value)"
                             oninput="this.style.borderColor='var(--primary)'">
                    </div>
                  </div>
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
                    <span style="font-size:11px;color:var(--text-muted)">
                      ${it.cantidad} × $${it.precio.toFixed(2)}
                    </span>
                    <button class="btn-quitar" onclick="Carrito.quitar('${cod}')">
                      <i class="fa-solid fa-trash-can"></i> Quitar
                    </button>
                  </div>
                </div>`;
            } else {
                // ── Item normal: +/− ──
                return `
                <div class="cart-item">
                  <div class="cart-item-header">
                    <span class="cart-item-name">${escHtml(it.nombre)}</span>
                    <span class="cart-item-sub">$${sub}</span>
                  </div>
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="cart-item-precio">$${it.precio.toFixed(2)} c/u</span>
                    <div style="display:flex;align-items:center;gap:6px">
                      <div class="cart-qty">
                        <button onclick="Carrito.cambiar('${cod}',-1)">−</button>
                        <span>${it.cantidad}</span>
                        <button onclick="Carrito.cambiar('${cod}',+1)">+</button>
                      </div>
                      <button class="btn-quitar" onclick="Carrito.quitar('${cod}')">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>
                </div>`;
            }
        }).join('');

        tot.textContent = '$' + total().toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        btn.disabled = false;
    }

    // ── Registrar venta ────────────────────────────
    async function registrar() {
        const btn  = document.getElementById('btn-registrar-venta');
        const nota = document.getElementById('venta-nota')?.value ?? '';
        btn.disabled    = true;
        btn.textContent = 'Procesando...';

        const detalle = Object.values(items).map(i => ({
            codigoprod      : i.codigoprod,
            cantidad        : i.cantidad,
            precio_unitario : i.precio,
            subtotal        : parseFloat((i.precio * i.cantidad).toFixed(2)),
        }));

        try {
            const res  = await fetch(BASE + 'ventas/registrar', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/json' },
                body    : JSON.stringify({ detalle, metodo_pago: metodoPago, nota }),
            });
            const data = await res.json();
            if (data.ok) {
                mostrarToast('Venta registrada correctamente');
                Object.keys(items).forEach(k => delete items[k]);
                render();
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarToast(data.mensaje || 'Error al registrar', 'err');
            }
        } catch (e) {
            mostrarToast('Error de conexión', 'err');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Registrar Venta';
        }
    }

    function setMetodo(m) {
        metodoPago = m;
        document.querySelectorAll('.metodo-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.metodo === m));
    }

    return { agregar, agregarPeso, cambiar, actualizarPeso, quitar, registrar, setMetodo };
})();

// ── Helper escapeHtml ──────────────────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php cerrarLayout(); ?>
