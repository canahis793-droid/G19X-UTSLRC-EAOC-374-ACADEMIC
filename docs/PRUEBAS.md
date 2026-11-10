# Pruebas básicas del sistema

Ejecuta en este orden con datos demo recién importados.

## P1. Login y roles
1. `admin@demo.local/admin123` entra a dashboard. Debe ver Usuarios, Bitácora, Configuración.
2. Sal, entra como `usuario@demo.local/user123`. No debe ver Usuarios/Bitácora (sidebar). Intenta abrir `/public/index.php?p=usuarios` → debe decir 403.
3. Sal, entra como `steward@demo.local/steward123`. Debe ver Clasificación/Calidad pero no Usuarios.

## P2. Fuentes CRUD
1. Como admin crea fuente "Prueba CSV" tipo Archivo CSV. Busca "Prueba", filtra tipo. Edítala y desactívala. Elimínala.
✔ Sin errores PDO, aparece en bitácora.

## P3. Captura CSV
1. Crea un `prueba.csv` con `nombre,email,total` y 3 filas (una con vacío).
2. Captura → Subir CSV → verifica activo creado + 3 metadatos + proceso exitoso en historial.

## P4. Clasificación
1. Clasificación → Analizar un pendiente → debe decir categoría + confianza.
2. Elegir una categoría (p. ej. "Venta") y pulsar Validar → la fila pasa al historial como validada y el activo cambia a esa categoría y estado activo.

## P5. Búsqueda inteligente
1. Busca `informacion de compradores` en modo Inteligente → deben salir `clientes` y `usuarios_crm` arriba con %.
2. Cambia a Normal → compara (menos tolerante a sinónimos).

## P6. Calidad y enriquecimiento
1. Calidad → Evaluar `ventas_2025.csv` → % bajo + problemas (sin descripción, etc.).
2. Enriquecimiento → Generar para `usuarios_crm` → Aceptar → verifica descripción y etiquetas nuevas en detalle.

## P7. Trazabilidad
1. Crea relación `clientes → usuarios_crm` tipo similitud. Verifica tarjeta en mapa y fila en tabla.

## P8. Reportes + bitácora
1. Reportes → Exportar los 4 CSV, ábrelos en Excel.
2. Bitácora → filtra módulo `captura`, busca tu captura. Debe tener tu usuario, IP y fecha.

## P9. Python directo (opcional)
```
py python/clasificador.py  (con JSON de prueba)
py python/buscador.py
```
Deben imprimir JSON con `ok:true`. Si no, revisa `python_path` en Configuración.

## Pruebas automatizadas

Viven en `tests/` y se ejecutan contra la aplicacion ya servida por Apache.

```bash
py tests/crawl.py        # recorre las 20 pantallas y busca errores de PHP
py tests/aceptacion.py   # 26 pruebas funcionales de los flujos principales
```

Ambos usan solo la libreria estandar de Python. Aceptan `--url` si la aplicacion
no esta en `http://localhost/sistema-metadata-management`.
`tests/aceptacion.py` crea y elimina sus propios datos de prueba.

## Criterios de aceptación
- Sin referencias rotas (todas las rutas del sidebar abren).
- Login rechaza clave mala, usuario inactivo y POST sin CSRF; bloquea en el servidor tras 5 fallos (el bloqueo no se reinicia al borrar la cookie).
- PDO sin concatenar variables (revisar codigo).
- Python funciona sin pip (solo stdlib).
- Crawl de rutas: 20/20 sin errores.
- Bateria automatizada: 26/26 OK (login 3 roles, 403 por rol, CRUD fuentes, captura CSV, clasificacion masiva, validacion real de una sugerencia, busqueda inteligente, calidad masiva, trazabilidad crear/eliminar, export CSV, bitacora, test Python, 404, API JSON, fuerza bruta).

## Última ejecución - 9-nov-2026 · commit d1c15f2

Base `metadata_db` recién importada desde `database.sql` (18 tablas, 3 usuarios,
6 fuentes, 18 activos, 32 metadatos). Servidor: XAMPP, Apache en el puerto 80 y
MariaDB en el 3307.

```
> py tests/crawl.py
ok      /public/index.php?p=dashboard
ok      /public/index.php?p=busqueda
ok      /public/index.php?p=activos
ok      /public/index.php?p=activos/crear
ok      /public/index.php?p=activos/detalle&id=1
ok      /public/index.php?p=activos/editar&id=1
ok      /public/index.php?p=fuentes
ok      /public/index.php?p=metadatos
ok      /public/index.php?p=metadatos&activo=1
ok      /public/index.php?p=captura
ok      /public/index.php?p=clasificacion
ok      /public/index.php?p=enriquecimiento
ok      /public/index.php?p=calidad
ok      /public/index.php?p=trazabilidad
ok      /public/index.php?p=trazabilidad&activo=1
ok      /public/index.php?p=reportes
ok      /public/index.php?p=bitacora
ok      /public/index.php?p=papelera
ok      /public/index.php?p=usuarios
ok      /public/index.php?p=configuracion

rutas revisadas: 20
rutas con problema: 0

> py tests/aceptacion.py
MetaData Manager - bateria de aceptacion
Servidor: http://localhost/sistema-metadata-management

== 1. Acceso y roles por permisos ==
  OK    administrador inicia sesion
  OK    data steward inicia sesion
  OK    usuario de consulta inicia sesion
  OK    consulta bloqueado en usuarios (403)
  OK    steward bloqueado en usuarios (403)
  OK    consulta bloqueado en configuracion (403)

== 2. CRUD de fuentes ==
  OK    fuente creada y visible en el listado
  OK    fuente eliminada (pasa a papelera)

== 3. Captura de archivo CSV con Python ==
  OK    CSV procesado y redirige al detalle
  OK    el activo creado muestra sus metadatos

== 4. Clasificacion asistida ==
  OK    pagina de clasificacion operativa
  OK    el analisis masivo deja sugerencias pendientes
  OK    validar aplica la categoria elegida
  OK    la fila pasa al historial como validada

== 5. Busqueda inteligente ==
  OK    encuentra clientes por sinonimo
  OK    muestra porcentaje de coincidencia

== 6. Calidad de datos ==
  OK    calidad evaluada con promedio

== 7. Trazabilidad ==
  OK    relacion registrada
  OK    relacion eliminada

== 8. Reportes, bitacora y configuracion ==
  OK    exportacion CSV de activos
  OK    bitacora accesible para administrador
  OK    prueba de conexion con Python
  OK    pagina 404 responde 404
  OK    API JSON responde con la sesion

== 9. Proteccion contra fuerza bruta (limite en el servidor) ==
  OK    bloquea tras 5 intentos fallidos
  OK    el bloqueo sobrevive a borrar la cookie

==============================================
RESULTADO: 26 OK, 0 FALLAS
```

Además se verificó en navegador real, con sesión de steward, que al pulsar
«Validar» el formulario envía `accion=validar` y la categoría elegida aunque el
botón quede deshabilitado; la fila pasa al historial como `validada` y el activo
cambia de categoría. «Aceptar y aplicar» en Enriquecimiento deja la sugerencia en
`aceptado`, y un POST sin `accion` ya no la marca como rechazada.
