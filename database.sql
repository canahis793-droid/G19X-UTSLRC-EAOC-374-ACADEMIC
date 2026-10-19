-- ============================================================
-- Sistema Inteligente de Metadata Management
-- Base de datos MySQL - Compatible XAMPP (MariaDB/MySQL 10.x)
-- Stack: PHP + MySQL + Python + HTML/CSS/JS
-- ============================================================

CREATE DATABASE IF NOT EXISTS metadata_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE metadata_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bitacora;
DROP TABLE IF EXISTS calidad_metadatos;
DROP TABLE IF EXISTS clasificaciones;
DROP TABLE IF EXISTS enriquecimientos;
DROP TABLE IF EXISTS reportes_guardados;
DROP TABLE IF EXISTS procesos_captura;
DROP TABLE IF EXISTS relaciones_activos;
DROP TABLE IF EXISTS activo_etiqueta;
DROP TABLE IF EXISTS etiquetas;
DROP TABLE IF EXISTS metadatos;
DROP TABLE IF EXISTS activos;
DROP TABLE IF EXISTS fuentes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS configuracion;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------- Roles ----------------
CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------- Usuarios ----------------
CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  id_rol INT NOT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso DATETIME DEFAULT NULL,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
  INDEX idx_usuarios_correo (correo),
  INDEX idx_usuarios_rol (id_rol)
) ENGINE=InnoDB;

-- ---------------- Fuentes ----------------
CREATE TABLE fuentes (
  id_fuente INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  tipo ENUM('Base de datos','Archivo CSV','Archivo JSON','API','Sistema empresarial','Data lake simulado','Otra') NOT NULL DEFAULT 'Otra',
  descripcion TEXT,
  ubicacion VARCHAR(255) DEFAULT NULL,
  responsable VARCHAR(100) DEFAULT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_ultima_sincronizacion DATETIME DEFAULT NULL,
  INDEX idx_fuentes_tipo (tipo),
  INDEX idx_fuentes_estado (estado)
) ENGINE=InnoDB;

-- ---------------- Activos ----------------
CREATE TABLE activos (
  id_activo INT AUTO_INCREMENT PRIMARY KEY,
  id_fuente INT NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  tipo_activo ENUM('tabla','archivo','api','dataset','reporte','aplicacion','vista','otro') NOT NULL DEFAULT 'tabla',
  descripcion TEXT,
  categoria ENUM('Cliente','Usuario','Venta','Producto','Inventario','Finanzas','Recursos Humanos','Operaciones','Otro') NOT NULL DEFAULT 'Otro',
  propietario VARCHAR(100) DEFAULT NULL,
  area_responsable VARCHAR(100) DEFAULT NULL,
  estado ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'pendiente',
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_activos_fuente FOREIGN KEY (id_fuente) REFERENCES fuentes(id_fuente) ON DELETE CASCADE,
  INDEX idx_activos_fuente (id_fuente),
  INDEX idx_activos_categoria (categoria),
  INDEX idx_activos_estado (estado),
  FULLTEXT INDEX ft_activos_busqueda (nombre, descripcion)
) ENGINE=InnoDB;

-- ---------------- Metadatos ----------------
CREATE TABLE metadatos (
  id_metadato INT AUTO_INCREMENT PRIMARY KEY,
  id_activo INT NOT NULL,
  tipo ENUM('tecnico','operativo','negocio') NOT NULL DEFAULT 'tecnico',
  nombre_campo VARCHAR(150) NOT NULL,
  tipo_dato VARCHAR(50) DEFAULT NULL,
  longitud INT DEFAULT NULL,
  es_pk TINYINT(1) NOT NULL DEFAULT 0,
  es_fk TINYINT(1) NOT NULL DEFAULT 0,
  permite_nulos TINYINT(1) NOT NULL DEFAULT 1,
  formato VARCHAR(80) DEFAULT NULL,
  descripcion TEXT,
  frecuencia_actualizacion VARCHAR(80) DEFAULT NULL,
  responsable VARCHAR(100) DEFAULT NULL,
  estado_campo VARCHAR(50) DEFAULT 'activo',
  proceso_relacionado VARCHAR(150) DEFAULT NULL,
  definicion_negocio TEXT,
  area_responsable VARCHAR(100) DEFAULT NULL,
  importancia ENUM('baja','media','alta','critica') DEFAULT 'media',
  uso_principal VARCHAR(255) DEFAULT NULL,
  clasificacion VARCHAR(80) DEFAULT NULL,
  regla_negocio TEXT,
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_metadatos_activo FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
  INDEX idx_metadatos_activo (id_activo),
  INDEX idx_metadatos_tipo (tipo)
) ENGINE=InnoDB;

