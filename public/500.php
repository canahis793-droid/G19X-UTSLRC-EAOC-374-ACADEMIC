<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/funciones.php';
if (!headers_sent()) { http_response_code(500); }
?>
<!DOCTYPE html>
<html lang="es">
  <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 500 · MetaData Manager</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>"></head>
  <body class="login-body">
    <div class="login-card" style="text-align:center">
      <div class="brand-mark big" style="margin:0 auto">M</div>
      <h2>Error 500</h2>
      <p class="muted">No se pudo completar la operación. Intente de nuevo en unos minutos.</p>
      <p><a class="btn btn-primary" href="<?= e(base_url('public/index.php?p=dashboard')) ?>">Ir al dashboard</a></p>
    </div>
  </body>
</html>
