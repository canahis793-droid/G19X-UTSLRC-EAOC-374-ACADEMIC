# Endpoints (páginas + API JSON)

Base: `http://localhost/sistema-metadata-management`

## Páginas (requieren sesión salvo login)
| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET/POST | /public/index.php?p=public/login | público | Login correo+password |
| GET | /public/index.php?p=public/logout | auth | Cierra sesión |
| GET | /public/index.php?p=dashboard | todos | Totales, gráficos Canvas, pendientes, bitácora |
| GET/POST | /public/index.php?p=usuarios | admin | CRUD + activar/desactivar |
| GET/POST | /public/index.php?p=fuentes&q=&tipo= | ver todos, editar steward+ | CRUD + filtros |
| GET | /public/index.php?p=activos&q=&categoria=&fuente=&estado= | todos | Catálogo con filtros |
| GET/POST | /public/index.php?p=activos/crear | steward+ | Crea + pre-clasifica con Python |
| GET/POST | /public/index.php?p=activos/editar&id= | steward+ | Edita + etiquetas |
| GET | /public/index.php?p=activos/detalle&id= | todos | Ficha: metadatos, calidad, clasificación, enriquecimiento, relaciones |
| GET/POST | /public/index.php?p=metadatos&activo=&tipo= | ver todos, editar steward+ | CRUD técnico/operativo/negocio |
| GET | /public/index.php?p=captura | steward+ | Formularios MySQL/CSV/JSON/API + historial |
| POST | /public/index.php?p=captura/procesar_mysql | steward+ | Params: id_fuente,host,bd,user,pass |
| POST | /public/index.php?p=captura/procesar_archivo | steward+ | multipart archivo, tipo=csv\|json, id_fuente |
| POST | /public/index.php?p=captura/procesar_api | steward+ | Params: id_fuente,url,nombre |
| GET/POST | /public/index.php?p=clasificacion (?analizar=) | steward+ | Genera con Python, valida/rechaza |
| GET/POST | /public/index.php?p=enriquecimiento (?generar=) | steward+ | Genera sugerencias, acepta (aplica) / rechaza |
| GET | /public/index.php?p=busqueda&q=&modo=inteligente\|normal | todos | MySQL LIKE o Python TF-IDF |
| GET/POST | /public/index.php?p=trazabilidad (?activo=) | ver todos, crear steward+ | CRUD relaciones + mapa JS |
| GET | /public/index.php?p=calidad (?analizar=) | ver todos, evaluar steward+ | Lista + evaluación Python |
| GET | /public/index.php?p=reportes | todos (exportar todos) | Agregados por categoría/fuente/tipo |
| GET | /public/index.php?p=reportes (+POST guardar, ?eliminar_rep=) | todos (eliminar admin) | Resumen + guardar foto + exportar CSV |
| GET | /public/index.php?p=reportes/exportar&tipo=activos\|calidad\|metadatos\|bitacora | auth | Descarga CSV con BOM |
| GET | /public/index.php?p=bitacora&modulo=&q= | admin | Filtros + export |
| GET/POST | /public/index.php?p=configuracion (+tokens, ?probar_python) | admin | Parámetros, test Python, crear/revocar tokens API |
| GET | /public/index.php?p=papelera (?restaurar=, ?purgar=) | admin | Restaura o purga fuentes/activos/usuarios |

## API JSON (sesión o token: `?token=` o `Authorization: Bearer`)
- `GET /public/index.php?p=api/activos&q=` → [{id,nombre,descripcion,categoria,tipo,fuente}] (401 sin auth)
- `GET /public/index.php?p=api/fuentes` → [{id,nombre,tipo,estado}]
- `GET /public/index.php?p=api/busqueda&q=` → {ok,query,resultados:[{id,score}]} (vía `buscador.py`)

## Contratos Python (JSON por archivos temporales)
- `extractor.py` in {tipo,archivo,nombre} → {ok,resultado|error}
- `clasificador.py` in {nombre,descripcion,texto} → {ok,categoria,confianza,palabras_clave}
- `buscador.py` in {query,activos[]} → {ok,resultados:[{id,score}]}
- `calidad.py` in {activo,metadatos[]} → {ok,porcentaje,problemas[],recomendaciones[]}
- `enriquecimiento.py` in {activo,metadatos[],todos[]} → {ok,descripcion_sugerida,palabras_clave[],categoria_sugerida,relaciones_sugeridas[],calidad_estimada}
