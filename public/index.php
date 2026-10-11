<?php
// Front Controller: UNICO punto de entrada web (?p=modulo[/accion]).
// Los archivos de cada modulo son parciales internos (exigen APP_ENTRY).
define('APP_ENTRY', true);

$rutas = [
  '' => 'dashboard/index.php',
  'dashboard' => 'dashboard/index.php',
  'usuarios' => 'usuarios/index.php',
  'fuentes' => 'fuentes/index.php',
  'activos' => 'activos/index.php',
  'activos/crear' => 'activos/crear.php',
  'activos/editar' => 'activos/editar.php',
  'activos/detalle' => 'activos/detalle.php',
  'metadatos' => 'metadatos/index.php',
  'captura' => 'captura/index.php',
  'captura/procesar_mysql' => 'captura/procesar_mysql.php',
  'captura/procesar_archivo' => 'captura/procesar_archivo.php',
  'captura/procesar_api' => 'captura/procesar_api.php',
  'clasificacion' => 'clasificacion/index.php',
  'enriquecimiento' => 'enriquecimiento/index.php',
  'busqueda' => 'busqueda/index.php',
  'trazabilidad' => 'trazabilidad/index.php',
  'calidad' => 'calidad/index.php',
  'reportes' => 'reportes/index.php',
  'reportes/exportar' => 'reportes/exportar.php',
  'papelera' => 'papelera/index.php',
  'bitacora' => 'bitacora/index.php',
  'configuracion' => 'configuracion/index.php',
];

$p = $_GET['p'] ?? '';
if (!array_key_exists($p, $rutas)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
require __DIR__ . '/../' . $rutas[$p];
