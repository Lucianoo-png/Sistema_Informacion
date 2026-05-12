<?php
// =====================================================
// vista/vendedor/ventas.php — Nueva Venta
// FIX: Carrito definido ANTES de cargar abarrotes.js
//      Modal peso cargado ANTES del script de ventas
// =====================================================

require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Producto.php';

$paginaActual = 'ventas';

// Categorías que usan precio por peso/cantidad variable
define('CAT_PESO', [
    'Frutas y verduras','Frutas y Verduras','frutas y verduras',
    'Verduras','Frutas','Semillas','semillas',
    'Chiles','chiles','Chiles y otras semillas',
    'Granel','A granel',
]);

// Agrupar por categoría
$todosProd  = (new Producto())->obtenerTodos();
$categorias = [];
foreach ($todosProd as $p) {
    $cat = $p['categoria'] ?: 'Sin categoría';
    $categorias[$cat][] = $p;
}
ksort($categorias);

// Iconos por categoría
$catIcons = [
    'Bebidas'                => 'fa-solid fa-whiskey-glass',
    'Botanas'                => 'fa-solid fa-cookie',
    'Papas'                  => 'fa-solid fa-cookie',
    'Lacteos'                => 'fa-solid fa-cheese',
    'Panaderia'              => 'fa-solid fa-bread-slice',
    'Pan'                    => 'fa-solid fa-bread-slice',
    'Limpieza'               => 'fa-solid fa-soap',
    'Granos'                 => 'fa-solid fa-seedling',
    'Aceites'                => 'fa-solid fa-flask',
    'Abarrotes'              => 'fa-solid fa-box',
    'Higiene'                => 'fa-solid fa-pump-soap',
    'Frutas y verduras'      => 'fa-solid fa-carrot',
    'Frutas y Verduras'      => 'fa-solid fa-carrot',
    'Verduras'               => 'fa-solid fa-leaf',
    'Frutas'                 => 'fa-solid fa-apple-whole',
    'Semillas'               => 'fa-solid fa-seedling',
    'Chiles'                 => 'fa-solid fa-pepper-hot',
    'Chiles y otras semillas'=> 'fa-solid fa-pepper-hot',
    'Granel'                 => 'fa-solid fa-scale-balanced',
];

// Separar: categorías con peso vs categorías normales
$catsPeso    = [];
$catsNormal  = [];
foreach ($categorias as $cat => $prods) {
    if (in_array($cat, CAT_PESO)) $catsPeso[$cat]   = $prods;
    else                          $catsNormal[$cat]  = $prods;
}

abrirLayout('Nueva Venta', 'ventas');
?>

<style>
/* ── Override main-content para ventas ── */
.main-content {
    padding-right: 0 !important;    /* el ventas-page se encarga */
    max-width: none !important;
    overflow-x: hidden;
}

/* ── Layout ventas — catálogo fluido + carrito fijo ── */
.ventas-page {
    /* El carrito es fixed 300px, dejamos espacio a la derecha */
    padding-right: 320px;
}

.catalogo-header {
    margin-bottom: 16px;
}
.catalogo-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 8px;
}
.catalogo-header h1 { font-size: 20px; font-weight: 700; margin: 0; }
.catalogo-header p  { font-size: 13px; color: var(--text-muted); margin: 2px 0 0; }

.catalogo-inner { /* catálogo normal en el flujo */ }

/* ── Sección de categoría ── */
.cat-grupo       { margin-bottom: 20px; }
.cat-titulo {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--text-muted);
    padding: 8px 0 6px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 10px;
    display: flex; align-items: center; gap: 8px;
}
.cat-badge {
    font-size: 10px; padding: 1px 7px; border-radius: 10px;
    background: var(--primary); color: #fff;
    font-weight: 700; letter-spacing: 0; text-transform: none;
}
.peso-badge {
    font-size: 10px; padding: 1px 7px; border-radius: 10px;
    background: #e8f5ee; color: var(--success);
    font-weight: 700; letter-spacing: 0; text-transform: none;
}

/* ── Sección agrupadora ── */
.grupo-label {
    font-size: 12px; font-weight: 700; color: #fff;
    background: var(--primary); display: inline-flex;
    align-items: center; gap: 6px;
    padding: 4px 14px; border-radius: 20px;
    margin-bottom: 10px; margin-top: 4px;
}
.grupo-label.peso { background: var(--success); }

