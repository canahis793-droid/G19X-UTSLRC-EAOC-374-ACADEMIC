<?php
// API JSON: fuentes.
require_once __DIR__ . '/_boot.php';

try {
  $rows = $db->query("SELECT id_fuente AS id, nombre, tipo, estado FROM fuentes ORDER BY nombre")->fetchAll();
  api_ok($rows);
} catch (Exception $ex) { api_error($ex->getMessage()); }
