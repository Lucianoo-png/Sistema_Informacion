
// ── CSRF token (leído del meta tag, incluido en todos los fetch) ──
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

// Wrapper fetch que incluye automáticamente el CSRF header
async function apiFetch(url, opciones = {}) {
    // No forzar Content-Type cuando es FormData — el navegador lo pone con el boundary correcto
    const esFormData = opciones.body instanceof FormData;
    const headers = {
        ...(esFormData ? {} : { 'Content-Type': 'application/json' }),
        'X-CSRF-Token': CSRF_TOKEN,
        ...(opciones.headers || {})
    };
    const resp = await fetch(url, { ...opciones, headers });
    // Si el servidor rechaza por CSRF (403), recargar para obtener nuevo token
    if (resp.status === 403) {
        mostrarToast('Sesión expirada. Recargando...', 'err');
        setTimeout(() => location.reload(), 1500);
        // Devolver respuesta simulada para que el caller no falle
        return new Response(JSON.stringify({ok:false, mensaje:'Sesión expirada. La página se recargará.'}),
            {status: 200, headers: {'Content-Type': 'application/json'}});
    }
    return resp;
}

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
