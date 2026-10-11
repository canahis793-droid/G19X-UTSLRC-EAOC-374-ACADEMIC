<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Reportes';
$descripcion = 'Números y exportaciones en CSV.';
$db = getDB();
$porCat = $db->query("SELECT categoria, COUNT(*) c FROM activos GROUP BY categoria ORDER BY c DESC")->fetchAll();
$porFuente = $db->query("SELECT f.nombre, COUNT(a.id_activo) c FROM fuentes f LEFT JOIN activos a ON a.id_fuente=f.id_fuente GROUP BY f.id_fuente, f.nombre ORDER BY c DESC")->fetchAll();
$porTipo = $db->query("SELECT tipo_activo, COUNT(*) c FROM activos GROUP BY tipo_activo ORDER BY c DESC")->fetchAll();
$pend = $db->query("SELECT COUNT(*) FROM activos WHERE estado='pendiente'")->fetchColumn();
$sinDesc = $db->query("SELECT COUNT(*) FROM activos WHERE descripcion IS NULL OR descripcion=''")->fetchColumn();
$promCal = $db->query("SELECT AVG(porcentaje) FROM calidad_metadatos WHERE id_calidad IN (SELECT MAX(id_calidad) FROM calidad_metadatos GROUP BY id_activo)")->fetchColumn();
$porMeta = $db->query("SELECT tipo, COUNT(*) c FROM metadatos GROUP BY tipo")->fetchAll();
$metaMap = ['tecnico' => 0, 'operativo' => 0, 'negocio' => 0];
foreach ($porMeta as $r) { $metaMap[$r['tipo']] = (int)$r['c']; }
$totalMeta = array_sum($metaMap);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'guardar') {
  $nombre = trim($_POST['nombre'] ?? '');
  if ($nombre === '') { flash_set('error', 'Ponle un nombre al reporte.'); }
  else {
    $resumen = json_encode(['pendientes' => (int)$pend, 'sin_descripcion' => (int)$sinDesc, 'calidad_promedio' => round((float)$promCal, 1), 'metadatos' => $metaMap, 'por_categoria' => $porCat], JSON_UNESCAPED_UNICODE);
    $db->prepare("INSERT INTO reportes_guardados (nombre, tipo, id_usuario, resultado) VALUES (?,?,?,?)")->execute([$nombre, 'resumen_general', $_SESSION['user_id'], $resumen]);
    registrar_bitacora('guardar_reporte', 'reportes', "Guardo reporte $nombre");
    flash_set('ok', 'Reporte guardado.');
  }
redirect(base_url('public/index.php?p=reportes'));
}
if (!empty($_GET['eliminar_rep']) && es_admin()) {
  $db->prepare("DELETE FROM reportes_guardados WHERE id_reporte=?")->execute([(int)$_GET['eliminar_rep']]);
  registrar_bitacora('eliminar_reporte', 'reportes', 'Elimino reporte id=' . (int)$_GET['eliminar_rep']);
  flash_set('ok', 'Reporte eliminado.');
  redirect(base_url('public/index.php?p=reportes'));
}
$guardados = $db->query("SELECT r.*, u.nombre AS usuario FROM reportes_guardados r LEFT JOIN usuarios u ON u.id_usuario=r.id_usuario ORDER BY r.fecha_generacion DESC LIMIT 20")->fetchAll();
$maxMeta = max(1, max($metaMap));
include __DIR__ . '/../includes/header.php';
?>
<div class="cards stats kpi">
  <div class="card stat warn"><div><span>Pendientes de validación</span><strong><?= (int)$pend ?></strong></div></div>
  <div class="card stat"><div><span>Sin descripción</span><strong><?= (int)$sinDesc ?></strong></div></div>
  <div class="card stat"><div><span>Calidad promedio</span><strong><?= round((float)$promCal,1) ?>%</strong></div></div>
  <div class="card stat"><div><span>Total metadatos</span><strong><?= $totalMeta ?></strong></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><h3>Metadatos por tipo</h3></div>
    <?php foreach (['tecnico' => 'Técnicos', 'operativo' => 'Operativos', 'negocio' => 'De negocio'] as $k => $label): $w = round($metaMap[$k] / $maxMeta * 100); ?>
      <div class="dist-row"><span class="dist-label"><?= $label ?></span><div class="bar"><i style="width:<?= $w ?>%"></i></div><b><?= $metaMap[$k] ?></b></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-head"><h3>Exportar</h3><span class="badge">CSV</span></div>
    <p class="muted">Descarga la información para Excel o tus reportes.</p>
    <div class="export-grid">
      <a class="btn" href="<?= e(base_url('public/index.php?p=reportes/exportar&tipo=activos')) ?>"><strong>Activos</strong><small>Catálogo completo</small></a>
      <a class="btn" href="<?= e(base_url('public/index.php?p=reportes/exportar&tipo=calidad')) ?>"><strong>Calidad</strong><small>Porcentaje por activo</small></a>
      <a class="btn" href="<?= e(base_url('public/index.php?p=reportes/exportar&tipo=metadatos')) ?>"><strong>Metadatos</strong><small>Detalle por campo</small></a>
      <a class="btn" href="<?= e(base_url('public/index.php?p=reportes/exportar&tipo=bitacora')) ?>"><strong>Bitácora</strong><small>Auditoría</small></a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>Reportes guardados</h3></div>
  <form method="post" class="filters">
    <?= csrf_field() ?><input type="hidden" name="op" value="guardar">
    <input name="nombre" required placeholder="Nombre del reporte…" maxlength="150">
    <button class="btn btn-primary btn-sm">Guardar foto actual</button>
  </form>
  <table class="table"><thead><tr><th>Nombre</th><th>Fecha</th><th>Por</th><th>Resumen</th><?php if (es_admin()): ?><th></th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($guardados as $g): $rj = json_decode($g['resultado'] ?? '{}', true) ?: []; ?>
      <tr>
        <td><strong><?= e($g['nombre']) ?></strong></td>
        <td><small><?= e($g['fecha_generacion']) ?></small></td>
        <td><?= e($g['usuario'] ?? '—') ?></td>
        <td><small class="muted">Pend: <?= (int)($rj['pendientes'] ?? 0) ?> · Sin desc: <?= (int)($rj['sin_descripcion'] ?? 0) ?> · Cal: <?= e($rj['calidad_promedio'] ?? '—') ?>%</small></td>
        <?php if (es_admin()): ?><td><a class="btn btn-sm danger" data-confirm="¿Eliminar reporte guardado?" href="?p=reportes&eliminar_rep=<?= $g['id_reporte'] ?>">Eliminar</a></td><?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody></table>
