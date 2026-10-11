<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_admin();
$titulo = 'Bitácora del sistema';
$descripcion = 'Quién hizo qué y cuándo.';
$db = getDB();
$mod = $_GET['modulo'] ?? '';
$q = trim($_GET['q'] ?? '');
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$sql = "SELECT b.*, u.nombre AS usuario FROM bitacora b LEFT JOIN usuarios u ON u.id_usuario=b.id_usuario WHERE 1=1";
$p = [];
if ($mod !== '') { $sql .= " AND b.modulo=?"; $p[] = $mod; }
if ($q !== '') { $sql .= " AND (b.accion LIKE ? OR b.descripcion LIKE ?)"; $p[]="%$q%"; $p[]="%$q%"; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) { $sql .= " AND b.fecha >= ?"; $p[] = $desde . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) { $sql .= " AND b.fecha <= ?"; $p[] = $hasta . ' 23:59:59'; }
$sql .= " ORDER BY b.fecha DESC";
$csql = "SELECT COUNT(*) " . substr($sql, strpos($sql, 'FROM'));
$cst = $db->prepare($csql); $cst->execute($p);
[$por, $off, $pag, $maxpag] = paginar((int)$cst->fetchColumn(), 20);
$st = $db->prepare($sql . " LIMIT $por OFFSET $off"); $st->execute($p);
$rows = $st->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><form class="filters" method="get">
<input type="hidden" name="p" value="bitacora">
    <input name="q" placeholder="Buscar acción…" value="<?= e($q) ?>">
  <select name="modulo"><option value="">Todos los módulos</option><?php foreach (['auth','usuarios','fuentes','activos','metadatos','captura','clasificacion','enriquecimiento','busqueda','calidad','trazabilidad','reportes'] as $m): ?><option <?= $mod===$m?'selected':'' ?>><?= e($m) ?></option><?php endforeach; ?></select>
<input type="date" name="desde" value="<?= e($desde) ?>" title="Desde">
<input type="date" name="hasta" value="<?= e($hasta) ?>" title="Hasta">
<button class="btn">Filtrar</button>
<a class="btn" href="<?= e(base_url('public/index.php?p=reportes/exportar&tipo=bitacora')) ?>">Exportar CSV</a></form></div>
<div class="card"><table class="table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Descripción</th><th>IP</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td><small><?= e($r['fecha']) ?></small></td><td><?= e($r['usuario'] ?? '—') ?></td><td><?= e($r['accion']) ?></td><td><span class="badge"><?= e($r['modulo']) ?></span></td><td><?= e(mb_substr($r['descripcion'] ?? '',0,120)) ?></td><td><?= e($r['ip']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
<?= links_paginacion($pag, $maxpag) ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
