<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/funciones.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Proteger pagina: exige login
if (empty($_SESSION['user_id'])) {
  $login = base_url('public/login.php');
  header('Location: ' . $login);
  exit;
}

// Expiracion por inactividad (30 min)
if (!empty($_SESSION['ultima_actividad']) && time() - $_SESSION['ultima_actividad'] > 1800) {
  $_SESSION = [];
  session_destroy();
  header('Location: ' . base_url('public/login.php'));
  exit;
}
$_SESSION['ultima_actividad'] = time();

// CSRF global para todos los POST de paginas protegidas
exigir_csrf();

// Refrescar datos basicos de sesion si faltan
if (empty($_SESSION['rol'])) {
  try {
    $db = getDB();
    $st = $db->prepare("SELECT u.nombre, u.correo, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=?");
    $st->execute([$_SESSION['user_id']]);
    if ($row = $st->fetch()) {
      $_SESSION['nombre'] = $row['nombre'];
      $_SESSION['correo'] = $row['correo'];
      $_SESSION['rol'] = $row['rol'];
    }
} catch (Exception $ex) {}
}
