<?php
// Configuracion de la base de datos MySQL (XAMPP por defecto)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307'); // MariaDB XAMPP en 3307 (3306 ocupado por MySQL 8.0)
define('DB_NAME', 'metadata_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP por defecto sin password. Cambiar si aplica.
define('DB_CHARSET', 'utf8mb4');

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
