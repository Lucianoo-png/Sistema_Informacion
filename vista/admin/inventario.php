<?php
require_once BASE_PATH . 'helpers/layout.php';
require_once BASE_PATH . 'modelo/Producto.php';

$paginaActual  = 'inventario';
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
                <?php $low = (int)$p['stock'] <= (int)$p['stock_minimo']; ?>
                <tr>
                    <td><code style="background:#f5f0eb;padding:2px 6px;border-radius:4px;font-size:12px">
                        <?= htmlspecialchars($p['codigoprod']) ?></code></td>
                    <td style="font-weight:600"><?= htmlspecialchars($p['nombre']) ?></td>
                    <td style="color:#888"><?= $p['categoria'] ?></td>
                    <td><?= formatMXN($p['precio_compra']) ?></td>
                    <td class="price"><?= formatMXN($p['precio_venta']) ?></td>
                    <td class="<?= $low?'stock-low':'stock-ok' ?>">
                        <?php if ($low): ?>
                            <i class="fa-solid fa-circle-exclamation" style="color:var(--danger)"></i>
                        <?php endif; ?>
                        <?= $p['stock'] ?>
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
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label><i class="fa-solid fa-arrow-down" style="color:var(--primary)"></i> P. Compra</label>
                    <input type="number" class="form-control" name="precio_compra" id="prod-p-compra" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-arrow-up" style="color:var(--primary)"></i> P. Venta</label>
                    <input type="number" class="form-control" name="precio_venta" id="prod-p-venta" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-cubes" style="color:var(--primary)"></i> Stock actual <span id="lbl-stock-hint" style="font-size:10px;color:var(--text-muted)">(unidades)</span></label>
                    <input type="number" class="form-control" name="stock" id="prod-stock" min="0" step="0.001" placeholder="0">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-triangle-exclamation" style="color:#d97706"></i> Stock mínimo</label>
                    <input type="number" class="form-control" name="stock_minimo" id="prod-stock-min" min="0" placeholder="3 (opcional)">
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


</div>
<?php cerrarLayout(BASE_URL . 'js/inventario.js'); ?>
