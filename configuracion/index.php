<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_admin();
$titulo = 'Configuración';
$descripcion = 'Ajustes generales del sistema.';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $validas = array_column($db->query("SELECT clave FROM configuracion")->fetchAll(), 'clave');
  foreach (($_POST['cfg'] ?? []) as $k => $v) {
    if (!in_array($k, $validas, true)) continue; // sin claves arbitrarias
    $db->prepare("UPDATE configuracion SET valor=? WHERE clave=?")->execute([$v, $k]);
  }
registrar_bitacora('configurar','configuracion','Actualizo parametros generales');
flash_set('ok','Configuración guardada.');
redirect(base_url('configuracion/index.php'));
}
$rows = $db->query("SELECT * FROM configuracion ORDER BY clave")->fetchAll();
// Tokens API
if (!empty($_POST['nuevo_token_nombre'])) {
  $tok = bin2hex(random_bytes(24));
  $db->prepare("INSERT INTO api_tokens (id_usuario, nombre, token_hash) VALUES (?,?,?)")->execute([$_SESSION['user_id'], mb_substr(trim($_POST['nuevo_token_nombre']), 0, 100), hash('sha256', $tok)]);
  registrar_bitacora('crear_token', 'configuracion', 'Creo token API ' . trim($_POST['nuevo_token_nombre']));
  flash_set('ok', 'Token creado (cópialo, solo se muestra una vez): ' . $tok);
  redirect(base_url('configuracion/index.php'));
}
if (!empty($_GET['revocar_token'])) {
  $db->prepare("DELETE FROM api_tokens WHERE id_token=?")->execute([(int)$_GET['revocar_token']]);
  registrar_bitacora('revocar_token', 'configuracion', 'Revoco token id=' . (int)$_GET['revocar_token']);
  flash_set('ok', 'Token revocado.');
  redirect(base_url('configuracion/index.php'));
}
$tokens = $db->query("SELECT t.*, u.nombre AS usuario FROM api_tokens t JOIN usuarios u ON u.id_usuario=t.id_usuario ORDER BY t.fecha_creacion DESC")->fetchAll();
$testPy = null;
if (!empty($_GET['probar_python'])) {
  $t = ejecutar_python('clasificador.py', ['nombre' => 'clientes_test', 'descripcion' => 'tabla de prueba', 'texto' => 'clientes']);
  $testPy = !empty($t['ok']) ? 'OK: Python responde (' . ($t['categoria'] ?? '?') . ' ' . ($t['confianza'] ?? 0) . '%)' : 'FALLO: ' . ($t['error'] ?? 'sin respuesta');
  registrar_bitacora('probar_python', 'configuracion', (string)$testPy);
}
include __DIR__ . '/../includes/header.php';
?>
<?php if ($testPy): ?><div class="alert <?= str_starts_with($testPy, 'OK') ? 'ok' : 'error' ?>"><?= e($testPy) ?></div><?php endif; ?>
<div class="card"><h3>Parámetros generales</h3>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <?php foreach ($rows as $r): ?>
      <label class="full"><?= e($r['clave']) ?> <small class="muted"><?= e($r['descripcion'] ?? '') ?></small><input name="cfg[<?= e($r['clave']) ?>]" value="<?= e($r['valor']) ?>"></label>
  <?php endforeach; ?>
<div><button class="btn btn-primary">Guardar</button></div>
</form>
<p class="muted">python_path: en Windows XAMPP usa <code>py</code>; en Linux usa <code>python3</code>. Verifica con <code>py --version</code>.</p>
<p><a class="btn" href="?probar_python=1">Probar conexión Python</a></p>
</div>
<div class="card"><div class="card-head"><h3> Tokens API para integraciones</h3></div>
  <p class="muted">Uso: <code>/api/activos.php?token=ABC</code> o cabecera <code>Authorization: Bearer ABC</code>. El token hereda tu rol.</p>
  <form method="post" class="filters">
    <?= csrf_field() ?>
    <input name="nuevo_token_nombre" required maxlength="100" placeholder="Nombre (ej. ETL nocturno)">
    <button class="btn btn-primary btn-sm">Crear token</button></form>
  <table class="table"><thead><tr><th>Nombre</th><th>De</th><th>Creado</th><th>Último uso</th><th></th></tr></thead><tbody>
      <?php foreach ($tokens as $t): ?><tr><td><strong><?= e($t['nombre']) ?></strong></td><td><?= e($t['usuario']) ?></td><td><small><?= e($t['fecha_creacion']) ?></small></td><td><small><?= e($t['ultimo_uso'] ?? 'nunca') ?></small></td>
          <td><a class="btn btn-sm danger" data-confirm="¿Revocar este token?" href="?revocar_token=<?= $t['id_token'] ?>">Revocar</a></td></tr><?php endforeach; ?>
  </tbody></table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
