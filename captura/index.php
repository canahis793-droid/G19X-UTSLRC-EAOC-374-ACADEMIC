<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$titulo = 'Captura automática';
$descripcion = 'Trae datos nuevos en pasos guiados.';
$db = getDB();
$fuentes = $db->query("SELECT * FROM fuentes WHERE estado='activo' ORDER BY nombre")->fetchAll();
$procesos = $db->query("SELECT p.*, f.nombre AS fuente, u.nombre AS usuario FROM procesos_captura p LEFT JOIN fuentes f ON f.id_fuente=p.id_fuente LEFT JOIN usuarios u ON u.id_usuario=p.id_usuario ORDER BY p.fecha_ejecucion DESC LIMIT 20")->fetchAll();

// Gestion de archivos subidos
if (!empty($_GET['borrar_arch']) && puede_editar()) {
  $f = basename($_GET['borrar_arch']);
  $path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $f;
  if (is_file($path) && !in_array($f, ['.htaccess', '.gitkeep'], true)) {
    @unlink($path);
    registrar_bitacora('borrar_archivo', 'captura', "Borro archivo $f");
    flash_set('ok', 'Archivo eliminado.');
  }
redirect(base_url('public/index.php?p=captura'));
}
if (!empty($_GET['purgar']) && es_admin()) {
  $n = 0;
  foreach (glob(rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . '*') as $path) {
    $f = basename($path);
    if (is_file($path) && !in_array($f, ['.htaccess', '.gitkeep'], true) && filemtime($path) < time() - 30 * 86400) {
      @unlink($path); $n++;
    }
}
registrar_bitacora('purgar_archivos', 'captura', "Purgo $n archivos >30 dias");
flash_set('ok', "Purgados $n archivos antiguos.");
redirect(base_url('public/index.php?p=captura'));
}
$archivos = [];
foreach (glob(rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
  $f = basename($path);
  if (is_file($path) && !in_array($f, ['.htaccess', '.gitkeep'], true)) {
    $archivos[] = ['nombre' => $f, 'kb' => round(filesize($path) / 1024, 1), 'fecha' => date('Y-m-d H:i', filemtime($path))];
  }
}
usort($archivos, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
include __DIR__ . '/../includes/header.php';
?>
<div class="steps">
  <div class="step"><span class="step-num">1</span><span>MySQL</span></div>
  <div class="step"><span class="step-num">2</span><span>CSV</span></div>
  <div class="step"><span class="step-num">3</span><span>JSON</span></div>
  <div class="step"><span class="step-num">4</span><span>API</span></div>
</div>

<div class="grid-2">
  <div class="card capture">
    <div class="capture-head"><span class="step-num">1</span><div><h3>Base de datos MySQL</h3><p class="muted">Lee tablas y columnas vía INFORMATION_SCHEMA. Cada tabla será un activo.</p></div></div>
    <form method="post" action="<?= e(base_url('public/index.php?p=captura/procesar_mysql')) ?>" class="form-grid">
    <?= csrf_field() ?>
    <label class="full">Guardar en fuente*<select name="id_fuente" required><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>"><?= e($f['nombre']) ?></option><?php endforeach; ?></select></label>
  <label>Host<input name="host" required placeholder="servidor.midominio.com"></label>
  <label>Puerto<input name="puerto" type="number" min="1" max="65535" value="3306"></label>
  <label>Base de datos*<input name="bd" required placeholder="erp"></label>
  <label>Usuario<input name="user" value="root"></label>
  <label>Contraseña<input type="password" name="pass" autocomplete="off"></label>
  <div class="full muted">Por seguridad solo se permiten servidores públicos (no localhost ni IPs privadas). La contraseña se usa solo para abrir la conexión: no se guarda.</div>
  <div class="full"><button class="btn btn-primary"> Ejecutar captura MySQL</button></div>
</form>
</div>

<div>
  <div class="card capture">
    <div class="capture-head"><span class="step-num">2</span><div><h3>Archivo CSV</h3><p class="muted">Python detecta columnas, tipos, filas y vacíos. Máx 10 MB.</p></div></div>
    <form method="post" enctype="multipart/form-data" action="<?= e(base_url('public/index.php?p=captura/procesar_archivo')) ?>" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="tipo" value="csv">
    <label>Guardar en fuente*<select name="id_fuente" required><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>"><?= e($f['nombre']) ?></option><?php endforeach; ?></select></label>
  <label>Archivo CSV*<input type="file" name="archivo" accept=".csv" required class="file-input"></label>
  <div class="full"><button class="btn btn-primary"> Analizar CSV con Python</button></div>
</form>
</div>
<div class="card capture">
  <div class="capture-head"><span class="step-num">3</span><div><h3>Archivo JSON</h3><p class="muted">Python detecta propiedades, tipos y registros. Máx 10 MB.</p></div></div>
  <form method="post" enctype="multipart/form-data" action="<?= e(base_url('public/index.php?p=captura/procesar_archivo')) ?>" class="form-grid">
  <?= csrf_field() ?>
  <input type="hidden" name="tipo" value="json">
  <label>Guardar en fuente*<select name="id_fuente" required><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>"><?= e($f['nombre']) ?></option><?php endforeach; ?></select></label>
<label>Archivo JSON*<input type="file" name="archivo" accept=".json" required class="file-input"></label>
<div class="full"><button class="btn btn-primary"> Analizar JSON con Python</button></div>
</form>
</div>
<div class="card capture">
  <div class="capture-head"><span class="step-num">4</span><div><h3>Registrar API</h3><p class="muted">Guarda el endpoint y una muestra de respuesta como metadatos.</p></div></div>
  <form method="post" action="<?= e(base_url('public/index.php?p=captura/procesar_api')) ?>" class="form-grid">
  <?= csrf_field() ?>
  <label>Guardar en fuente*<select name="id_fuente" required><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>"><?= e($f['nombre']) ?></option><?php endforeach; ?></select></label>
<label>Nombre del activo*<input name="nombre" required placeholder="api_facturas"></label>
<label class="full">URL de la API*<input name="url" required placeholder="https://api.ejemplo.com/v1/facturas"></label>
<div class="full"><button class="btn btn-primary"> Registrar API</button></div>
</form>
</div>
</div>
</div>

<div class="card">
  <div class="card-head"><h3> Archivos subidos (<?= count($archivos) ?>)</h3>
    <?php if (es_admin() && $archivos): ?><a class="btn btn-sm" data-confirm="¿Borrar archivos de más de 30 días?" href="?p=captura&purgar=1"> Purgar +30 días</a><?php endif; ?></div>
  <table class="table"><thead><tr><th>Archivo</th><th>Tamaño</th><th>Subido</th><th></th></tr></thead><tbody>
      <?php foreach ($archivos as $a): ?><tr><td><?= e($a['nombre']) ?></td><td><?= e($a['kb']) ?> KB</td><td><small><?= e($a['fecha']) ?></small></td>
          <td><a class="btn btn-sm danger" data-confirm="¿Borrar archivo?" href="?p=captura&borrar_arch=<?= urlencode($a['nombre']) ?>">Borrar</a></td></tr><?php endforeach; ?>
  </tbody></table>
</div>

<div class="card">
  <h3> Historial de capturas</h3>
  <table class="table"><thead><tr><th>Fecha</th><th>Fuente</th><th>Tipo</th><th>Activos</th><th>Estado</th><th>Usuario</th><th>Detalle</th></tr></thead><tbody>
      <?php foreach ($procesos as $p): ?>
        <tr><td><small><?= e($p['fecha_ejecucion']) ?></small></td><td><?= e($p['fuente'] ?? '—') ?></td>
          <td><span class="badge"><?= e($p['tipo']) ?></span></td><td><?= (int)$p['total_activos'] ?></td>
          <td><span class="badge <?= $p['estado']==='exitoso'?'ok':($p['estado']==='fallido'?'bad':'warn') ?>"><?= e($p['estado']) ?></span></td>
        <td><?= e($p['usuario'] ?? '—') ?></td><td><?= e(mb_substr($p['detalle'] ?? '',0,100)) ?></td></tr>
    <?php endforeach; ?>
</tbody></table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