/* ── Fila de producto ── */
.prod-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    background: var(--card-bg);
    border: 1.5px solid var(--border);
    border-radius: 10px;
    margin-bottom: 7px;
    transition: border-color .15s, box-shadow .15s;
}
.prod-row > div:first-child { flex: 1; min-width: 0; overflow: hidden; }
.prod-row { overflow: hidden; }
.prod-row:hover { border-color: var(--primary); box-shadow: 0 0 0 2px #e8772212; }
.prod-row.sin-stock { opacity: .45; }

.prow-name   { font-weight: 600; font-size: 13px; line-height: 1.3; }
.prow-code   { font-size: 11px; color: var(--text-muted); }
.prow-precio { font-weight: 700; color: var(--primary); font-size: 14px; text-align: right; }
.prow-precio small { display: block; font-size: 10px; color: var(--text-muted); font-weight: 400; }
.prow-stock  { font-size: 12px; text-align: center; }
.prow-stock.low { color: var(--danger); font-weight: 700; }
.prow-stock.ok  { color: var(--success); }
.prow-unit   { font-size: 11px; color: var(--text-muted); text-align: center; }

/* Botón añadir */
.btn-add-prod {
    width: 38px; height: 38px; border-radius: 9px;
    background: var(--primary); color: #fff;
    border: none; cursor: pointer; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    transition: opacity .15s, transform .1s;
    flex-shrink: 0;
}
.btn-add-prod:hover  { opacity: .85; }
.btn-add-prod:active { transform: scale(.92); }
.btn-add-prod.peso   { background: var(--success); }
.btn-add-prod:disabled { opacity: .35; cursor: not-allowed; }

/* ── Carrito ── */
.cart-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 300px;
    height: 100vh;
    background: var(--card-bg);
    border-left: 1px solid var(--border);
    padding: 24px 18px 20px;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: -2px 0 12px rgba(0,0,0,.07);
    overflow-y: auto;
}
.cart-title {
    font-size: 15px; font-weight: 700; margin-bottom: 10px;
    display: flex; align-items: center; gap: 8px;
}
.cart-badge {
    background: var(--primary); color: #fff;
    font-size: 11px; font-weight: 700;
    padding: 1px 7px; border-radius: 10px; display: none;
}
.cart-items  { flex: 1; overflow-y: auto; }
.cart-empty  { color: var(--text-muted); text-align: center; margin-top: 40px; font-size: 13px; }

/* Item carrito normal */
.cart-item {
    padding: 9px 0; border-bottom: 1px solid var(--border); font-size: 13px;
}
.cart-item-header {
    display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;
}
.cart-item-name  { font-weight: 600; font-size: 12px; flex: 1; margin-right: 6px; }
.cart-item-sub   { font-weight: 700; color: var(--primary); white-space: nowrap; font-size: 13px; }
.cart-qty {
    display: flex; align-items: center; gap: 3px;
}
.cart-qty button {
    width: 22px; height: 22px; border-radius: 5px;
    border: 1.5px solid var(--border); background: #fff;
    cursor: pointer; font-size: 14px; color: var(--primary);
    display: flex; align-items: center; justify-content: center;
}
.cart-qty span { font-weight: 700; min-width: 26px; text-align: center; font-size: 12px; }
.cart-item-precio { font-size: 11px; color: var(--text-muted); }

/* Item carrito por peso */
.cart-item-peso .peso-inputs {
    display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-top: 5px;
}
.peso-input-group { display: flex; flex-direction: column; gap: 2px; }
.peso-input-group label { font-size: 10px; color: var(--text-muted); font-weight: 600; }
.peso-input-group input {
    padding: 4px 7px; border: 1.5px solid var(--border);
    border-radius: 5px; font-size: 12px; text-align: right; width: 100%;
}
.peso-input-group input:focus { outline: none; border-color: var(--primary); }
.btn-quitar {
    background: none; border: none; color: var(--danger);
    cursor: pointer; font-size: 11px; padding: 1px 3px;
}

