-- Migracion v4: limite de intentos de login en el servidor (anti fuerza bruta)
-- La tabla login_intentos permite contar los fallos por IP + correo sin
-- depender de la sesion (que se puede resetear borrando la cookie).
USE metadata_db;

CREATE TABLE IF NOT EXISTS login_intentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  correo VARCHAR(120) NOT NULL,
  intentos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME DEFAULT NULL,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_login_ip_correo (ip, correo),
  INDEX idx_login_fecha (fecha)
) ENGINE=InnoDB;

-- Limpieza opcional de registros antiguos (ejecutar de vez en cuando):
-- DELETE FROM login_intentos WHERE fecha < NOW() - INTERVAL 1 DAY;
