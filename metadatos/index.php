<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Gestión de metadatos';
$descripcion = 'Detalle técnico, operativo y de negocio por campo.';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && puede_editar()) {
  $op = $_POST['op'] ?? '';
  try {
    if ($op === 'crear' || $op === 'editar') {
      $id_activo = (int)$_POST['id_activo'];
      $tipo = $_POST['tipo'] ?? 'tecnico';
      if (!in_array($tipo, ['tecnico','operativo','negocio'], true)) $tipo = 'tecnico';
      $campo = trim($_POST['nombre_campo'] ?? '');
      if ($campo === '' || $id_activo <= 0) throw new Exception('Activo y nombre de campo obligatorios.');
      $data = [
      $id_activo, $tipo, $campo,
      $_POST['tipo_dato'] ?: null, $_POST['longitud'] !== '' ? (int)$_POST['longitud'] : null,
      !empty($_POST['es_pk']) ? 1 : 0, !empty($_POST['es_fk']) ? 1 : 0,
      isset($_POST['permite_nulos']) ? 1 : 0,
      $_POST['formato'] ?: null, $_POST['descripcion'] ?: null,
      $_POST['frecuencia_actualizacion'] ?: null, $_POST['responsable'] ?: null,
      $_POST['proceso_relacionado'] ?: null, $_POST['definicion_negocio'] ?: null,
      $_POST['area_responsable'] ?: null, $_POST['importancia'] ?: 'media',
      $_POST['uso_principal'] ?: null, $_POST['clasificacion'] ?: null, $_POST['regla_negocio'] ?: null
      ];
      if ($op === 'crear') {
        $st = $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,longitud,es_pk,es_fk,permite_nulos,formato,descripcion,frecuencia_actualizacion,responsable,proceso_relacionado,definicion_negocio,area_responsable,importancia,uso_principal,clasificacion,regla_negocio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->execute($data);
        registrar_bitacora('crear_metadato','metadatos',"Creo metadato $campo en activo $id_activo");
      } else {
      $idm = (int)$_POST['id_metadato'];
      $st = $db->prepare("UPDATE metadatos SET id_activo=?,tipo=?,nombre_campo=?,tipo_dato=?,longitud=?,es_pk=?,es_fk=?,permite_nulos=?,formato=?,descripcion=?,frecuencia_actualizacion=?,responsable=?,proceso_relacionado=?,definicion_negocio=?,area_responsable=?,importancia=?,uso_principal=?,clasificacion=?,regla_negocio=? WHERE id_metadato=?");
      $data[] = $idm; $st->execute($data);
      registrar_bitacora('editar_metadato','metadatos',"Edito metadato id=$idm");
    }
  flash_set('ok','Metadato guardado.');
} elseif ($op === 'eliminar' && es_admin()) {
$db->prepare("DELETE FROM metadatos WHERE id_metadato=?")->execute([(int)$_POST['id_metadato']]);
registrar_bitacora('eliminar_metadato','metadatos','Elimino metadato id='.(int)$_POST['id_metadato']);
flash_set('ok','Metadato eliminado.');
}
} catch (Exception $ex) { flash_set('error','Error: '.$ex->getMessage()); }
redirect(base_url('public/index.php?p=metadatos&activo='.(int)($_POST['id_activo'] ?? $_GET['activo'] ?? 0)));
}

$filtroActivo = (int)($_GET['activo'] ?? 0);
$filtroTipo = $_GET['tipo'] ?? '';
$sql = "SELECT m.*, a.nombre AS activo FROM metadatos m JOIN activos a ON a.id_activo=m.id_activo WHERE 1=1";
$p = [];
if ($filtroActivo > 0) { $sql .= " AND m.id_activo=?"; $p[] = $filtroActivo; }
if ($filtroTipo !== '') { $sql .= " AND m.tipo=?"; $p[] = $filtroTipo; }
$sql .= " ORDER BY m.id_metadato DESC";
$csql = "SELECT COUNT(*) " . substr($sql, strpos($sql, 'FROM'));
$cst = $db->prepare($csql); $cst->execute($p);
[$por, $off, $pag, $maxpag] = paginar((int)$cst->fetchColumn());
$st = $db->prepare($sql . " LIMIT $por OFFSET $off"); $st->execute($p);
$rows = $st->fetchAll();
$activos = $db->query("SELECT id_activo, nombre FROM activos ORDER BY nombre")->fetchAll();
$edit = null;
if (!empty($_GET['editar']) && puede_editar()) {
  $s = $db->prepare("SELECT * FROM metadatos WHERE id_metadato=?"); $s->execute([(int)$_GET['editar']]); $edit = $s->fetch();
}
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><form class="filters" method="get">
<input type="hidden" name="p" value="metadatos">
    <select name="activo"><option value="0">Todos los activos</option><?php foreach ($activos as $a): ?><option value="<?= $a['id_activo'] ?>" <?= $filtroActivo==$a['id_activo']?'selected':'' ?>><?= e($a['nombre']) ?></option><?php endforeach; ?></select>
  <select name="tipo"><option value="">Técnico+Operativo+Negocio</option><?php foreach (['tecnico','operativo','negocio'] as $t): ?><option <?= $filtroTipo===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select>
