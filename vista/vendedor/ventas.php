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

abrirLayout('Nueva Venta', 'ventas', BASE_URL . 'estilos/ventas.css');
?>
<script>document.body.classList.add('page-ventas');</script>
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
                <label style="font-size:13px;font-weight:600;color:var(--text-muted)">
                    Precio a cobrar ($)
                </label>
                <input type="number" id="mp-prec" min="0.01" step="0.5" placeholder="0.00"
                       style="font-size:24px;font-weight:700;text-align:center;padding:14px 12px;
                              border:2px solid var(--primary);border-radius:10px;width:100%;
                              margin-top:6px;box-sizing:border-box">
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
            <a href="<?= BASE_URL ?>ventas/historial" class="btn btn-outline btn-sm">
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
        <div class="cart-footer-inner">
            <div class="cart-total-row">
                <span class="cart-total-label" style="white-space:nowrap;flex-shrink:0">Total</span>
                <span class="cart-total-val" id="cart-total" style="overflow-wrap:anywhere;word-break:break-all;min-width:0;max-width:70%;text-align:right">$0.00</span>
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

            <!-- Panel efectivo: monto recibido y cambio -->
            <div id="panel-efectivo" style="margin-bottom:8px">
                <div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:4px">MONTO RECIBIDO <span style="color:var(--danger)">*</span></div>
                <input type="number" id="monto-recibido" min="0" max="500" step="1"
                       placeholder="$ 0.00"
                       style="width:100%;box-sizing:border-box;padding:8px 10px;
                              border:1.5px solid var(--border);border-radius:8px;
                              font-size:15px;font-weight:700;color:var(--text-dark);
                              background:#fff;margin-bottom:6px"
                       oninput="calcCambio()">
                <div id="cambio-row"
                     style="display:none;justify-content:space-between;align-items:center;
                            background:#f0faf4;border-radius:8px;padding:7px 10px">
                    <span style="font-size:12px;font-weight:600;color:#38a169">CAMBIO</span>
                    <span id="cambio-val" style="font-size:16px;font-weight:800;color:#38a169">$0.00</span>
                </div>
                <div id="cambio-insuf"
                     style="display:none;background:#fff5f5;border-radius:8px;
                            padding:7px 10px;font-size:12px;font-weight:700;color:#e53e3e">
                    ⚠ Monto insuficiente
                </div>
            </div>


            <!-- Comentario -->
            <textarea id="venta-nota" class="nota-input"
                      style="min-height:46px;max-height:70px;margin-bottom:0"
                      placeholder="Comentario..."></textarea>
        </div>

        <!-- Botón edge-to-edge -->
        <button id="btn-registrar" disabled
                style="width:100%; height:58px; border:none; border-radius:0;
                       background:var(--primary); color:#fff; font-size:15px;
                       font-weight:700; cursor:pointer; display:flex;
                       align-items:center; justify-content:center; gap:8px;
                       transition:background .15s,opacity .15s;
                       user-select:none; -webkit-user-select:none;
                       pointer-events:auto;">
            <i class="fa-solid fa-circle-check"
               style="font-size:17px; pointer-events:none;"></i>
            <span style="pointer-events:none;">Registrar Venta</span>
        </button>
    </div>
</div>

<?php cerrarLayout(BASE_URL . 'js/ventas.js'); ?>
