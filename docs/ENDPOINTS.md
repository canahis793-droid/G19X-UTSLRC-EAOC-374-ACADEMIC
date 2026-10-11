# Endpoints (páginas + API JSON)

Base: `http://localhost/sistema-metadata-management`

## Páginas (requieren sesión salvo login)
| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET/POST | /public/login.php | público | Login correo+password |
| GET | /public/logout.php | auth | Cierra sesión |
| GET | /dashboard/index.php | todos | Totales, gráficos Canvas, pendientes, bitácora |
| GET/POST | /usuarios/index.php | admin | CRUD + activar/desactivar |
| GET/POST | /fuentes/index.php?q=&tipo= | ver todos, editar steward+ | CRUD + filtros |
| GET | /activos/index.php?q=&categoria=&fuente=&estado= | todos | Catálogo con filtros |
| GET/POST | /activos/crear.php | steward+ | Crea + pre-clasifica con Python |
| GET/POST | /activos/editar.php?id= | steward+ | Edita + etiquetas |
| GET | /activos/detalle.php?id= | todos | Ficha: metadatos, calidad, clasificación, enriquecimiento, relaciones |
| GET/POST | /metadatos/index.php?activo=&tipo= | ver todos, editar steward+ | CRUD técnico/operativo/negocio |
| GET | /captura/index.php | steward+ | Formularios MySQL/CSV/JSON/API + historial |
| POST | /captura/procesar_mysql.php | steward+ | Params: id_fuente,host,bd,user,pass |
| POST | /captura/procesar_archivo.php | steward+ | multipart archivo, tipo=csv\|json, id_fuente |
| POST | /captura/procesar_api.php | steward+ | Params: id_fuente,url,nombre |
| GET/POST | /clasificacion/index.php (?analizar=) | steward+ | Genera con Python, valida/rechaza |
| GET/POST | /enriquecimiento/index.php (?generar=) | steward+ | Genera sugerencias, acepta (aplica) / rechaza |
| GET | /busqueda/index.php?q=&modo=inteligente\|normal | todos | MySQL LIKE o Python TF-IDF |
| GET/POST | /trazabilidad/index.php (?activo=) | ver todos, crear steward+ | CRUD relaciones + mapa JS |
| GET | /calidad/index.php (?analizar=) | ver todos, evaluar steward+ | Lista + evaluación Python |
| GET | /reportes/index.php | todos (exportar todos) | Agregados por categoría/fuente/tipo |
| GET | /reportes/index.php (+POST guardar, ?eliminar_rep=) | todos (eliminar admin) | Resumen + guardar foto + exportar CSV |
| GET | /reportes/exportar.php?tipo=activos\|calidad\|metadatos\|bitacora | auth | Descarga CSV con BOM |
| GET | /bitacora/index.php?modulo=&q= | admin | Filtros + export |
| GET/POST | /configuracion/index.php (+tokens, ?probar_python) | admin | Parámetros, test Python, crear/revocar tokens API |
| GET | /papelera/index.php (?restaurar=, ?purgar=) | admin | Restaura o purga fuentes/activos/usuarios |

## API JSON (sesión o token: `?token=` o `Authorization: Bearer`)
- `GET /api/activos.php?q=` → [{id,nombre,descripcion,categoria,tipo,fuente}] (401 sin auth)
- `GET /api/fuentes.php` → [{id,nombre,tipo,estado}]
- `GET /api/busqueda.php?q=` → {ok,query,resultados:[{id,score}]} (vía `buscador.py`)

## Contratos Python (JSON por archivos temporales)
- `extractor.py` in {tipo,archivo,nombre} → {ok,resultado|error}
- `clasificador.py` in {nombre,descripcion,texto} → {ok,categoria,confianza,palabras_clave}
- `buscador.py` in {query,activos[]} → {ok,resultados:[{id,score}]}
- `calidad.py` in {activo,metadatos[]} → {ok,porcentaje,problemas[],recomendaciones[]}
- `enriquecimiento.py` in {activo,metadatos[],todos[]} → {ok,descripcion_sugerida,palabras_clave[],categoria_sugerida,relaciones_sugeridas[],calidad_estimada}
