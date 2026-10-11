# PRD — Sistema Inteligente de Metadata Management (MetaData Manager v1.0)

| Campo | Detalle |
|---|---|
| Producto | MetaData Manager — plataforma web de gobierno de metadatos |
| Versión | 1.0.0 |
| Stack | PHP 8 + MySQL/MariaDB + Python 3 (stdlib) + HTML5/CSS3/JS puro |
| Entorno | Local, XAMPP (Apache + MariaDB), sin nube ni APIs de pago |
| Tema visual | Baby blue `#89CFF0` + morado `#7C3AED`, responsive, fuente Inter |

## 1. Problema
Los datos viven dispersos (bases, CSV, JSON, APIs) sin inventario central: nadie sabe qué existe, qué significa, quién lo cuida ni qué tan confiable es. Resultado: duplicados, reportes contradictorios y decisiones a ciegas.

## 2. Objetivo del producto
Plataforma central que **registra fuentes, captura activos automáticamente, documenta metadatos, sugiere con Python, valida con humanos y permite encontrar todo con búsqueda inteligente**, con trazabilidad, calidad medida y auditoría total.

## 3. Usuarios y roles
| Rol | Puede | No puede |
|---|---|---|
| Administrador | Todo: usuarios, fuentes, capturas, reportes, papelera, bitácora, configuración, tokens API | Eliminarse / dejar al sistema sin admin (bloqueado) |
| Data Steward | Crear/editar activos, metadatos, capturar, clasificar, enriquecer, calidad, trazabilidad | Usuarios, bitácora, configuración, papelera, eliminar fuentes |
| Usuario | Dashboard, búsqueda, detalle, trazabilidad, reportes, exports | Crear/editar/validar nada |

## 4. Requerimientos funcionales
### F1 Autenticación y sesiones
- Login correo+contraseña (bcrypt), CSRF, throttle 5 intentos/1 min, expiración 30 min inactivo, cierre de sesión, protección de rutas por rol (403).
### F2 Dashboard
- Saludo, accesos rápidos, 6 KPIs, barras por categoría/fuente (Canvas), top peor calidad, actividad reciente.
### F3 Fuentes (CRUD + filtros + conteo de activos; borrado a papelera con cascada documentada)
- Tipos: Base de datos, Archivo CSV, Archivo JSON, API, Sistema empresarial, Data lake simulado, Otra.
### F4 Activos (CRUD + filtros + paginación; borrado admin a papelera)
- Tipos: tabla, archivo, api, dataset, reporte, aplicacion, vista, otro. Categorías: Cliente, Usuario, Venta, Producto, Inventario, Finanzas, Recursos Humanos, Operaciones, Otro. Estados: activo/pendiente/inactivo.
### F5 Metadatos (CRUD técnico/operativo/negocio + filtros + paginación)
### F6 Captura automática
- MySQL vía INFORMATION_SCHEMA (tablas→activos, columnas→metadatos, PK/FK, sin duplicados).
- CSV/JSON (upload ≤10 MB, extensión validada, `extractor.py`: columnas/tipos/filas/vacíos o propiedades/registros).
- API (registro descriptivo + muestra, anti-SSRF: solo http/https públicas).
- Historial de procesos + gestor de archivos (borrar/purgar +30 días).
### F7 Clasificación (sugerir con `clasificador.py`, validar/rechazar, bulk, historial; whitelist de categorías)
### F8 Enriquecimiento (`enriquecimiento.py` → aceptar aplica cambios / rechazar; todo marcado como sugerencia)
### F9 Búsqueda (normal LIKE + inteligente TF-IDF/sinónimos con score; top global y recientes por usuario)
### F10 Trazabilidad (relaciones tipadas + mapa + tabla; detalle con dirección entrante/saliente correcta)
### F11 Calidad (`calidad.py` 0–100% + problemas + recomendaciones; evaluar uno/todos; NULLs al final)
### F12 Reportes (KPIs, desgloses con barras, 4 exports CSV, guardar/recuperar/eliminar fotos de resumen)
### F13 Papelera (fuentes con activos+metadatos, activos con metadatos+etiquetas, usuarios; restaurar/purgar; usuarios restaurados inactivos sin clave)
### F14 Bitácora (filtros módulo/texto/fechas, paginación, export; login, CRUD, capturas, análisis Python)
### F15 Configuración + API (parámetros, test Python, tokens Bearer/?token= con último uso; endpoints activos/fuentes/búsqueda; 401 sin auth)

## 5. Inteligencia Python (sin dependencias)
`nlp_utils` (tokens, stopwords ES/EN, sinónimos, TF-IDF, coseno) + 5 scripts desacoplados vía JSON temporal con timeout 30s. Ninguna sugerencia se aplica sin validación humana.

## 6. Requerimientos no funcionales
- **Seguridad:** PDO preparadas, `e()` en salidas, JSON_HEX en JS inline, CSRF global, uploads blindados (.htaccess + validación), guardián último admin, sesiones con regeneración.
- **Rendimiento:** paginación (15/pág), límites en listados, bulk con `set_time_limit`.
- **Usabilidad:** Inter, encabezados que explican cada módulo, toasts, modal de confirmación, ripple, estados vacíos amigables, responsive ≤900px.
- **Portabilidad:** `BASE_URL` y `DB_PORT` configurables; rutas 404 relativas.

## 7. Datos iniciales
3 cuentas (admin/steward/consulta), 6 fuentes, 18 activos, metadatos/etiquetas/relaciones/calidad/clasificaciones de ejemplo. Migraciones versionadas (`database.sql`, `migracion_v2/v3.sql`).

## 8. Criterios de aceptación
- Batería automatizada 22/22 (roles, 403, CRUD, CSV, bulk, búsqueda, calidad, trazabilidad, exports, tokens, 404, fuerza bruta) + crawl 18/18 rutas sin errores.
- `php -l` limpio, Python solo-stdlib compilado.

## 9. Fuera de alcance v1.0
SSO/LDAP, multi-idioma, conectores empresariales propietarios, tiempo real, app móvil, roles personalizables por UI.

## 10. Roadmap sugerido
v1.1 limpieza programada de uploads + alertas de calidad; v1.2 roles configurables + papelera con expiración; v2.0 API write + webhooks.
