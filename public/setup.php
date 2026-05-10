<?php
// =====================================================
// public/setup.php — Crear / Resetear cuenta admin
// Usa pgcrypto crypt() igual que la BD.
// Visita: http://localhost/AbarrotesAngy/public/setup.php
// ¡Borra o renombra este archivo tras usarlo!
// =====================================================

if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Solo accesible desde localhost.');
}

define('BASE_PATH', dirname(__DIR__) . '/');
require_once BASE_PATH . 'modelo/Conexion.php';

$mensaje = '';
$tipo    = '';

// ── Procesar formulario ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db        = Conexion::obtener();
    $clave     = strtoupper(trim($_POST['clave']     ?? ''));
    $contrasena= trim($_POST['contrasena'] ?? '');
    $nombre    = trim($_POST['nombre']     ?? 'Admin');
    $apellidos = trim($_POST['apellidos']  ?? 'General');

    if (strlen($clave) !== 5) {
        $mensaje = '❌ La clave debe tener exactamente 5 caracteres.';
        $tipo    = 'err';
    } elseif (empty($contrasena)) {
        $mensaje = '❌ La contraseña no puede estar vacía.';
        $tipo    = 'err';
    } else {
        try {
            // ¿Ya existe?
            $chk = $db->prepare("SELECT COUNT(*) FROM cuenta WHERE ClaveCuenta = :c");
            $chk->execute([':c' => $clave]);

            if ((int)$chk->fetchColumn() > 0) {
                // Actualizar contraseña con crypt / md5 (igual a la BD original)
                $stmt = $db->prepare(
                    "UPDATE cuenta
                        SET Contrasena = crypt(:pass, gen_salt('md5')),
                            Nombre     = :n,
                            Apellidos  = :a,
                            activo     = TRUE
                      WHERE ClaveCuenta = :c"
                );
                $stmt->execute([':pass'=>$contrasena,':n'=>$nombre,':a'=>$apellidos,':c'=>$clave]);
                $mensaje = "<i class="fa-solid fa-circle-check" style="color:var(--success)"></i> Contraseña de <strong>{$clave}</strong> actualizada.";
            } else {
                // Insertar nueva cuenta
                $stmt = $db->prepare(
                    "INSERT INTO cuenta (ClaveCuenta, Contrasena, Nombre, Apellidos)
                     VALUES (:c, crypt(:pass, gen_salt('md5')), :n, :a)"
                );
                $stmt->execute([':c'=>$clave,':pass'=>$contrasena,':n'=>$nombre,':a'=>$apellidos]);
                $mensaje = "<i class="fa-solid fa-circle-check" style="color:var(--success)"></i> Cuenta <strong>{$clave}</strong> creada correctamente.";
            }
            $tipo = 'ok';
        } catch (Exception $e) {
            $mensaje = '❌ Error BD: ' . htmlspecialchars($e->getMessage());
            $tipo    = 'err';
        }
    }
}

