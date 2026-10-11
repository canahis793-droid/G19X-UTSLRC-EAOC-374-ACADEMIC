<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$titulo = 'Clasificación automática';
$descripcion = 'Revisa y valida las categorías sugeridas por Python.';
$db = getDB();

if (!empty($_GET['analizar'])) {
  $ids = $_GET['analizar'] === 'todos'
  ? array_column($db->query("SELECT a.id_activo FROM activos a LEFT JOIN clasificaciones c ON c.id_activo=a.id_activo AND c.estado='pendiente' WHERE c.id_clasificacion IS NULL")->fetchAll(), 'id_activo')
  : [(int)$_GET['analizar']];
  $n = 0;
  set_time_limit(300);
  foreach ($ids as $id) {
    $id = (int)$id;
    $st = $db->prepare("SELECT * FROM activos WHERE id_activo=?"); $st->execute([$id]); $a = $st->fetch();
    if ($a) {
      $res = ejecutar_python('clasificador.py', ['nombre'=>$a['nombre'],'descripcion'=>$a['descripcion'] ?? '','texto'=>$a['nombre'].' '.($a['descripcion'] ?? '')]);
      $db->prepare("INSERT INTO clasificaciones (id_activo,categoria_sugerida,confianza,estado,palabras_clave) VALUES (?,?,?,'pendiente',?)")
      ->execute([$id, $res['categoria'] ?? 'Otro', $res['confianza'] ?? 0, implode(', ', $res['palabras_clave'] ?? [])]);
      $n++;
    }
}
registrar_bitacora('clasificacion','clasificacion',"Python clasifico $n activos (masivo)");
flash_set('ok',"Clasificaciones generadas: $n.");
redirect(base_url('public/index.php?p=clasificacion'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idc = (int)($_POST['id_clasificacion'] ?? 0);
  $accion = $_POST['accion'] ?? '';
  $s = $db->prepare("SELECT * FROM clasificaciones WHERE id_clasificacion=?"); $s->execute([$idc]); $c = $s->fetch();
  if ($c) {
    $cats = ['Cliente','Usuario','Venta','Producto','Inventario','Finanzas','Recursos Humanos','Operaciones','Otro'];
    if ($accion === 'validar') {
      $final = $_POST['categoria_final'] ?? $c['categoria_sugerida'];
      if (!in_array($final, $cats, true)) $final = $c['categoria_sugerida'];
      $final = $_POST['categoria_final'] ?? $c['categoria_sugerida'];
      $db->prepare("UPDATE clasificaciones SET categoria_final=?, estado='validada' WHERE id_clasificacion=?")->execute([$final,$idc]);
      $db->prepare("UPDATE activos SET categoria=?, estado='activo', fecha_actualizacion=NOW() WHERE id_activo=?")->execute([$final,$c['id_activo']]);
      registrar_bitacora('validar_clasificacion','clasificacion',"Valido id=$idc final=$final");
      flash_set('ok','Clasificación validada y aplicada al activo.');
    } elseif ($accion === 'rechazar') {
    $db->prepare("UPDATE clasificaciones SET estado='rechazada' WHERE id_clasificacion=?")->execute([$idc]);
    registrar_bitacora('rechazar_clasificacion','clasificacion',"Rechazo id=$idc");
    flash_set('ok','Sugerencia rechazada.');
  }
}
redirect(base_url('public/index.php?p=clasificacion'));
}
$pend = $db->query("SELECT c.*, a.nombre AS activo FROM clasificaciones c JOIN activos a ON a.id_activo=c.id_activo WHERE c.estado='pendiente' ORDER BY c.fecha DESC")->fetchAll();
$hist = $db->query("SELECT c.*, a.nombre AS activo FROM clasificaciones c JOIN activos a ON a.id_activo=c.id_activo WHERE c.estado!='pendiente' ORDER BY c.fecha DESC LIMIT 20")->fetchAll();
$sin = $db->query("SELECT a.id_activo, a.nombre, a.tipo_activo, f.nombre AS fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente LEFT JOIN clasificaciones c ON c.id_activo=a.id_activo AND c.estado='pendiente' WHERE c.id_clasificacion IS NULL ORDER BY a.nombre LIMIT 30")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-head"><h3> Sin analizar (<?= count($sin) ?>)</h3>
    <?php if ($sin): ?><a class="btn btn-primary btn-sm" href="?p=clasificacion&analizar=todos" data-confirm="¿Analizar todos con Python?"> Analizar todos</a><?php endif; ?></div>
  <p class="muted">Python sugerirá categoría, confianza y palabras clave para cada uno.</p>
  <?php if ($sin): ?>
    <table class="table"><thead><tr><th>Activo</th><th>Tipo</th><th>Fuente</th><th></th></tr></thead><tbody>
        <?php foreach ($sin as $a): ?>
          <tr><td><strong><?= e($a['nombre']) ?></strong></td><td><?= e($a['tipo_activo']) ?></td><td><?= e($a['fuente']) ?></td>
            <td><a class="btn btn-sm" href="?p=clasificacion&analizar=<?= $a['id_activo'] ?>">Analizar</a></td></tr>
      <?php endforeach; ?>
  </tbody></table>
<?php else: ?><p class="empty-note"> Todo analizado. Buen trabajo.</p><?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h3> Pendientes de revisión (<?= count($pend) ?>)</h3></div>
  <?php if ($pend): ?>
    <table class="table"><thead><tr><th>Activo</th><th>Sugerencia Python</th><th>Confianza</th><th>Palabras clave</th><th>Tu decisión</th></tr></thead><tbody>
        <?php foreach ($pend as $c): $conf = (float)$c['confianza']; ?>
          <tr><td><strong><?= e($c['activo']) ?></strong></td>
            <td><span class="badge big"><?= e($c['categoria_sugerida']) ?></span></td>
            <td><div class="conf"><i style="width:<?= min(100, $conf) ?>%"></i></div><small><?= e($c['confianza']) ?>%</small></td>
          <td><small class="muted"><?= e($c['palabras_clave']) ?></small></td>
          <td><form method="post" class="filters">
              <?= csrf_field() ?><input type="hidden" name="id_clasificacion" value="<?= $c['id_clasificacion'] ?>">
            <select name="categoria_final" title="Categoría final"><?php foreach (['Cliente','Usuario','Venta','Producto','Inventario','Finanzas','Recursos Humanos','Operaciones','Otro'] as $cat): ?><option <?= $cat===$c['categoria_sugerida']?'selected':'' ?>><?= e($cat) ?></option><?php endforeach; ?></select>
          <button name="accion" value="validar" class="btn btn-sm btn-primary">Validar</button>
          <button name="accion" value="rechazar" class="btn btn-sm danger">Rechazar</button></form></td></tr>
  <?php endforeach; ?>
</tbody></table>
<?php else: ?><p class="empty-note"> Nada pendiente. Todo validado.</p><?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h3> Historial</h3></div>
  <table class="table"><thead><tr><th>Activo</th><th>Sugerida</th><th>Final</th><th>Estado</th></tr></thead><tbody>
      <?php foreach ($hist as $c): ?><tr><td><?= e($c['activo']) ?></td><td><span class="badge"><?= e($c['categoria_sugerida']) ?></span></td><td><?= e($c['categoria_final'] ?? '—') ?></td><td><span class="badge <?= $c['estado']==='validada'?'ok':'bad' ?>"><?= e($c['estado']) ?></span></td></tr><?php endforeach; ?>
  </tbody></table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
