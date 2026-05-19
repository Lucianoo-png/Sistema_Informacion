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
  <link rel="stylesheet" href="<?= $base ?>estilos/login.css">
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
  <script src="<?= $base ?>js/login.js"></script>
</body>
</html>
