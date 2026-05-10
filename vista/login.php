<?php
// =====================================================
// vista/login.php — Inicio de Sesión
// NUEVO: formulario con ClaveCuenta + Contraseña
// =====================================================

$base  = defined('BASE_URL') ? BASE_URL : './';
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión — Abarrotes Angy</title>
  <link rel="stylesheet" href="<?= $base ?>estilos/abarrotes.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg); }
    .login-box {
      background:#fff; border-radius:16px; padding:40px 36px;
      width:380px; box-shadow:0 4px 24px rgba(0,0,0,.12);
    }
    .login-logo { text-align:center; font-size:48px; margin-bottom:8px; }
    .login-title { text-align:center; font-size:22px; font-weight:700; margin-bottom:4px; }
    .login-sub   { text-align:center; color:#888; font-size:13px; margin-bottom:28px; }
    .login-error {
      background:#fff5f5; border:1px solid #fed7d7; border-radius:8px;
      padding:10px 14px; color:#e53e3e; font-size:13px; margin-bottom:16px;
    }
    .login-btn {
      width:100%; padding:12px; border-radius:10px; border:none;
      background:var(--primary); color:#fff; font-size:15px;
      font-weight:700; cursor:pointer; margin-top:8px; transition:.15s;
    }
    .login-btn:hover { opacity:.9; }
    .login-footer { text-align:center; margin-top:20px; font-size:12px; color:#aaa; }
    .field-hint { font-size:11px; color:#aaa; margin-top:3px; }
  </style>
</head>
<body>
<div class="login-box">
    <div class="login-logo"><i class="fa-solid fa-store-alt" style="font-size:52px;color:#e87722"></i></div>
    <div class="login-title">Abarrotes Angy</div>
    <div class="login-sub">Sistema de Información — Iniciar sesión</div>

    <?php if ($error): ?>
    <div class="login-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $base ?>login">
        <div class="form-group">
            <label>Clave de Cuenta</label>
            <input type="text" name="clave" class="form-control"
                   placeholder="Ej: ADM01" maxlength="5"
                   autocomplete="username" required autofocus
                   style="text-transform:uppercase">
            <div class="field-hint">5 caracteres alfanuméricos</div>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="contrasena" class="form-control"
                   placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-btn">Entrar →</button>
    </form>

    <div class="login-footer">v1.0 — 2026 · Abarrotes Angy</div>
</div>
<script>
// Forzar mayúsculas en el campo clave
document.querySelector('input[name="clave"]').addEventListener('input', function(){
    this.value = this.value.toUpperCase();
});
</script>
</body>
</html>
