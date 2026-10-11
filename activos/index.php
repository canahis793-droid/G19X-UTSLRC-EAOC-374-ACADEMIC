<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
$titulo = 'Catálogo de activos';
$descripcion = 'Explora, crea y edita los activos de información.';
$db = getDB();

$q = trim($_GET['q'] ?? '');
$cat = $_GET['categoria'] ?? '';
$fuente = (int)($_GET['fuente'] ?? 0);
$estado = $_GET['estado'] ?? '';

$sql = "SELECT a.*, f.nombre AS fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente WHERE 1=1";
$p = [];
if ($q !== '') { $sql .= " AND (a.nombre LIKE ? OR a.descripcion LIKE ?)"; $p[]="%$q%"; $p[]="%$q%"; }
if ($cat !== '') { $sql .= " AND a.categoria=?"; $p[]=$cat; }
if ($fuente > 0) { $sql .= " AND a.id_fuente=?"; $p[]=$fuente; }
if ($estado !== '') { $sql .= " AND a.estado=?"; $p[]=$estado; }
$sql .= " ORDER BY a.id_activo DESC";
$csql = "SELECT COUNT(*) " . substr($sql, strpos($sql, 'FROM'));
$cst = $db->prepare($csql); $cst->execute($p);
[$por, $off, $pag, $maxpag] = paginar((int)$cst->fetchColumn());
$st = $db->prepare($sql . " LIMIT $por OFFSET $off"); $st->execute($p);
$activos = $st->fetchAll();
$fuentes = $db->query("SELECT * FROM fuentes ORDER BY nombre")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <form class="filters" method="get">
<input type="hidden" name="p" value="activos">
    <input name="q" placeholder="Buscar activo…" value="<?= e($q) ?>">
  <select name="categoria"><option value="">Todas las categorías</option><?php foreach (['Cliente','Usuario','Venta','Producto','Inventario','Finanzas','Recursos Humanos','Operaciones','Otro'] as $c): ?><option <?= $cat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select>
<select name="fuente"><option value="0">Todas las fuentes</option><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>" <?= $fuente==$f['id_fuente']?'selected':'' ?>><?= e($f['nombre']) ?></option><?php endforeach; ?></select>
<select name="estado"><option value="">Todos los estados</option><?php foreach (['activo','pendiente','inactivo'] as $es): ?><option <?= $estado===$es?'selected':'' ?>><?= e($es) ?></option><?php endforeach; ?></select>
<button class="btn">Filtrar</button>
</form>
<?php if (puede_editar()): ?><a class="btn btn-primary" href="<?= e(base_url('public/index.php?p=activos/crear')) ?>">+ Nuevo activo</a><?php endif; ?>
</div>
<div class="card">
  <table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Categoría</th><th>Fuente</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
      <?php foreach ($activos as $a): ?>
        <tr><td><?= $a['id_activo'] ?></td><td><strong><?= e($a['nombre']) ?></strong><br><small class="muted"><?= e(mb_substr($a['descripcion'] ?? '',0,80)) ?></small></td>
          <td><?= e($a['tipo_activo']) ?></td><td><span class="badge"><?= e($a['categoria']) ?></span></td><td><?= e($a['fuente']) ?></td>
          <td><span class="badge <?= $a['estado']==='activo'?'ok':($a['estado']==='pendiente'?'warn':'bad') ?>"><?= e($a['estado']) ?></span></td>
        <td class="actions"><a class="btn btn-sm" href="<?= e(base_url('public/index.php?p=activos/detalle&id='.$a['id_activo'])) ?>">Detalle</a>
        <?php if (puede_editar()): ?><a class="btn btn-sm" href="<?= e(base_url('public/index.php?p=activos/editar&id='.$a['id_activo'])) ?>">Editar</a><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?= links_paginacion($pag, $maxpag) ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