</div>

<div class="grid-3">
  <div class="card">
    <div class="card-head"><h3>Por categoría</h3></div>
    <?php $m = max(1, max(array_column($porCat, 'c') ?: [1])); foreach ($porCat as $r): ?>
      <div class="dist-row"><span class="dist-label"><?= e($r['categoria']) ?></span><div class="bar"><i style="width:<?= round($r['c']/$m*100) ?>%"></i></div><b><?= (int)$r['c'] ?></b></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-head"><h3>Por fuente</h3></div>
    <?php $m2 = max(1, max(array_column($porFuente, 'c') ?: [1])); foreach ($porFuente as $r): ?>
      <div class="dist-row"><span class="dist-label"><?= e($r['nombre']) ?></span><div class="bar"><i style="width:<?= round($r['c']/$m2*100) ?>%"></i></div><b><?= (int)$r['c'] ?></b></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-head"><h3>Por tipo</h3></div>
    <?php $m3 = max(1, max(array_column($porTipo, 'c') ?: [1])); foreach ($porTipo as $r): ?>
      <div class="dist-row"><span class="dist-label"><?= e($r['tipo_activo']) ?></span><div class="bar"><i style="width:<?= round($r['c']/$m3*100) ?>%"></i></div><b><?= (int)$r['c'] ?></b></div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
