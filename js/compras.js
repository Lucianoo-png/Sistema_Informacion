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
                            letter-spacing:.5px;margin-bottom:5px;">PRECIO PAGADO</div>
                <div style="display:flex;gap:6px;align-items:center;">
                  ${bloqueado
                    ? `<div style="flex:1;">
                         <div style="font-size:16px;font-weight:800;color:var(--primary);">${fmt(it.subtotal)}</div>
                       </div>`
                    : `<input type="number" value="${it.subtotal.toFixed(2)}" min="0.01" step="0.5"
                              placeholder="Total pagado"
                              style="flex:1;min-width:0;padding:5px 8px;font-size:15px;font-weight:700;
                                     border:2px solid var(--primary);border-radius:7px;
                                     text-align:right;background:#fff;box-sizing:border-box;"
                              oninput="updPesoTotal('${cod}',this.value)">`
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
              <div style="margin-top:7px;padding-top:6px;border-top:1px solid var(--border);">
                <!-- Fila 1: subtotal + controles -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:5px;">
                  <span style="font-size:15px;font-weight:800;color:var(--primary);
                               white-space:nowrap;line-height:1.2;">${fmt(it.subtotal)}</span>
                  <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">
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
                <!-- Fila 2: precio unitario editable -->
                <div style="display:flex;align-items:center;gap:5px;margin-top:3px;">
                  <span style="font-size:10px;color:var(--text-muted);font-weight:600;">P/U:</span>
                  <input type="number" value="${parseFloat(it.precio_unitario).toFixed(2)}"
                         min="0.01" max="1000" step="0.01"
                         style="width:80px;padding:3px 6px;font-size:12px;font-weight:700;
                                border:1.5px solid var(--primary);border-radius:5px;
                                text-align:right;background:#fff;box-sizing:border-box;"
                         title="Precio de compra por unidad (máx. $1,000)"
                         oninput="if(+this.value>1000){this.value=1000} updCampo('${cod}','precio_unitario',this.value)"
                         onblur="blurPrecio('${cod}')">
                  <span style="font-size:10px;color:var(--text-muted);">c/u</span>
                </div>
              </div>
            </div>`;
        }
    }).join('');

    tot.textContent = fmt(total);
    btn.disabled    = false;
}

// ── Actualizar campo en producto GENERAL ────────
// No re-renderiza en cada tecla (perdería el foco del input)
// Solo actualiza el dato; el total se recalcula en render al perder foco
function updCampo(cod, campo, val) {
    let v = parseFloat(val);
    if (isNaN(v) || v < 0) return;
    if (campo === 'precio_unitario' && v > 1000) {
        v = 1000;
        // Actualizar el input visualmente
        const inp = document.querySelector(`input[oninput*="${cod}"]`);
        if (inp) inp.value = v.toFixed(2);
        mostrarToast('El precio unitario no puede superar $1,000', 'err');
    }
    items[cod][campo] = v;
    items[cod].subtotal = parseFloat(
        (items[cod].cantidad * items[cod].precio_unitario).toFixed(2)
    );
    // Actualizar solo el subtotal visible sin re-render completo
    const rows = document.querySelectorAll('.cart-item');
    rows.forEach(row => {
        const span = row.querySelector('.ci-total');
        // buscar el input con este cod para encontrar la fila correcta
        const inp = row.querySelector('input[oninput*="' + cod + '"]');
        if (inp && span) span.textContent = fmt(items[cod].subtotal);
    });
    // Actualizar total general
    const total = Object.values(items).reduce((s, i) => s + i.subtotal, 0);
    document.getElementById('cart-total').textContent = fmt(total);
}

// Al perder foco en precio: re-render limpio
function blurPrecio(cod) {
    if (items[cod] && items[cod].precio_unitario > 0) render();
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

function quitar(cod) { delete items[cod]; render(); sincronizarSelectorProveedor(); }

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
            codigoprod         : cod,
            nombre             : p.nombre,
            tipo               : 'normal',
            cantidad           : 1,
            precio_unitario    : pu,
            subtotal           : parseFloat(pu.toFixed(2)),
            proveedor_sugerido : p.proveedor_sugerido  ?? null,
            proveedor_exclusivo: p.proveedor_exclusivo ?? false,
        };
    }

    // Proveedor sugerido / exclusivo
    if (p.proveedor_sugerido && tipoActual === 'proveedor') {
        const sel = document.getElementById('sel-proveedor');
        if (sel) {
            // Si el producto es exclusivo: forzar y bloquear el selector
            if (p.proveedor_exclusivo == 1 || p.proveedor_exclusivo === true) {
                sel.value    = String(p.proveedor_sugerido);
                sel.disabled = true;
                sel.title    = 'Este producto solo puede comprarse a ' + (sel.options[sel.selectedIndex]?.text || 'este proveedor');
                actualizarBadgeProveedor('🔒 proveedor exclusivo', '#c0392b');
            } else if (!sel.value) {
                // Solo sugerido: pre-seleccionar si está vacío
                sel.value = String(p.proveedor_sugerido);
                actualizarBadgeProveedor('✓ sugerido por producto', '#1D9E75');
            }
        }
    }

    render();
    // Re-evaluar si el selector debe desbloquearse
    sincronizarSelectorProveedor();
}

function actualizarBadgeProveedor(texto, color) {
    let badge = document.getElementById('prov-sugerido-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.id = 'prov-sugerido-badge';
        badge.style.cssText = 'font-size:10px;font-weight:700;margin-left:8px;';
        const sel = document.getElementById('sel-proveedor');
        if (sel) sel.parentNode.appendChild(badge);
    }
    badge.textContent = texto;
    badge.style.color = color;
}

// Recalcula si el selector de proveedor debe estar bloqueado
// (lo es si algún producto en el carrito es exclusivo)
function sincronizarSelectorProveedor() {
    if (tipoActual !== 'proveedor') return;
    const sel = document.getElementById('sel-proveedor');
    if (!sel) return;

    const exclusivos = Object.values(items).filter(i => i.proveedor_exclusivo);
    if (exclusivos.length > 0) {
        const provId = String(exclusivos[0].proveedor_sugerido);
        sel.value    = provId;
        sel.disabled = true;
        actualizarBadgeProveedor('🔒 proveedor exclusivo', '#c0392b');
    } else {
        sel.disabled = false;
        const badge = document.getElementById('prov-sugerido-badge');
        if (badge && badge.textContent.includes('exclusivo')) badge.remove();
    }
}

// ── Modal KG ───────────────────────────────────
function abrirMP(p) {
    _prodModal = p;
    document.getElementById('mp-nombre').textContent = p.nombre;
    document.getElementById('mp-precio-total').value = '';
    document.getElementById('mp-sub').textContent    = '$0.00';
    document.getElementById('mp-overlay').classList.add('open');
    setTimeout(() => document.getElementById('mp-precio-total').focus(), 80);
}
function cerrarMP() {
    document.getElementById('mp-overlay').classList.remove('open');
    _prodModal = null;
}

// Recalcula la vista del modal: muestra total y equivalente $/kg
function calcModalKg() {
    const inp = document.getElementById('mp-precio-total');
    let   total = parseFloat(inp.value) || 0;
    if (total > 10000) { total = 10000; inp.value = '10000'; }
    document.getElementById('mp-sub').textContent = fmt(total);
    document.getElementById('mp-sub').style.color =
        total > 0 ? 'var(--primary)' : 'var(--text-muted)';
}

function confirmarMP() {
    const total = parseFloat(document.getElementById('mp-precio-total').value);
    if (!total || total <= 0) { mostrarToast('Ingresa el precio pagado', 'err'); return; }
    if (total > 10000) { mostrarToast('El precio no puede superar $10,000', 'err'); return; }

    const p   = _prodModal;
    const cod = p.codigoprod;
    // cantidad=1 como registro (el trigger no descuenta kg en compras de todas formas)
    // precio_unitario = total pagado, subtotal = total pagado
    items[cod] = {
        codigoprod:      cod,
        nombre:          p.nombre,
        tipo:            'peso',
        cantidad:        1,
        precio_unitario: parseFloat(total.toFixed(2)),
        subtotal:        parseFloat(total.toFixed(2)),
        editando:        false
    };
    mostrarToast('✓ ' + p.nombre + ' — ' + fmt(total));
    cerrarMP(); render();
}

// ── Tipo de compra ───────────────────────────────
function setTipo(t) {
    tipoActual = t;
    // En Compra Directa: siempre desbloquear el selector
    if (t === 'directa') {
        const sel = document.getElementById('sel-proveedor');
        if (sel) { sel.disabled = false; }
        const badge = document.getElementById('prov-sugerido-badge');
        if (badge) badge.remove();
    } else {
        sincronizarSelectorProveedor();
    }
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
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="pointer-events:none"></i>' +
                        '<span style="pointer-events:none">Guardando...</span>';

        const ctrl = new AbortController();
        const tId  = setTimeout(() => ctrl.abort(), 15000);

        // Validar total máximo
        if (total > 10000) {
            mostrarToast('El total de la compra no puede superar $10,000', 'err');
            return;
        }
        // Validar nota
        const notaVal = document.getElementById('inp-nota').value.trim();
        if (notaVal.length > 300) {
            mostrarToast('El comentario no puede superar 300 caracteres', 'err');
            document.getElementById('inp-nota').focus();
            return;
        }

        let exitoCompra = false;
        try {
            const r = await apiFetch(BASE + 'compras/registrar', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    tipo         : tipoActual,
                    proveedor_id : document.getElementById('sel-proveedor')?.value
                                   ? parseInt(document.getElementById('sel-proveedor').value)
                                   : null,
                    total,
                    nota   : document.getElementById('inp-nota').value.trim().substring(0, 300),
                    detalle,
                }),
                signal: ctrl.signal,
            });
            clearTimeout(tId);

            let data;
            try { data = await r.json(); }
            catch(_) { mostrarToast('Respuesta inválida del servidor', 'err'); return; }

            if (data.ok) {
                exitoCompra = true;
                mostrarToast('Compra registrada — inventario actualizado');
                Object.keys(items).forEach(k => delete items[k]);
                render();
                document.getElementById('inp-nota').value = '';
                if (document.getElementById('sel-proveedor'))
                    document.getElementById('sel-proveedor').value = '';
                setTimeout(() => {
                    try { location.reload(); } catch(_) {
                        enviando = false;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-circle-check" style="pointer-events:none"></i>' +
                                        '<span style="pointer-events:none">Registrar Compra</span>';
                    }
                }, 1400);
                return;
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
        } finally {
            if (!exitoCompra) {
                enviando      = false;
                btn.disabled  = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-check" style="pointer-events:none"></i>' +
                                '<span style="pointer-events:none">Registrar Compra</span>';
            }
        }
    });
});

// ── Contador de caracteres en nota ────────────────────────
function actualizarContadorNota(el) {
    const counter = document.getElementById('nota-counter');
    if (!counter) return;
    const n = el.value.length;
    counter.textContent = n + '/300';
    counter.style.color = n >= 280 ? 'var(--danger)' : 'var(--text-muted)';
}
