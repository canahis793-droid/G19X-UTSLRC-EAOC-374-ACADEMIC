<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/funciones.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error = '';
$ip = client_ip();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $correo = trim($_POST['correo'] ?? '');
  $pass = $_POST['password'] ?? '';
  $bloqueo = login_bloqueo_restante($correo, $ip);
  if (!csrf_validar($_POST['csrf'] ?? '')) {
    $error = 'Sesión expirada. Recargue la página e intente de nuevo.';
  } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $pass === '') {
  $error = 'Ingrese un correo válido y su contraseña.';
} elseif ($bloqueo > 0) {
$error = 'Demasiados intentos. Espere ' . ceil($bloqueo / 60) . ' minuto(s).';
} else {
try {
  $db = getDB();
  $st = $db->prepare("SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.correo=? LIMIT 1");
  $st->execute([$correo]);
  $u = $st->fetch();
  if (!$u || !password_verify($pass, $u['password'])) {
    login_registrar_fallo($correo, $ip);
    $error = 'Credenciales incorrectas.';
  } elseif ($u['estado'] !== 'activo') {
  $error = 'Usuario inactivo. Contacte al administrador.';
} else {
login_limpiar_intentos($correo, $ip);
login_purgar_registros();
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$u['id_usuario'];
$_SESSION['nombre'] = $u['nombre'];
$_SESSION['correo'] = $u['correo'];
$_SESSION['rol'] = $u['rol'];
$db->prepare("UPDATE usuarios SET ultimo_acceso=NOW() WHERE id_usuario=?")->execute([$u['id_usuario']]);
registrar_bitacora('login', 'auth', 'Inicio de sesion exitoso');
redirect(base_url('public/index.php?p=dashboard'));
}
} catch (Exception $ex) {
$error = 'Error de conexión: ' . $ex->getMessage();
}
}
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Iniciar sesión · MetaData Manager</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_url('assets/img/favicon.svg')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-brand"><div class="brand-mark big">M</div>
      <h2>MetaData Manager</h2><p>Sistema Inteligente de Metadata Management</p>
    </div>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label>Correo<input type="email" name="correo" required placeholder="admin@demo.local" value="<?= e($_POST['correo'] ?? '') ?>"></label>
  <label>Contraseña<input type="password" name="password" required placeholder="••••••••"></label>
  <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
</form>
<div class="login-help">
  <strong>Cuentas de acceso:</strong><br>
  admin@demo.local / admin123 (Administración)<br>
  steward@demo.local / steward123 (Data Steward)<br>
  usuario@demo.local / user123 (Consulta)
</div>
</div>
</body>
</html>
