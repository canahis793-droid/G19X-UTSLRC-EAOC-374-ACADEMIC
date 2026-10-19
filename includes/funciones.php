<?php
// Funciones comunes: sanitizacion, bitacora, ejecucion Python, helpers
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function e($str) {
  return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
  header('Location: ' . $path);
  exit;
}

function base_url($path = '') {
  $base = defined('BASE_URL') ? BASE_URL : '';
  return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function client_ip() {
  return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/* ------------------------------------------------------------------
 * Limite de intentos de login (anti fuerza bruta) - lado del servidor.
 * El contador vive en la tabla login_intentos (IP + correo), no en la
 * sesion: borrando la cookie se reinicia la sesion, pero no el bloqueo.
 * ------------------------------------------------------------------ */

/**
 * Devuelve los segundos restantes de bloqueo, o 0 si no esta bloqueado.
 */
function login_bloqueo_restante($correo, $ip) {
  try {
    $db = getDB();
    // El calculo se hace en MySQL: comparar en PHP convertiria la fecha con
    // la zona horaria de PHP y el bloqueo pareceria expirado antes de tiempo.
    $st = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) AS restante
                        FROM login_intentos
                        WHERE ip=? AND correo IN (?, '') AND bloqueado_hasta IS NOT NULL
                        ORDER BY bloqueado_hasta DESC LIMIT 1");
    $st->execute([$ip, $correo]);
    $restante = (int)($st->fetchColumn() ?: 0);
    return $restante > 0 ? $restante : 0;
  } catch (Exception $ex) {
    return 0; // si la tabla no existe todavia, no se bloquea a nadie
  }
}

/**
 * Registra un intento fallido. A los 5 fallos bloquea 1 minuto; si sigue
 * fallando, el bloqueo se alarga hasta 15 minutos.
 */
function login_registrar_fallo($correo, $ip) {
  try {
    $db = getDB();
    $db->prepare("INSERT INTO login_intentos (ip, correo, intentos) VALUES (?,?,1)
                  ON DUPLICATE KEY UPDATE intentos = intentos + 1, fecha = NOW()")
      ->execute([$ip, $correo]);
    $st = $db->prepare("SELECT intentos FROM login_intentos WHERE ip=? AND correo=?");
    $st->execute([$ip, $correo]);
    $intentos = (int)($st->fetchColumn() ?: 0);
    if ($intentos >= 5) {
      $minutos = min(15, 1 * (int)ceil($intentos / 5));
      $db->prepare("UPDATE login_intentos SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE ip=? AND correo=?")
        ->execute([$minutos, $ip, $correo]);
    }
    // Tope por IP: impide probar muchas cuentas distintas desde la misma maquina.
    $st = $db->prepare("SELECT COALESCE(SUM(intentos),0) FROM login_intentos WHERE ip=? AND correo<>''");
    $st->execute([$ip]);
    $total = (int)$st->fetchColumn();
    if ($total >= 20) {
      $db->prepare("INSERT INTO login_intentos (ip, correo, intentos, bloqueado_hasta) VALUES (?, '', 1, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
                    ON DUPLICATE KEY UPDATE bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 5 MINUTE)")->execute([$ip]);
    }
  } catch (Exception $ex) { /* no bloquear el login por el contador */ }
}

/**
 * Limpia el contador tras un login exitoso.
 */
function login_limpiar_intentos($correo, $ip) {
  try {
    $db = getDB();
    $db->prepare("DELETE FROM login_intentos WHERE ip=? AND correo=?")->execute([$ip, $correo]);
  } catch (Exception $ex) {}
}

/**
 * Borra registros de mas de 1 dia (llamado de vez en cuando).
 */
function login_purgar_registros() {
  try {
    $db = getDB();
    $db->exec("DELETE FROM login_intentos WHERE fecha < NOW() - INTERVAL 1 DAY");
  } catch (Exception $ex) {}
}

function registrar_bitacora($accion, $modulo, $descripcion) {
  try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO bitacora (id_usuario, accion, modulo, descripcion, ip) VALUES (?,?,?,?,?)");
    $stmt->execute([$_SESSION['user_id'] ?? null, $accion, $modulo, $descripcion, client_ip()]);
  } catch (Exception $ex) { /* no bloquear flujo por bitacora */ }
}

function obtener_config($clave, $defecto = null) {
  try {
    $db = getDB();
    $st = $db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    $st->execute([$clave]);
    $row = $st->fetch();
    return $row ? $row['valor'] : $defecto;
  } catch (Exception $ex) { return $defecto; }
}

function python_cmd() {
  $c = obtener_config('python_path', null);
  if ($c) return $c;
  return defined('PYTHON_CMD') ? PYTHON_CMD : 'py';
}

/**
* Ejecuta un script Python pasando un array como JSON por archivo temporal.
* Retorna array decodificado o ['ok'=>false,'error'=>...].
*/
function ejecutar_python($script, array $payload = []) {
  $pyDir = defined('PYTHON_DIR') ? PYTHON_DIR : (__DIR__ . '/../python/');
  $scriptPath = rtrim($pyDir, '/\\') . DIRECTORY_SEPARATOR . $script;
  if (!file_exists($scriptPath)) {
    return ['ok' => false, 'error' => 'Script Python no encontrado: ' . $script];
  }
$tmpIn = tempnam(sys_get_temp_dir(), 'mdm_in_');
$tmpOut = $tmpIn . '.out.json';
file_put_contents($tmpIn, json_encode($payload, JSON_UNESCAPED_UNICODE));
$cmd = python_cmd() . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($tmpIn) . ' ' . escapeshellarg($tmpOut);
// proc_open con timeout de 30s (evita colgar PHP si Python se traba)
$desc = [1 => ['pipe','w'], 2 => ['pipe','w']];
$proc = proc_open($cmd, $desc, $pipes);
$salida = '';
if (is_resource($proc)) {
  stream_set_blocking($pipes[1], false);
  stream_set_blocking($pipes[2], false);
  $inicio = time();
  $salida = $err = '';
  do {
    $s = proc_get_status($proc);
    $salida .= stream_get_contents($pipes[1]);
    $err .= stream_get_contents($pipes[2]);
    if (!$s['running']) break;
    usleep(100000);
  } while (time() - $inicio < 30);
$s = proc_get_status($proc);
if ($s['running']) { proc_terminate($proc); $salida .= "\n[TIMEOUT 30s] $err"; }
fclose($pipes[1]); fclose($pipes[2]);
proc_close($proc);
} else {
$salida = shell_exec($cmd . ' 2>&1');
}
$resultado = null;
if (file_exists($tmpOut)) {
  $json = file_get_contents($tmpOut);
  $resultado = json_decode($json, true);
  @unlink($tmpOut);
}
@unlink($tmpIn);
if (is_array($resultado)) return $resultado;
// Fallback: el script pudo imprimir JSON a stdout
$try = json_decode((string)$salida, true);
if (is_array($try)) return $try;
return ['ok' => false, 'error' => 'Python no devolvio JSON valido. Salida: ' . substr((string)$salida, 0, 1000)];
}

function flash_set($tipo, $mensaje) {
  $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}
function flash_get() {
  $f = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  return $f;
}

function csrf_token() {
  if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
  return $_SESSION['csrf'];
}
function csrf_validar($t) {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t);
}
function csrf_field() {
  return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
/** Aborta con 419 si el POST no trae CSRF valido. Llamar en paginas protegidas. */
function exigir_csrf() {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_validar($_POST['csrf'] ?? '')) {
    http_response_code(419);
    die('Sesión expirada o petición inválida (CSRF). Vuelva atrás y reintente.');
  }
}
/**
 * Resuelve un host y comprueba que NO sea una direccion interna o privada.
 * Se usa en las capturas externas (MySQL y API) para que un usuario con permiso
 * no pueda usar la aplicacion para alcanzar maquinas de la red interna (SSRF).
 * Devuelve la IP validada. Lanza Exception si el host no es valido o es interno.
 */