/* ── Modal peso ── */
.modal-peso-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 9000;
    align-items: center; justify-content: center;
}
.modal-peso-overlay.open { display: flex; }
.modal-peso {
    background: #fff; border-radius: 14px; padding: 26px;
    width: 340px; max-width: 95vw;
    box-shadow: 0 12px 40px rgba(0,0,0,.22);
    animation: slideUp .17s ease;
}
@keyframes slideUp {
    from { transform: translateY(12px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.modal-peso h3 { font-size: 17px; font-weight: 700; margin-bottom: 4px; }
.mp-tag {
    font-size: 11px; background: #e8f5ee; color: var(--success);
    padding: 2px 9px; border-radius: 6px; display: inline-block; margin-bottom: 14px;
    font-weight: 600;
}
.mp-ref  { font-size: 11px; color: var(--text-muted); margin-left: 6px; }
.mp-row  { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.mp-group { display: flex; flex-direction: column; gap: 5px; }
.mp-group label { font-size: 12px; font-weight: 600; color: var(--text-muted); }
.mp-group input {
    padding: 10px 12px; border: 1.5px solid var(--border);
    border-radius: 8px; font-size: 15px; text-align: right; width: 100%;
}
.mp-group input:focus { outline: none; border-color: var(--primary); }
.mp-total {
    background: #f5f0eb; border-radius: 8px; padding: 11px 14px;
    margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;
}
.mp-total span  { font-size: 13px; color: var(--text-muted); }
.mp-total strong { font-size: 20px; font-weight: 800; color: var(--primary); }
.mp-btns { display: flex; gap: 10px; }
.mp-btns button { flex: 1; padding: 10px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
.mp-cancel  { background: var(--border); color: var(--text-dark); }
.mp-confirm { background: var(--success); color: #fff; }
</style>

<!-- ── Modal para Peso/Cantidad Variable ── -->
<div class="modal-peso-overlay" id="modal-peso">
    <div class="modal-peso">
        <h3 id="mp-nombre">Producto</h3>
        <span class="mp-tag" id="mp-cat-label">
            <i class="fa-solid fa-scale-balanced"></i> Precio por peso
        </span>
        <span class="mp-ref" id="mp-precio-ref"></span>
        <div class="mp-row">
            <div class="mp-group">
                <label><i class="fa-solid fa-scale-balanced" style="color:var(--success)"></i>
                    Cantidad <span id="mp-unidad-lbl">(kg)</span>
                </label>
                <input type="number" id="mp-cantidad" min="0.001" step="0.001" placeholder="0.000">
            </div>
            <div class="mp-group">
                <label><i class="fa-solid fa-tag" style="color:var(--primary)"></i>
                    Precio a cobrar ($)
                </label>
                <input type="number" id="mp-precio" min="0.01" step="0.50" placeholder="0.00">
            </div>
        </div>
        <div class="mp-total">
            <span>Total confirmado</span>
            <strong id="mp-total-val">$0.00</strong>
        </div>
        <div class="mp-btns">
            <button class="mp-cancel" onclick="cerrarModalPeso()">
                <i class="fa-solid fa-xmark"></i> Cancelar
            </button>
            <button class="mp-confirm" onclick="mpConfirmar()">
                <i class="fa-solid fa-plus"></i> Agregar
            </button>
        </div>
    </div>
</div>

<!-- ══ ESTRUCTURA PRINCIPAL ══ -->
<div class="ventas-page">

<!-- ═══ CATÁLOGO ═══ -->
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
            <input type="text" id="input-buscar"
                   placeholder="Buscar por nombre o código..."
                   oninput="filtrarProductos(this.value)">
        </div>
    </div>

    <div class="catalogo-inner" id="catalogo-inner">

        <?php if (!empty($catsPeso)): ?>
        <!-- ── Grupo: precio por peso ── -->
        <div class="grupo-label peso">
            <i class="fa-solid fa-scale-balanced"></i>
            Frutas, Verduras &amp; Semillas — precio por peso
        </div>
        <?php foreach ($catsPeso as $cat => $prods):
            $icon = $catIcons[$cat] ?? 'fa-solid fa-tag';
        ?>
        <div class="cat-grupo" data-cat="<?= htmlspecialchars($cat) ?>">
            <div class="cat-titulo">
                <i class="<?= $icon ?>"></i>
                <?= htmlspecialchars(strtoupper($cat)) ?>
                <span class="cat-badge"><?= count($prods) ?></span>
                <span class="peso-badge"><i class="fa-solid fa-scale-balanced"></i> peso</span>
            </div>
            <?php foreach ($prods as $p):
                $sinStock = (int)$p['stock'] <= 0;
                $low      = (int)$p['stock'] <= (int)$p['stock_minimo'];
                $dataJson = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="prod-row <?= $sinStock?'sin-stock':'' ?>"
                 data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
                 data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">
                <div>
                    <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
                    <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
                </div>
                <div class="prow-precio">
                    <?= formatMXN($p['precio_venta']) ?>
                    <small>ref. / <?= $p['unidad'] ?></small>
                </div>
                <div class="prow-stock <?= $low?'low':'ok' ?>" style="text-align:right;white-space:nowrap">
                    <?= $sinStock
                        ? '<i class="fa-solid fa-circle-xmark"></i> Agotado'
                        : '<i class="fa-solid fa-circle-check"></i> ' . $p['stock'] . ' ' . $p['unidad'] ?>
                </div>
                <button class="btn-add-prod peso btn-peso"
                        <?= $sinStock?'disabled':'' ?>
                        data-producto="<?= $dataJson ?>"
                        title="Ingresar cantidad y precio">
                    <i class="fa-solid fa-scale-balanced" style="font-size:13px"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($catsNormal)): ?>
        <!-- Separador -->
        <?php if (!empty($catsPeso)): ?>
        <div style="height:4px;background:var(--border);border-radius:2px;margin:8px 0 16px"></div>
        <div class="grupo-label">
            <i class="fa-solid fa-box"></i>
            Productos generales
        </div>
        <?php endif; ?>

        <?php foreach ($catsNormal as $cat => $prods):
            $icon = $catIcons[$cat] ?? 'fa-solid fa-tag';
        ?>
        <div class="cat-grupo" data-cat="<?= htmlspecialchars($cat) ?>">
            <div class="cat-titulo">
                <i class="<?= $icon ?>"></i>
                <?= htmlspecialchars(strtoupper($cat)) ?>
                <span class="cat-badge"><?= count($prods) ?></span>
            </div>
            <?php foreach ($prods as $p):
                $sinStock = (int)$p['stock'] <= 0;
                $low      = (int)$p['stock'] <= (int)$p['stock_minimo'];
                $dataJson = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="prod-row <?= $sinStock?'sin-stock':'' ?>"
                 data-nombre="<?= htmlspecialchars(strtolower($p['nombre'])) ?>"
                 data-codigo="<?= htmlspecialchars(strtolower($p['codigoprod'])) ?>">
                <div>
                    <div class="prow-name"><?= htmlspecialchars($p['nombre']) ?></div>
                    <div class="prow-code"><?= htmlspecialchars($p['codigoprod']) ?></div>
                </div>
                <div class="prow-precio"><?= formatMXN($p['precio_venta']) ?></div>
                <div class="prow-stock <?= $low?'low':'ok' ?>" style="text-align:right;white-space:nowrap">
                    <?= $sinStock
                        ? '<i class="fa-solid fa-circle-xmark"></i> Agotado'
                        : '<i class="fa-solid fa-circle-check"></i> ' . $p['stock'] . ' ' . $p['unidad'] ?>
                </div>
                <button class="btn-add-prod btn-normal"
                        <?= $sinStock?'disabled':'' ?>
                        data-producto="<?= $dataJson ?>"
                        title="Agregar al carrito">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /catalogo-inner -->
<!-- ═══ CARRITO ═══ -->
<div class="cart-panel">
    <div class="cart-title">
        <i class="fa-solid fa-cart-shopping" style="color:var(--primary)"></i>
        Carrito
        <span class="cart-badge" id="cart-count">0</span>
    </div>

    <div class="cart-items" id="cart-items">
        <p class="cart-empty">Agrega productos al carrito</p>
    </div>

    <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:8px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <span style="font-size:14px;font-weight:700">Total</span>
            <span style="font-size:20px;font-weight:800;color:var(--primary)" id="cart-total">$0.00</span>
        </div>

        <div class="metodo-pago">
            <label>Método de Pago</label>
            <div class="metodo-btns">
                <button class="metodo-btn active" data-metodo="efectivo" id="btn-metodo-efectivo">
                    <i class="fa-solid fa-money-bill-wave"></i> Efectivo
                </button>
                <button class="metodo-btn" data-metodo="transferencia" id="btn-metodo-trans">
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
                id="btn-registrar-venta" disabled>
            <i class="fa-solid fa-circle-check"></i> Registrar Venta
        </button>
    </div>
</div>

</div><!-- /ventas-page -->

<!-- ── SCRIPTS ── -->
<script>
// =====================================================
// Toda la lógica de ventas — DEBE ir antes de cerrarLayout
// para que BASE y mostrarToast ya existan cuando se usen
// =====================================================

// ── Estado global del carrito ────────────────────────
const VentasCarrito = (() => {
    const items      = {};
    let metodoPago   = 'efectivo';

    // Agregar producto normal (cantidad entera)
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

    // Agregar producto por peso (cantidad decimal, precio libre)
    function agregarPeso(p, cantidad, precio) {
        const cod = p.codigoprod;
        if (items[cod] && items[cod].tipo === 'peso') {
            items[cod].cantidad = parseFloat((items[cod].cantidad + cantidad).toFixed(3));
            items[cod].precio   = precio;
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
        mostrarToast('✓ ' + p.nombre + ' — $' + (cantidad * precio).toFixed(2));
        render();
    }

    function cambiar(cod, delta) {
        if (!items[cod] || items[cod].tipo !== 'normal') return;
        items[cod].cantidad += delta;
        if (items[cod].cantidad <= 0) delete items[cod];
        render();
    }

    function actualizarPeso(cod, campo, val) {
        if (!items[cod] || items[cod].tipo !== 'peso') return;
        const v = parseFloat(val);
        if (!isNaN(v) && v > 0) items[cod][campo] = v;
        render();
    }

    function quitar(cod) { delete items[cod]; render(); }

    function total() {
        return Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
    }

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
            el.innerHTML     = '<p class="cart-empty">Agrega productos al carrito</p>';
            tot.textContent  = '$0.00';
            btn.disabled     = true;
            return;
        }

        el.innerHTML = keys.map(cod => {
            const it  = items[cod];
            const sub = (it.precio * it.cantidad).toFixed(2);

            if (it.tipo === 'peso') {
                return `
                <div class="cart-item cart-item-peso">
                  <div class="cart-item-header">
                    <span class="cart-item-name">
                      <i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px"></i>
                      ${esc(it.nombre)}
                    </span>
                    <span class="cart-item-sub">$${sub}</span>
                  </div>
                  <div class="peso-inputs">
                    <div class="peso-input-group">
                      <label>Cantidad (${esc(it.unidad||'kg')})</label>
                      <input type="number" value="${it.cantidad}" min="0.001" step="0.001"
                             oninput="VentasCarrito.actualizarPeso('${cod}','cantidad',this.value)">
                    </div>
                    <div class="peso-input-group">
                      <label>Precio cobrado</label>
                      <input type="number" value="${it.precio.toFixed(2)}" min="0.01" step="0.5"
                             oninput="VentasCarrito.actualizarPeso('${cod}','precio',this.value)">
                    </div>
                  </div>
                  <div style="display:flex;justify-content:space-between;margin-top:4px;align-items:center">
                    <span style="font-size:11px;color:var(--text-muted)">
                      ${it.cantidad} × $${it.precio.toFixed(2)}
                    </span>
                    <button class="btn-quitar" onclick="VentasCarrito.quitar('${cod}')">
                      <i class="fa-solid fa-trash-can"></i> Quitar
                    </button>
                  </div>
                </div>`;
            } else {
                return `
                <div class="cart-item">
                  <div class="cart-item-header">
                    <span class="cart-item-name">${esc(it.nombre)}</span>
                    <span class="cart-item-sub">$${sub}</span>
                  </div>
                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="cart-item-precio">$${it.precio.toFixed(2)} c/u</span>
                    <div style="display:flex;align-items:center;gap:5px">
                      <div class="cart-qty">
                        <button onclick="VentasCarrito.cambiar('${cod}',-1)">−</button>
                        <span>${it.cantidad}</span>
                        <button onclick="VentasCarrito.cambiar('${cod}',+1)">+</button>
                      </div>
                      <button class="btn-quitar" onclick="VentasCarrito.quitar('${cod}')">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>
                </div>`;
            }
        }).join('');

        tot.textContent  = '$' + total().toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        btn.disabled     = false;
    }

    async function registrar() {
        const btn  = document.getElementById('btn-registrar-venta');
        const nota = document.getElementById('venta-nota')?.value ?? '';
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

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
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrar Venta';
        }
    }

    function setMetodo(m) {
        metodoPago = m;
        document.querySelectorAll('.metodo-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.metodo === m));
    }

    return { agregar, agregarPeso, cambiar, actualizarPeso, quitar, registrar, setMetodo };
})();

// ── Estado del modal de peso ─────────────────────────
let _prodPeso = null;

function abrirModalPeso(productoObj) {
    _prodPeso = productoObj;
    const unidad   = productoObj.unidad || 'kg';
    const precioRef = parseFloat(productoObj.precio_venta).toFixed(2);

    document.getElementById('mp-nombre').textContent    = productoObj.nombre;
    document.getElementById('mp-cat-label').textContent = productoObj.categoria + ' — precio por peso';
    document.getElementById('mp-precio-ref').textContent = '(ref: $' + precioRef + '/' + unidad + ')';
    document.getElementById('mp-unidad-lbl').textContent = '(' + unidad + ')';
    document.getElementById('mp-cantidad').value = '';
    document.getElementById('mp-precio').value   = '';
    document.getElementById('mp-total-val').textContent = '$0.00';
    document.getElementById('mp-total-val').style.color = 'var(--primary)';
    document.getElementById('modal-peso').classList.add('open');
    setTimeout(() => document.getElementById('mp-cantidad').focus(), 80);
}

function cerrarModalPeso() {
    document.getElementById('modal-peso').classList.remove('open');
    _prodPeso = null;
}

function mpActualizar() {
    const cant  = parseFloat(document.getElementById('mp-cantidad').value) || 0;
    const prec  = parseFloat(document.getElementById('mp-precio').value)   || 0;
    const total = cant * prec;
    const el    = document.getElementById('mp-total-val');
    el.textContent = '$' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    el.style.color = (cant > 0 && prec > 0) ? 'var(--success)' : 'var(--primary)';
}

function mpConfirmar() {
    if (!_prodPeso) return;
    const cant = parseFloat(document.getElementById('mp-cantidad').value);
    const prec = parseFloat(document.getElementById('mp-precio').value);
    if (!cant || cant <= 0) { mostrarToast('Ingresa una cantidad mayor a cero.', 'err'); return; }
    if (!prec || prec <= 0) { mostrarToast('Ingresa un precio mayor a cero.', 'err'); return; }
    VentasCarrito.agregarPeso(_prodPeso, cant, prec);
    cerrarModalPeso();
}

// ── Cerrar modal al click fuera ──────────────────────
document.getElementById('modal-peso').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPeso();
});

// ── Filtrar catálogo ─────────────────────────────────
function filtrarProductos(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.cat-grupo').forEach(sec => {
        let vis = 0;
        sec.querySelectorAll('.prod-row').forEach(row => {
            const show = !q || row.dataset.nombre.includes(q) || row.dataset.codigo.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) vis++;
        });
        sec.style.display = vis ? '' : 'none';
    });
}

