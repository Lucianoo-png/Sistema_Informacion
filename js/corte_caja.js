// corte_caja.js — JavaScript específico de la página corte_caja

function aplicarPersonalizado() {
    const d = document.getElementById('inp-desde').value;
    const h = document.getElementById('inp-hasta').value;
    if (!d || !h) { mostrarToast('Selecciona ambas fechas', 'err'); return; }
    location.href = 'corte?periodo=personalizado&desde=' + d + '&hasta=' + h;
}
