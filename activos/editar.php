<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$titulo = 'Editar activo';
$descripcion = 'Corrige la información del activo.';
$db = getDB();
$id = (int)($_GET['id'] ?? $_POST['id_activo'] ?? 0);
$st = $db->prepare("SELECT * FROM activos WHERE id_activo=?"); $st->execute([$id]);
$activo = $st->fetch();
if (!$activo) { die('Activo no encontrado.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['op'] ?? '') === 'eliminar' && es_admin()) {
    $m = $db->prepare("SELECT * FROM metadatos WHERE id_activo=?"); $m->execute([$id]);
    $et = $db->prepare("SELECT e.nombre FROM etiquetas e JOIN activo_etiqueta ae ON ae.id_etiqueta=e.id_etiqueta WHERE ae.id_activo=?"); $et->execute([$id]);
    a_papelera('activo', $id, $activo['nombre'], ['activo' => $activo, 'metadatos' => $m->fetchAll(), 'etiquetas' => array_column($et->fetchAll(), 'nombre')]);
    $db->prepare("DELETE FROM activos WHERE id_activo=?")->execute([$id]);
    registrar_bitacora('eliminar_activo', 'activos', "Elimino activo id=$id (a papelera)");
    flash_set('ok', 'Activo eliminado (restaurable en Papelera).');
    redirect(base_url('public/index.php?p=activos'));
  }
$nombre = trim($_POST['nombre'] ?? '');
try {
  if ($nombre === '') throw new Exception('Nombre obligatorio.');
  $s = $db->prepare("UPDATE activos SET id_fuente=?,nombre=?,tipo_activo=?,descripcion=?,categoria=?,propietario=?,area_responsable=?,estado=?,fecha_actualizacion=NOW() WHERE id_activo=?");
  $s->execute([(int)$_POST['id_fuente'],$nombre,$_POST['tipo_activo'],$_POST['descripcion'],$_POST['categoria'],$_POST['propietario'] ?: null,$_POST['area_responsable'] ?: null,$_POST['estado'],$id]);
  $tags = array_filter(array_map('trim', explode(',', $_POST['etiquetas'] ?? '')));
  $db->prepare("DELETE FROM activo_etiqueta WHERE id_activo=?")->execute([$id]);
  foreach ($tags as $t) {
    $t = mb_substr($t, 0, 80);
    $db->prepare("INSERT IGNORE INTO etiquetas (nombre) VALUES (?)")->execute([$t]);
    $eid = $db->query("SELECT id_etiqueta FROM etiquetas WHERE nombre=" . $db->quote($t))->fetchColumn();
    if ($eid) { $db->prepare("INSERT IGNORE INTO activo_etiqueta (id_activo,id_etiqueta) VALUES (?,?)")->execute([$id,$eid]); }
  }
registrar_bitacora('editar_activo','activos',"Edito activo id=$id");
flash_set('ok','Activo actualizado.');
redirect(base_url('public/index.php?p=activos/detalle&id='.$id));
} catch (Exception $ex) { flash_set('error','Error: '.$ex->getMessage()); }
}
$fuentes = $db->query("SELECT * FROM fuentes ORDER BY nombre")->fetchAll();
$ets = $db->prepare("SELECT e.nombre FROM etiquetas e JOIN activo_etiqueta ae ON ae.id_etiqueta=e.id_etiqueta WHERE ae.id_activo=?");
$ets->execute([$id]); $actual = implode(', ', array_column($ets->fetchAll(), 'nombre'));
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><h3>Editar activo #<?= $id ?></h3>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="id_activo" value="<?= $id ?>">
  <label>Fuente<select name="id_fuente"><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>" <?= $activo['id_fuente']==$f['id_fuente']?'selected':'' ?>><?= e($f['nombre']) ?></option><?php endforeach; ?></select></label>
<label>Nombre*<input name="nombre" required value="<?= e($activo['nombre']) ?>"></label>
<label>Tipo<select name="tipo_activo"><?php foreach (['tabla','archivo','api','dataset','reporte','aplicacion','vista','otro'] as $t): ?><option <?= $activo['tipo_activo']===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
<label>Categoría<select name="categoria"><?php foreach (['Cliente','Usuario','Venta','Producto','Inventario','Finanzas','Recursos Humanos','Operaciones','Otro'] as $c): ?><option <?= $activo['categoria']===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
<label>Propietario<input name="propietario" value="<?= e($activo['propietario'] ?? '') ?>"></label>
<label>Área<input name="area_responsable" value="<?= e($activo['area_responsable'] ?? '') ?>"></label>
<label>Estado<select name="estado"><?php foreach (['activo','pendiente','inactivo'] as $es): ?><option <?= $activo['estado']===$es?'selected':'' ?>><?= e($es) ?></option><?php endforeach; ?></select></label>
<label class="full">Descripción<textarea name="descripcion" rows="3"><?= e($activo['descripcion']) ?></textarea></label>
<label class="full">Etiquetas (separadas por coma)<input name="etiquetas" value="<?= e($actual) ?>" placeholder="ventas, critico"></label>
<div><button class="btn btn-primary">Guardar</button> <a class="btn" href="<?= e(base_url('public/index.php?p=activos/detalle&id='.$id)) ?>">Cancelar</a></div>
</form>
<?php if (es_admin()): ?>
  <form method="post" data-confirm="¿Eliminar este activo? Se guardará en Papelera." style="margin-top:10px">
    <?= csrf_field() ?>
    <input type="hidden" name="id_activo" value="<?= $id ?>">
  <input type="hidden" name="op" value="eliminar">
  <button class="btn danger"> Eliminar activo</button>
</form>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