// ── Listeners de botones ─────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Botones PESO (escala)
    document.querySelectorAll('.btn-peso').forEach(btn => {
        btn.addEventListener('click', function () {
            try {
                const p = JSON.parse(this.dataset.producto);
                abrirModalPeso(p);
            } catch(e) { mostrarToast('Error al leer producto', 'err'); }
        });
    });

    // Botones NORMAL (+)
    document.querySelectorAll('.btn-normal').forEach(btn => {
        btn.addEventListener('click', function () {
            try {
                const p = JSON.parse(this.dataset.producto);
                VentasCarrito.agregar(p);
            } catch(e) { mostrarToast('Error al leer producto', 'err'); }
        });
    });

    // Botón Registrar Venta
    document.getElementById('btn-registrar-venta')?.addEventListener('click', VentasCarrito.registrar);

    // Botones método de pago
    document.getElementById('btn-metodo-efectivo')?.addEventListener('click',
        () => VentasCarrito.setMetodo('efectivo'));
    document.getElementById('btn-metodo-trans')?.addEventListener('click',
        () => VentasCarrito.setMetodo('transferencia'));

    // Inputs del modal
    document.getElementById('mp-cantidad').addEventListener('input', mpActualizar);
    document.getElementById('mp-precio').addEventListener('input',   mpActualizar);

    // Enter en modal cantidad → pasar a precio
    document.getElementById('mp-cantidad').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') document.getElementById('mp-precio').focus();
    });
    // Enter en modal precio → confirmar
    document.getElementById('mp-precio').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') mpConfirmar();
    });
});

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php cerrarLayout(); ?>
