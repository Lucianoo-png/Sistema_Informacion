// transferencias.js

// Validación en tiempo real del monto de transferencia
document.addEventListener('DOMContentLoaded', () => {
    const inpMonto = document.getElementById('tf-monto');
    if (inpMonto) {
        inpMonto.addEventListener('input', function() {
            const v = parseFloat(this.value) || 0;
            if (v > 1000) { this.value = 1000; }
            if (v < 0)    { this.value = 0; }
        });
    }
});

const Transferencias = {
    registrar: async function() {
        const monto   = parseFloat(document.getElementById('tf-monto').value) || 0;
        const concepto = document.getElementById('tf-concepto')?.value?.trim() || '';
        const btn     = document.querySelector('button[onclick="Transferencias.registrar()"]');

        if (monto < 1) {
            mostrarToast('El monto debe ser de al menos $1.00', 'err');
            return;
        }
        if (monto > 1000) {
            mostrarToast('El monto no puede superar $1,000', 'err');
            document.getElementById('tf-monto').value = 1000;
            return;
        }

        if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

        let exito = false;
        try {
            const r    = await apiFetch(BASE + 'transferencias/registrar', {
                method : 'POST',
                body   : JSON.stringify({ monto, concepto }),
            });
            const data = await r.json();
            if (data.ok) {
                exito = true;
                mostrarToast('Transferencia registrada');
                document.getElementById('tf-monto').value   = '0.00';
                if (document.getElementById('tf-concepto'))
                    document.getElementById('tf-concepto').value = '';
                setTimeout(() => {
                    try { location.reload(); } catch(_) {}
                }, 900);
            } else {
                mostrarToast(data.mensaje || 'Error al registrar', 'err');
            }
        } catch(e) {
            mostrarToast('Error de conexión', 'err');
        } finally {
            if (!exito && btn) {
                btn.disabled = false;
                btn.textContent = '+ Registrar';
            }
        }
    }
};

async function eliminarTransferencia(id) {
    if (!confirm('¿Eliminar esta transferencia?')) return;
    try {
        const res  = await apiFetch(BASE + 'transferencias/eliminar/' + id);
        const resp = await res.json();
        mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
        if (resp.ok) setTimeout(() => location.reload(), 900);
    } catch(e) {
        mostrarToast('Error de conexión', 'err');
    }
}
