<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$db = getDB();
$id_fuente = (int)($_POST['id_fuente'] ?? 0);
$host = trim($_POST['host'] ?? 'localhost');
$bdname = trim($_POST['bd'] ?? '');
$user = trim($_POST['user'] ?? 'root');
$pass = $_POST['pass'] ?? '';
try {
  if ($id_fuente <= 0 || $bdname === '') throw new Exception('Fuente y base de datos son obligatorios.');
  $dsn = "mysql:host=$host;dbname=" . $bdname . ";charset=utf8mb4";
  $ext = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $tablas = $ext->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=" . $ext->quote($bdname))->fetchAll(PDO::FETCH_COLUMN);
  if (!$tablas) throw new Exception("No se encontraron tablas en $bdname.");
  $total = 0;
  foreach ($tablas as $t) {
    $chk = $db->prepare("SELECT id_activo FROM activos WHERE id_fuente=? AND nombre=?");
    $chk->execute([$id_fuente, $t]);
    if ($chk->fetch()) continue;
    $ins = $db->prepare("INSERT INTO activos (id_fuente,nombre,tipo_activo,descripcion,categoria,estado) VALUES (?,?,?,?,?, 'pendiente')");
    // Clasificar rapido con Python
    $res = ejecutar_python('clasificador.py', ['nombre'=>$t,'descripcion'=>"Tabla $t de $bdname",'texto'=>"$t tabla base datos"]);
    $cat = $res['categoria'] ?? 'Otro';
    $ins->execute([$id_fuente, $t, 'tabla', "Tabla $t capturada desde $bdname.", $cat]);
    $idAct = (int)$db->lastInsertId();
    $total++;
    // Columnas
    $cols = $ext->query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=" . $ext->quote($bdname) . " AND TABLE_NAME=" . $ext->quote($t))->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
      $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,longitud,es_pk,es_fk,permite_nulos,descripcion) VALUES (?,?,?,?,?,?,?,?,?)")
      ->execute([$idAct,'tecnico',$c['COLUMN_NAME'],strtoupper($c['DATA_TYPE']),$c['CHARACTER_MAXIMUM_LENGTH'],$c['COLUMN_KEY']==='PRI'?1:0,$c['COLUMN_KEY']==='MUL'?1:0,$c['IS_NULLABLE']==='YES'?1:0,"Columna {$c['COLUMN_NAME']} capturada de $t"]);
    }
  // Clasificacion + calidad inicial
  $kw = implode(', ', $res['palabras_clave'] ?? []);
  $db->prepare("INSERT INTO clasificaciones (id_activo,categoria_sugerida,confianza,estado,palabras_clave) VALUES (?,?,?,'pendiente',?)")->execute([$idAct,$cat,$res['confianza'] ?? 0,$kw]);
}
$db->prepare("UPDATE fuentes SET fecha_ultima_sincronizacion=NOW() WHERE id_fuente=?")->execute([$id_fuente]);
$db->prepare("INSERT INTO procesos_captura (id_fuente,id_usuario,tipo,detalle,total_activos,estado) VALUES (?,?,?,?,?,?)")
->execute([$id_fuente,$_SESSION['user_id'],'mysql',"Captura MySQL $bdname@$host: $total tablas nuevas.",$total,'exitoso']);
registrar_bitacora('captura_mysql','captura',"MySQL $bdname: $total activos");
flash_set('ok',"Captura MySQL exitosa: $total tablas nuevas.");
} catch (Exception $ex) {
try { $db->prepare("INSERT INTO procesos_captura (id_fuente,id_usuario,tipo,detalle,total_activos,estado) VALUES (?,?,?,?,?,?)")->execute([$id_fuente ?: null,$_SESSION['user_id'],'mysql','Error: '.$ex->getMessage(),0,'fallido']); } catch (Exception $e2) {}
flash_set('error','Error captura MySQL: '.$ex->getMessage());
}
redirect(base_url('captura/index.php'));
