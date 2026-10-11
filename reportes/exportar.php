<?php
// Parcial interno: solo via Front Controller (public/index.php).
if (!defined('APP_ENTRY')) { header('Location: ../public/index.php'); exit; }

require_once __DIR__ . '/../includes/auth.php';
$db = getDB();
$tipo = $_GET['tipo'] ?? 'activos';
$permitidos = ['activos','calidad','metadatos','bitacora'];
if (!in_array($tipo, $permitidos, true)) $tipo = 'activos';
registrar_bitacora('exportar', 'reportes', "Exporto CSV $tipo");

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename={$tipo}_" . date('Ymd_His') . ".csv");
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
if ($tipo === 'activos') {
  fputcsv($out, ['id','fuente','nombre','tipo','categoria','propietario','estado']);
  foreach ($db->query("SELECT a.id_activo,f.nombre AS fuente,a.nombre,a.tipo_activo,a.categoria,a.propietario,a.estado FROM activos a JOIN fuentes f ON f.id_fuente=a.id_fuente") as $r) fputcsv($out, array_values($r));
} elseif ($tipo === 'calidad') {
fputcsv($out, ['activo','porcentaje','fecha']);
foreach ($db->query("SELECT a.nombre,c.porcentaje,c.fecha_evaluacion FROM calidad_metadatos c JOIN activos a ON a.id_activo=c.id_activo") as $r) fputcsv($out, array_values($r));
} elseif ($tipo === 'metadatos') {
fputcsv($out, ['activo','tipo','campo','tipo_dato','descripcion']);
foreach ($db->query("SELECT a.nombre,m.tipo,m.nombre_campo,m.tipo_dato,m.descripcion FROM metadatos m JOIN activos a ON a.id_activo=m.id_activo") as $r) fputcsv($out, array_values($r));
} else {
fputcsv($out, ['fecha','usuario','accion','modulo','descripcion','ip']);
foreach ($db->query("SELECT b.fecha,u.nombre,b.accion,b.modulo,b.descripcion,b.ip FROM bitacora b LEFT JOIN usuarios u ON u.id_usuario=b.id_usuario ORDER BY b.fecha DESC LIMIT 1000") as $r) fputcsv($out, array_values($r));
}
fclose($out);
exit;
