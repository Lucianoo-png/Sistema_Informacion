// reporte_diario.js — JavaScript específico de la página reporte_diario

function aplicarPersonalizado() {
    const d = document.getElementById('inp-desde').value;
    const h = document.getElementById('inp-hasta').value;
    if (!d || !h) { mostrarToast('Selecciona ambas fechas', 'err'); return; }
    location.href = 'reporte?periodo=personalizado&desde=' + d + '&hasta=' + h;
}