<button class="btn">Filtrar</button></form></div>

<?php if (puede_editar()): ?>
  <div class="card"><h3><?= $edit?'Editar metadato':'Nuevo metadato' ?></h3>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="<?= $edit?'editar':'crear' ?>">
    <?php if ($edit): ?><input type="hidden" name="id_metadato" value="<?= (int)$edit['id_metadato'] ?>"><?php endif; ?>
  <label>Activo<select name="id_activo"><?php foreach ($activos as $a): ?><option value="<?= $a['id_activo'] ?>" <?= ($edit['id_activo'] ?? $filtroActivo)==$a['id_activo']?'selected':'' ?>><?= e($a['nombre']) ?></option><?php endforeach; ?></select></label>
<label>Tipo<select name="tipo"><?php foreach (['tecnico','operativo','negocio'] as $t): ?><option <?= ($edit['tipo']??'tecnico')===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
<label>Nombre campo*<input name="nombre_campo" required value="<?= e($edit['nombre_campo'] ?? '') ?>"></label>
<label>Tipo dato<input name="tipo_dato" value="<?= e($edit['tipo_dato'] ?? '') ?>" placeholder="VARCHAR, INT, DATE…"></label>
<label>Longitud<input type="number" name="longitud" value="<?= e($edit['longitud'] ?? '') ?>"></label>
<label>Formato<input name="formato" value="<?= e($edit['formato'] ?? '') ?>"></label>
<label>Responsable<input name="responsable" value="<?= e($edit['responsable'] ?? '') ?>"></label>
<label>Frecuencia<input name="frecuencia_actualizacion" value="<?= e($edit['frecuencia_actualizacion'] ?? '') ?>"></label>
<label>Importancia<select name="importancia"><?php foreach (['baja','media','alta','critica'] as $i): ?><option <?= ($edit['importancia']??'media')===$i?'selected':'' ?>><?= e($i) ?></option><?php endforeach; ?></select></label>
<label><input type="checkbox" name="es_pk" <?= !empty($edit['es_pk'])?'checked':'' ?>> Llave primaria</label>
<label><input type="checkbox" name="es_fk" <?= !empty($edit['es_fk'])?'checked':'' ?>> Llave foránea</label>
<label><input type="checkbox" name="permite_nulos" <?= !isset($edit) || !empty($edit['permite_nulos'])?'checked':'' ?>> Permite nulos</label>
<label class="full">Descripción técnica<textarea name="descripcion" rows="2"><?= e($edit['descripcion'] ?? '') ?></textarea></label>
<label class="full">Definición de negocio<textarea name="definicion_negocio" rows="2"><?= e($edit['definicion_negocio'] ?? '') ?></textarea></label>
<label>Uso principal<input name="uso_principal" value="<?= e($edit['uso_principal'] ?? '') ?>"></label>
<label>Proceso relacionado<input name="proceso_relacionado" value="<?= e($edit['proceso_relacionado'] ?? '') ?>"></label>
<label>Área responsable<input name="area_responsable" value="<?= e($edit['area_responsable'] ?? '') ?>"></label>
<label>Clasificación<input name="clasificacion" value="<?= e($edit['clasificacion'] ?? '') ?>"></label>
<label class="full">Regla de negocio<textarea name="regla_negocio" rows="2"><?= e($edit['regla_negocio'] ?? '') ?></textarea></label>
<div><button class="btn btn-primary">Guardar</button></div>
</form></div>
<?php endif; ?>

<div class="card"><h3>Metadatos (<?= count($rows) ?>)</h3>
  <table class="table"><thead><tr><th>Activo</th><th>Tipo</th><th>Campo</th><th>Dato</th><th>Definición</th><?php if(puede_editar()): ?><th>Acciones</th><?php endif; ?></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td><?= e($r['activo']) ?></td><td><span class="badge"><?= e($r['tipo']) ?></span></td><td><?= e($r['nombre_campo']) ?></td><td><?= e($r['tipo_dato']) ?></td><td><?= e(mb_substr($r['descripcion'] ?: $r['definicion_negocio'] ?? '',0,90)) ?></td>
          <?php if(puede_editar()): ?><td><a class="btn btn-sm" href="?p=metadatos&editar=<?= $r['id_metadato'] ?>&activo=<?= $filtroActivo ?>">Editar</a></td><?php endif; ?></tr><?php endforeach; ?>
  </tbody></table>
<?= links_paginacion($pag, $maxpag) ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
