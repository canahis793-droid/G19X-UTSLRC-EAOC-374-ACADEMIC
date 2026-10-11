<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
$titulo = 'Búsqueda inteligente';
$descripcion = 'Encuentra activos aunque no recuerdes el nombre exacto.';
$db = getDB();
$q = trim($_GET['q'] ?? '');
$modo = $_GET['modo'] ?? 'inteligente';
$resultados = [];
$info = '';
// Historial: top global + mis recientes (desde bitacora)
$topBusq = $db->query("SELECT REPLACE(descripcion,'Busco: ','') AS q, COUNT(*) c FROM bitacora WHERE modulo='busqueda' AND accion='busqueda' GROUP BY q ORDER BY c DESC LIMIT 6")->fetchAll();
$misBusq = $db->prepare("SELECT DISTINCT REPLACE(descripcion,'Busco: ','') AS q FROM bitacora WHERE modulo='busqueda' AND accion='busqueda' AND id_usuario=? ORDER BY fecha DESC LIMIT 5");
$misBusq->execute([$_SESSION['user_id']]);
$misBusq = array_column($misBusq->fetchAll(), 'q');
if ($q !== '') {
  registrar_bitacora('busqueda', 'busqueda', "Busco: $q");
  if ($modo === 'normal') {
    $st = $db->prepare("SELECT a.*, f.nombre AS fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente WHERE a.nombre LIKE ? OR a.descripcion LIKE ? OR a.categoria LIKE ? LIMIT 50");
    $st->execute(["%$q%", "%$q%", "%$q%"]);
    foreach ($st->fetchAll() as $r) { $resultados[] = ['activo' => $r, 'score' => 1.0, 'origen' => 'MySQL LIKE']; }
    $info = 'Búsqueda normal MySQL (LIKE).';
  } else {
  $todos = $db->query("SELECT a.id_activo AS id, a.nombre, a.descripcion, a.categoria, a.tipo_activo AS tipo, f.nombre AS fuente, GROUP_CONCAT(e.nombre SEPARATOR ' ') AS etiquetas FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente LEFT JOIN activo_etiqueta ae ON ae.id_activo=a.id_activo LEFT JOIN etiquetas e ON e.id_etiqueta=ae.id_etiqueta GROUP BY a.id_activo")->fetchAll();
  $py = ejecutar_python('buscador.py', ['query' => $q, 'activos' => $todos]);
  if (!empty($py['ok'])) {
    $map = [];
    foreach ($todos as $t) { $map[$t['id']] = $t; }
    foreach (($py['resultados'] ?? []) as $r) {
      if (isset($map[$r['id']])) { $resultados[] = ['activo' => $map[$r['id']], 'score' => $r['score'], 'origen' => 'Python TF-IDF']; }
    }
  $info = 'Búsqueda inteligente Python (TF-IDF + coseno + sinónimos). Ej: "información de compradores" encuentra clientes/usuarios.';
} else {
$info = 'Python falló, usando MySQL. Error: ' . ($py['error'] ?? 'desconocido');
$st = $db->prepare("SELECT a.*, f.nombre AS fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente WHERE a.nombre LIKE ? OR a.descripcion LIKE ? LIMIT 50");
$st->execute(["%$q%", "%$q%"]);
foreach ($st->fetchAll() as $r) { $resultados[] = ['activo' => $r, 'score' => 0.5, 'origen' => 'Fallback MySQL']; }
}
}
}
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <form method="get" class="search-big">
<input type="hidden" name="p" value="busqueda">
    <input name="q" value="<?= e($q) ?>" placeholder='Prueba: "información de compradores"'>
  <select name="modo"><option value="inteligente" <?= $modo!=='normal'?'selected':'' ?>>Inteligente (Python)</option><option value="normal" <?= $modo==='normal'?'selected':'' ?>>Normal (MySQL)</option></select>
<button class="btn btn-primary">Buscar</button>
</form>
<?php if ($q !== ''): ?><p class="muted"><?= e($info) ?> · <?= count($resultados) ?> resultados</p><?php endif; ?>
<?php if ($q === ''): ?>
  <div class="grid-2" style="margin-top:12px">
    <div><strong> Más buscado</strong><br><?php foreach ($topBusq as $t): ?><a class="badge" href="?p=busqueda&q=<?= urlencode($t['q']) ?>"><?= e($t['q']) ?> (<?= (int)$t['c'] ?>)</a> <?php endforeach; ?>
    <?php if (!$topBusq): ?><small class="muted">Aún sin datos.</small><?php endif; ?></div>
  <div><strong> Mis recientes</strong><br><?php foreach ($misBusq as $m): ?><a class="badge" href="?p=busqueda&q=<?= urlencode($m) ?>"><?= e($m) ?></a> <?php endforeach; ?>
  <?php if (!$misBusq): ?><small class="muted">Haz tu primera búsqueda.</small><?php endif; ?></div>
</div>
<?php endif; ?>
</div>
<?php if ($q !== ''): ?>
  <div class="cards">
    <?php foreach ($resultados as $r): $a = $r['activo']; $pct = round($r['score']*100); ?>
      <div class="card">
        <h3><a href="<?= e(base_url('public/index.php?p=activos/detalle&id='.($a['id_activo'] ?? $a['id']))) ?>"><?= e($a['nombre']) ?></a></h3>
      <p class="muted"><?= e(mb_substr($a['descripcion'] ?? '',0,160)) ?></p>
      <p><span class="badge"><?= e($a['categoria'] ?? '') ?></span> <span class="badge"><?= e($a['tipo_activo'] ?? $a['tipo'] ?? '') ?></span> <span class="muted"><?= e($a['fuente'] ?? '') ?></span></p>
      <div class="bar-row"><div class="bar"><i style="width:<?= min(100,$pct) ?>%"></i></div><b><?= $pct ?>%</b> <small class="muted"><?= e($r['origen']) ?></small></div>
  </div>
<?php endforeach; ?>
<?php if (!$resultados): ?><div class="card"><p>No se encontraron activos. Prueba con sinónimos.</p></div><?php endif; ?>
</div>
<?php endif; ?>
<script src="<?= e(base_url('assets/js/busqueda.js')) ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
