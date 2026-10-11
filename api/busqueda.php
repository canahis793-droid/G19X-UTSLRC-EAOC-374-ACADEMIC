<?php
// API busqueda inteligente: ?q=texto -> ranking Python (TF-IDF + sinonimos).
require_once __DIR__ . '/_boot.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') { api_error('Falta q', 400); }
try {
  $todos = $db->query("SELECT a.id_activo AS id, a.nombre, a.descripcion, a.categoria FROM activos a LIMIT 200")->fetchAll();
  echo json_encode(ejecutar_python('buscador.py', ['query' => $q, 'activos' => $todos]), JSON_UNESCAPED_UNICODE);
} catch (Exception $ex) { api_error($ex->getMessage()); }
