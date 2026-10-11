<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Fuentes de información';
$descripcion = 'Orígenes de donde vienen tus datos.';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && puede_editar()) {
  $op = $_POST['op'] ?? '';
  $nombre = trim($_POST['nombre'] ?? '');
  $tipo = $_POST['tipo'] ?? 'Otra';
  $validos = ['Base de datos','Archivo CSV','Archivo JSON','API','Sistema empresarial','Data lake simulado','Otra'];
  if (!in_array($tipo, $validos, true)) $tipo = 'Otra';
  $descripcion = trim($_POST['descripcion'] ?? '');
  $ubicacion = trim($_POST['ubicacion'] ?? '');
  $responsable = trim($_POST['responsable'] ?? '');
  $estado = ($_POST['estado'] ?? 'activo') === 'inactivo' ? 'inactivo' : 'activo';
  $id = (int)($_POST['id_fuente'] ?? 0);
  try {
    if ($op === 'crear' || $op === 'editar') {
      if ($nombre === '') throw new Exception('El nombre es obligatorio.');
    }
  if ($op === 'crear') {
    $st = $db->prepare("INSERT INTO fuentes (nombre,tipo,descripcion,ubicacion,responsable,estado) VALUES (?,?,?,?,?,?)");
    $st->execute([$nombre,$tipo,$descripcion,$ubicacion,$responsable,$estado]);
    registrar_bitacora('crear_fuente','fuentes',"Creo fuente $nombre");
    flash_set('ok','Fuente creada.');
  } elseif ($op === 'editar') {
  $st = $db->prepare("UPDATE fuentes SET nombre=?,tipo=?,descripcion=?,ubicacion=?,responsable=?,estado=?,fecha_ultima_sincronizacion=NOW() WHERE id_fuente=?");
  $st->execute([$nombre,$tipo,$descripcion,$ubicacion,$responsable,$estado,$id]);
  registrar_bitacora('editar_fuente','fuentes',"Edito fuente id=$id");
  flash_set('ok','Fuente actualizada.');
} elseif ($op === 'eliminar' && es_admin()) {
$f = $db->prepare("SELECT * FROM fuentes WHERE id_fuente=?"); $f->execute([$id]); $frow = $f->fetch();
$acts = $db->prepare("SELECT * FROM activos WHERE id_fuente=?"); $acts->execute([$id]); $arows = $acts->fetchAll();
$mets = [];
if ($arows) {
  $in = implode(',', array_fill(0, count($arows), '?'));
  $mm = $db->prepare("SELECT * FROM metadatos WHERE id_activo IN ($in)"); $mm->execute(array_column($arows, 'id_activo')); $mets = $mm->fetchAll();
}
a_papelera('fuente', $id, $frow['nombre'] ?? "fuente $id", ['fuente' => $frow, 'activos' => $arows, 'metadatos' => $mets]);
$db->prepare("DELETE FROM fuentes WHERE id_fuente=?")->execute([$id]);
registrar_bitacora('eliminar_fuente','fuentes',"Elimino fuente id=$id (a papelera)");
flash_set('ok','Fuente eliminada (puedes restaurarla en Papelera).');
}
} catch (Exception $ex) { flash_set('error','Error: '.$ex->getMessage()); }
redirect(base_url('public/index.php?p=fuentes'));
}

