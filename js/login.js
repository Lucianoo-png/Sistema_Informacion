// login.js — JavaScript específico de la página login

// Forzar mayúsculas en el campo clave
document.querySelector('input[name="clave"]').addEventListener('input', function(){
    this.value = this.value.toUpperCase();
});
