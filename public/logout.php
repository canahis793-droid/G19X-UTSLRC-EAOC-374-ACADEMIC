<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/funciones.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['user_id'])) { registrar_bitacora('logout', 'auth', 'Cierre de sesion'); }
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: ' . base_url('public/login.php'));
exit;
