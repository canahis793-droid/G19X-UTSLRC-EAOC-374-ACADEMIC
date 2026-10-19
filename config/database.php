<?php
/**
 * Configuracion de la base de datos MySQL.
 *
 * Valores por defecto pensados para XAMPP recien instalado (puerto 3306).
 * Si tu MySQL/MariaDB usa otro puerto o tiene clave, no edites este archivo:
 * crea config/database.local.php (no se sube al repositorio) con las lineas:
 *
 *   <?php
 *   define('DB_PORT', '3307');
 *   define('DB_PASS', 'mi_clave');
 */

// Ajustes locales: se cargan primero y mandan sobre los valores por defecto.
if (file_exists(__DIR__ . '/database.local.php')) {
  require __DIR__ . '/database.local.php';
}

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306'); // XAMPP por defecto
if (!defined('DB_NAME')) define('DB_NAME', 'metadata_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', ''); // XAMPP por defecto sin clave
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna una instancia PDO unica.
 */
function getDB() {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  }
  return $pdo;
}