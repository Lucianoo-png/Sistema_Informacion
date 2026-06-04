// corte_caja.js
// La validación de inputs (max=hoy, min dinámico, coherencia de rango)
// la maneja abarrotes.js automáticamente al detectar inputs name="desde"/"hasta".
// Este archivo solo contiene la navegación de períodos personalizados.

function aplicarPersonalizado() {
    const hoy = new Date().toISOString().slice(0, 10);
    const d   = document.getElementById('inp-desde').value;
    const h   = document.getElementById('inp-hasta').value;

    if (!d || !h)  { mostrarToast('Selecciona ambas fechas', 'err'); return; }
    if (d > hoy)   { mostrarToast('La fecha "Desde" no puede ser futura', 'err'); return; }
    if (h > hoy)   { mostrarToast('La fecha "Hasta" no puede ser futura', 'err'); return; }
    if (h < d)     { mostrarToast('"Hasta" no puede ser anterior a "Desde"', 'err'); return; }

    location.href = 'corte?periodo=personalizado&desde=' + d + '&hasta=' + h;
}
