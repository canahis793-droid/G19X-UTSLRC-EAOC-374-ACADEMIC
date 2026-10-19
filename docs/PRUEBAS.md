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
2. Validar como "Venta" → el activo cambia a esa categoría y estado activo.

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
py tests/crawl.py        # recorre las 20 pantallas y busca errores de PHP/MySQL
py tests/aceptacion.py   # 22 pruebas funcionales de los flujos principales
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
- Bateria automatizada: 23/23 OK (login 3 roles, 403 por rol, CRUD fuentes, captura CSV, clasificacion masiva, busqueda inteligente, calidad masiva, trazabilidad crear/eliminar, export CSV, bitacora, test Python, 404, API JSON, fuerza bruta).
