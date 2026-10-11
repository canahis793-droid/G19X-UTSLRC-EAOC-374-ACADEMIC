-- Migracion v2: nombres profesionales + activos empresariales adicionales
USE metadata_db;

UPDATE usuarios SET nombre='Administradora del Sistema' WHERE correo='admin@demo.local';
UPDATE usuarios SET nombre='Steward de Datos Principal' WHERE correo='steward@demo.local';
UPDATE usuarios SET nombre='Usuario de Consulta' WHERE correo='usuario@demo.local';

INSERT INTO activos (id_fuente, nombre, tipo_activo, descripcion, categoria, propietario, area_responsable, estado) VALUES
(1,'proveedores','tabla','Catálogo de proveedores con datos fiscales y condiciones de pago.','Operaciones','Ana Garcia','Compras','activo'),
(1,'ordenes_compra','tabla','Órdenes de compra emitidas a proveedores con montos y fechas de entrega.','Operaciones','Ana Garcia','Compras','activo'),
(6,'nominas_mensual','dataset','Nómina mensual para analítica de costos de personal.','Recursos Humanos','Diego Fernandez','RRHH','activo'),
(2,'reclamos_clientes','tabla','Reclamos y tickets de clientes con estado de atención.','Cliente','Luis Perez','Atención al Cliente','pendiente');

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
