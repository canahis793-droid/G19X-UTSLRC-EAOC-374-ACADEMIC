<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$titulo = 'Enriquecimiento de metadatos';
$descripcion = 'Acepta descripciones y palabras clave generadas por Python.';
$db = getDB();

if (!empty($_GET['generar'])) {
  $id = (int)$_GET['generar'];
  $st = $db->prepare("SELECT * FROM activos WHERE id_activo=?"); $st->execute([$id]); $a = $st->fetch();
  $m = $db->prepare("SELECT * FROM metadatos WHERE id_activo=?"); $m->execute([$id]); $mets = $m->fetchAll();
  $todos = $db->query("SELECT id_activo AS id, nombre, descripcion FROM activos WHERE id_activo != $id LIMIT 50")->fetchAll();
  if ($a) {
    $res = ejecutar_python('enriquecimiento.py', ['activo'=>['id'=>$id,'nombre'=>$a['nombre'],'descripcion'=>$a['descripcion'],'propietario'=>$a['propietario']], 'metadatos'=>array_map(fn($x)=>['nombre_campo'=>$x['nombre_campo']], $mets), 'todos'=>$todos]);
    if (!empty($res['ok'])) {
      $db->prepare("INSERT INTO enriquecimientos (id_activo,descripcion_sugerida,palabras_clave,categoria_sugerida,relaciones_sugeridas,calidad_estimada,estado) VALUES (?,?,?,?,?,?, 'pendiente')")
      ->execute([$id, $res['descripcion_sugerida'] ?? '', implode(', ', $res['palabras_clave'] ?? []), $res['categoria_sugerida'] ?? 'Otro', json_encode($res['relaciones_sugeridas'] ?? [], JSON_UNESCAPED_UNICODE), $res['calidad_estimada'] ?? 0]);
      registrar_bitacora('enriquecimiento','enriquecimiento',"Python genero sugerencias para activo id=$id");
      flash_set('ok','Sugerencias generadas por Python. Revísalas abajo.');
    } else { flash_set('error','Python falló: '.($res['error'] ?? '')); }
}
redirect(base_url('public/index.php?p=enriquecimiento'));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ide = (int)($_POST['id_enriquecimiento'] ?? 0);
  $acc = $_POST['accion'] ?? '';
  $s = $db->prepare("SELECT * FROM enriquecimientos WHERE id_enriquecimiento=?"); $s->execute([$ide]); $en = $s->fetch();
  if ($en) {
    if ($acc === 'aceptar') {
      $db->prepare("UPDATE enriquecimientos SET estado='aceptado' WHERE id_enriquecimiento=?")->execute([$ide]);
      // Aplicar descripcion y categoria si el steward lo aprueba
      $db->prepare("UPDATE activos SET descripcion=?, categoria=?, fecha_actualizacion=NOW() WHERE id_activo=?")->execute([$en['descripcion_sugerida'], $en['categoria_sugerida'], $en['id_activo']]);
      foreach (explode(',', $en['palabras_clave'] ?? '') as $kw) {
        $kw = trim($kw); if ($kw === '') continue;
        $db->prepare("INSERT IGNORE INTO etiquetas (nombre) VALUES (?)")->execute([mb_substr($kw,0,80)]);
        $eid = $db->query("SELECT id_etiqueta FROM etiquetas WHERE nombre=".$db->quote(mb_substr($kw,0,80)))->fetchColumn();
        if ($eid) $db->prepare("INSERT IGNORE INTO activo_etiqueta (id_activo,id_etiqueta) VALUES (?,?)")->execute([$en['id_activo'],$eid]);
      }
    registrar_bitacora('aceptar_enriquecimiento','enriquecimiento',"Acepto sugerencia id=$ide");
    flash_set('ok','Sugerencia aceptada y aplicada.');
  } else {
  $db->prepare("UPDATE enriquecimientos SET estado='rechazado' WHERE id_enriquecimiento=?")->execute([$ide]);
  flash_set('ok','Sugerencia rechazada.');
}
}
redirect(base_url('public/index.php?p=enriquecimiento'));
}
$pend = $db->query("SELECT e.*, a.nombre AS activo FROM enriquecimientos e JOIN activos a ON a.id_activo=e.id_activo WHERE e.estado='pendiente' ORDER BY e.fecha DESC")->fetchAll();
$activos = $db->query("SELECT * FROM activos ORDER BY nombre LIMIT 100")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><h3>Generar sugerencias (Python)</h3>
  <form method="get" class="filters">
<input type="hidden" name="p" value="enriquecimiento"><select name="generar"><?php foreach ($activos as $a): ?><option value="<?= $a['id_activo'] ?>"><?= e($a['nombre']) ?></option><?php endforeach; ?></select><button class="btn btn-primary">Generar</button></form>
<p class="muted">Python analiza nombre, campos y activos similares. Todo lo generado se marca como "sugerencia".</p></div>
<?php foreach ($pend as $p): $rels = json_decode($p['relaciones_sugeridas'] ?? '[]', true) ?: []; ?>
  <div class="card"><h3><?= e($p['activo']) ?> <span class="badge">sugerencia Python</span></h3>
    <p><?= e($p['descripcion_sugerida']) ?></p>
    <p><small class="muted">Palabras clave: <?= e($p['palabras_clave']) ?> · Categoría: <?= e($p['categoria_sugerida']) ?> · Calidad estimada: <?= e($p['calidad_estimada']) ?>%</small></p>
    <p><small>Relaciones sugeridas: <?php foreach ($rels as $r): ?><span class="badge"><?= e($r['activo']) ?> (<?= e($r['similitud']) ?>)</span> <?php endforeach; ?></small></p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="id_enriquecimiento" value="<?= $p['id_enriquecimiento'] ?>"><button name="accion" value="aceptar" class="btn btn-sm btn-primary">Aceptar y aplicar</button> <button name="accion" value="rechazar" class="btn btn-sm danger">Rechazar</button></form>
</div>
<?php endforeach; ?>
<?php if (!$pend): ?><div class="card"><p class="muted">No hay sugerencias pendientes.</p></div><?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
