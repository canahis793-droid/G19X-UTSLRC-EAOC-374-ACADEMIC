<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Calidad de metadatos';
$descripcion = 'Mide la salud de tus metadatos y corrige lo crítico.';
$db = getDB();

if (!empty($_GET['analizar']) && puede_editar()) {
  $ids = $_GET['analizar'] === 'todos'
  ? array_column($db->query("SELECT id_activo FROM activos")->fetchAll(), 'id_activo')
  : [(int)$_GET['analizar']];
  $n = 0;
  set_time_limit(300);
  foreach ($ids as $id) {
    $id = (int)$id;
    $st = $db->prepare("SELECT * FROM activos WHERE id_activo=?"); $st->execute([$id]); $a = $st->fetch();
    $m = $db->prepare("SELECT nombre_campo, descripcion, definicion_negocio, responsable FROM metadatos WHERE id_activo=?"); $m->execute([$id]); $mets = $m->fetchAll();
    if ($a) {
      $res = ejecutar_python('calidad.py', ['activo'=>$a, 'metadatos'=>$mets]);
      if (!empty($res['ok'])) {
        $db->prepare("INSERT INTO calidad_metadatos (id_activo,porcentaje,problemas,recomendaciones) VALUES (?,?,?,?)")
        ->execute([$id, $res['porcentaje'], json_encode($res['problemas'], JSON_UNESCAPED_UNICODE), json_encode($res['recomendaciones'], JSON_UNESCAPED_UNICODE)]);
        $n++;
      }
  }
}
registrar_bitacora('analisis_calidad','calidad',"Python evaluo $n activos (masivo)");
flash_set('ok',"Calidad calculada para $n activos.");
redirect(base_url('public/index.php?p=calidad'));
}
$rows = $db->query("SELECT a.id_activo, a.nombre, a.categoria, c.porcentaje, c.fecha_evaluacion FROM activos a LEFT JOIN calidad_metadatos c ON c.id_calidad=(SELECT MAX(id_calidad) FROM calidad_metadatos WHERE id_activo=a.id_activo) ORDER BY (c.porcentaje IS NULL), c.porcentaje ASC")->fetchAll();
$prom = $db->query("SELECT AVG(porcentaje) FROM calidad_metadatos WHERE id_calidad IN (SELECT MAX(id_calidad) FROM calidad_metadatos GROUP BY id_activo)")->fetchColumn();
include __DIR__ . '/../includes/header.php';
?>
<div class="cards"><div class="card stat"><span>Calidad promedio</span><strong><?= round((float)$prom,1) ?>%</strong></div>
  <div class="card stat danger"><span>Críticos (&lt;60%)</span><strong><?= count(array_filter($rows, fn($r)=>$r['porcentaje']!==null && $r['porcentaje']<60)) ?></strong></div>
  <?php if (puede_editar()): ?><div class="card stat"><span>Acción masiva</span><a class="btn btn-primary" href="?p=calidad&analizar=todos" data-confirm="¿Evaluar todos los activos con Python?">Evaluar todos</a></div><?php endif; ?></div>
<div class="card"><table class="table"><thead><tr><th>Activo</th><th>Categoría</th><th>Calidad</th><th>Evaluación</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><a href="<?= e(base_url('public/index.php?p=activos/detalle&id='.$r['id_activo'])) ?>"><?= e($r['nombre']) ?></a></td><td><?= e($r['categoria']) ?></td>
        <td><?php if ($r['porcentaje']===null): ?><span class="muted">sin evaluar</span><?php else: ?><span class="badge <?= $r['porcentaje']<60?'bad':($r['porcentaje']<80?'warn':'ok') ?>"><?= e($r['porcentaje']) ?>%</span><?php endif; ?></td>
        <td><small><?= e($r['fecha_evaluacion'] ?? '—') ?></small></td>
        <td><?php if (puede_editar()): ?><a class="btn btn-sm" href="?p=calidad&analizar=<?= $r['id_activo'] ?>">Evaluar (Python)</a><?php endif; ?></td></tr>
  <?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
