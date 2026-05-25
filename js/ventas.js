// ventas.js — JavaScript específico de la página ventas

// ═══════════════════════════════════════════════
//  ESTADO DEL CARRITO
// ═══════════════════════════════════════════════
const items       = {};        // { codigoprod → {...} }
let   metodoPago  = 'efectivo';
let   _prodModal  = null;

// ── Helpers ──────────────────────────────────────
function fmt(n) {
    return 'MX$' + parseFloat(n).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Toggle edición KG en carrito ventas ──────────
function toggleEditarKg(cod) {
    if (!items[cod]) return;
    items[cod].editando = !items[cod].editando;
    render();
}

// ── RENDER CARRITO ────────────────────────────────
function render() {
    const el    = document.getElementById('cart-items');
    const tot   = document.getElementById('cart-total');
    const btn   = document.getElementById('btn-registrar');
    const cnt   = document.getElementById('cart-count');
    if (!el) return;

    const keys = Object.keys(items);
    cnt.style.display = keys.length ? 'inline-block' : 'none';
    cnt.textContent   = keys.length;

    if (!keys.length) {
        el.innerHTML     = '<p class="cart-empty">Agrega productos al carrito</p>';
        tot.textContent  = '$0.00';
        btn.disabled     = true;
        return;
    }

    const total = keys.reduce((s, k) => s + items[k].precio * items[k].cantidad, 0);

    el.innerHTML = keys.map(cod => {
        const it  = items[cod];
        const sub = it.precio * it.cantidad;

        if (it.tipo === 'peso') {
            const bloqueado = !it.editando;
            return `
            <div class="cart-item">
              <div class="ci-top">
                <span class="ci-name">
                  <i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px;margin-right:3px"></i>${esc(it.nombre)}
                </span>
                <span class="ci-total">${fmt(sub)}</span>
              </div>
              <div style="background:#f5f0eb;border-radius:8px;padding:8px 10px;margin-top:6px;">
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;
                            letter-spacing:.5px;margin-bottom:5px;">PRECIO COBRADO</div>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                  ${bloqueado
                    ? `<span style="font-size:18px;font-weight:800;color:var(--primary);flex:1;">${fmt(sub)}</span>`
                    : `<input type="number" value="${it.precio.toFixed(2)}" min="0.01" step="0.5"
                              style="flex:1;min-width:0;padding:5px 8px;font-size:16px;font-weight:700;
                                     border:2px solid var(--primary);border-radius:7px;
                                     text-align:right;background:#fff;box-sizing:border-box;"
                              oninput="updateCampo('${cod}','precio',this.value)">`
                  }
                  <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;align-items:flex-end;">
                    <button onclick="toggleEditarKg('${cod}')"
                            style="background:var(--primary);border:none;border-radius:6px;
                                   cursor:pointer;font-size:11px;color:#fff;padding:4px 10px;
                                   white-space:nowrap;user-select:none;">
                      <i class="fa-solid fa-${bloqueado ? 'pen' : 'check'}"></i>
                      ${bloqueado ? 'Editar' : 'Listo'}
                    </button>
                    <button onclick="quitar('${cod}')"
                            style="background:none;border:none;color:var(--danger);
                                   cursor:pointer;font-size:11px;padding:0;
                                   user-select:none;white-space:nowrap;">
                      <i class="fa-solid fa-trash-can"></i> Quitar
                    </button>
                  </div>
                </div>
              </div>
            </div>`;
        } else {
            return `
            <div class="cart-item">
              <div class="ci-top" style="margin-bottom:0;">
                <span class="ci-name">${esc(it.nombre)}</span>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;
                          gap:8px;margin-top:7px;padding-top:6px;border-top:1px solid var(--border);">
                <div style="display:flex;flex-direction:column;flex-shrink:0;gap:1px;">
                  <span style="font-size:15px;font-weight:800;color:var(--primary);
                               white-space:nowrap;line-height:1.2;">${fmt(sub)}</span>
                  <span style="font-size:11px;color:var(--text-muted);
                               white-space:nowrap;font-weight:500;">${fmt(it.precio)}&nbsp;c/u</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                  <div class="qty-ctrl">
                    <button onclick="cambiarCant('${cod}',-1)" style="user-select:none;">−</button>
                    <span>${it.cantidad}</span>
                    <button onclick="cambiarCant('${cod}',+1)" style="user-select:none;">+</button>
                  </div>
                  <button onclick="quitar('${cod}')"
                          style="background:none;border:none;color:var(--danger);
                                 cursor:pointer;font-size:16px;padding:0 2px;user-select:none;">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
              </div>
            </div>`;
        }
    }).join('');

    tot.textContent = fmt(total);
    btn.disabled    = false;
}

// ── Agregar producto normal ───────────────────────
function agregarNormal(p) {
    const cod = p.codigoprod;
    if (items[cod]) {
        if (items[cod].cantidad < parseFloat(p.stock)) items[cod].cantidad++;
        else { mostrarToast('Sin más stock disponible', 'err'); return; }
    } else {
        // CORRECCIÓN: parseFloat — stock es NUMERIC(10,3), parseInt truncaba decimales
        items[cod] = { codigoprod:cod, nombre:p.nombre, tipo:'normal',
                       precio:parseFloat(p.precio_venta), cantidad:1,
                       stock:parseFloat(p.stock) };
    }
    render();
}

// ── Controles carrito ─────────────────────────────
function cambiarCant(cod, d) {
    if (!items[cod]) return;
    items[cod].cantidad += d;
    if (items[cod].cantidad <= 0) delete items[cod];
    render();
}
function updateCampo(cod, campo, val) {
    if (!items[cod]) return;
    const v = parseFloat(val);
    if (!isNaN(v) && v > 0) items[cod][campo] = v;
    render();
}
function quitar(cod) { delete items[cod]; render(); }

// ── Modal peso ────────────────────────────────────
function abrirMP(p) {
    _prodModal = p;
    document.getElementById('mp-nombre').textContent = p.nombre;
    document.getElementById('mp-ref').textContent =
        '(ref: ' + fmt(p.precio_venta) + '/kg)';
    document.getElementById('mp-prec').value  = '';
    document.getElementById('mp-total').textContent = '$0.00';
    document.getElementById('mp-overlay').classList.add('open');
    setTimeout(() => document.getElementById('mp-prec').focus(), 80);
}
function cerrarMP() {
    document.getElementById('mp-overlay').classList.remove('open');
    _prodModal = null;
}
function calcMP() {
    const p = parseFloat(document.getElementById('mp-prec').value) || 0;
    document.getElementById('mp-total').textContent = fmt(p);
    document.getElementById('mp-total').style.color = p > 0 ? 'var(--success)' : 'var(--primary)';
}
function confirmarMP() {
    const prec = parseFloat(document.getElementById('mp-prec').value);
    if (!prec || prec <= 0) { mostrarToast('Ingresa el precio a cobrar','err'); return; }

    const p   = _prodModal;
    const cod = p.codigoprod;
    if (items[cod] && items[cod].tipo === 'peso') {
        // Ya existe en carrito: agregar como cobro adicional
        items[cod].cantidad += 1;
        items[cod].precio    = parseFloat(((items[cod].precio * (items[cod].cantidad - 1) + prec) / items[cod].cantidad).toFixed(2));
    } else {
        // cantidad=1, precio=total cobrado
        items[cod] = { codigoprod:cod, nombre:p.nombre, tipo:'peso',
                       precio:prec, cantidad:1,
                       stock:parseFloat(p.stock), unidad:'kg', editando:false };
    }
    mostrarToast('✓ ' + p.nombre + ' — ' + fmt(prec));
    cerrarMP(); render();
}

// ── Método de pago ────────────────────────────────
document.getElementById('btn-efectivo').addEventListener('click', () => {
    metodoPago = 'efectivo';
    document.getElementById('btn-efectivo').classList.add('active');
    document.getElementById('btn-transf').classList.remove('active');
});
document.getElementById('btn-transf').addEventListener('click', () => {
    metodoPago = 'transferencia';
    document.getElementById('btn-transf').classList.add('active');
    document.getElementById('btn-efectivo').classList.remove('active');
});

// ── Registrar venta ───────────────────────────────
// CORRECCIÓN 1: flag para evitar doble-envío por doble-click
let enviando = false;

document.getElementById('btn-registrar').addEventListener('click', async () => {
    if (enviando) return;              // Guard: ignora clicks mientras ya se procesa
    enviando = true;

    const btn  = document.getElementById('btn-registrar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const detalle = Object.values(items).map(i => ({
        codigoprod      : i.codigoprod,
        cantidad        : i.cantidad,
        precio_unitario : i.precio,
        subtotal        : parseFloat((i.precio * i.cantidad).toFixed(2)),
    }));

    // CORRECCIÓN 2: AbortController — cancela y desbloquea el botón tras 15 s
    // sin respuesta (causa del "botón se queda bloqueado después de un rato")
    const ctrl = new AbortController();
    const tId  = setTimeout(() => ctrl.abort(), 15000);

    try {
        const r = await fetch(BASE + 'ventas/registrar', {
            method  : 'POST',
            headers : {'Content-Type': 'application/json'},
            body    : JSON.stringify({
                detalle,
                metodo_pago : metodoPago,
                nota        : document.getElementById('venta-nota').value
            }),
            signal  : ctrl.signal   // ← timeout activo
        });
        clearTimeout(tId);
        const data = await r.json();

        if (data.ok) {
            mostrarToast('Venta registrada correctamente');
            Object.keys(items).forEach(k => delete items[k]);
            render();
            // CORRECCIÓN 3: NO re-habilitar el botón en éxito — la página recarga
            // en 1400 ms y lo resetea; evita que el usuario registre una venta vacía
            // durante la ventana de espera del reload.
            setTimeout(() => location.reload(), 1400);
            return;   // ← salir aquí; el código de re-habilitación NO debe correr
        }

        mostrarToast(data.mensaje || 'Error al registrar', 'err');

    } catch(e) {
        clearTimeout(tId);
        mostrarToast(
            e.name === 'AbortError'
                ? 'Sin respuesta del servidor. Intenta de nuevo.'
                : 'Error de conexión',
            'err'
        );
    }

    // Solo llega aquí si hubo ERROR (el return de éxito lo evita)
    enviando      = false;
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrar Venta';
});

// ── Filtrar catálogo ──────────────────────────────
function filtrar(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.prod-row').forEach(row => {
        const ok = !q || row.dataset.nombre.includes(q) || row.dataset.codigo.includes(q);
        row.style.display = ok ? '' : 'none';
    });
}

// ── Listeners en botones (DOMContentLoaded) ───────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-add.normal').forEach(btn => {
        btn.addEventListener('click', function() {
            try { agregarNormal(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto','err'); }
        });
    });
    document.querySelectorAll('.btn-add.peso').forEach(btn => {
        btn.addEventListener('click', function() {
            try { abrirMP(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto','err'); }
        });
    });

    // Modal: inputs en tiempo real
    document.getElementById('mp-prec').addEventListener('input', calcMP);
    document.getElementById('mp-prec').addEventListener('keydown', e => { if (e.key==='Enter') confirmarMP(); });

    // Cerrar modal al click fuera
    document.getElementById('mp-overlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarMP();
    });
});
