# Pruebas automatizadas

Dos scripts en Python (solo libreria estandar) que verifican la aplicacion ya
servida por Apache. No necesitan instalar nada.

## Requisitos
- XAMPP encendido (Apache + MySQL/MariaDB).
- Base `metadata_db` importada (`database.sql`).
- Sesión cerrada (los scripts inician su propia).

## Como ejecutarlas

```bash
py tests/crawl.py
py tests/aceptacion.py
```

Si la aplicacion esta en otra ruta:

```bash
py tests/aceptacion.py --url http://localhost/mi-carpeta
```

## Que hace cada una

### `tests/crawl.py` — recorrido de pantallas
Entra como administrador y abre las 20 pantallas del sistema. Falla si alguna
devuelve un codigo distinto de 200 o si la pagina contiene un error de PHP o de
MySQL (Fatal error, Warning, SQLSTATE, Uncaught...).

Salida esperada:
```
rutas revisadas: 20
rutas con problema: 0
```

### `tests/aceptacion.py` — flujos funcionales
Ejecuta 22 comprobaciones:

| Bloque | Comprueba |
|---|---|
| 1. Acceso | login de los 3 roles, 403 de usuario en usuarios/configuracion, 403 de steward en usuarios |
| 2. Fuentes | crear fuente, verla en el listado, eliminarla (va a papelera) |
| 3. Captura | sube un CSV real y comprueba que crea el activo con sus metadatos |
| 4. Clasificacion | analisis masivo con Python |
| 5. Busqueda | encuentra `clientes` al buscar "compradores" y muestra el % de coincidencia |
| 6. Calidad | evaluacion masiva y promedio visible |
| 7. Trazabilidad | crea una relacion y la elimina |
| 8. Reportes | export CSV, bitacora, test de Python, 404, API JSON |
| 9. Seguridad | bloqueo tras varios intentos fallidos de login |

Salida esperada: `RESULTADO: 22 OK, 0 FALLAS`.

La prueba **crea y elimina sus propios datos** (fuente de prueba, CSV de prueba y
su activo, relacion de prueba). Si se interrumpe a la mitad, se limpian a mano
en `activos` (nombre que empieza con `qa_`) y en `fuentes`.

## Nota
Los datos de la base pueden verse afectados por estas pruebas (bitacora, ultima
clasificacion, evaluacion de calidad). Es esperado: prueban el sistema real.
Para dejarla igual que en la entrega, vuelve a importar `database.sql`.