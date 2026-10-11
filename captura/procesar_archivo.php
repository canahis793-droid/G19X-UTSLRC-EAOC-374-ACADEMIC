<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$db = getDB();
$tipo = $_POST['tipo'] ?? '';
$id_fuente = (int)($_POST['id_fuente'] ?? 0);
try {
  if (!in_array($tipo, ['csv','json'], true)) throw new Exception('Tipo inválido.');
  if ($id_fuente <= 0) throw new Exception('Seleccione fuente.');
  if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) throw new Exception('Suba un archivo válido.');
  if ($_FILES['archivo']['size'] > 10 * 1024 * 1024) throw new Exception('Archivo muy grande (máximo 10 MB).');
  $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
  if ($ext !== $tipo) throw new Exception("Extensión inválida: se esperaba .$tipo.");
  $tmp = $_FILES['archivo']['tmp_name'];
  $orig = basename($_FILES['archivo']['name']);
  $dest = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
  if (!move_uploaded_file($tmp, $dest)) throw new Exception('No se pudo guardar el archivo.');
  // Llamar a Python extractor
  $res = ejecutar_python('extractor.py', ['tipo' => $tipo, 'archivo' => $dest, 'nombre' => $orig]);
  if (empty($res['ok'])) throw new Exception('Python: ' . ($res['error'] ?? 'fallo extractor'));
  $info = $res['resultado'];
  $nombreActivo = $orig;
  $desc = $tipo === 'csv'
  ? "Archivo CSV $orig: " . ($info['num_columnas'] ?? 0) . " columnas, " . ($info['num_filas'] ?? 0) . " filas."
  : "Archivo JSON $orig: " . ($info['num_registros'] ?? 0) . " registros, " . count($info['propiedades'] ?? []) . " propiedades.";
  $cla = ejecutar_python('clasificador.py', ['nombre'=>$orig,'descripcion'=>$desc,'texto'=>"$orig $desc"]);
  $cat = $cla['categoria'] ?? 'Otro';
  $st = $db->prepare("INSERT INTO activos (id_fuente,nombre,tipo_activo,descripcion,categoria,estado) VALUES (?,?,?,?,?,'pendiente')");
  $st->execute([$id_fuente,$nombreActivo,'archivo',$desc,$cat]);
  $idAct = (int)$db->lastInsertId();
  // Crear metadatos por columna/propiedad
  if ($tipo === 'csv') {
    foreach (($info['columnas'] ?? []) as $c) {
      $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,permite_nulos,descripcion) VALUES (?,?,?,?,?,?)")
      ->execute([$idAct,'tecnico',$c['nombre'],$c['tipo_inferido'] ?? 'TEXT',($c['vacios'] ?? 0) > 0 ? 1 : 0,"Columna detectada por Python. Vacios: ".($c['vacios'] ?? 0)]);
    }
} else {
foreach (($info['propiedades'] ?? []) as $p) {
  $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,descripcion) VALUES (?,?,?,?,?)")
  ->execute([$idAct,'tecnico',$p['nombre'],$p['tipo'] ?? 'TEXT',"Propiedad JSON detectada por Python."]);
}
}
$db->prepare("INSERT INTO clasificaciones (id_activo,categoria_sugerida,confianza,estado,palabras_clave) VALUES (?,?,?,'pendiente',?)")
->execute([$idAct,$cat,$cla['confianza'] ?? 0,implode(', ',$cla['palabras_clave'] ?? [])]);
$db->prepare("INSERT INTO procesos_captura (id_fuente,id_usuario,tipo,detalle,total_activos,estado) VALUES (?,?,?,?,?,?)")
->execute([$id_fuente,$_SESSION['user_id'],$tipo,"Archivo $orig analizado con Python. $desc",1,'exitoso']);
$db->prepare("UPDATE fuentes SET fecha_ultima_sincronizacion=NOW() WHERE id_fuente=?")->execute([$id_fuente]);
registrar_bitacora('captura_'.$tipo,'captura',"Archivo $orig -> activo $idAct");
flash_set('ok',"Archivo analizado con Python. Activo creado: $nombreActivo");
redirect(base_url('activos/detalle.php?id='.$idAct));
} catch (Exception $ex) {
flash_set('error','Error: '.$ex->getMessage());
redirect(base_url('captura/index.php'));
}
