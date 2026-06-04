<?php
// =====================================================
// vista/admin/compras.php — con carrito de productos
// Registra mercancía y actualiza inventario automáticamente
// CAMBIO: modal pesables ahora pide "cantidad kg" + "precio TOTAL pagado"
//         en lugar de "precio por kg" — más natural para compra directa
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

abrirLayout('Compras', 'compras', BASE_URL . 'estilos/compras.css');
?>
<script>document.body.classList.add('page-compras');</script>

<!-- ── Modal KG compra ── -->
<div class="mp-overlay" id="mp-overlay">
  <div class="mp-box">
    <h3 id="mp-nombre">Producto</h3>
    <span class="mp-tag"><i class="fa-solid fa-leaf"></i> Compra por peso — precio pagado</span>

    <!-- Solo precio total pagado -->
    <div class="mp-group">
      <label>¿Cuánto pagaste? ($)</label>
      <input type="number" id="mp-precio-total" min="0.01" max="10000" step="0.50"
             placeholder="0.00"
             oninput="if(+this.value>10000)this.value=10000; calcModalKg()">
    </div>

    <div class="mp-sub-box" style="overflow:hidden">
      <span>Total</span>
      <strong id="mp-sub" style="font-size:clamp(13px,3vw,20px);word-break:break-all;overflow-wrap:anywhere">$0.00</strong>
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
  <a href="<?= BASE_URL ?>compras/historial" class="btn btn-outline btn-sm">
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
      <select class="form-control" id="sel-proveedor" onchange="document.getElementById('prov-sugerido-badge')?.remove()">
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
    <i class="fa-solid fa-leaf"></i> Pesables — precio por kg
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
      <i class="fa-solid fa-leaf" style="color:var(--success)"></i>
      <?= number_format((float)$p['stock'], 2) ?> kg
    </div>
    <button class="btn-add peso" data-prod="<?= $dj ?>" title="Registrar precio pagado">
      <i class="fa-solid fa-bag-shopping"></i>
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
    <div class="cart-footer-inner">
      <div class="cart-total-row">
        <span class="cart-total-label">Total compra</span>
        <span class="cart-total-val" id="cart-total" style="font-size:clamp(14px,3vw,22px);word-break:break-all;overflow-wrap:anywhere;max-width:100%">$0.00</span>
      </div>
      <textarea id="inp-nota" class="nota-input" maxlength="300"
                style="min-height:44px;max-height:68px;margin-bottom:0"
                oninput="actualizarContadorNota(this)"
                placeholder="Nota / descripción (opcional)..."></textarea>
      <div id="nota-counter" style="font-size:10px;color:var(--text-muted);text-align:right;margin-top:2px">0/300</div>
    </div>

    <!-- Botón edge-to-edge: ocupa todo el ancho sin padding lateral -->
    <button id="btn-registrar" disabled
            style="width:100%; height:58px; border:none; border-radius:0;
                   background:var(--primary); color:#fff; font-size:15px;
                   font-weight:700; cursor:pointer; display:flex;
                   align-items:center; justify-content:center; gap:8px;
                   transition:background .15s,opacity .15s;
                   user-select:none; -webkit-user-select:none; pointer-events:auto;">
      <i class="fa-solid fa-circle-check"
         style="font-size:17px; pointer-events:none;"></i>
      <span style="pointer-events:none;">Registrar Compra</span>
    </button>
  </div>
</div>

<?php cerrarLayout(BASE_URL . 'js/compras.js'); ?>
