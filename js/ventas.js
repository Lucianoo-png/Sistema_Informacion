// ventas.js

const items      = {};
let   metodoPago = 'efectivo';
let   _prodModal = null;

function fmt(n) {
    return 'MX$' + parseFloat(n).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── RENDER CARRITO ─────────────────────────────────────────
function render() {
    const el  = document.getElementById('cart-items');
    const tot = document.getElementById('cart-total');
    const btn = document.getElementById('btn-registrar');
    const cnt = document.getElementById('cart-count');
    if (!el) return;

    const keys = Object.keys(items);
    cnt.style.display = keys.length ? 'inline-block' : 'none';
    cnt.textContent   = keys.length;

    if (!keys.length) {
        el.innerHTML    = '<p class="cart-empty">Agrega productos al carrito</p>';
        tot.textContent = '$0.00';
        btn.disabled    = true;
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
                <div style="font-size:10px;color:var(--text-muted);font-weight:700;letter-spacing:.5px;margin-bottom:5px;">PRECIO COBRADO</div>
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
                      <i class="fa-solid fa-${bloqueado?'pen':'check'}"></i> ${bloqueado?'Editar':'Listo'}
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
            // Mostrar alerta si cantidad está al límite del stock
            const enLimite = it.stock > 0 && it.cantidad >= it.stock;
            return `
            <div class="cart-item">
              <div class="ci-top" style="margin-bottom:0;">
                <span class="ci-name">${esc(it.nombre)}</span>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;
                          gap:8px;margin-top:7px;padding-top:6px;border-top:1px solid var(--border);">
                <div style="display:flex;flex-direction:column;flex-shrink:0;gap:1px;">
                  <span style="font-size:15px;font-weight:800;color:var(--primary);white-space:nowrap;line-height:1.2;">${fmt(sub)}</span>
                  <span style="font-size:11px;color:var(--text-muted);white-space:nowrap;font-weight:500;">${fmt(it.precio)}&nbsp;c/u</span>
                  ${enLimite ? `<span style="font-size:10px;color:var(--danger);font-weight:700;">máx. stock</span>` : ''}
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                  <div class="qty-ctrl">
                    <button onclick="cambiarCant('${cod}',-1)" style="user-select:none;">−</button>
                    <span>${it.cantidad}</span>
                    <button onclick="cambiarCant('${cod}',+1)" ${enLimite?'disabled style="opacity:.4;cursor:not-allowed;"':''} style="user-select:none;">+</button>
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
    if (total > 500) {
        btn.disabled = true; tot.style.color = 'var(--danger)';
        let w=document.getElementById('total-warn');
        if(!w){w=document.createElement('div');w.id='total-warn';
        w.style.cssText='font-size:11px;color:var(--danger);font-weight:700;margin-top:2px;';
        tot.parentNode.appendChild(w);}
        w.textContent='⚠ Total supera $500 — no aceptado';
    } else {
        btn.disabled = false; tot.style.color = '';
        const w=document.getElementById('total-warn'); if(w) w.textContent='';
    }

    // Bloquear botones + del catálogo si total >= $500
    const limiteAlcanzado = total >= 500;
    document.querySelectorAll('.btn-add.normal').forEach(addBtn => {
        if (limiteAlcanzado) {
            addBtn.disabled = true;
            addBtn.style.opacity = '0.35';
            addBtn.style.cursor  = 'not-allowed';
            addBtn.title = 'Total máximo $500';
        } else {
            const sinStock = addBtn.closest('.prod-row')?.classList.contains('sin-stock') ||
                             addBtn.closest('.prod-row')?.querySelector('.prow-stock')?.textContent?.includes('Sin stock');
            if (!sinStock) {
                addBtn.disabled = false;
                addBtn.style.opacity = '';
                addBtn.style.cursor  = '';
                addBtn.title = '';
            }
        }
    });
}

function toggleEditarKg(cod) {
    if (!items[cod]) return;
    items[cod].editando = !items[cod].editando;
    render();
}

// ── AGREGAR PRODUCTO NORMAL ────────────────────────────────
function agregarNormal(p) {
    const cod   = p.codigoprod;
    const stock = parseFloat(p.stock) || 0;
    const precio = parseFloat(p.precio_venta) || 0;

    // Calcular total actual + lo que se va a agregar
    const totalActual = Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const cantActual  = items[cod] ? items[cod].cantidad : 0;
    const totalNuevo  = totalActual + precio; // sumar 1 unidad más

    if (totalNuevo > 500) {
        mostrarToast('Agregar este producto supera el límite de $500', 'err');
        return;
    }

    if (items[cod]) {
        if (items[cod].cantidad >= stock) {
            mostrarToast('Sin más stock disponible (' + stock + ' ' + (p.unidad||'') + ')', 'err');
            return;
        }
        items[cod].cantidad++;
    } else {
        if (stock <= 0) {
            mostrarToast('Sin stock disponible', 'err');
            return;
        }
        items[cod] = {
            codigoprod : cod,
            nombre     : p.nombre,
            tipo       : 'normal',
            precio     : precio,
            cantidad   : 1,
            stock      : stock,
            unidad     : p.unidad || ''
        };
    }
    render();
}

// ── CONTROLES CARRITO ──────────────────────────────────────
function cambiarCant(cod, d) {
    if (!items[cod]) return;
    if (d > 0 && items[cod].tipo !== 'peso') {
        // Bloquear si ya se alcanzó el stock máximo
        if (items[cod].cantidad >= items[cod].stock) {
            mostrarToast('Sin más stock disponible', 'err');
            return;
        }
        // Bloquear si agregar uno más supera $500
        const totalActual = Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
        if (totalActual + items[cod].precio > 500) {
            mostrarToast('Agregar más supera el límite de $500', 'err');
            return;
        }
    }
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

// ── MODAL PESO ────────────────────────────────────────────
function abrirMP(p) {
    _prodModal = p;
    document.getElementById('mp-nombre').textContent = p.nombre;
    document.getElementById('mp-ref').textContent = '(ref: ' + fmt(p.precio_venta) + '/kg)';
    document.getElementById('mp-prec').value = '';
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

    // Verificar que el precio a cobrar no supere el límite de $500
    const totalActual = Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const itemActual  = items[_prodModal.codigoprod];
    const totalSinEste = itemActual ? totalActual - itemActual.precio * itemActual.cantidad : totalActual;
    if (totalSinEste + prec > 500) {
        mostrarToast('Este cobro supera el límite de $500 por venta', 'err');
        return;
    }

    const p = _prodModal, cod = p.codigoprod;
    if (items[cod] && items[cod].tipo === 'peso') {
        items[cod].cantidad += 1;
        items[cod].precio = parseFloat(((items[cod].precio * (items[cod].cantidad - 1) + prec) / items[cod].cantidad).toFixed(2));
    } else {
        items[cod] = { codigoprod:cod, nombre:p.nombre, tipo:'peso',
                       precio:prec, cantidad:1, stock:0, unidad:'kg', editando:false };
    }
    mostrarToast('✓ ' + p.nombre + ' — ' + fmt(prec));
    cerrarMP(); render();
}

// ── MÉTODO DE PAGO ────────────────────────────────────────
function setMetodo(m) {
    metodoPago = m;
    document.getElementById('btn-efectivo').classList.toggle('active', m === 'efectivo');
    document.getElementById('btn-transf').classList.toggle('active', m === 'transferencia');
    document.getElementById('panel-efectivo').style.display = m === 'efectivo' ? 'block' : 'none';
    // Limpiar cambio al cambiar método
    document.getElementById('monto-recibido').value = '';
    document.getElementById('cambio-row').style.display   = 'none';
    document.getElementById('cambio-insuf').style.display = 'none';
    // Si el botón quedó trabado (enviando=true) al cambiar método, liberar
    if (enviando) resetBtn();
}
document.getElementById('btn-efectivo').addEventListener('click', () => setMetodo('efectivo'));
document.getElementById('btn-transf').addEventListener('click',   () => setMetodo('transferencia'));

// Calcular cambio en tiempo real
// Parsear monto del carrito correctamente (quita MX$, comas, espacios)
function parsearTotal() {
    const txt = document.getElementById('cart-total').textContent || '0';
    return parseFloat(txt.replace(/[^0-9]/g, '').replace(/(\d+)(\d{2})$/, '$1.$2')) || 0;
}

function calcCambio() {
    const total    = Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
    const recibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
    const rowOk    = document.getElementById('cambio-row');
    const rowErr   = document.getElementById('cambio-insuf');
    const valEl    = document.getElementById('cambio-val');

    if (recibido <= 0) {
        rowOk.style.display  = 'none';
        rowErr.style.display = 'none';
        return;
    }
    // Límite de 500 pesos en el monto recibido
    if (recibido > 500) {
        document.getElementById('monto-recibido').value = 500;
        calcCambio(); // recalcular con el valor corregido
        return;
    }
    if (recibido >= total) {
        valEl.textContent    = fmt(recibido - total);
        rowOk.style.display  = 'flex';
        rowErr.style.display = 'none';
    } else {
        rowOk.style.display  = 'none';
        rowErr.style.display = 'block';
    }
}

// ── REGISTRAR VENTA ───────────────────────────────────────
let enviando = false;

function resetBtn() {
    enviando = false;
    const btn = document.getElementById('btn-registrar');
    if (!btn) return;
    btn.disabled = false;
    btn.style.opacity = '';
    btn.innerHTML =
        '<i class="fa-solid fa-circle-check" style="font-size:17px;pointer-events:none"></i>' +
        '<span style="pointer-events:none">Registrar Venta</span>';
}

document.getElementById('btn-registrar').addEventListener('click', async () => {
    if (enviando) return;
    if (!Object.keys(items).length) return;

    // ── Validar total ≤ $500 ─────────────────────────────────
    const totalVenta = Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
    if (totalVenta > 500) {
        mostrarToast('El total ($' + totalVenta.toFixed(2) + ') supera $500. No se aceptan billetes mayores.', 'err');
        return;
    }

    // ── Validar stock ─────────────────────────────────────────
    for (const cod of Object.keys(items)) {
        const it = items[cod];
        if (it.tipo !== 'peso' && it.stock > 0 && it.cantidad > it.stock) {
            mostrarToast('Stock insuficiente para ' + it.nombre, 'err');
            return;
        }
    }

    // ── Validar monto efectivo (OBLIGATORIO) ─────────────────
    if (metodoPago === 'efectivo') {
        const total    = Object.values(items).reduce((s, i) => s + i.precio * i.cantidad, 0);
        const recibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
        if (recibido <= 0) {
            mostrarToast('Ingresa el monto recibido para continuar', 'err');
            document.getElementById('monto-recibido').focus();
            return; // NO toca enviando — botón queda libre
        }
        if (recibido > 500) {
            mostrarToast('El monto recibido no puede superar $500.00', 'err');
            document.getElementById('monto-recibido').value = 500;
            document.getElementById('monto-recibido').focus();
            calcCambio();
            return;
        }
        if (recibido < total) {
            mostrarToast('El monto ('+fmt(recibido)+') es menor al total ('+fmt(total)+')', 'err');
            document.getElementById('monto-recibido').focus();
            return;
        }
    }

    // ── Bloquear botón ────────────────────────────────────────
    enviando = true;
    const btn = document.getElementById('btn-registrar');
    btn.disabled = true;
    btn.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin" style="pointer-events:none"></i>' +
        '<span style="pointer-events:none">Guardando...</span>';
    // Safety: forzar reset después de 20s para que nunca quede atascado
    const safetyReset = setTimeout(() => {
        if (enviando) {
            resetBtn();
            mostrarToast('La operación tardó demasiado. Intenta de nuevo.', 'err');
        }
    }, 20000);

    const detalle = Object.values(items).map(i => ({
        codigoprod      : i.codigoprod,
        cantidad        : i.cantidad,
        precio_unitario : i.precio,
        subtotal        : parseFloat((i.precio * i.cantidad).toFixed(2)),
    }));

    const ctrl = new AbortController();
    const tId  = setTimeout(() => { ctrl.abort(); }, 15000);

    let exito = false;
    try {
        const r = await apiFetch(BASE + 'ventas/registrar', {
            method  : 'POST',
            headers : {'Content-Type': 'application/json'},
            body    : JSON.stringify({
                detalle,
                metodo_pago : metodoPago,
                nota        : document.getElementById('venta-nota').value,
            }),
            signal : ctrl.signal
        });
        clearTimeout(tId);

        let data;
        try {
            data = await r.json();
        } catch (_) {
            mostrarToast('Respuesta inválida del servidor', 'err');
            resetBtn();
            return;
        }

        if (data.ok) {
            exito = true;
            mostrarToast('Venta registrada correctamente');
            Object.keys(items).forEach(k => delete items[k]);
            document.getElementById('venta-nota').value = '';
            document.getElementById('monto-recibido').value = '';
            document.getElementById('cambio-row').style.display   = 'none';
            document.getElementById('cambio-insuf').style.display = 'none';
            render();
            // Reload después de 1.5s — si falla, resetear botón
            setTimeout(() => {
                try { location.reload(); } catch(_) { resetBtn(); }
            }, 1500);
        } else {
            mostrarToast(data.mensaje || 'Error al registrar la venta', 'err');
        }

    } catch(e) {
        clearTimeout(tId);
        if (e.name === 'AbortError') {
            mostrarToast('Tiempo de espera agotado. Verifica tu conexión.', 'err');
        } else {
            mostrarToast('Error de conexión al servidor', 'err');
        }
    } finally {
        clearTimeout(safetyReset); // cancelar el safety reset si ya terminó
        if (!exito) resetBtn();
    }
});

// ── FILTRAR CATÁLOGO ──────────────────────────────────────
function filtrar(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.prod-row').forEach(row => {
        const ok = !q || row.dataset.nombre.includes(q) || row.dataset.codigo.includes(q);
        row.style.display = ok ? '' : 'none';
    });
}

// ── LISTENERS ────────────────────────────────────────────
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

    document.getElementById('mp-prec').addEventListener('input', calcMP);
    document.getElementById('mp-prec').addEventListener('keydown', e => { if (e.key==='Enter') confirmarMP(); });
    document.getElementById('mp-overlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarMP();
    });
});

// ═══════════════════════════════════════════════════════════════
// POLLING DE STOCK EN TIEMPO REAL
// Consulta el servidor cada 5 segundos y actualiza la UI
// sin necesidad de recargar la página
// ═══════════════════════════════════════════════════════════════
(function iniciarPollingStock() {
    const INTERVALO_MS = 5000; // 5 segundos
    let   stockAnterior = {};

    async function actualizarStock() {
        try {
            const r = await fetch(BASE + 'api/stock', {
                headers: { 'X-CSRF-Token': CSRF_TOKEN },
                cache: 'no-store',
            });
            if (!r.ok) return;
            const stockActual = await r.json();

            let hayDiferencias = false;

            // ── 1. Actualizar filas del catálogo ─────────────────
            for (const [cod, info] of Object.entries(stockActual)) {
                const stockNuevo   = parseFloat(info.stock) || 0;
                const stockViejo   = stockAnterior[cod]?.stock ?? stockNuevo;
                const esPesable    = ['kg','litro'].includes(info.unidad?.toLowerCase());

                if (stockNuevo === stockViejo && Object.keys(stockAnterior).length > 0) continue;

                hayDiferencias = true;

                // Actualizar el elemento de stock en la fila del catálogo
                const row    = document.querySelector(`.prod-row[data-codigo="${cod.toLowerCase()}"]`);
                if (!row) continue;

                const stkEl  = row.querySelector('.prow-stock');
                if (!stkEl) continue;

                if (esPesable) {
                    // Pesables: nunca se deshabilitan
                    stkEl.innerHTML = stockNuevo > 0
                        ? `<i class="fa-solid fa-circle-check" style="color:var(--success)"></i> ${stockNuevo.toFixed(3)} kg`
                        : `<i class="fa-solid fa-scale-balanced" style="color:var(--success)"></i> siempre disponible`;
                } else {
                    const btn = row.querySelector('.btn-add');
                    if (stockNuevo <= 0) {
                        stkEl.innerHTML = `<i class="fa-solid fa-circle-xmark" style="color:var(--danger)"></i> Sin stock`;
                        stkEl.className = 'prow-stock low';
                        row.classList.add('sin-stock');
                        if (btn) { btn.disabled = true; btn.style.opacity = '.35'; btn.style.cursor = 'not-allowed'; }
                    } else {
                        stkEl.innerHTML = `<i class="fa-solid fa-circle-check" style="color:var(--success)"></i> ${stockNuevo} ${info.unidad}`;
                        stkEl.className = 'prow-stock ok';
                        row.classList.remove('sin-stock');
                        if (btn) { btn.disabled = false; btn.style.opacity = ''; btn.style.cursor = ''; }
                    }
                    // Actualizar data-prod con nuevo stock
                    if (btn && btn.dataset.prod) {
                        try {
                            const prod = JSON.parse(btn.dataset.prod);
                            prod.stock = stockNuevo;
                            btn.dataset.prod = JSON.stringify(prod);
                        } catch(_) {}
                    }
                }

                // ── 2. Actualizar stock en items del carrito ──────
                if (items[cod]) {
                    items[cod].stock = stockNuevo;

                    // Si la cantidad en carrito supera el stock nuevo → advertir
                    if (!esPesable && items[cod].cantidad > stockNuevo) {
                        mostrarToast(
                            `⚠ Stock de "${items[cod].nombre}" bajó a ${stockNuevo}. ` +
                            (stockNuevo <= 0 ? 'Ya no hay disponible.' : `Ajusta la cantidad.`),
                            'err'
                        );
                        // Ajustar cantidad al máximo disponible
                        if (stockNuevo <= 0) {
                            delete items[cod];
                        } else {
                            items[cod].cantidad = stockNuevo;
                            items[cod].subtotal = parseFloat((stockNuevo * items[cod].precio).toFixed(2));
                        }
                        render();
                    }
                }
            }

            stockAnterior = stockActual;

        } catch (_) {
            // Error de red: no hacer nada, el próximo polling lo reintentará
        }
    }

    // Primera consulta al cargar la página (sin esperar 5s)
    document.addEventListener('DOMContentLoaded', () => {
        actualizarStock();
        setInterval(actualizarStock, INTERVALO_MS);
    });
})();
