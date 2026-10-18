<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="es">
  <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · MetaData Manager</title>
    <link rel="stylesheet" href="../assets/css/style.css"></head>
  <body class="login-body">
    <div class="login-card" style="text-align:center">
      <div class="brand-mark big" style="margin:0 auto">?</div>
      <h2>Error 404</h2>
      <p class="muted">La página solicitada no existe.</p>
      <p><a class="btn btn-primary" href="<?= e(base_url('public/index.php?p=dashboard')) ?>">Ir al dashboard</a></p>
    </div>
</body>
</html>
