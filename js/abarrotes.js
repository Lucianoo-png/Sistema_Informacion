// =====================================================
// js/abarrotes.js — Lógica frontend Abarrotes Angy
// =====================================================

const BASE = document.querySelector('meta[name="base-url"]')?.content ?? './';

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
  return '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ══════════════════════════════════════════════════
// MÓDULO: VENTAS (carrito)
// ══════════════════════════════════════════════════
const Carrito = (() => {
  let items = {};   // { producto_id: {nombre, precio, cantidad} }
  let metodoPago = 'efectivo';

  function agregar(producto) {
    if (items[producto.id]) {
      if (items[producto.id].cantidad < producto.stock) {
        items[producto.id].cantidad++;
      } else {
        mostrarToast('No hay más stock disponible', 'err'); return;
      }
    } else {
      items[producto.id] = {
        nombre: producto.nombre,
        precio: parseFloat(producto.precio_venta),
        cantidad: 1,
        stock: parseInt(producto.stock),
        producto_id: producto.id,
      };
    }
    renderCarrito();
  }

  function cambiarCantidad(id, delta) {
    if (!items[id]) return;
    items[id].cantidad += delta;
    if (items[id].cantidad <= 0) delete items[id];
    renderCarrito();
  }

  function total() {
    return Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
  }

  function renderCarrito() {
    const container = document.getElementById('cart-items');
    const totalEl   = document.getElementById('cart-total');
    const btnReg    = document.getElementById('btn-registrar-venta');
    if (!container) return;

    if (Object.keys(items).length === 0) {
      container.innerHTML = '<p class="cart-empty">Agrega productos al carrito</p>';
      totalEl.textContent  = formatMXN(0);
      btnReg.disabled = true;
      return;
    }

    container.innerHTML = Object.entries(items).map(([id, it]) => `
      <div class="cart-item">
        <div>
          <div class="cart-item-name">${it.nombre}</div>
          <div style="font-size:12px;color:#888">${formatMXN(it.precio)} c/u</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="cart-item-qty">
            <button onclick="Carrito.cambiar(${id},-1)">−</button>
            <span>${it.cantidad}</span>
            <button onclick="Carrito.cambiar(${id},+1)">+</button>
          </div>
          <span style="font-weight:700;min-width:60px;text-align:right">${formatMXN(it.precio * it.cantidad)}</span>
        </div>
      </div>`).join('');

    totalEl.textContent = formatMXN(total());
    btnReg.disabled = false;
  }

  async function registrarVenta() {
    const btn  = document.getElementById('btn-registrar-venta');
    const nota = document.getElementById('venta-nota')?.value ?? '';
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    const detalle = Object.values(items).map(i => ({
      producto_id:    i.producto_id,
      cantidad:       i.cantidad,
      precio_unitario:i.precio,
      subtotal:       i.precio * i.cantidad,
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
      btn.disabled = false;
      btn.textContent = 'Registrar Venta';
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
    document.getElementById('prov-dias').value       = datos.dias_visita ?? '';
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
    const detalle = lineas.filter(l => l.producto_id && l.cantidad > 0 && l.precio > 0).map(l => ({
      producto_id:    parseInt(l.producto_id),
      cantidad:       parseInt(l.cantidad),
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
