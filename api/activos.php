<?php
// API JSON: lista/busca activos.
require_once __DIR__ . '/_boot.php';

$q = trim($_GET['q'] ?? '');
try {
  if ($q !== '') {
    $st = $db->prepare("SELECT a.id_activo AS id, a.nombre, a.descripcion, a.categoria, a.tipo_activo AS tipo, f.nombre AS fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente WHERE a.nombre LIKE ? OR a.descripcion LIKE ? LIMIT 50");
    $st->execute(["%$q%", "%$q%"]);
  } else {
  $st = $db->query("SELECT a.id_activo AS id, a.nombre, a.descripcion, a.categoria, a.tipo_activo AS tipo, f.nombre AS fuente FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente LIMIT 100");
}
api_ok($st->fetchAll());
} catch (Exception $ex) { api_error($ex->getMessage()); }
