<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permisos.php';
requerir_steward();
$db = getDB();
try {
  $id_fuente = (int)($_POST['id_fuente'] ?? 0);
  $url = trim($_POST['url'] ?? '');
  $nombre = trim($_POST['nombre'] ?? '');
  if ($id_fuente <= 0 || $url === '' || $nombre === '') throw new Exception('Fuente, URL y nombre son obligatorios.');
  if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('URL inválida.');
  $partes = parse_url($url);
  if (!in_array(strtolower($partes['scheme'] ?? ''), ['http','https'], true)) throw new Exception('Solo se permiten URLs http/https.');
  $host = strtolower($partes['host'] ?? '');
  $ip = validar_host_publico($host);
  $muestra = '';
try {
  $ctx = stream_context_create(['http'=>['timeout'=>6,'header'=>"User-Agent: MetaDataManager/1.0\r\n"]]);
  $muestra = @file_get_contents($url, false, $ctx);
  if ($muestra) $muestra = mb_substr($muestra, 0, 2000);
} catch (Exception $e) {}
$desc = "Recurso API registrado: $url." . ($muestra ? " Respuesta de ejemplo almacenada (primeros 2KB)." : " Sin respuesta de ejemplo (URL descriptiva).");
$cla = ejecutar_python('clasificador.py', ['nombre'=>$nombre,'descripcion'=>$desc,'texto'=>"$nombre api $url"]);
$cat = $cla['categoria'] ?? 'Otro';
$st = $db->prepare("INSERT INTO activos (id_fuente,nombre,tipo_activo,descripcion,categoria,estado) VALUES (?,?,?,?,?,'pendiente')");
$st->execute([$id_fuente,$nombre,'api',$desc,$cat]);
$idAct = (int)$db->lastInsertId();
$db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,descripcion) VALUES (?,?,?,?,?)")
->execute([$idAct,'tecnico','endpoint','URL',"Endpoint base: $url"]);
if ($muestra) {
  $db->prepare("INSERT INTO metadatos (id_activo,tipo,nombre_campo,tipo_dato,descripcion) VALUES (?,?,?,?,?)")
  ->execute([$idAct,'operativo','respuesta_ejemplo','TEXT',"Muestra: ".mb_substr($muestra,0,500)]);
}
$db->prepare("INSERT INTO procesos_captura (id_fuente,id_usuario,tipo,detalle,total_activos,estado) VALUES (?,?,?,?,?,?)")
->execute([$id_fuente,$_SESSION['user_id'],'api',"API $url registrada como $nombre",1,'exitoso']);
registrar_bitacora('captura_api','captura',"API $url -> activo $idAct");
flash_set('ok','API registrada correctamente.');
redirect(base_url('public/index.php?p=activos/detalle&id='.$idAct));
} catch (Exception $ex) {
flash_set('error','Error: '.$ex->getMessage());
redirect(base_url('public/index.php?p=captura'));
}
