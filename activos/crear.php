<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$titulo = 'Crear activo';
$descripcion = 'Registra un activo paso a paso.';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_fuente = (int)$_POST['id_fuente'];
  $nombre = trim($_POST['nombre'] ?? '');
  $tipo_activo = $_POST['tipo_activo'] ?? 'tabla';
  $descripcion = trim($_POST['descripcion'] ?? '');
  $categoria = $_POST['categoria'] ?? 'Otro';
  $propietario = trim($_POST['propietario'] ?? '');
  $area = trim($_POST['area_responsable'] ?? '');
  $estado = $_POST['estado'] ?? 'pendiente';
  try {
    if ($nombre === '' || $id_fuente <= 0) throw new Exception('Fuente y nombre son obligatorios.');
    $st = $db->prepare("INSERT INTO activos (id_fuente,nombre,tipo_activo,descripcion,categoria,propietario,area_responsable,estado) VALUES (?,?,?,?,?,?,?,?)");
    $st->execute([$id_fuente,$nombre,$tipo_activo,$descripcion,$categoria,$propietario ?: null,$area ?: null,$estado]);
    $id = (int)$db->lastInsertId();
    // Clasificacion inicial pendiente via Python (rapido, sin bloquear si falla)
    $res = ejecutar_python('clasificador.py', ['nombre'=>$nombre,'descripcion'=>$descripcion,'texto'=>"$nombre $descripcion"]);
    $sug = $res['categoria'] ?? 'Otro'; $conf = $res['confianza'] ?? 0; $kw = implode(', ', $res['palabras_clave'] ?? []);
    $db->prepare("INSERT INTO clasificaciones (id_activo,categoria_sugerida,confianza,estado,palabras_clave) VALUES (?,?,?,'pendiente',?)")->execute([$id,$sug,$conf,$kw]);
    registrar_bitacora('crear_activo','activos',"Creo activo $nombre id=$id");
    flash_set('ok','Activo creado. Se generó clasificación sugerida con Python.');
    redirect(base_url('activos/detalle.php?id='.$id));
  } catch (Exception $ex) { flash_set('error','Error: '.$ex->getMessage()); }
}
$fuentes = $db->query("SELECT * FROM fuentes WHERE estado='activo' ORDER BY nombre")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><h3>Nuevo activo</h3>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <label>Fuente*<select name="id_fuente" required><?php foreach ($fuentes as $f): ?><option value="<?= $f['id_fuente'] ?>"><?= e($f['nombre']) ?> (<?= e($f['tipo']) ?>)</option><?php endforeach; ?></select></label>
  <label>Nombre*<input name="nombre" required placeholder="clientes"></label>
  <label>Tipo<select name="tipo_activo"><?php foreach (['tabla','archivo','api','dataset','reporte','aplicacion','vista','otro'] as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select></label>
  <label>Categoría<select name="categoria"><?php foreach (['Cliente','Usuario','Venta','Producto','Inventario','Finanzas','Recursos Humanos','Operaciones','Otro'] as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></label>
  <label>Propietario<input name="propietario"></label>
  <label>Área responsable<input name="area_responsable"></label>
  <label>Estado<select name="estado"><option value="pendiente">pendiente</option><option value="activo">activo</option><option value="inactivo">inactivo</option></select></label>
  <label class="full">Descripción<textarea name="descripcion" rows="3"></textarea></label>
  <div><button class="btn btn-primary">Guardar</button> <a class="btn" href="<?= e(base_url('activos/index.php')) ?>">Volver</a></div>
</form></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
