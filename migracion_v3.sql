-- Migracion v3: tokens API + papelera
USE metadata_db;

CREATE TABLE IF NOT EXISTS api_tokens (
  id_token INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  nombre VARCHAR(100) NOT NULL DEFAULT 'integracion',
  token_hash VARCHAR(255) NOT NULL,
  ultimo_uso DATETIME DEFAULT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tok_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_tok_hash (token_hash(64))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS papelera (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('fuente','activo','usuario') NOT NULL,
  id_original INT NOT NULL,
  nombre VARCHAR(200) NOT NULL,
  datos TEXT NOT NULL,
  id_usuario INT,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pap_tipo (tipo)
) ENGINE=InnoDB;