function validar_host_publico($host) {
  $host = strtolower(trim((string)$host));
  if ($host === '') throw new Exception('Host es obligatorio.');
  if (!filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
    throw new Exception("Host no valido: $host");
  }
  if (filter_var($host, FILTER_VALIDATE_IP)) {
    $ip = $host;
  } else {
    $ip = gethostbyname($host);
    if ($ip === $host) throw new Exception("No se pudo resolver el host: $host");
  }
  if (!filter_var($ip, FILTER_VALIDATE_IP)) throw new Exception("IP no valida para $host.");
  $permitir_local = defined('CAPTURA_PERMITE_LOCAL') && CAPTURA_PERMITE_LOCAL;
  if ($permitir_local) return $ip;
  $bloqueada = $ip === '::1'
    || str_starts_with($ip, '127.')
    || str_starts_with($ip, '10.')
    || str_starts_with($ip, '192.168.')
    || str_starts_with($ip, '169.254.')
    || $ip === '0.0.0.0'
    || preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $ip)
    || preg_match('/^f[cd][0-9a-f]{2}:/i', $ip)
    || preg_match('/^fe80:/i', $ip);
  if ($bloqueada) {
    throw new Exception("Por seguridad no se permiten conexiones a hosts internos o privados ($host).");
  }
  return $ip;
}

