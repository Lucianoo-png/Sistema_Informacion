<?php
require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Producto.php';

$paginaActual  = 'inventario';
$proveedoresLista = (new Proveedor())->obtenerTodos();
$modelo        = new Producto();
$soloStockBajo = ($_GET['filtro'] ?? '') === 'stock_bajo';
$buscar        = trim($_GET['buscar'] ?? '');

$productos = $soloStockBajo
    ? $modelo->stockBajo()
    : ($buscar ? $modelo->buscar($buscar) : $modelo->obtenerTodos());

abrirLayout('Inventario', 'inventario');
?>
<div class="pag-wrap-lg">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <div class="page-header" style="margin-bottom:0">
        <h1>Inventario</h1>
        <p>Catálogo de productos</p>
    </div>
    <button class="btn btn-primary" onclick="Inventario.abrirModal('crear')">
        <i class="fa-solid fa-plus"></i> Nuevo Producto
    </button>
</div>

<div class="top-bar">
    <div class="searchbar" style="flex:1">
        <span class="searchbar-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" placeholder="Buscar por nombre o código..."
               value="<?= htmlspecialchars($buscar) ?>"
               oninput="filtrarTabla(this.value)">
    </div>
    <a href="?filtro=stock_bajo"
       class="btn btn-outline <?= $soloStockBajo?'active':'' ?>">
        <i class="fa-solid fa-triangle-exclamation"></i> Stock Bajo
    </a>
    <?php if ($soloStockBajo || $buscar): ?>
    <a href="inventario" class="btn btn-outline">
        <i class="fa-solid fa-xmark"></i> Limpiar
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrapper">
        <table id="tabla-productos">
            <thead>
                <tr>
                    <th>Código</th><th>Nombre</th><th>Categoría</th>
                    <th>P. Compra</th><th>P. Venta</th><th>Stock</th><th>Unidad</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                <?php
                    $esKg  = strtolower($p['unidad']) === 'kg';   // solo kg se bloquea
                    $low   = !$esKg && ((float)$p['stock'] <= (float)$p['stock_minimo']);
                ?>
                <tr>
                    <td><code style="background:#f5f0eb;padding:2px 6px;border-radius:4px;font-size:12px">
                        <?= htmlspecialchars($p['codigoprod']) ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($p['nombre']) ?></td>
                    <td style="color:#888"><?= $p['categoria'] ?></td>
                    <td><?= formatMXN($p['precio_compra']) ?></td>
                    <td class="price"><?= formatMXN($p['precio_venta']) ?></td>
                    <td class="<?= $low?'stock-low':'stock-ok' ?>">
                        <?php if ($esKg): ?>
                            <span style="color:var(--text-muted);font-style:italic;font-size:12px"
                                  title="El stock de productos kg se gestiona por compras">—</span>
                        <?php else: ?>
                            <?php if ($low): ?>
                                <i class="fa-solid fa-circle-exclamation" style="color:var(--danger)"></i>
                            <?php endif; ?>
                            <?= (int)$p['stock'] ?>
                        <?php endif; ?>
                    </td>
                    <td style="color:#888;font-size:13px"><?= $p['unidad'] ?></td>
                    <td style="white-space:nowrap">
                        <button class="btn-icon btn-editar-prod"
                            data-prod="<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>"
                            title="Editar">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon del"
                            onclick="Inventario.eliminar('<?= addslashes($p['codigoprod']) ?>', '<?= addslashes($p['nombre']) ?>')"
                            title="Eliminar">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                <tr><td colspan="8" class="empty-state">Sin productos que mostrar</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Producto -->