$q = trim($_GET['q'] ?? '');
$tipo_f = $_GET['tipo'] ?? '';
$sql = "SELECT f.*, (SELECT COUNT(*) FROM activos a WHERE a.id_fuente=f.id_fuente) AS total_activos FROM fuentes f WHERE 1=1";
$params = [];
if ($q !== '') { $sql .= " AND (f.nombre LIKE ? OR f.descripcion LIKE ? OR f.responsable LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; $params[]="%$q%"; }
if ($tipo_f !== '') { $sql .= " AND f.tipo=?"; $params[]=$tipo_f; }
$sql .= " ORDER BY f.id_fuente DESC";
$st = $db->prepare($sql); $st->execute($params);
$fuentes = $st->fetchAll();
$edit = null;
if (!empty($_GET['editar']) && puede_editar()) {
  $s = $db->prepare("SELECT * FROM fuentes WHERE id_fuente=?"); $s->execute([(int)$_GET['editar']]); $edit = $s->fetch();
}
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <form method="get" class="filters">
<input type="hidden" name="p" value="fuentes">
    <input name="q" placeholder="Buscar fuente…" value="<?= e($q) ?>">
  <select name="tipo"><option value="">Todos los tipos</option>
    <?php foreach (['Base de datos','Archivo CSV','Archivo JSON','API','Sistema empresarial','Data lake simulado','Otra'] as $t): ?>
      <option <?= $tipo_f===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select>
<button class="btn">Filtrar</button>
</form>
<?php if (puede_editar()): ?><a class="btn btn-primary" href="<?= e(base_url('public/index.php?p=captura')) ?>">+ Nueva captura</a><?php endif; ?>
</div>

<?php if (puede_editar()): ?>
  <div class="card"><h3><?= $edit?'Editar fuente':'Agregar fuente' ?></h3>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="<?= $edit?'editar':'crear' ?>">
    <?php if ($edit): ?><input type="hidden" name="id_fuente" value="<?= (int)$edit['id_fuente'] ?>"><?php endif; ?>
  <label>Nombre*<input name="nombre" required value="<?= e($edit['nombre'] ?? '') ?>"></label>
<label>Tipo<select name="tipo"><?php foreach (['Base de datos','Archivo CSV','Archivo JSON','API','Sistema empresarial','Data lake simulado','Otra'] as $t): ?><option <?= ($edit['tipo']??'')===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
<label>Ubicación<input name="ubicacion" value="<?= e($edit['ubicacion'] ?? '') ?>" placeholder="mysql://localhost:3306/erp o /uploads/archivo.csv"></label>
<label>Responsable<input name="responsable" value="<?= e($edit['responsable'] ?? '') ?>"></label>
<label>Estado<select name="estado"><option value="activo">activo</option><option value="inactivo" <?= ($edit['estado']??'')==='inactivo'?'selected':'' ?>>inactivo</option></select></label>
<label class="full">Descripción<textarea name="descripcion" rows="2"><?= e($edit['descripcion'] ?? '') ?></textarea></label>
<div><button class="btn btn-primary">Guardar</button> <?php if($edit): ?><a class="btn" href="<?= e(base_url('public/index.php?p=fuentes')) ?>">Cancelar</a><?php endif; ?></div>
</form></div>
<?php endif; ?>

<div class="cards">
  <?php foreach ($fuentes as $f): ?>
    <div class="card fuente">
      <h3><?= e($f['nombre']) ?></h3>
      <p><span class="badge"><?= e($f['tipo']) ?></span> <span class="badge <?= $f['estado']==='activo'?'ok':'bad' ?>"><?= e($f['estado']) ?></span> <span class="muted"><?= (int)$f['total_activos'] ?> activos</span></p>
    <p class="muted"><?= e(mb_substr($f['descripcion'] ?? '', 0, 140)) ?></p>
    <p class="muted"> <?= e($f['ubicacion'] ?? '—') ?> · <?= e($f['responsable'] ?? '—') ?></p>
    <div class="actions">
      <a class="btn btn-sm" href="<?= e(base_url('public/index.php?p=activos&fuente='.$f['id_fuente'])) ?>">Ver activos</a>
    <?php if (puede_editar()): ?><a class="btn btn-sm" href="?p=fuentes&editar=<?= $f['id_fuente'] ?>">Editar</a>
    <?php if (es_admin()): ?><form method="post" style="display:inline" data-confirm="¿Eliminar fuente y sus activos?">
        <?= csrf_field() ?><input type="hidden" name="op" value="eliminar"><input type="hidden" name="id_fuente" value="<?= $f['id_fuente'] ?>"><button class="btn btn-sm danger">Eliminar</button></form><?php endif; ?><?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