/** Valida token API (?token= o Bearer). Retorna fila usuario+rol o null. */
function api_token_user() {
  $tok = trim($_GET['token'] ?? '');
  if ($tok === '' && !empty($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
    $tok = trim(substr($_SERVER['HTTP_AUTHORIZATION'], 7));
  }
if ($tok === '' || strlen($tok) > 128) return null;
try {
  $db = getDB();
  $st = $db->prepare("SELECT t.id_token, t.id_usuario, u.nombre, u.correo, r.nombre AS rol FROM api_tokens t JOIN usuarios u ON u.id_usuario=t.id_usuario JOIN roles r ON r.id_rol=u.id_rol WHERE t.token_hash=? AND u.estado='activo' LIMIT 1");
  $st->execute([hash('sha256', $tok)]);
  $row = $st->fetch();
  if ($row) { $db->prepare("UPDATE api_tokens SET ultimo_uso=NOW() WHERE id_token=?")->execute([$row['id_token']]); }
  return $row ?: null;
} catch (Exception $e) { return null; }
}
function paginar($total, $por_defecto = null) {
  $por = (int)($por_defecto ?? obtener_config('registros_por_pagina', 15));
  $por = max(5, min(100, $por));
  $pag = max(1, (int)($_GET['pag'] ?? 1));
  $maxpag = max(1, (int)ceil($total / $por));
  $pag = min($pag, $maxpag);
  return [$por, ($pag - 1) * $por, $pag, $maxpag];
}
/** Guarda snapshot en papelera antes de borrar. */
function a_papelera($tipo, $id_original, $nombre, array $datos) {
  try {
    $db = getDB();
    $db->prepare("INSERT INTO papelera (tipo, id_original, nombre, datos, id_usuario) VALUES (?,?,?,?,?)")
    ->execute([$tipo, $id_original, mb_substr($nombre, 0, 200), json_encode($datos, JSON_UNESCAPED_UNICODE), $_SESSION['user_id'] ?? null]);
  } catch (Exception $e) {}
}
function links_paginacion($pag, $maxpag) {
  if ($maxpag <= 1) return '';
  $q = $_GET; $h = '';
  for ($i = 1; $i <= $maxpag; $i++) {
    $q['pag'] = $i;
    $url = '?' . http_build_query($q);
    $h .= $i === $pag ? " <strong>[$i]</strong>" : " <a href=\"" . e($url) . "\">$i</a>";
  }
return '<div class="paginacion">Página:' . $h . '</div>';
}
