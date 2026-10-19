# Sistema Inteligente de Metadata Management (MetaData Manager v1.0)

Plataforma web para administrar de forma centralizada los metadatos de una organización:
registra fuentes, captura activos automáticamente, documenta metadatos técnicos/operativos/de
negocio, sugiere categorías con Python, valida con humanos y permite encontrar todo con
búsqueda inteligente. Con trazabilidad, calidad medida, reportes, bitácora y control por roles.

**Stack:** PHP 8 + MySQL/MariaDB + Python 3 (solo librería estándar) + HTML5/CSS3/JS puro.
Sin frameworks, sin nube, sin APIs de pago. Tema baby blue + morado, responsive.

---

## Índice
1. [Requisitos](#1-requisitos)
2. [Descargar el proyecto](#2-descargar-el-proyecto)
3. [Instalar y arrancar XAMPP](#3-instalar-y-arrancar-xampp)
4. [Colocar el proyecto en htdocs](#4-colocar-el-proyecto-en-htdocs)
5. [Crear la base de datos](#5-crear-la-base-de-datos)
6. [Configurar la aplicación](#6-configurar-la-aplicación)
7. [Verificar Python](#7-verificar-python)
8. [Entrar al sistema](#8-entrar-al-sistema)
9. [Verificación rápida (5 minutos)](#9-verificación-rápida-5-minutos)
10. [Estructura del proyecto](#10-estructura-del-proyecto)
11. [Usuarios y roles](#11-usuarios-y-roles)
12. [Seguridad de las capturas externas](#12-seguridad-de-las-capturas-externas)
13. [Solución de problemas](#13-solución-de-problemas)

---

## 1. Requisitos

| Necesitas | Versión | Dónde conseguirlo |
|---|---|---|
| Windows 10/11 (o Linux) | — | — |
| XAMPP (Apache + MariaDB + PHP) | 8.x | https://www.apachefriends.org |
| Python | 3.8 o superior | https://www.python.org (marca **Add to PATH** al instalar) |
| Navegador moderno | Chrome / Edge / Firefox | — |
| Git (opcional, para clonar) | cualquiera | https://git-scm.com |

> No se necesita internet para usar el sistema (la fuente Inter de Google es opcional y tiene
> respaldo local). Solo se usa internet para descargar XAMPP/Python la primera vez.

## 2. Descargar el proyecto

**Opción A — clonar (recomendado):**
```bash
cd C:\xampp\htdocs
git clone https://github.com/canahis793-droid/G19X-UTSLRC-EAOC-374-ACADEMIC.git sistema-metadata-management
```

**Opción B — ZIP:** en GitHub pulsa *Code → Download ZIP*, descomprímelo y renombra la
carpeta a `sistema-metadata-management` dentro de `C:\xampp\htdocs`.

Al final debe existir esta ruta:
```
C:\xampp\htdocs\sistema-metadata-management\public\login.php
```

## 3. Instalar y arrancar XAMPP

1. Instala XAMPP con opciones por defecto.
2. Abre el **XAMPP Control Panel** y pulsa **Start** en **Apache** y **MySQL**.
3. Comprueba: abre `http://localhost/` → debe salir la página de XAMPP.

> Si el puerto 3306 está ocupado por otro MySQL, MariaDB no arrancará en ese puerto.
> Solución: arráncala en otro puerto (ej. 3307) y apúntalo en el paso 6
> (`DB_PORT`). La app funciona igual.

## 4. Colocar el proyecto en htdocs

Si clonaste directo en `htdocs` ya está. Si lo tienes en otro lado, cópialo:
```
(origen)\sistema-metadata-management  →  C:\xampp\htdocs\sistema-metadata-management
```
Crea la carpeta `uploads` con escritura (XAMPP en Windows ya la permite):
`C:\xampp\htdocs\sistema-metadata-management\uploads\`

## 5. Crear la base de datos

1. Abre `http://localhost/phpmyadmin`.
2. Pestaña **Importar** → *Seleccionar archivo* → elige `database.sql` del proyecto → **Importar**.
3. Verifica: base `metadata_db` con 17 tablas y datos iniciales
   (3 usuarios, 6 fuentes, 18 activos, 32 metadatos).

**Alternativa por consola:**
```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\sistema-metadata-management\database.sql
```
*(Si tu MariaDB corre en otro puerto, agrega `-P 3307`.)*

## 6. Configurar la aplicación

Edita `config/database.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');      // valor por defecto de XAMPP
define('DB_NAME', 'metadata_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // tu clave de MySQL, si tiene
```

### Si tu base de datos no está en el puerto 3306
El repositorio deja **3306**, que es el puerto por defecto de XAMPP. Si el tuyo usa otro,
cambia **solo** `DB_PORT` en `config/database.php`. Para saber en qué puerto trabaja
tu MySQL/MariaDB:
- XAMPP: abre el **Shell** de XAMPP y ejecuta `mysqladmin ping`; el puerto aparece en el log
  (`xampp\mysql\bin\mysqld.exe --port=...` en `my.ini`, sección `[mysqld]`).
- Si tu puerto no es 3306, queda así:
```php
define('DB_PORT', '3307');   // tu MariaDB escucha en 3307
```
Nada más cambia: la aplicación funciona igual en cualquier puerto.

Edita `config/config.php` solo si hace falta:
```php
define('BASE_URL', '/sistema-metadata-management'); // cambia si la carpeta se llama distinto
define('PYTHON_CMD', 'py');     // Windows: py | Linux: python3
```

## 7. Verificar Python

En terminal:
```bash
py --version        # Windows  →  Python 3.x
# python3 --version # Linux
```
Sin librerías extra: el sistema solo usa la librería estándar.
Prueba definitiva dentro de la app: entra como admin → **Configuración → Probar conexión Python**
(debe responder `OK: Python responde`).

## 8. Entrar al sistema

Abre:
```
http://localhost/sistema-metadata-management/public/login.php
```

| Cuenta | Clave | Rol |
|---|---|---|
| `admin@demo.local` | `admin123` | Administración total |
| `steward@demo.local` | `steward123` | Valida datos y calidad |
| `usuario@demo.local` | `user123` | Solo consulta |

## 9. Verificación rápida (5 minutos)

1. Entra como admin → ves el **Dashboard** con números y gráficos.
2. Ve a **Captura automática → Subir CSV** (fuente *Ventas CSV 2025*) con cualquier CSV de
   3 columnas → te redirige a la ficha del activo creado.
3. Ve a **Clasificación → Analizar todos** → valida uno como *Venta*.
4. Ve a **Búsqueda inteligente**, escribe `informacion de compradores` → deben salir
   `clientes` y `usuarios_crm` con %.
5. Ve a **Calidad → Evaluar todos**, luego **Reportes → Exportar → Activos CSV**.
6. Todo lo que hiciste quedó en **Bitácora**. Si estos 6 pasos funcionan, el despliegue es correcto.

## 10. Estructura del proyecto

```
config/          database.php (PDO), config.php (app)
includes/        auth.php, permisos.php, header/footer, funciones.php (puente Python, CSRF…)
public/          index.php (Front Controller: único punto de entrada ?p=…), login.php, logout.php, 404.php
dashboard/       tablero general
usuarios/        CRUD + activar/desactivar (admin)
fuentes/         CRUD de orígenes
activos/         catálogo (index/crear/editar/detalle)
metadatos/       CRUD técnico/operativo/negocio
captura/         MySQL / CSV / JSON / API + historial + archivos
clasificacion/   sugerir + validar/rechazar + bulk
enriquecimiento/ sugerencias Python + aceptar/rechazar
busqueda/        normal (MySQL) e inteligente (TF-IDF)
trazabilidad/    relaciones + mapa
calidad/         % + problemas + recomendaciones + bulk
reportes/        KPIs + 4 exports CSV + guardados
papelera/        restaurar/purgar borrados (admin)
bitacora/        auditoría con filtros (admin)
configuracion/   parámetros + test Python + tokens API (admin)
api/             activos.php, fuentes.php, busqueda.php (sesión o ?token=)
python/          extractor, clasificador, buscador, calidad, enriquecimiento, nlp_utils
assets/          css/style.css, js/*.js, img/favicon.svg
docs/            ARQUITECTURA, ENDPOINTS, MANUAL_USUARIO, PRUEBAS, PRD (+Word)
tests/           crawl.py (20 rutas), aceptacion.py (22 pruebas), README.md
uploads/         archivos subidos (protegido con .htaccess)
database.sql     BD completa + datos · migracion_v2/v3.sql (mejoras)
```

## 11. Correr las pruebas automatizadas

Con la aplicacion ya servida por Apache:

```bash
py tests/crawl.py        # 20 pantallas, busca errores de PHP/MySQL
py tests/aceptacion.py   # 22 pruebas funcionales de los flujos principales
```

Solo usan la libreria estandar de Python. Detalle en `tests/README.md`.

## 11. Usuarios y roles

- **Administrador:** usuarios, fuentes, capturas, reportes, papelera, bitácora, configuración,
  tokens API. No puede eliminarse ni dejar al sistema sin admin (protegido).
- **Data Steward:** activos, metadatos, capturas, clasificar, enriquecer, calidad, trazabilidad.
- **Usuario:** dashboard, búsqueda, detalle, trazabilidad, reportes y exportaciones.

## 12. Seguridad de las capturas externas

Las capturas desde **MySQL** y desde **API** son peticiones que salen del servidor hacia
otras máquinas. Para que alguien con permiso de captura no pueda usar el sistema para
alcanzar equipos de la red interna (ataque SSRF), ambas validan el host con la misma
función `validar_host_publico()` de `includes/funciones.php`:

- Solo se aceptan hosts públicos: bloquea `localhost`, loopback (`127.x`, `::1`),
  redes privadas (`10.x`, `172.16-31.x`, `192.168.x`), enlace local (`169.254.x`)
  y direcciones IPv6 internas.
- Se valida el **puerto** (1–65535) y el **nombre de la base** (solo caracteres seguros).

Si necesitas capturar desde tu propio `localhost` durante una demostración, activa
`CAPTURA_PERMITE_LOCAL` en `config/config.php` (ponlo en `true`) y vuelve a `false`
después: con esa opción el sistema puede alcanzar máquinas internas.

### Por qué no se guarda la contraseña de la base externa

La contraseña que escribes en el formulario de captura se usa **únicamente** para abrir
esa conexión PDO en ese instante. No se escribe en la base de datos, ni en la bitácora,
ni en el historial de procesos, ni en la sesión. Es la forma correcta de handling: si se
guardara, quedaría en texto plano accesible a cualquiera con permiso de captura o de
lectura de la bitácora, y bastaría un respaldo de la base para exponerla. Al no
guardarla, el único momento en que existe en memoria es mientras corre la captura, y se
descarta al terminar. Si necesitas capturarla de forma automática en el futuro, el
lugar correcto es el gestor de secretos del servidor (variables de entorno), no la
base de datos del catálogo.

## 13. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `SQLSTATE[HY000] [1045]` | Usuario/clave MySQL mal | Revisa `config/database.php` |
| `Unknown database` | No importaste el SQL | Repite el paso 5 |
| `No se encontró Python` | `PYTHON_CMD` mal | `py` en Windows, `python3` en Linux; usa *Probar conexión* |
| Página en blanco / 404 | `BASE_URL` distinto a la carpeta | Iguala `BASE_URL` al nombre real en `htdocs` |
| Login dice *Sesión expirada* | Token CSRF vencido | Recarga el login e intenta de nuevo |
| Error 419 en formularios | Sesión caducada (30 min) | Vuelve a entrar |
| Puerto 3306 ocupado | Otro MySQL instalado | MariaDB en 3307 + `DB_PORT='3307'` |
| Archivo rechazado | >10 MB o extensión distinta | Sube `.csv` al módulo CSV y `.json` al JSON |

---

**Flujo de uso típico:** admin registra fuente → captura → Python sugiere → steward valida →
usuario busca y usa → reportes + bitácora. Documentación a detalle en `docs/`.
