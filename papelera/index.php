<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_admin();
$titulo = 'Papelera';
$descripcion = 'Restaura o elimina definitivamente lo borrado.';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['restaurar'])) {
  $id = (int)$_POST['restaurar'];
  $st = $db->prepare("SELECT * FROM papelera WHERE id_item=?"); $st->execute([$id]);
  $item = $st->fetch();
  try {
    if (!$item) throw new Exception('Elemento no encontrado.');
    $d = json_decode($item['datos'], true) ?: [];
    if ($item['tipo'] === 'fuente') {
      $f = $d['fuente'];
      $db->prepare("INSERT INTO fuentes (nombre,tipo,descripcion,ubicacion,responsable,estado) VALUES (?,?,?,?,?,?)")
      ->execute([$f['nombre'], $f['tipo'], $f['descripcion'], $f['ubicacion'], $f['responsable'], 'activo']);
      $nf = (int)$db->lastInsertId();
      $map = [];
      foreach (($d['activos'] ?? []) as $a) {
        $db->prepare("INSERT INTO activos (id_fuente,nombre,tipo_activo,descripcion,categoria,propietario,area_responsable,estado) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$nf, $a['nombre'], $a['tipo_activo'], $a['descripcion'], $a['categoria'], $a['propietario'], $a['area_responsable'], $a['estado']]);
        $map[$a['id_activo']] = (int)$db->lastInsertId();
      }
    foreach (($d['metadatos'] ?? []) as $m) {
      if (!isset($map[$m['id_activo']])) continue;
      $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,longitud,es_pk,es_fk,permite_nulos,formato,descripcion,responsable,definicion_negocio,importancia) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
      ->execute([$map[$m['id_activo']], $m['tipo'], $m['nombre_campo'], $m['tipo_dato'], $m['longitud'], $m['es_pk'], $m['es_fk'], $m['permite_nulos'], $m['formato'], $m['descripcion'], $m['responsable'], $m['definicion_negocio'], $m['importancia']]);
    }
} elseif ($item['tipo'] === 'activo') {
$a = $d['activo'];
$chk = $db->prepare("SELECT COUNT(*) FROM fuentes WHERE id_fuente=?"); $chk->execute([$a['id_fuente']]);
$fuente = $chk->fetchColumn() ? $a['id_fuente'] : (int)$db->query("SELECT id_fuente FROM fuentes ORDER BY id_fuente LIMIT 1")->fetchColumn();
$db->prepare("INSERT INTO activos (id_fuente,nombre,tipo_activo,descripcion,categoria,propietario,area_responsable,estado) VALUES (?,?,?,?,?,?,?,?)")
->execute([$fuente, $a['nombre'], $a['tipo_activo'], $a['descripcion'], $a['categoria'], $a['propietario'], $a['area_responsable'], $a['estado']]);
$na = (int)$db->lastInsertId();
foreach (($d['metadatos'] ?? []) as $m) {
  $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,longitud,es_pk,es_fk,permite_nulos,formato,descripcion,responsable,definicion_negocio,importancia) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
  ->execute([$na, $m['tipo'], $m['nombre_campo'], $m['tipo_dato'], $m['longitud'], $m['es_pk'], $m['es_fk'], $m['permite_nulos'], $m['formato'], $m['descripcion'], $m['responsable'], $m['definicion_negocio'], $m['importancia']]);
}
foreach (($d['etiquetas'] ?? []) as $t) {
  $db->prepare("INSERT IGNORE INTO etiquetas (nombre) VALUES (?)")->execute([$t]);
  $eid = $db->query("SELECT id_etiqueta FROM etiquetas WHERE nombre=" . $db->quote($t))->fetchColumn();
  if ($eid) $db->prepare("INSERT IGNORE INTO activo_etiqueta (id_activo,id_etiqueta) VALUES (?,?)")->execute([$na, $eid]);
}
} elseif ($item['tipo'] === 'usuario') {
$u = $d['usuario'];
$db->prepare("INSERT INTO usuarios (nombre, correo, password, id_rol, estado) SELECT ?,?,?,?,? FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE correo=?)")
->execute([$u['nombre'], $u['correo'], 'TEMP-' . bin2hex(random_bytes(8)), $u['id_rol'], 'inactivo', $u['correo']]);
if ($db->query("SELECT ROW_COUNT()")->fetchColumn() == 0) throw new Exception('Ya existe un usuario con ese correo.');
flash_set('ok', 'Usuario restaurado INACTIVO y sin contraseña: edítalo para activarlo.');
$db->prepare("DELETE FROM papelera WHERE id_item=?")->execute([$id]);
registrar_bitacora('restaurar', 'papelera', "Restauro usuario {$u['correo']}");
redirect(base_url('public/index.php?p=papelera'));
} else { throw new Exception('Tipo desconocido.'); }
$db->prepare("DELETE FROM papelera WHERE id_item=?")->execute([$id]);
registrar_bitacora('restaurar', 'papelera', "Restauro {$item['tipo']} {$item['nombre']}");
flash_set('ok', 'Restaurado correctamente.');
} catch (PDOException $ex) {
  error_log('papelera: ' . $ex->getMessage());
  flash_set('error', 'No se pudo restaurar el elemento. Intente de nuevo.');
} catch (Exception $ex) { flash_set('error', 'Error: ' . $ex->getMessage()); }
redirect(base_url('public/index.php?p=papelera'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['purgar'])) {
  $db->prepare("DELETE FROM papelera WHERE id_item=?")->execute([(int)$_POST['purgar']]);
  registrar_bitacora('purgar', 'papelera', 'Elimino definitivo id=' . (int)$_POST['purgar']);
  flash_set('ok', 'Eliminado definitivamente.');
  redirect(base_url('public/index.php?p=papelera'));
}
$items = $db->query("SELECT p.*, u.nombre AS usuario FROM papelera p LEFT JOIN usuarios u ON u.id_usuario=p.id_usuario ORDER BY p.fecha DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-head"><h3> Elementos borrados (<?= count($items) ?>)</h3></div>
  <p class="muted">Fuentes, activos y usuarios borrados se guardan aquí con sus datos. Restaurar crea registros nuevos.</p>
  <table class="table"><thead><tr><th>Tipo</th><th>Nombre</th><th>Borrado</th><th>Por</th><th></th></tr></thead><tbody>
      <?php foreach ($items as $i): ?><tr><td><span class="badge"><?= e($i['tipo']) ?></span></td><td><strong><?= e($i['nombre']) ?></strong></td>
          <td><small><?= e($i['fecha']) ?></small></td><td><?= e($i['usuario'] ?? '—') ?></td>
          <td class="actions">
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="restaurar" value="<?= $i['id_item'] ?>"><button class="btn btn-sm btn-primary">Restaurar</button></form>
            <form method="post" data-confirm="¿Eliminar DEFINITIVAMENTE? No se puede deshacer." style="display:inline"><?= csrf_field() ?><input type="hidden" name="purgar" value="<?= $i['id_item'] ?>"><button class="btn btn-sm danger">Eliminar</button></form>
          </td></tr><?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
