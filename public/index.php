<?php
// Entrada principal: redirige segun sesion
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/funciones.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['user_id'])) { redirect(base_url('public/login.php')); }
redirect(base_url('dashboard/index.php'));
