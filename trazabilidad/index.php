<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Trazabilidad y relaciones';
$descripcion = 'Descubre cómo se conectan tus activos.';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && puede_editar()) {
  $o = (int)$_POST['id_origen']; $d = (int)$_POST['id_destino'];
  $tipo = trim($_POST['tipo_relacion'] ?? 'relacionado');
  $desc = trim($_POST['descripcion'] ?? '');
  try {
    if ($o <= 0 || $d <= 0 || $o === $d) throw new Exception('Seleccione dos activos distintos.');
    $db->prepare("INSERT INTO relaciones_activos (id_origen,id_destino,tipo_relacion,descripcion) VALUES (?,?,?,?)")->execute([$o,$d,$tipo,$desc]);
    registrar_bitacora('crear_relacion','trazabilidad',"Relacion $o -> $d ($tipo)");
    flash_set('ok','Relación creada.');
  } catch (Exception $ex) { flash_set('error','Error: '.$ex->getMessage()); }
redirect(base_url('public/index.php?p=trazabilidad'));
}
if (!empty($_GET['eliminar']) && es_admin()) {
  $db->prepare("DELETE FROM relaciones_activos WHERE id_relacion=?")->execute([(int)$_GET['eliminar']]);
  registrar_bitacora('eliminar_relacion','trazabilidad','Elimino relacion id='.(int)$_GET['eliminar']);
  flash_set('ok','Relación eliminada.');
  redirect(base_url('public/index.php?p=trazabilidad'));
}
$activos = $db->query("SELECT id_activo, nombre FROM activos ORDER BY nombre")->fetchAll();
$rels = $db->query("SELECT r.*, a1.nombre AS origen, a2.nombre AS destino FROM relaciones_activos r JOIN activos a1 ON a1.id_activo=r.id_origen JOIN activos a2 ON a2.id_activo=r.id_destino ORDER BY r.id_relacion DESC")->fetchAll();
$foco = (int)($_GET['activo'] ?? 0);
include __DIR__ . '/../includes/header.php';
?>
<?php if (puede_editar()): ?>
  <div class="card"><h3>Nueva relación (Fuente → Tabla → Campo / API → Activo → Reporte)</h3>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <label>Origen<select name="id_origen"><?php foreach ($activos as $a): ?><option value="<?= $a['id_activo'] ?>" <?= $foco==$a['id_activo']?'selected':'' ?>><?= e($a['nombre']) ?></option><?php endforeach; ?></select></label>
    <label>Destino<select name="id_destino"><?php foreach ($activos as $a): ?><option value="<?= $a['id_activo'] ?>"><?= e($a['nombre']) ?></option><?php endforeach; ?></select></label>
  <label>Tipo<select name="tipo_relacion"><?php foreach (['uno-a-muchos','uno-a-uno','muchos-a-muchos','deriva','origen','agregacion','flujo','similitud','relacionado'] as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select></label>
  <label class="full">Descripción<input name="descripcion" placeholder="clientes.id_cliente -> facturas.id_cliente"></label>
  <div><button class="btn btn-primary">Guardar relación</button></div>
</form></div>
<?php endif; ?>
<div class="card"><h3>Mapa de relaciones</h3>
  <div id="traceMap" class="trace-map"></div>
  <p class="muted">Vista gráfica sencilla con HTML/CSS/JS. Cada tarjeta es un activo, las flechas indican el tipo de relación.</p>
</div>
<div class="card"><h3>Relaciones (<?= count($rels) ?>)</h3>
  <table class="table"><thead><tr><th>Origen</th><th></th><th>Destino</th><th>Tipo</th><th>Descripción</th><?php if(es_admin()): ?><th></th><?php endif; ?></tr></thead><tbody>
      <?php foreach ($rels as $r): ?><tr><td><strong><?= e($r['origen']) ?></strong></td><td>→</td><td><?= e($r['destino']) ?></td><td><span class="badge"><?= e($r['tipo_relacion']) ?></span></td><td><?= e($r['descripcion']) ?></td>
          <?php if(es_admin()): ?><td><a class="btn btn-sm danger" data-confirm="¿Eliminar?" href="?p=trazabilidad&eliminar=<?= $r['id_relacion'] ?>">Eliminar</a></td><?php endif; ?></tr><?php endforeach; ?>
  </tbody></table></div>
<script>window.__rels = <?= json_encode($rels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= e(base_url('assets/js/trazabilidad.js')) ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
