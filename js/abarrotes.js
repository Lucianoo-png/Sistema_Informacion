// =====================================================
// js/abarrotes.js — Lógica frontend Abarrotes Angy
// CORRECCIÓN: const BASE eliminada — ya la declara cerrarLayout() en layout.php
// =====================================================

// BASE viene declarada por el inline-script de cerrarLayout() → no redeclarar aquí

// ── TOAST ──────────────────────────────────────────
function mostrarToast(msg, tipo = 'ok') {
  let t = document.getElementById('toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'toast';
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.className = `toast ${tipo} show`;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

// ── FORMATO MONEDA ──────────────────────────────────
function formatMXN(n) {
  return '$' + parseFloat(n).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// ══════════════════════════════════════════════════
// MÓDULO: VENTAS (carrito)
// ══════════════════════════════════════════════════
const Carrito = (() => {
  // CORRECCIÓN: clave del mapa es codigoprod (VARCHAR), no producto.id (INT viejo)
  let items = {};   // { codigoprod: {nombre, precio, cantidad, stock, codigoprod} }
  let metodoPago = 'efectivo';

  function agregar(producto) {
    const cod = producto.codigoprod;
    if (items[cod]) {
      if (items[cod].cantidad < parseFloat(producto.stock)) {
        items[cod].cantidad++;
      } else {
        mostrarToast('No hay más stock disponible', 'err'); return;
      }
    } else {
      items[cod] = {
        codigoprod: cod,
        nombre:     producto.nombre,
        precio:     parseFloat(producto.precio_venta),
        cantidad:   1,
        // CORRECCIÓN: parseFloat en lugar de parseInt — stock es NUMERIC(10,3)
        stock:      parseFloat(producto.stock),
      };
    }
    renderCarrito();
  }

  function cambiarCantidad(cod, delta) {
    if (!items[cod]) return;
    items[cod].cantidad += delta;
    if (items[cod].cantidad <= 0) delete items[cod];
    renderCarrito();
  }

  function total() {
    return Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
  }

  function renderCarrito() {
    const container = document.getElementById('cart-items');
    const totalEl   = document.getElementById('cart-total');
    // CORRECCIÓN: el botón ahora se llama 'btn-registrar' en el HTML nuevo
    const btnReg    = document.getElementById('btn-registrar');
    if (!container) return;

    if (Object.keys(items).length === 0) {
      container.innerHTML = '<p class="cart-empty">Agrega productos al carrito</p>';
      if (totalEl) totalEl.textContent = formatMXN(0);
      if (btnReg)  btnReg.disabled = true;
      return;
    }

    container.innerHTML = Object.entries(items).map(([cod, it]) => `
      <div class="cart-item">
        <div>
          <div class="cart-item-name">${it.nombre}</div>
          <div style="font-size:12px;color:#888">${formatMXN(it.precio)} c/u</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="cart-item-qty">
            <button onclick="Carrito.cambiar('${cod}',-1)">−</button>
            <span>${it.cantidad}</span>
            <button onclick="Carrito.cambiar('${cod}',+1)">+</button>
          </div>
          <span style="font-weight:700;min-width:60px;text-align:right">${formatMXN(it.precio * it.cantidad)}</span>
        </div>
      </div>`).join('');

    if (totalEl) totalEl.textContent = formatMXN(total());
    if (btnReg)  btnReg.disabled = false;
  }

  async function registrarVenta() {
    // CORRECCIÓN: botón renombrado a 'btn-registrar'
    const btn  = document.getElementById('btn-registrar');
    const nota = document.getElementById('venta-nota')?.value ?? '';
    if (btn) { btn.disabled = true; btn.textContent = 'Procesando...'; }

    // CORRECCIÓN: envía codigoprod (VARCHAR) en lugar del antiguo producto_id (INT)
    const detalle = Object.values(items).map(i => ({
      codigoprod:     i.codigoprod,
      cantidad:       i.cantidad,
      precio_unitario:i.precio,
      subtotal:       parseFloat((i.precio * i.cantidad).toFixed(2)),
    }));

    try {
      const res  = await fetch(BASE + 'ventas/registrar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ detalle, metodo_pago: metodoPago, nota }),
      });
      const data = await res.json();
      if (data.ok) {
        mostrarToast('✅ Venta registrada correctamente');
        items = {};
        renderCarrito();
        setTimeout(() => location.reload(), 1200);
      } else {
        mostrarToast(data.mensaje || 'Error al registrar', 'err');
      }
    } catch(e) {
      mostrarToast('Error de conexión', 'err');
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Registrar Venta'; }
    }
  }

  function setMetodo(m) {
    metodoPago = m;
    document.querySelectorAll('.metodo-btn').forEach(b => b.classList.toggle('active', b.dataset.metodo === m));
  }

  return { agregar, cambiar: cambiarCantidad, registrar: registrarVenta, setMetodo };
})();

// ══════════════════════════════════════════════════
// MÓDULO: INVENTARIO
// ══════════════════════════════════════════════════
const Inventario = (() => {
  function abrirModal(modo, datos = {}) {
    const modal = document.getElementById('modal-producto');
    if (!modal) return;
    document.getElementById('modal-prod-titulo').textContent = modo === 'crear' ? 'Nuevo Producto' : 'Editar Producto';
    document.getElementById('prod-id').value           = datos.id       ?? '';
    document.getElementById('prod-codigo').value       = datos.codigo   ?? '';
    document.getElementById('prod-nombre').value       = datos.nombre   ?? '';
    document.getElementById('prod-categoria').value    = datos.categoria?? '';
    document.getElementById('prod-p-compra').value     = datos.precio_compra ?? '';
    document.getElementById('prod-p-venta').value      = datos.precio_venta  ?? '';
    document.getElementById('prod-stock').value        = datos.stock    ?? '';
    document.getElementById('prod-stock-min').value    = datos.stock_minimo ?? 5;
    document.getElementById('prod-unidad').value       = datos.unidad   ?? 'pieza';
    document.getElementById('prod-codigo').readOnly    = modo === 'editar';
    modal.classList.add('open');
  }

  function cerrarModal() {
    document.getElementById('modal-producto')?.classList.remove('open');
  }

  async function guardar() {
    const id = document.getElementById('prod-id').value;
    const accion = id ? 'actualizar' : 'crear';
    const form = document.getElementById('form-producto');
    const data = new FormData(form);

    try {
      const res  = await fetch(BASE + 'inventario/' + accion, { method: 'POST', body: data });
      const resp = await res.json();
      if (resp.ok) {
        mostrarToast(resp.mensaje);
        cerrarModal();
        setTimeout(() => location.reload(), 900);
      } else {
        mostrarToast(resp.mensaje, 'err');
      }
    } catch(e) {
      mostrarToast('Error de conexión', 'err');
    }
  }

  async function eliminar(id, nombre) {
    if (!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)) return;
    try {
      const res  = await fetch(BASE + `inventario/eliminar/${id}`);
      const resp = await res.json();
      mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
      if (resp.ok) setTimeout(() => location.reload(), 900);
    } catch(e) {
      mostrarToast('Error de conexión', 'err');
    }
  }

  return { abrirModal, cerrarModal, guardar, eliminar };
})();

// ══════════════════════════════════════════════════
// MÓDULO: PROVEEDORES
// ══════════════════════════════════════════════════
const Proveedores = (() => {
  function abrirModal(modo, datos = {}) {
    const modal = document.getElementById('modal-proveedor');
    if (!modal) return;
    document.getElementById('modal-prov-titulo').textContent = modo === 'crear' ? 'Nuevo Proveedor' : 'Editar Proveedor';
    document.getElementById('prov-id').value         = datos.id          ?? '';
    document.getElementById('prov-nombre').value     = datos.nombre      ?? '';
    document.getElementById('prov-telefono').value   = datos.telefono    ?? '';
    // CORRECCIÓN: columna en BD es 'diavisita' (sin guión bajo), no 'dias_visita'
    document.getElementById('prov-dias').value       = datos.diavisita ?? datos.DiaVisita ?? '';
    modal.classList.add('open');
  }
  function cerrarModal() { document.getElementById('modal-proveedor')?.classList.remove('open'); }

  async function guardar() {
    const id     = document.getElementById('prov-id').value;
    const accion = id ? 'actualizar' : 'crear';
    const form   = document.getElementById('form-proveedor');
    const data   = new FormData(form);
    try {
      const res  = await fetch(BASE + 'proveedores/' + accion, { method: 'POST', body: data });
      const resp = await res.json();
      mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
      if (resp.ok) { cerrarModal(); setTimeout(() => location.reload(), 900); }
    } catch(e) { mostrarToast('Error de conexión', 'err'); }
  }

  async function eliminar(id, nombre) {
    if (!confirm(`¿Eliminar proveedor "${nombre}"?`)) return;
    try {
      const res  = await fetch(BASE + `proveedores/eliminar/${id}`);
      const resp = await res.json();
      mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
      if (resp.ok) setTimeout(() => location.reload(), 900);
    } catch(e) { mostrarToast('Error de conexión', 'err'); }
  }

  return { abrirModal, cerrarModal, guardar, eliminar };
})();

// ══════════════════════════════════════════════════
// MÓDULO: COMPRAS
// ══════════════════════════════════════════════════
const Compras = (() => {
  let tipo = 'proveedor';
  let lineas = []; // array de {producto_id, cantidad, precio_unitario}

  function setTipo(t) {
    tipo = t;
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.toggle('active', b.dataset.tipo === t));
    const provRow = document.getElementById('row-proveedor');
    if (provRow) provRow.style.display = t === 'proveedor' ? '' : 'none';
  }

  function calcularTotal() {
    return lineas.reduce((s, l) => s + (parseFloat(l.precio||0) * parseInt(l.cantidad||0)), 0);
  }

  async function registrar() {
    // CORRECCIÓN: codigoprod (VARCHAR) en lugar del antiguo producto_id (INT)
    const detalle = lineas.filter(l => l.codigoprod && l.cantidad > 0 && l.precio > 0).map(l => ({
      codigoprod:     l.codigoprod,
      cantidad:       parseFloat(l.cantidad),
      precio_unitario:parseFloat(l.precio),
    }));

    if (detalle.length === 0) { mostrarToast('Agrega al menos un producto.', 'err'); return; }

    const body = {
      tipo,
      proveedor_id: document.getElementById('sel-proveedor')?.value ?? 0,
      nota: document.getElementById('compra-nota')?.value ?? '',
      detalle,
    };

    try {
      const res  = await fetch(BASE + 'compras/registrar', {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body)
      });
      const resp = await res.json();
      mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
      if (resp.ok) setTimeout(() => location.reload(), 900);
    } catch(e) { mostrarToast('Error de conexión', 'err'); }
  }

  return { setTipo, registrar, lineas };
})();

// ══════════════════════════════════════════════════
// MÓDULO: TRANSFERENCIAS
// ══════════════════════════════════════════════════
const Transferencias = (() => {
  async function registrar() {
    const monto     = parseFloat(document.getElementById('tf-monto')?.value ?? 0);
    const concepto  = document.getElementById('tf-concepto')?.value ?? '';
    const referencia= document.getElementById('tf-referencia')?.value ?? '';

    if (monto <= 0) { mostrarToast('El monto debe ser mayor a cero.', 'err'); return; }

    try {
      const res  = await fetch(BASE + 'transferencias/registrar', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ monto, concepto, referencia }),
      });
      const resp = await res.json();
      mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
      if (resp.ok) setTimeout(() => location.reload(), 900);
    } catch(e) { mostrarToast('Error de conexión', 'err'); }
  }

  return { registrar };
})();

// ── Búsqueda de productos en ventas / inventario ──
function filtrarProductos(input, grid) {
  const q = input.toLowerCase();
  document.querySelectorAll(`#${grid} .product-card`).forEach(card => {
    const txt = card.textContent.toLowerCase();
    card.style.display = txt.includes(q) ? '' : 'none';
  });
}
