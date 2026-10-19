<?php
// Header + sidebar. Requiere auth.php antes.
$flash = function_exists('flash_get') ? flash_get() : null;
$nombre = $_SESSION['nombre'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'usuario';
$inicial = strtoupper(substr($nombre, 0, 1));
$pagina = $titulo ?? 'Panel';
// Resalta la seccion actual del menu (viene de ?p= del Front Controller)
$seccionActual = strtok($_GET['p'] ?? '', '/') ?: '';
function nav_act($seccion) {
    global $seccionActual;
    return $seccionActual === trim($seccion, '/') ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title><?= e($pagina) ?> · <?= e(defined('APP_NAME') ? APP_NAME : 'MetaData Manager') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_url('assets/img/favicon.svg')) ?>">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>
  <div class="layout">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div class="brand-mark">M</div>
        <div><div class="brand-name">MetaData Manager</div><div class="brand-sub">Sistema Inteligente</div></div>
      </div>
    <nav class="nav">
      <a class="<?= nav_act('dashboard') ?>" href="<?= e(base_url('public/index.php?p=dashboard')) ?>"> Dashboard</a>
    <a class="<?= nav_act('busqueda') ?>" href="<?= e(base_url('public/index.php?p=busqueda')) ?>"> Búsqueda inteligente</a>
  <a class="<?= nav_act('activos') ?>" href="<?= e(base_url('public/index.php?p=activos')) ?>"> Catálogo de activos</a>
<a class="<?= nav_act('fuentes') ?>" href="<?= e(base_url('public/index.php?p=fuentes')) ?>"> Fuentes</a>
<a class="<?= nav_act('metadatos') ?>" href="<?= e(base_url('public/index.php?p=metadatos')) ?>"> Metadatos</a>
<a class="<?= nav_act('captura') ?>" href="<?= e(base_url('public/index.php?p=captura')) ?>"> Captura automática</a>
<?php if (in_array($rol, ['administrador','steward'])): ?>
  <a class="<?= nav_act('clasificacion') ?>" href="<?= e(base_url('public/index.php?p=clasificacion')) ?>"> Clasificación</a>
<a class="<?= nav_act('enriquecimiento') ?>" href="<?= e(base_url('public/index.php?p=enriquecimiento')) ?>"> Enriquecimiento</a>
<a class="<?= nav_act('calidad') ?>" href="<?= e(base_url('public/index.php?p=calidad')) ?>"> Calidad</a>
<a class="<?= nav_act('trazabilidad') ?>" href="<?= e(base_url('public/index.php?p=trazabilidad')) ?>"> Trazabilidad</a>
<?php endif; ?>
<?php if ($rol === 'administrador'): ?>
  <a class="<?= nav_act('usuarios') ?>" href="<?= e(base_url('public/index.php?p=usuarios')) ?>"> Usuarios</a>
<a class="<?= nav_act('reportes') ?>" href="<?= e(base_url('public/index.php?p=reportes')) ?>"> Reportes</a>
<a class="<?= nav_act('bitacora') ?>" href="<?= e(base_url('public/index.php?p=bitacora')) ?>"> Bitácora</a>
<a class="<?= nav_act('papelera') ?>" href="<?= e(base_url('public/index.php?p=papelera')) ?>"> Papelera</a>
<a class="<?= nav_act('configuracion') ?>" href="<?= e(base_url('public/index.php?p=configuracion')) ?>"> Configuración</a>
<?php else: ?>
  <a class="<?= nav_act('reportes') ?>" href="<?= e(base_url('public/index.php?p=reportes')) ?>"> Reportes</a>
<?php endif; ?>
<?php if (!in_array($rol, ['administrador', 'steward'], true)): ?>
  <a class="<?= nav_act('trazabilidad') ?>" href="<?= e(base_url('public/index.php?p=trazabilidad')) ?>"> Trazabilidad</a>
<?php endif; ?>
</nav>
<div class="side-foot">
  <div class="user-chip"><span class="avatar"><?= e($inicial) ?></span><span><?= e($nombre) ?><br><small><?= e($rol) ?></small></span></div>
  <a class="btn btn-ghost" href="<?= e(base_url('public/logout.php')) ?>">Cerrar sesión</a>
</div>
</aside>
<div class="main">
  <header class="topbar">
    <button class="icon-btn" onclick="document.getElementById('sidebar').classList.toggle('open')"></button>
    <span class="top-welcome"> Hola, <strong><?= e(strtok($nombre, ' ')) ?></strong></span>
    <form class="top-search" action="<?= e(base_url('public/index.php?p=busqueda')) ?>" method="get">
    <input type="text" name="q" placeholder="Buscar activos, categorías, etiquetas…" value="<?= e($_GET['q'] ?? '') ?>">
  <button type="submit">Buscar</button>
</form>
</header>
<main class="content">
  <?php if ($flash): ?>
    <div class="alert <?= e($flash['tipo']) ?>"><?= e($flash['mensaje']) ?></div>
<?php endif; ?>
<div class="page-head"><div><h2><?= e($pagina) ?></h2><?php if (!empty($descripcion)): ?><p><?= e($descripcion) ?></p><?php endif; ?></div></div>
