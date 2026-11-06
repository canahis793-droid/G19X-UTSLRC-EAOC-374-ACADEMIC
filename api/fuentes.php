<?php
// API JSON: fuentes.
require_once __DIR__ . '/_boot.php';

try {
  $rows = $db->query("SELECT id_fuente AS id, nombre, tipo, estado FROM fuentes ORDER BY nombre")->fetchAll();
  api_ok($rows);
} catch (PDOException $ex) {
  error_log('api/fuentes: ' . $ex->getMessage());
  api_error('Error interno del servidor.', 500);
} catch (Exception $ex) { api_error($ex->getMessage()); }
