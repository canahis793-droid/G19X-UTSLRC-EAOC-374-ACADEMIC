<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT a.*, f.nombre AS fuente, f.tipo AS tipo_fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente WHERE a.id_activo=?");
$st->execute([$id]); $a = $st->fetch();
if (!$a) { die('Activo no encontrado.'); }
$titulo = 'Activo: ' . $a['nombre'];
$descripcion = 'Ficha completa del activo.';

$md = $db->prepare("SELECT * FROM metadatos WHERE id_activo=? ORDER BY tipo, id_metadato");
$md->execute([$id]); $metadatos = $md->fetchAll();
$et = $db->prepare("SELECT e.nombre FROM etiquetas e JOIN activo_etiqueta ae ON ae.id_etiqueta=e.id_etiqueta WHERE ae.id_activo=?");
$et->execute([$id]); $etiquetas = array_column($et->fetchAll(), 'nombre');
$rel = $db->prepare("SELECT r.id_relacion, r.tipo_relacion, r.descripcion, a2.nombre AS otro, 'saliente' AS direccion FROM relaciones_activos r JOIN activos a2 ON a2.id_activo=r.id_destino WHERE r.id_origen=? UNION ALL SELECT r.id_relacion, r.tipo_relacion, r.descripcion, a1.nombre AS otro, 'entrante' AS direccion FROM relaciones_activos r JOIN activos a1 ON a1.id_activo=r.id_origen WHERE r.id_destino=? ORDER BY id_relacion");
$rel->execute([$id,$id]); $relaciones = $rel->fetchAll();
$cal = $db->prepare("SELECT * FROM calidad_metadatos WHERE id_activo=? ORDER BY fecha_evaluacion DESC LIMIT 1");
$cal->execute([$id]); $calidad = $cal->fetch();
$cla = $db->prepare("SELECT * FROM clasificaciones WHERE id_activo=? ORDER BY fecha DESC LIMIT 1");
$cla->execute([$id]); $clas = $cla->fetch();
$enr = $db->prepare("SELECT * FROM enriquecimientos WHERE id_activo=? ORDER BY fecha DESC LIMIT 1");
$enr->execute([$id]); $enri = $enr->fetch();

include __DIR__ . '/../includes/header.php';
?>
<div class="grid-2">
  <div class="card ficha">
    <div class="ficha-top"><div><h3><?= e($a['nombre']) ?></h3>
        <div><span class="badge big"><?= e($a['categoria']) ?></span> <span class="badge <?= $a['estado']==='activo'?'ok':($a['estado']==='pendiente'?'warn':'bad') ?>"><?= e($a['estado']) ?></span> <span class="badge"><?= e($a['tipo_activo']) ?></span></div></div>
  </div>
<p class="ficha-desc"><?= e($a['descripcion'] ?? 'Sin descripción') ?></p>
<dl class="meta-list">
  <div><dt>Fuente</dt><dd><?= e($a['fuente']) ?> · <?= e($a['tipo_fuente']) ?></dd></div>
  <div><dt>Propietario</dt><dd><?= e($a['propietario'] ?? '— Sin asignar') ?></dd></div>
  <div><dt>Área</dt><dd><?= e($a['area_responsable'] ?? '—') ?></dd></div>
  <div><dt>Etiquetas</dt><dd><?php foreach ($etiquetas as $t): ?><span class="badge"><?= e($t) ?></span> <?php endforeach; ?><?php if (!$etiquetas): ?><span class="muted">—</span><?php endif; ?></dd></div>
</dl>
<?php if (puede_editar()): ?>
  <div class="actions">
    <a class="btn btn-sm btn-primary" href="<?= e(base_url('activos/editar.php?id='.$id)) ?>"> Editar</a>
  <a class="btn btn-sm" href="<?= e(base_url('metadatos/index.php?activo='.$id)) ?>"> Metadatos</a>
