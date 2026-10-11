<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Dashboard';
$descripcion = 'Vista general del estado de tus datos.';
$db = getDB();

$tot = function($sql, $params=[]) use ($db) {
  $st = $db->prepare($sql); $st->execute($params);
  return (int)$st->fetchColumn();
};

$totalUsuarios = $tot("SELECT COUNT(*) FROM usuarios");
$totalFuentes = $tot("SELECT COUNT(*) FROM fuentes");
$totalActivos = $tot("SELECT COUNT(*) FROM activos");
$totalMetadatos = $tot("SELECT COUNT(*) FROM metadatos");
$tec = $tot("SELECT COUNT(*) FROM activos WHERE tipo_activo IN ('tabla','vista')");
$neg = $tot("SELECT COUNT(*) FROM activos WHERE tipo_activo IN ('archivo','dataset')");
$ops = $tot("SELECT COUNT(*) FROM activos WHERE tipo_activo IN ('api','reporte','aplicacion','otro')");
$pend = $tot("SELECT COUNT(*) FROM activos WHERE estado='pendiente'");
$bajaCalidad = (int)$db->query("SELECT COUNT(*) FROM calidad_metadatos WHERE porcentaje < 60")->fetchColumn();

$porCat = $db->query("SELECT categoria, COUNT(*) c FROM activos GROUP BY categoria ORDER BY c DESC")->fetchAll();
$porFuente = $db->query("SELECT f.nombre, COUNT(a.id_activo) c FROM fuentes f LEFT JOIN activos a ON a.id_fuente=f.id_fuente GROUP BY f.id_fuente, f.nombre ORDER BY c DESC LIMIT 8")->fetchAll();
$ultimas = $db->query("SELECT b.*, u.nombre AS usuario FROM bitacora b LEFT JOIN usuarios u ON u.id_usuario=b.id_usuario ORDER BY b.fecha DESC LIMIT 6")->fetchAll();
$problemas = $db->query("SELECT a.id_activo, a.nombre, c.porcentaje FROM calidad_metadatos c JOIN activos a ON a.id_activo=c.id_activo ORDER BY c.porcentaje ASC LIMIT 5")->fetchAll();

$hora = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
include __DIR__ . '/../includes/header.php';
?>
<div class="hero slim">
  <div class="hero-text"><h2><?= e($saludo) ?>, <?= e(strtok($_SESSION['nombre'] ?? 'Usuario', ' ')) ?> </h2>
    <p><?= $totalActivos ?> activos bajo control · <?= count($porCat) ?> categorías · <?= $totalMetadatos ?> metadatos</p></div>
  <div class="hero-actions">
    <a class="btn btn-light" href="<?= e(base_url('public/index.php?p=busqueda')) ?>"> Buscar</a>
  <?php if (puede_editar()): ?>
    <a class="btn btn-light" href="<?= e(base_url('public/index.php?p=activos/crear')) ?>"> Nuevo</a>
  <a class="btn btn-light" href="<?= e(base_url('public/index.php?p=captura')) ?>"> Capturar</a>
<a class="btn btn-light" href="<?= e(base_url('public/index.php?p=calidad')) ?>"> Calidad</a>
<?php endif; ?>
</div>
</div>

<?php if ($pend > 0 || $bajaCalidad > 0): ?>
  <div class="alert-strip">
    <?php if ($pend > 0): ?><span> <strong><?= $pend ?> pendientes</strong> de validación <a href="<?= e(base_url('public/index.php?p=clasificacion')) ?>">revisar →</a></span><?php endif; ?>
  <?php if ($bajaCalidad > 0): ?><span> <strong><?= $bajaCalidad ?> en calidad crítica</strong> <a href="<?= e(base_url('public/index.php?p=calidad')) ?>">atender →</a></span><?php endif; ?>
</div>
<?php endif; ?>

<div class="cards stats kpi">
  <div class="card stat"><div><span>Usuarios</span><strong><?= $totalUsuarios ?></strong></div></div>
  <div class="card stat"><div><span>Fuentes</span><strong><?= $totalFuentes ?></strong></div></div>
  <div class="card stat"><div><span>Activos</span><strong><?= $totalActivos ?></strong></div></div>
  <div class="card stat"><div><span>Metadatos</span><strong><?= $totalMetadatos ?></strong></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><h3>Activos por categoría</h3><a href="<?= e(base_url('public/index.php?p=reportes')) ?>">Ver reportes →</a></div>
  <canvas id="chartCat" width="400" height="220"></canvas>
</div>
<div class="card">
  <h3>Activos por fuente</h3>
  <canvas id="chartFuente" width="400" height="220"></canvas>
  <p class="muted">Estructurados (tablas/vistas): <?= $tec ?> · Archivos y datasets: <?= $neg ?> · APIs y reportes: <?= $ops ?></p>
</div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><h3> Necesitan tu atención</h3><a href="<?= e(base_url('public/index.php?p=calidad')) ?>">Ir a calidad →</a></div>
  <table class="table"><thead><tr><th>Activo</th><th>Calidad</th><th></th></tr></thead><tbody>
      <?php foreach ($problemas as $p): ?>
        <tr><td><?= e($p['nombre']) ?></td><td><span class="badge <?= $p['porcentaje']<60?'bad':($p['porcentaje']<80?'warn':'ok') ?>"><?= e($p['porcentaje']) ?>%</span></td>
        <td><a href="<?= e(base_url('public/index.php?p=activos/detalle&id='.$p['id_activo'])) ?>">Ver</a></td></tr>
  <?php endforeach; ?>
</tbody></table>
</div>
<div class="card">
  <div class="card-head"><h3> Actividad reciente</h3><?php if (es_admin()): ?><a href="<?= e(base_url('public/index.php?p=bitacora')) ?>">Ver bitácora →</a><?php endif; ?></div>
<ul class="timeline">
  <?php foreach ($ultimas as $b): ?>
    <li><b><?= e($b['usuario'] ?? '—') ?></b> · <?= e($b['accion']) ?> <small>(<?= e($b['modulo']) ?> · <?= e($b['fecha']) ?>)</small><br><span class="muted"><?= e(mb_substr($b['descripcion'] ?? '', 0, 120)) ?></span></li>
  <?php endforeach; ?>
</ul>
</div>
</div>

<script>
  window.__catData = <?= json_encode($porCat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  window.__fuenteData = <?= json_encode($porFuente, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= e(base_url('assets/js/dashboard.js')) ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
