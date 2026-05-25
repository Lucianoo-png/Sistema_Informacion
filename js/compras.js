// compras.js — JavaScript específico de la página compras

// ═══════════════════════════════════════════════
//  ESTADO
// ═══════════════════════════════════════════════
const items = {};          // { codigoprod: {...} }
let tipoActual  = 'proveedor';
let _prodModal  = null;

function fmt(n) {
    return 'MX$' + parseFloat(n).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── RENDER ─────────────────────────────────────
function render() {
    const el  = document.getElementById('cart-items');
    const tot = document.getElementById('cart-total');
    const btn = document.getElementById('btn-registrar');
    const cnt = document.getElementById('cart-count');

    const keys = Object.keys(items);
    cnt.style.display = keys.length ? 'inline-block' : 'none';
    cnt.textContent   = keys.length;

    if (!keys.length) {
        el.innerHTML    = '<p class="cart-empty">Agrega productos de la lista</p>';
        tot.textContent = 'MX$0.00';
        btn.disabled    = true;
        return;
    }

    const total = keys.reduce((s, k) => s + items[k].subtotal, 0);

    el.innerHTML = keys.map(cod => {
        const it = items[cod];

        if (it.tipo === 'peso') {
            // Pesable: igual que ventas — precio grande con editar/listo
            const bloqueado = !it.editando;
            return `
            <div class="cart-item">
              <div class="ci-top">
                <span class="ci-name">
                  <i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px;margin-right:3px"></i>${esc(it.nombre)}
                </span>
                <span class="ci-total">${fmt(it.subtotal)}</span>
              </div>
              <div style="background:#f5f0eb;border-radius:8px;padding:8px 10px;margin-top:6px;">
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;
                            letter-spacing:.5px;margin-bottom:5px;">CANTIDAD · TOTAL PAGADO</div>
                <div style="display:flex;gap:6px;align-items:center;">
                  ${bloqueado
                    ? `<div style="flex:1;">
                         <div style="font-size:16px;font-weight:800;color:var(--primary);">${fmt(it.subtotal)}</div>
                         <div style="font-size:11px;color:var(--text-muted);">${it.cantidad.toFixed(3)} kg &middot; ${fmt(it.precio_unitario)}/kg</div>
                       </div>`
                    : `<div style="flex:1;display:flex;gap:6px;">
                         <input type="number" value="${it.cantidad.toFixed(3)}" min="0.001" step="0.001"
                                placeholder="kg"
                                style="flex:1;min-width:0;padding:5px 8px;font-size:13px;font-weight:600;
                                       border:2px solid var(--primary);border-radius:7px;
                                       text-align:right;background:#fff;box-sizing:border-box;"
                                oninput="updPesoCantidad('${cod}',this.value)">
                         <input type="number" value="${it.subtotal.toFixed(2)}" min="0.01" step="0.5"
                                placeholder="Total"
                                style="flex:1;min-width:0;padding:5px 8px;font-size:13px;font-weight:600;
                                       border:2px solid var(--primary);border-radius:7px;
                                       text-align:right;background:#fff;box-sizing:border-box;"
                                oninput="updPesoTotal('${cod}',this.value)">
                       </div>`
                  }
                  <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;align-items:flex-end;">
                    <button onclick="toggleEditarKgC('${cod}')"
                            style="background:var(--primary);border:none;border-radius:6px;
                                   cursor:pointer;font-size:11px;color:#fff;padding:4px 10px;
                                   white-space:nowrap;user-select:none;">
                      <i class="fa-solid fa-${bloqueado ? 'pen' : 'check'}"></i>
                      ${bloqueado ? 'Editar' : 'Listo'}
                    </button>
                    <button onclick="quitar('${cod}')"
                            style="background:none;border:none;color:var(--danger);
                                   cursor:pointer;font-size:11px;padding:0;user-select:none;white-space:nowrap;">
                      <i class="fa-solid fa-trash-can"></i> Quitar
                    </button>
                  </div>
                </div>
              </div>
            </div>`;
        } else {
            // General: igual que ventas — precio + controles +/−
            return `
            <div class="cart-item">
              <div class="ci-top" style="margin-bottom:0;">
                <span class="ci-name">${esc(it.nombre)}</span>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;
                          gap:8px;margin-top:7px;padding-top:6px;border-top:1px solid var(--border);">
                <div style="display:flex;flex-direction:column;flex-shrink:0;gap:1px;">
                  <span style="font-size:15px;font-weight:800;color:var(--primary);
                               white-space:nowrap;line-height:1.2;">${fmt(it.subtotal)}</span>
                  <span style="font-size:11px;color:var(--text-muted);white-space:nowrap;font-weight:500;display:flex;align-items:center;gap:3px;">
                    <input type="number" value="${it.precio_unitario.toFixed(2)}" min="0" step="0.01"
                           style="width:66px;padding:2px 5px;font-size:11px;font-weight:600;
                                  border:1.5px solid var(--border);border-radius:5px;
                                  text-align:right;background:#fff;box-sizing:border-box;"
                           oninput="updCampo('${cod}','precio_unitario',this.value)"> c/u
                  </span>
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

// ── Actualizar campo en producto GENERAL ────────
function updCampo(cod, campo, val) {
    const v = parseFloat(val);
    if (isNaN(v) || v <= 0) return;
    items[cod][campo] = v;
    items[cod].subtotal = parseFloat(
        (items[cod].cantidad * items[cod].precio_unitario).toFixed(2)
    );
    render();
}

// ── Actualizar cantidad en producto PESABLE ─────
function updPesoCantidad(cod, val) {
    const kg = parseFloat(val);
    if (isNaN(kg) || kg <= 0) return;
    items[cod].cantidad = kg;
    // Recalcular precio/kg desde el total que ya estaba guardado
    items[cod].precio_unitario = parseFloat((items[cod].subtotal / kg).toFixed(4));
    render();
}

// ── Actualizar precio total en producto PESABLE ─
function updPesoTotal(cod, val) {
    const total = parseFloat(val);
    if (isNaN(total) || total <= 0) return;
    items[cod].subtotal        = parseFloat(total.toFixed(2));
    items[cod].precio_unitario = parseFloat((total / items[cod].cantidad).toFixed(4));
    render();
}

function quitar(cod) { delete items[cod]; render(); }

// ── Toggle editar pesable en carrito compras ─────
function toggleEditarKgC(cod) {
    if (!items[cod]) return;
    items[cod].editando = !items[cod].editando;
    render();
}

// ── Cambiar cantidad de producto general ─────────
function cambiarCant(cod, d) {
    if (!items[cod]) return;
    items[cod].cantidad = Math.max(1, items[cod].cantidad + d);
    items[cod].subtotal = parseFloat(
        (items[cod].cantidad * items[cod].precio_unitario).toFixed(2)
    );
    render();
}

// ── Agregar producto general ─────────────────────
function agregarNormal(p) {
    const cod = p.codigoprod;
    if (items[cod]) {
        items[cod].cantidad++;
        items[cod].subtotal = parseFloat(
            (items[cod].cantidad * items[cod].precio_unitario).toFixed(2)
        );
    } else {
        const pu = parseFloat(p.precio_compra) || 0;
        items[cod] = {
            codigoprod: cod, nombre: p.nombre, tipo: 'normal',
            cantidad: 1, precio_unitario: pu,
            subtotal: parseFloat(pu.toFixed(2))
        };
    }
    render();
}

// ── Modal KG ───────────────────────────────────
function abrirMP(p) {
    _prodModal = p;
    document.getElementById('mp-nombre').textContent = p.nombre;
    document.getElementById('mp-kg').value          = '';
    document.getElementById('mp-precio-total').value = '';
    document.getElementById('mp-sub').textContent    = '$0.00';
    document.getElementById('mp-hint-pxkg').style.display = 'none';
    document.getElementById('mp-overlay').classList.add('open');
    setTimeout(() => document.getElementById('mp-kg').focus(), 80);
}
function cerrarMP() {
    document.getElementById('mp-overlay').classList.remove('open');
    _prodModal = null;
}

// Recalcula la vista del modal: muestra total y equivalente $/kg
function calcModalKg() {
    const kg    = parseFloat(document.getElementById('mp-kg').value)           || 0;
    const total = parseFloat(document.getElementById('mp-precio-total').value) || 0;

    document.getElementById('mp-sub').textContent = fmt(total);
    document.getElementById('mp-sub').style.color =
        total > 0 ? 'var(--primary)' : 'var(--text-muted)';

    // Muestra equivalente $/kg si ambos campos tienen valor
    const hint = document.getElementById('mp-hint-pxkg');
    if (kg > 0 && total > 0) {
        document.getElementById('mp-pxkg-val').textContent = fmt(total / kg);
        hint.style.display = 'block';
    } else {
        hint.style.display = 'none';
    }
}

function confirmarMP() {
    const kg    = parseFloat(document.getElementById('mp-kg').value);
    const total = parseFloat(document.getElementById('mp-precio-total').value);

    if (!kg    || kg    <= 0) { mostrarToast('Ingresa la cantidad en kg',      'err'); return; }
    if (!total || total <= 0) { mostrarToast('Ingresa el precio total pagado', 'err'); return; }

    const p   = _prodModal;
    const cod = p.codigoprod;
    items[cod] = {
        codigoprod:     cod,
        nombre:         p.nombre,
        tipo:           'peso',
        cantidad:       kg,
        precio_unitario: parseFloat((total / kg).toFixed(4)),  // $/kg calculado
        subtotal:       parseFloat(total.toFixed(2)),          // total pagado
        editando:       false
    };
    mostrarToast('✓ ' + p.nombre + ' — ' + fmt(total));
    cerrarMP(); render();
}

// ── Tipo de compra ───────────────────────────────
function setTipo(t) {
    tipoActual = t;
    document.getElementById('btn-tipo-prov').classList.toggle('active', t === 'proveedor');
    document.getElementById('btn-tipo-dir').classList.toggle('active',  t === 'directa');
    document.getElementById('row-proveedor').style.display =
        t === 'proveedor' ? '' : 'none';
}

// ── Filtrar catálogo ─────────────────────────────
function filtrar(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.prod-row').forEach(row => {
        const ok = !q
            || row.dataset.nombre.includes(q)
            || row.dataset.codigo.includes(q);
        row.style.display = ok ? '' : 'none';
    });
}

// ── Init ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Botones catálogo
    document.querySelectorAll('.btn-add.normal').forEach(btn => {
        btn.addEventListener('click', function() {
            try { agregarNormal(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto', 'err'); }
        });
    });
    document.querySelectorAll('.btn-add.peso').forEach(btn => {
        btn.addEventListener('click', function() {
            try { abrirMP(JSON.parse(this.dataset.prod)); }
            catch(e) { mostrarToast('Error al leer producto', 'err'); }
        });
    });

    // Modal: teclado — Tab de cantidad → precio total → Enter confirma
    document.getElementById('mp-kg').addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('mp-precio-total').focus();
    });
    document.getElementById('mp-precio-total').addEventListener('keydown', e => {
        if (e.key === 'Enter') confirmarMP();
    });
    document.getElementById('mp-overlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarMP();
    });

    // Registrar compra (mismo guard de doble-click que en ventas)
    let enviando = false;
    document.getElementById('btn-registrar').addEventListener('click', async () => {
        if (enviando) return;
        enviando = true;

        const btn = document.getElementById('btn-registrar');

        if (tipoActual === 'proveedor') {
            const prov = document.getElementById('sel-proveedor')?.value;
            if (!prov) {
                mostrarToast('Selecciona un proveedor.', 'err');
                enviando = false;
                return;
            }
        }

        const keys = Object.keys(items);
        if (!keys.length) {
            mostrarToast('Agrega al menos un producto.', 'err');
            enviando = false;
            return;
        }

        const detalle = keys.map(cod => ({
            codigoprod      : cod,
            cantidad        : items[cod].cantidad,
            precio_unitario : items[cod].precio_unitario,
            subtotal        : items[cod].subtotal,
        }));
        const total = parseFloat(
            detalle.reduce((s, d) => s + d.subtotal, 0).toFixed(2)
        );

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        const ctrl = new AbortController();
        const tId  = setTimeout(() => ctrl.abort(), 15000);

        try {
            const r = await fetch(BASE + 'compras/registrar', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    tipo         : tipoActual,
                    proveedor_id : document.getElementById('sel-proveedor')?.value
                                   ? parseInt(document.getElementById('sel-proveedor').value)
                                   : null,
                    total,
                    nota   : document.getElementById('inp-nota').value.trim(),
                    detalle,
                }),
                signal: ctrl.signal,
            });
            clearTimeout(tId);
            const data = await r.json();

            if (data.ok) {
                mostrarToast('Compra registrada — inventario actualizado');
                Object.keys(items).forEach(k => delete items[k]);
                render();
                document.getElementById('inp-nota').value = '';
                if (document.getElementById('sel-proveedor'))
                    document.getElementById('sel-proveedor').value = '';
                setTimeout(() => location.reload(), 1400);
                return;  // mantener btn deshabilitado hasta reload
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

        // Solo en error: re-habilitar
        enviando      = false;
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Registrar Compra';
    });
});