<a class="btn btn-sm" href="<?= e(base_url('calidad/index.php?analizar='.$id)) ?>"> Calidad Python</a>
<a class="btn btn-sm" href="<?= e(base_url('enriquecimiento/index.php?generar='.$id)) ?>"> Enriquecer</a>
</div>
<?php endif; ?>
</div>
<div>
  <div class="card">
    <div class="card-head"><h3> Calidad</h3><?php if ($calidad): ?><span class="badge <?= $calidad['porcentaje']<60?'bad':($calidad['porcentaje']<80?'warn':'ok') ?> big"><?= e($calidad['porcentaje']) ?>%</span><?php endif; ?></div>
  <?php if ($calidad): $prob = json_decode($calidad['problemas'] ?? '[]', true) ?: []; $rec = json_decode($calidad['recomendaciones'] ?? '[]', true) ?: [];
    $pct = (float)$calidad['porcentaje']; ?>
    <div class="conf big-bar"><i style="width:<?= min(100,$pct) ?>%"></i></div>
  <p><strong>A corregir:</strong></p><ul class="qa-list bad"><?php foreach ($prob as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
  <p><strong>Recomendado:</strong></p><ul class="qa-list ok"><?php foreach ($rec as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
<?php else: ?><p class="empty-note">Sin evaluar. Pulsa “Calidad Python” arriba. </p><?php endif; ?>
</div>
<div class="card">
  <div class="card-head"><h3> Clasificación</h3><?php if ($clas): ?><span class="badge <?= $clas['estado']==='validada'?'ok':'warn' ?>"><?= e($clas['estado']) ?></span><?php endif; ?></div>
<?php if ($clas): ?><p>Python sugiere <span class="badge big"><?= e($clas['categoria_sugerida']) ?></span></p>
  <div class="conf"><i style="width:<?= min(100,(float)$clas['confianza']) ?>%"></i></div><small class="muted">Confianza <?= e($clas['confianza']) ?>% · <?= e($clas['palabras_clave']) ?></small>
<?php else: ?><p class="empty-note">Sin clasificación todavía.</p><?php endif; ?>
</div>
<div class="card">
  <div class="card-head"><h3> Enriquecimiento</h3><?php if ($enri): ?><span class="badge"><?= e($enri['estado']) ?></span><?php endif; ?></div>
  <?php if ($enri): ?><p class="muted"><small>Sugerencia de Python</small></p><p><?= e($enri['descripcion_sugerida']) ?></p><p><small class="muted"><?= e($enri['palabras_clave']) ?> · <?= e($enri['categoria_sugerida']) ?></small></p>
  <?php else: ?><p class="empty-note">Sin sugerencias. Pulsa “Enriquecer” arriba. </p><?php endif; ?>
</div>
</div>
</div>

<div class="card">
  <h3>Metadatos (<?= count($metadatos) ?>)</h3>
  <table class="table"><thead><tr><th>Tipo</th><th>Campo</th><th>Dato</th><th>PK/FK</th><th>Nulos</th><th>Descripción / Negocio</th></tr></thead><tbody>
      <?php foreach ($metadatos as $m): ?>
        <tr><td><span class="badge"><?= e($m['tipo']) ?></span></td><td><?= e($m['nombre_campo']) ?></td><td><?= e($m['tipo_dato']) ?><?= $m['longitud'] ? '('.$m['longitud'].')' : '' ?></td>
          <td><?= $m['es_pk']?'PK ':'' ?><?= $m['es_fk']?'FK':'' ?></td><td><?= $m['permite_nulos']?'Sí':'No' ?></td>
          <td><?= e($m['descripcion'] ?: $m['definicion_negocio'] ?: '—') ?></td></tr>
      <?php endforeach; ?>
  </tbody></table>
</div>

<div class="card">
  <h3>Relaciones / Trazabilidad</h3>
  <div class="trace">
    <?php foreach ($relaciones as $r): ?>
      <?php if ($r['direccion'] === 'saliente'): ?>
        <span class="trace-node"><?= e($a['nombre']) ?> → <?= e($r['otro']) ?><small><?= e($r['tipo_relacion']) ?></small></span>
      <?php else: ?>
        <span class="trace-node"><?= e($r['otro']) ?> → <?= e($a['nombre']) ?><small><?= e($r['tipo_relacion']) ?></small></span>
      <?php endif; ?>
  <?php endforeach; ?>
<?php if (!$relaciones): ?><p class="muted">Sin relaciones registradas.</p><?php endif; ?>
</div>
<a class="btn btn-sm" href="<?= e(base_url('trazabilidad/index.php?activo='.$id)) ?>">Ver en trazabilidad</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
