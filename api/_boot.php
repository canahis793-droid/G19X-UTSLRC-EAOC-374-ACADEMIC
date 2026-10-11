<?php
// Bootstrap compartido API JSON: sesion web o token (?token= / Bearer).
// Uso: require _boot.php; luego usar $db y $apiUser. Responde 401 JSON si no hay auth.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/funciones.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

$apiUser = api_token_user();
if (!$apiUser && empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'No autorizado. Inicie sesión o use ?token=']);
  exit;
}

$db = getDB();

function api_ok($data) {
  echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}
function api_error($msg, $code = 500) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg]);
  exit;
}