// ── Listar cuentas ─────────────────────────────────
$cuentas = [];
try {
    $db = Conexion::obtener();
    $cuentas = $db->query(
        "SELECT ClaveCuenta, Nombre, Apellidos, activo FROM cuenta ORDER BY ClaveCuenta"
    )->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Setup — Abarrotes Angy</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
         background:#f5f0eb;display:flex;flex-direction:column;
         align-items:center;min-height:100vh;padding:40px 16px}
    .card{background:#fff;border-radius:16px;padding:32px;width:480px;
          max-width:100%;box-shadow:0 4px 24px rgba(0,0,0,.1);margin-bottom:20px}
    h1{font-size:20px;margin-bottom:4px}
    .sub{color:#888;font-size:13px;margin-bottom:24px}
    .fg{margin-bottom:14px}
    label{display:block;font-size:13px;font-weight:600;margin-bottom:5px;color:#444}
    input{width:100%;padding:10px 12px;border:1.5px solid #e2d9d0;
          border-radius:8px;font-size:14px}
    input:focus{outline:none;border-color:#e87722}
    .btn{width:100%;padding:12px;border:none;border-radius:10px;
         background:#e87722;color:#fff;font-size:15px;font-weight:700;
         cursor:pointer;margin-top:6px}
    .btn:hover{opacity:.9}
    .alert{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:18px}
    .alert.ok {background:#e8f5ee;border:1px solid #9ae6b4;color:#276749}
    .alert.err{background:#fff5f5;border:1px solid #fed7d7;color:#e53e3e}
    table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
    th{text-align:left;padding:8px 10px;background:#f9f4ef;
       color:#888;font-size:11px;text-transform:uppercase}
    td{padding:8px 10px;border-bottom:1px solid #f0e9e2}
    .badge{display:inline-block;padding:2px 8px;border-radius:10px;
           font-size:11px;font-weight:700}
    .badge.on {background:#e8f5ee;color:#276749}
    .badge.off{background:#fff5f5;color:#e53e3e}
    .warn{background:#fffbea;border:1px solid #fbd38d;border-radius:8px;
          padding:12px 16px;font-size:13px;color:#744210;margin-top:16px}
    a.link{display:inline-block;margin-top:16px;color:#e87722;
           font-size:14px;text-decoration:none;font-weight:600}
    .info{background:#ebf8ff;border:1px solid #90cdf4;border-radius:8px;
          padding:10px 14px;font-size:13px;color:#2b6cb0;margin-bottom:16px}
  </style>
</head>
<body>

<div class="card">
  <h1><i class="fa-solid fa-screwdriver-wrench"></i> Setup — Abarrotes Angy</h1>
  <p class="sub">Crear o resetear cuenta de acceso. <strong>Elimina este archivo tras usarlo.</strong></p>

  <div class="info">
    <i class="fa-solid fa-lightbulb" style="color:#d97706"></i> Las contraseñas se guardan con <code>crypt()</code> de pgcrypto
    (igual a como se creó la BD).
  </div>

  <?php if ($mensaje): ?>
  <div class="alert <?= $tipo ?>"><?= $mensaje ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="fg">
      <label>Clave de Cuenta (5 caracteres)</label>
      <input type="text" name="clave" maxlength="5" value="ADM01"
             required oninput="this.value=this.value.toUpperCase()">
    </div>
    <div class="fg">
      <label>Contraseña</label>
      <input type="password" name="contrasena" placeholder="••••••••" required>
    </div>
    <div class="fg">
      <label>Nombre</label>
      <input type="text" name="nombre" value="Administrador1">
    </div>
    <div class="fg">
      <label>Apellidos</label>
      <input type="text" name="apellidos" value="General">
    </div>
    <button type="submit" class="btn">Crear / Actualizar Cuenta</button>
  </form>
</div>

<?php if ($cuentas): ?>
<div class="card">
  <strong>Cuentas en la base de datos:</strong>
  <table>
    <thead>
      <tr><th>Clave</th><th>Nombre</th><th>Apellidos</th><th>Activo</th></tr>
    </thead>
    <tbody>
      <?php foreach ($cuentas as $c): ?>
      <tr>
        <td><code><?= htmlspecialchars($c['clavecuenta'] ?? $c['ClaveCuenta']) ?></code></td>
        <td><?= htmlspecialchars($c['nombre'] ?? $c['Nombre']) ?></td>
        <td><?= htmlspecialchars($c['apellidos'] ?? $c['Apellidos']) ?></td>
        <td>
          <span class="badge <?= $c['activo'] ? 'on' : 'off' ?>">
            <?= $c['activo'] ? 'Sí' : 'No' ?>
          </span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="warn">
  <i class="fa-solid fa-triangle-exclamation"></i> <strong>Importante:</strong> Borra <code>public/setup.php</code>
  después de crear tu cuenta.
</div>

<a class="link" href="../index.php">← Ir al sistema</a>
</body>
</html>