<div class="modal-overlay" id="modal-producto">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="modal-prod-titulo">
                <i class="fa-solid fa-tags" style="color:var(--primary)"></i> Nuevo Producto
            </span>
            <button class="modal-close" onclick="Inventario.cerrarModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="form-producto">
            <input type="hidden" name="codigoprod" id="prod-codigoprod">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label><i class="fa-solid fa-barcode" style="color:var(--primary)"></i> Código
                        <span id="lbl-auto" style="font-size:10px;background:#e8f5ee;color:var(--success);
                              padding:1px 6px;border-radius:4px;margin-left:4px">auto</span>
                    </label>
                    <div style="display:flex;gap:6px;align-items:center">
                        <input type="text" class="form-control" id="prod-codigo-visible"
                               maxlength="15" placeholder="Generando..."
                               oninput="document.getElementById('prod-codigoprod').value=this.value.toUpperCase();this.value=this.value.toUpperCase()"
                               style="flex:1">
                        <button type="button" onclick="generarCodigo()" id="btn-gen-cod"
                                title="Generar automáticamente"
                                style="padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;
                                       background:#fff;cursor:pointer;white-space:nowrap;font-size:12px;color:var(--primary)">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-ruler" style="color:var(--primary)"></i> Unidad</label>
                    <select class="form-control" name="unidad" id="prod-unidad" onchange="actualizarHintStock(this.value)">
                        <option>pieza</option><option>kg</option><option>litro</option>
                        <option>bolsa</option><option>caja</option><option>paquete</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-box" style="color:var(--primary)"></i> Nombre del producto</label>
                <input type="text" class="form-control" name="nombre" id="prod-nombre" required>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-layer-group" style="color:var(--primary)"></i> Categoría</label>
                <input type="text" class="form-control" name="categoria" id="prod-categoria">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:5px">
                    <i class="fa-solid fa-truck" style="color:var(--primary)"></i>
                    Proveedor sugerido
                    <span style="font-size:10px;color:var(--text-muted);font-weight:400">(opcional)</span>
                </label>
                <select class="form-control" name="proveedor_sugerido" id="prod-proveedor"
                        onchange="document.getElementById('chk-exclusivo-wrap').style.display=this.value?'flex':'none';
                                  if(!this.value) document.getElementById('prod-exclusivo').checked=false;">
                    <option value="">Sin sugerencia</option>
                </select>
                <!-- Exclusivo: solo visible cuando hay proveedor seleccionado -->
                <div id="chk-exclusivo-wrap" style="display:none;align-items:center;gap:8px;margin-top:6px;
                     padding:6px 10px;background:#fff8ee;border:1px solid #f0c070;border-radius:6px;">
                    <input type="checkbox" name="proveedor_exclusivo" id="prod-exclusivo" value="1"
                           style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer">
                    <label for="prod-exclusivo" style="font-size:12px;cursor:pointer;margin:0;font-weight:500">
                        Solo puede comprarse a este proveedor
                        <span style="font-size:10px;color:var(--text-muted);font-weight:400;display:block">
                            El selector de proveedor se bloqueará en compras
                        </span>
                    </label>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label><i class="fa-solid fa-arrow-down" style="color:var(--primary)"></i> P. Compra</label>
                    <input type="number" class="form-control" name="precio_compra" id="prod-p-compra" step="0.01" min="0" max="1000"
                           oninput="if(+this.value>1000)this.value=1000;if(+this.value<0)this.value=0">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-arrow-up" style="color:var(--primary)"></i> P. Venta</label>
                    <input type="number" class="form-control" name="precio_venta" id="prod-p-venta" step="0.01" min="0" max="500"
                           oninput="if(+this.value>500)this.value=500;if(+this.value<0)this.value=0">
                </div>
                <div class="form-group" style="display:flex;flex-direction:column;">
                    <label style="display:flex;align-items:center;gap:4px;margin-bottom:6px;height:22px;overflow:hidden;">
                        <i class="fa-solid fa-cubes" style="color:var(--primary);flex-shrink:0"></i>
                        <span style="font-size:13px;">Stock actual</span>
                        <span id="lbl-stock-hint" style="font-size:10px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">(unidades — máx. 20)</span>
                    </label>
                    <input type="number" class="form-control" name="stock" id="prod-stock" min="0" max="20" step="1" placeholder="0"
                           oninput="if(+this.value>20)this.value=20;if(+this.value<0)this.value=0">
                </div>
                <div class="form-group" style="display:flex;flex-direction:column;">
                    <label style="display:flex;align-items:center;gap:4px;margin-bottom:6px;height:22px;">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#d97706;flex-shrink:0"></i>
                        <span style="font-size:13px;">Stock mínimo</span>
                    </label>
                    <input type="number" class="form-control" name="stock_minimo" id="prod-stock-min" min="0" max="20" step="1" placeholder="3"
                           oninput="if(+this.value>20)this.value=20;if(+this.value<0)this.value=0">
                </div>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Inventario.cerrarModal()">
                <i class="fa-solid fa-xmark"></i> Cancelar
            </button>
            <button class="btn btn-primary" onclick="Inventario.guardar()">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
window.PROVEEDORES_INIT = <?= json_encode(array_map(function($p) {
    return ['id' => (int)$p['id'], 'nombre' => $p['nombre']];
}, $proveedoresLista)) ?>;
</script>

</div>
<?php cerrarLayout(BASE_URL . 'js/inventario.js'); ?>