-- ---------------- Etiquetas ----------------
CREATE TABLE etiquetas (
  id_etiqueta INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE activo_etiqueta (
  id_activo INT NOT NULL,
  id_etiqueta INT NOT NULL,
  fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_activo, id_etiqueta),
  CONSTRAINT fk_ae_activo FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
  CONSTRAINT fk_ae_etiqueta FOREIGN KEY (id_etiqueta) REFERENCES etiquetas(id_etiqueta) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------- Relaciones ----------------
CREATE TABLE relaciones_activos (
  id_relacion INT AUTO_INCREMENT PRIMARY KEY,
  id_origen INT NOT NULL,
  id_destino INT NOT NULL,
  tipo_relacion VARCHAR(80) NOT NULL DEFAULT 'relacionado',
  descripcion TEXT,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rel_origen FOREIGN KEY (id_origen) REFERENCES activos(id_activo) ON DELETE CASCADE,
  CONSTRAINT fk_rel_destino FOREIGN KEY (id_destino) REFERENCES activos(id_activo) ON DELETE CASCADE,
  INDEX idx_rel_origen (id_origen),
  INDEX idx_rel_destino (id_destino)
) ENGINE=InnoDB;

-- ---------------- Procesos de captura ----------------
CREATE TABLE procesos_captura (
  id_proceso INT AUTO_INCREMENT PRIMARY KEY,
  id_fuente INT,
  id_usuario INT,
  tipo ENUM('mysql','csv','json','api') NOT NULL,
  detalle TEXT,
  total_activos INT NOT NULL DEFAULT 0,
  estado ENUM('exitoso','parcial','fallido') NOT NULL DEFAULT 'exitoso',
  fecha_ejecucion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_proc_fuente FOREIGN KEY (id_fuente) REFERENCES fuentes(id_fuente) ON DELETE SET NULL,
  CONSTRAINT fk_proc_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------- Calidad ----------------
CREATE TABLE calidad_metadatos (
  id_calidad INT AUTO_INCREMENT PRIMARY KEY,
  id_activo INT NOT NULL,
  porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0,
  problemas TEXT,
  recomendaciones TEXT,
  fecha_evaluacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_calidad_activo FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
  INDEX idx_calidad_activo (id_activo)
) ENGINE=InnoDB;

-- ---------------- Clasificaciones ----------------
CREATE TABLE clasificaciones (
  id_clasificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_activo INT NOT NULL,
  categoria_sugerida VARCHAR(60) NOT NULL,
  categoria_final VARCHAR(60) DEFAULT NULL,
  confianza DECIMAL(5,2) DEFAULT 0,
  estado ENUM('pendiente','validada','rechazada') NOT NULL DEFAULT 'pendiente',
  palabras_clave TEXT,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_clas_activo FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
  INDEX idx_clas_activo (id_activo),
  INDEX idx_clas_estado (estado)
) ENGINE=InnoDB;

-- ---------------- Enriquecimientos ----------------
CREATE TABLE enriquecimientos (
  id_enriquecimiento INT AUTO_INCREMENT PRIMARY KEY,
  id_activo INT NOT NULL,
  descripcion_sugerida TEXT,
  palabras_clave TEXT,
  categoria_sugerida VARCHAR(60),
  relaciones_sugeridas TEXT,
  calidad_estimada DECIMAL(5,2) DEFAULT 0,
  estado ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_enr_activo FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------- Bitacora ----------------
CREATE TABLE bitacora (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT,
  accion VARCHAR(100) NOT NULL,
  modulo VARCHAR(80) NOT NULL,
  descripcion TEXT,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(45) DEFAULT NULL,
  CONSTRAINT fk_bit_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
  INDEX idx_bit_fecha (fecha),
  INDEX idx_bit_modulo (modulo)
) ENGINE=InnoDB;

-- ---------------- Intentos de login (limite en el servidor) ----------------
CREATE TABLE login_intentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  correo VARCHAR(120) NOT NULL,
  intentos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME DEFAULT NULL,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_login_ip_correo (ip, correo),
  INDEX idx_login_fecha (fecha)
) ENGINE=InnoDB;

-- ---------------- Configuracion ----------------
CREATE TABLE configuracion (
  clave VARCHAR(80) PRIMARY KEY,
  valor TEXT,
  descripcion VARCHAR(255),
  fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------- Reportes guardados ----------------
CREATE TABLE reportes_guardados (
  id_reporte INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  tipo VARCHAR(80) NOT NULL,
  parametros TEXT,
  id_usuario INT,
  fecha_generacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resultado TEXT,
  CONSTRAINT fk_rep_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------- Tokens API ----------------
CREATE TABLE api_tokens (
  id_token INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  nombre VARCHAR(100) NOT NULL DEFAULT 'integracion',
  token_hash VARCHAR(255) NOT NULL,
  ultimo_uso DATETIME DEFAULT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tok_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_tok_hash (token_hash(64))
) ENGINE=InnoDB;

-- ---------------- Papelera ----------------
CREATE TABLE papelera (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('fuente','activo','usuario') NOT NULL,
  id_original INT NOT NULL,
  nombre VARCHAR(200) NOT NULL,
  datos TEXT NOT NULL,
  id_usuario INT,
  fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pap_tipo (tipo)
) ENGINE=InnoDB;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

INSERT INTO roles (nombre, descripcion) VALUES
('administrador','Acceso total: usuarios, fuentes, capturas, reportes, bitacora y configuracion.'),
('steward','Data Steward: valida metadatos, clasificaciones y calidad.'),
('usuario','Usuario final: consulta dashboard, busca y ve detalles.');

-- Usuarios (passwords: admin123 / steward123 / user123)
INSERT INTO usuarios (nombre, correo, password, id_rol, estado) VALUES
('Administradora del Sistema','admin@demo.local','$2y$10$Ie2NW2PSHn4h3VZGHs1cWuUjARJwY1dD1yr3ZwGeVZdVm//dO0DxO',1,'activo'),
('Steward de Datos Principal','steward@demo.local','$2y$10$PGOJ8qTJtxOt6scIzzA8g.xQpP9ePuWDQ65XTqMHh3KWTei.ptQWu',2,'activo'),
('Usuario de Consulta','usuario@demo.local','$2y$10$DFCGqRGXxs/4.Rq7xrFnMu0jjRvV/8Au6ElF3yK8XaB4y2Go638vq',3,'activo');

INSERT INTO configuracion (clave, valor, descripcion) VALUES
('nombre_sistema','Sistema Inteligente de Metadata Management','Nombre visible del sistema'),
('python_path','py','Comando Python (Windows: py | XAMPP/Linux: python3)'),
('version','1.0.0','Version del sistema'),
('registros_por_pagina','15','Paginacion por defecto'),
('permitir_registro','0','Permitir auto-registro (0=no,1=si)');

INSERT INTO fuentes (nombre, tipo, descripcion, ubicacion, responsable, estado, fecha_ultima_sincronizacion) VALUES
('ERP Principal','Base de datos','Base MySQL del ERP corporativo (ventas, clientes, inventario).','mysql://localhost:3306/erp','Ana Garcia','activo',NOW()),
('CRM Clientes','Base de datos','Base de clientes y contactos comerciales.','mysql://localhost:3306/crm','Luis Perez','activo',NOW()),
('Ventas CSV 2025','Archivo CSV','Archivos mensuales de ventas subidos por comercial.','/uploads/ventas_2025.csv','Maria Lopez','activo',NOW()),
('Catalogo Productos JSON','Archivo JSON','Catalogo de productos exportado del e-commerce.','/uploads/productos.json','Carlos Ruiz','activo',NOW()),
('API Facturacion','API','API REST de facturacion electronica.','https://api.demo.local/facturacion/v1','Sofia Torres','activo',NOW()),
('Data Lake Simulado','Data lake simulado','Zona de datasets historicos para analitica.','/datalake/historico','Diego Fernandez','activo',NOW());

INSERT INTO activos (id_fuente, nombre, tipo_activo, descripcion, categoria, propietario, area_responsable, estado) VALUES
(1,'clientes','tabla','Tabla maestra de clientes del ERP. Contiene datos de contacto y facturacion.','Cliente','Ana Garcia','Comercial','activo'),
(1,'facturas','tabla','Facturas emitidas con totales, impuestos y cliente relacionado.','Finanzas','Ana Garcia','Finanzas','activo'),
(1,'detalle_factura','tabla','Lineas de detalle de cada factura (producto, cantidad, precio).','Venta','Ana Garcia','Finanzas','activo'),
(1,'productos','tabla','Catalogo de productos con precios y stock.','Producto','Carlos Ruiz','Almacen','activo'),
(1,'inventario_almacen','tabla','Existencias por almacen y movimientos.','Inventario','Carlos Ruiz','Almacen','activo'),
(2,'usuarios_crm','tabla','Usuarios y contactos del CRM, incluye compradores y leads.','Usuario','Luis Perez','Marketing','activo'),
(2,'oportunidades','tabla','Pipeline comercial de oportunidades de venta.','Venta','Luis Perez','Comercial','pendiente'),
(3,'ventas_2025.csv','archivo','Archivo CSV con 12 columnas de ventas mensuales. Faltan descripciones de negocio.','Venta','Maria Lopez','Comercial','pendiente'),
(4,'productos.json','archivo','Export JSON del catalogo e-commerce con variantes y precios.','Producto','Carlos Ruiz','E-commerce','activo'),
(5,'api_facturas','api','Recurso API que expone facturas para integraciones.','Finanzas','Sofia Torres','TI','activo'),
(5,'api_reporte_ventas','reporte','Reporte generado desde API de ventas por region.','Venta','Sofia Torres','Comercial','activo'),
(6,'empleados_historico','dataset','Dataset historico de empleados para analitica de RRHH.','Recursos Humanos','Diego Fernandez','RRHH','activo'),
(6,'operaciones_logistica','dataset','Movimientos logisticos y rutas de distribucion.','Operaciones','Diego Fernandez','Operaciones','pendiente'),
(1,'vista_ventas_mensual','vista','Vista agregada de ventas por mes y categoria. Sin propietario asignado.','Venta',NULL,'Comercial','pendiente'),
(1,'proveedores','tabla','Catálogo de proveedores con datos fiscales y condiciones de pago.','Operaciones','Ana Garcia','Compras','activo'),
(1,'ordenes_compra','tabla','Órdenes de compra emitidas a proveedores con montos y fechas de entrega.','Operaciones','Ana Garcia','Compras','activo'),
(6,'nominas_mensual','dataset','Nómina mensual para analítica de costos de personal.','Recursos Humanos','Diego Fernandez','RRHH','activo'),
(2,'reclamos_clientes','tabla','Reclamos y tickets de clientes con estado de atención.','Cliente','Luis Perez','Atención al Cliente','pendiente');

INSERT INTO metadatos (id_activo, tipo, nombre_campo, tipo_dato, longitud, es_pk, es_fk, permite_nulos, formato, descripcion, frecuencia_actualizacion, responsable, definicion_negocio, importancia, uso_principal) VALUES
(1,'tecnico','id_cliente','INT',11,1,0,0,NULL,'Identificador unico del cliente',NULL,NULL,NULL,'alta',NULL),
(1,'tecnico','nombre','VARCHAR',150,0,0,0,NULL,'Nombre o razon social',NULL,NULL,NULL,'alta',NULL),
(1,'tecnico','email','VARCHAR',150,0,0,1,NULL,'Correo de contacto',NULL,NULL,NULL,'media',NULL),
(1,'negocio','email','VARCHAR',150,0,0,1,NULL,'Correo principal para notificaciones y marketing','Mensual','Ana Garcia','Direccion electronica validada del cliente','media','Contacto y marketing'),
(1,'operativo','ultima_actualizacion','DATETIME',NULL,0,0,1,NULL,'Fecha de ultima sincronizacion','Diaria','Ana Garcia',NULL,'media',NULL),
(2,'tecnico','id_factura','INT',11,1,0,0,NULL,'PK de factura',NULL,NULL,NULL,'alta',NULL),
(2,'tecnico','id_cliente','INT',11,0,1,0,NULL,'FK hacia clientes',NULL,NULL,NULL,'alta',NULL),
(2,'tecnico','total','DECIMAL',10,0,0,0,'#,##0.00','Total con impuestos',NULL,NULL,NULL,'critica',NULL),
(2,'negocio','total','DECIMAL',10,0,0,0,'#,##0.00','Monto total facturado con IVA','Diaria','Ana Garcia','Base para reportes financieros','critica','Facturacion y contabilidad'),
(3,'tecnico','id_detalle','INT',11,1,0,0,NULL,'PK detalle',NULL,NULL,NULL,'media',NULL),
(3,'tecnico','cantidad','INT',11,0,0,0,NULL,'Unidades vendidas',NULL,NULL,NULL,'alta',NULL),
(4,'tecnico','sku','VARCHAR',50,1,0,0,NULL,'Codigo unico de producto',NULL,NULL,NULL,'alta',NULL),
(4,'negocio','precio','DECIMAL',10,0,0,0,NULL,'Precio de lista vigente','Semanal','Carlos Ruiz','Precio oficial de venta al publico','alta','Pricing y e-commerce'),
(5,'tecnico','id_movimiento','INT',11,1,0,0,NULL,'PK movimiento inventario',NULL,NULL,NULL,'media',NULL),
(5,'operativo','frecuencia_actualizacion','VARCHAR',80,0,0,1,NULL,'Cada cuantas horas se actualiza','Cada 6 horas','Carlos Ruiz',NULL,'media',NULL),
(6,'tecnico','id_usuario','INT',11,1,0,0,NULL,'PK usuario CRM',NULL,NULL,NULL,'alta',NULL),
(6,'tecnico','nombre_completo','VARCHAR',150,0,0,0,NULL,'Nombre del contacto',NULL,NULL,NULL,'alta',NULL),
(6,'negocio','tipo_contacto','VARCHAR',50,0,0,1,NULL,'Lead, comprador o socio','Semanal','Luis Perez','Clasificacion comercial del contacto','media','Segmentacion'),
(8,'tecnico','columna_fecha','DATE',NULL,0,0,1,'YYYY-MM-DD','Fecha de la venta',NULL,NULL,NULL,'alta',NULL),
(8,'tecnico','columna_total','DECIMAL',10,0,0,1,NULL,'Total sin descripcion de negocio',NULL,NULL,NULL,'alta',NULL),
(9,'tecnico','propiedad_sku','VARCHAR',50,0,0,1,NULL,'SKU detectado en JSON',NULL,NULL,NULL,'alta',NULL),
(12,'tecnico','id_empleado','INT',11,1,0,0,NULL,'PK empleado',NULL,NULL,NULL,'alta',NULL),
(12,'negocio','puesto','VARCHAR',100,0,0,1,NULL,'Puesto actual','Mensual','Diego Fernandez','Rol oficial en organigrama','media','Analitica RRHH');

INSERT INTO etiquetas (nombre) VALUES
('cliente'),('facturacion'),('ventas'),('producto'),('inventario'),('crm'),('finanzas'),('rrhh'),('logistica'),('critico'),('pendiente-validacion');

INSERT INTO activo_etiqueta (id_activo, id_etiqueta) VALUES
(1,1),(1,3),(2,2),(2,7),(2,10),(3,3),(4,4),(5,5),(6,1),(6,6),(7,3),(8,3),(9,4),(10,2),(12,8),(13,9),(14,3);

INSERT INTO relaciones_activos (id_origen, id_destino, tipo_relacion, descripcion) VALUES
(1,2,'uno-a-muchos','Un cliente tiene muchas facturas (clientes.id_cliente -> facturas.id_cliente)'),
(2,3,'uno-a-muchos','Una factura tiene muchas lineas de detalle'),
(4,3,'muchos-a-muchos','Productos aparecen en lineas de detalle de factura'),
(4,5,'deriva','El inventario se calcula desde el catalogo de productos'),
(6,1,'similitud','usuarios_crm similar a clientes: posible duplicidad a revisar'),
(10,11,'deriva','Reporte de ventas generado desde API de facturas'),
(2,10,'origen','API expone la tabla facturas'),
(12,13,'flujo','Empleados participan en operaciones logisticas'),
(3,14,'agregacion','Vista mensual agregada desde detalle_factura');

INSERT INTO procesos_captura (id_fuente, id_usuario, tipo, detalle, total_activos, estado) VALUES
(1,1,'mysql','Captura INFORMATION_SCHEMA de ERP: 5 tablas, 42 columnas detectadas.',5,'exitoso'),
(3,1,'csv','Archivo ventas_2025.csv: 12 columnas, 4850 filas, 320 vacios detectados.',1,'exitoso'),
(4,1,'json','Archivo productos.json: 8 propiedades raiz, 1250 registros.',1,'exitoso');

INSERT INTO calidad_metadatos (id_activo, porcentaje, problemas, recomendaciones) VALUES
(1,92.00,'["Todo correcto, solo falta regla de negocio en 1 campo"]','["Agregar regla de negocio para email"]'),
(2,88.50,'["Falta definicion de negocio en id_factura"]','["Completar definicion de negocio"]'),
(8,45.00,'["Sin descripcion general","Sin categoria validada","Sin propietario en 2 campos","2 campos sin definicion de negocio"]','["Agregar descripcion","Validar categoria Venta","Asignar propietario","Completar definiciones"]'),
(14,38.00,'["Sin propietario","Sin descripcion detallada","Sin fecha de actualizacion reciente"]','["Asignar propietario","Redactar descripcion","Programar sincronizacion"]'),
(7,61.00,'["Categoria pendiente de validacion","Falta area responsable en metadatos"]','["Validar categoria con steward","Completar area responsable"]');

INSERT INTO clasificaciones (id_activo, categoria_sugerida, categoria_final, confianza, estado, palabras_clave) VALUES
(1,'Cliente','Cliente',96.50,'validada','cliente, contacto, persona, facturacion'),
(2,'Finanzas','Finanzas',91.20,'validada','factura, total, impuesto, cobro'),
(6,'Usuario',NULL,84.00,'pendiente','usuario, contacto, comprador, lead'),
(7,'Venta',NULL,78.40,'pendiente','oportunidad, pipeline, venta, comercial'),
(8,'Venta',NULL,72.10,'pendiente','venta, fecha, total, mensual'),
(13,'Operaciones',NULL,69.80,'pendiente','logistica, ruta, distribucion, operacion');

INSERT INTO enriquecimientos (id_activo, descripcion_sugerida, palabras_clave, categoria_sugerida, relaciones_sugeridas, calidad_estimada, estado) VALUES
(6,'Tabla de contactos comerciales del CRM. Almacena leads, compradores y datos de segmentacion para marketing.','usuario, comprador, contacto, lead, segmentacion','Usuario','Relacionado con: clientes (similitud 0.82)',84.00,'pendiente'),
(8,'Archivo mensual de ventas en CSV. Contiene fecha, totales y region. Requiere validar categoria y completar descripciones.','venta, mensual, total, region, comercial','Venta','Relacionado con: facturas (0.71), detalle_factura (0.68)',45.00,'pendiente');

INSERT INTO bitacora (id_usuario, accion, modulo, descripcion, ip) VALUES
(1,'login','auth','Inicio de sesion exitoso','127.0.0.1'),
(1,'crear_fuente','fuentes','Registro fuente ERP Principal','127.0.0.1'),
(1,'captura_mysql','captura','Captura MySQL ERP: 5 activos','127.0.0.1'),
(2,'validar_clasificacion','clasificacion','Valido categoria Cliente para activo clientes','127.0.0.1'),
(3,'busqueda','busqueda','Busco: informacion de compradores','127.0.0.1');

-- Activos empresariales adicionales (proveedores, compras, nomina, reclamos)
INSERT INTO metadatos (id_activo, tipo, nombre_campo, tipo_dato, longitud, es_pk, permite_nulos, descripcion, definicion_negocio, importancia) VALUES
((SELECT id_activo FROM activos WHERE nombre='proveedores'),'tecnico','id_proveedor','INT',11,1,0,'PK proveedor',NULL,'alta'),
((SELECT id_activo FROM activos WHERE nombre='proveedores'),'tecnico','rfc','VARCHAR',13,0,0,'RFC fiscal',NULL,'alta'),
((SELECT id_activo FROM activos WHERE nombre='proveedores'),'negocio','condiciones_pago','VARCHAR',80,0,1,'Condiciones pactadas','Días de crédito otorgados por el proveedor','media'),
((SELECT id_activo FROM activos WHERE nombre='ordenes_compra'),'tecnico','id_orden','INT',11,1,0,'PK orden',NULL,'alta'),
((SELECT id_activo FROM activos WHERE nombre='ordenes_compra'),'tecnico','id_proveedor','INT',11,0,0,'FK a proveedores',NULL,'alta'),
((SELECT id_activo FROM activos WHERE nombre='nominas_mensual'),'tecnico','periodo','VARCHAR',7,0,0,'Periodo YYYY-MM',NULL,'alta'),
((SELECT id_activo FROM activos WHERE nombre='nominas_mensual'),'negocio','costo_total','DECIMAL',12,0,0,'Costo total del periodo','Suma de percepciones menos deducciones','critica'),
((SELECT id_activo FROM activos WHERE nombre='reclamos_clientes'),'tecnico','id_reclamo','INT',11,1,0,'PK reclamo',NULL,'alta'),
((SELECT id_activo FROM activos WHERE nombre='reclamos_clientes'),'negocio','estado_atencion','VARCHAR',30,0,1,'Estado del ticket','Abierto, en proceso o cerrado','alta');

INSERT IGNORE INTO etiquetas (nombre) VALUES ('proveedores'),('compras'),('nomina'),('reclamos');
INSERT IGNORE INTO activo_etiqueta (id_activo, id_etiqueta)
SELECT a.id_activo, e.id_etiqueta FROM activos a JOIN etiquetas e ON
 ((a.nombre='proveedores' AND e.nombre IN ('proveedores','compras'))
  OR (a.nombre='ordenes_compra' AND e.nombre IN ('compras'))
  OR (a.nombre='nominas_mensual' AND e.nombre IN ('nomina'))
  OR (a.nombre='reclamos_clientes' AND e.nombre IN ('reclamos','cliente')));

INSERT INTO relaciones_activos (id_origen, id_destino, tipo_relacion, descripcion)
SELECT o.id_activo, d.id_activo, 'uno-a-muchos', 'Un proveedor tiene muchas órdenes de compra'
FROM activos o, activos d WHERE o.nombre='proveedores' AND d.nombre='ordenes_compra';

INSERT INTO calidad_metadatos (id_activo, porcentaje, problemas, recomendaciones)
SELECT id_activo, 90.00, '["Solo falta regla de negocio en 1 campo"]', '["Agregar regla de negocio"]'
FROM activos WHERE nombre IN ('proveedores','ordenes_compra','nominas_mensual');
