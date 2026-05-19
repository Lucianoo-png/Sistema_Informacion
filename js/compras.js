// compras.js — JavaScript específico de la página compras

// ═══════════════════════════════════════════════
//  ESTADO
// ═══════════════════════════════════════════════
const items = {};          // { codigoprod: {...} }
let tipoActual  = 'proveedor';
let _prodModal  = null;

function fmt(n) {
    return '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
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
        tot.textContent = '$0.00';
        btn.disabled    = true;
        return;
    }

    const total = keys.reduce((s, k) => s + items[k].subtotal, 0);

    el.innerHTML = keys.map(cod => {
        const it = items[cod];

        if (it.tipo === 'peso') {
            return `
            <div class="cart-item">
              <div class="ci-top">
                <span class="ci-name">
                  <i class="fa-solid fa-leaf" style="color:var(--success);font-size:10px;margin-right:3px"></i>${esc(it.nombre)}
                </span>
                <span class="ci-total">${fmt(it.subtotal)}</span>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                <div>
                  <label style="font-size:10px;color:var(--text-muted);font-weight:700;letter-spacing:.4px;display:block;margin-bottom:3px;">CANT. (kg)</label>
                  <input type="number"
                         value="${it.cantidad.toFixed(3)}"
                         min="0.001" step="0.001"
                         style="width:100%;padding:7px 8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);border-radius:7px;background:#fff;text-align:right;box-sizing:border-box;"
                         oninput="updPesoCantidad('${cod}', this.value)">
                </div>
                <div>
                  <label style="font-size:10px;color:var(--text-muted);font-weight:700;letter-spacing:.4px;display:block;margin-bottom:3px;">TOTAL PAGADO ($)</label>
                  <input type="number"
                         value="${it.subtotal.toFixed(2)}"
                         min="0.01" step="0.50"
                         style="width:100%;padding:7px 8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);border-radius:7px;background:#fff;text-align:right;box-sizing:border-box;"
                         oninput="updPesoTotal('${cod}', this.value)">
                </div>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;
                          margin-top:6px;padding-top:5px;border-top:1px solid var(--border);">
                <span style="font-size:11px;color:var(--text-muted);">${fmt(it.precio_unitario)}&nbsp;/&nbsp;kg</span>
                <button onclick="quitar('${cod}')"
                        style="background:none;border:none;color:var(--danger);cursor:pointer;
                               font-size:11px;padding:0;user-select:none;white-space:nowrap;">
                  <i class="fa-solid fa-trash-can"></i>&nbsp;Quitar
                </button>
              </div>
            </div>`;
        } else {
            return `
            <div class="cart-item">
              <div class="ci-top">
                <span class="ci-name">${esc(it.nombre)}</span>
                <span class="ci-total">${fmt(it.subtotal)}</span>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                <div>
                  <label style="font-size:10px;color:var(--text-muted);font-weight:700;letter-spacing:.4px;display:block;margin-bottom:3px;">CANTIDAD</label>
                  <input type="number"
                         value="${it.cantidad}"
                         min="1" step="1"
                         style="width:100%;padding:7px 8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);border-radius:7px;background:#fff;text-align:right;box-sizing:border-box;"
                         oninput="updCampo('${cod}','cantidad',this.value)">
                </div>
                <div>
                  <label style="font-size:10px;color:var(--text-muted);font-weight:700;letter-spacing:.4px;display:block;margin-bottom:3px;">PRECIO / UNIT.</label>
                  <input type="number"
                         value="${it.precio_unitario.toFixed(2)}"
                         min="0" step="0.01"
                         style="width:100%;padding:7px 8px;font-size:13px;font-weight:600;border:1.5px solid var(--border);border-radius:7px;background:#fff;text-align:right;box-sizing:border-box;"
                         oninput="updCampo('${cod}','precio_unitario',this.value)">
                </div>
              </div>
              <div style="display:flex;justify-content:flex-end;margin-top:6px;
                          padding-top:5px;border-top:1px solid var(--border);">
                <button onclick="quitar('${cod}')"
                        style="background:none;border:none;color:var(--danger);cursor:pointer;
                               font-size:11px;padding:0;user-select:none;white-space:nowrap;">
                  <i class="fa-solid fa-trash-can"></i>&nbsp;Quitar
                </button>
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
        subtotal:       parseFloat(total.toFixed(2))           // total pagado
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
