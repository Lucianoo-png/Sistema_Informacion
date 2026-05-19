// transferencias.js — JavaScript específico de la página transferencias

async function eliminarTransferencia(id) {
    if (!confirm('¿Eliminar esta transferencia?')) return;
    const res  = await fetch(BASE + 'transferencias/eliminar/' + id);
    const resp = await res.json();
    mostrarToast(resp.mensaje, resp.ok ? 'ok' : 'err');
    if (resp.ok) setTimeout(() => location.reload(), 900);
}
