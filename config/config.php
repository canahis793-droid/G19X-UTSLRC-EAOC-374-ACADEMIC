<?php
// Configuracion general del sistema
define('APP_NAME', 'MetaData Manager');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/sistema-metadata-management'); // Ajustar segun htdocs. Ej: http://localhost/sistema-metadata-management
define('PYTHON_CMD', 'py'); // Windows: py | Linux/XAMPP: python3. Se puede sobreescribir desde tabla configuracion.
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PYTHON_DIR', __DIR__ . '/../python/');

if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0777, true); }
