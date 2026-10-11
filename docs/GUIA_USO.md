# Guía de uso y verificación — MetaData Manager v1.0

Cómo usar la aplicación, qué puede hacer cada parte y cómo comprobar que todo funciona bien.

## 1. Entrar

1. Abre `http://localhost/sistema-metadata-management/public/login.php`.
2. Usa una cuenta según lo que quieras probar:

| Cuenta | Clave | Rol | Sirve para |
|---|---|---|---|
| `admin@demo.local` | `admin123` | Administración | Probar TODO |
| `steward@demo.local` | `steward123` | Data Steward | Capturar, validar, calidad |
| `usuario@demo.local` | `user123` | Consulta | Búsqueda y lectura |

3. Si ves el **Dashboard** con números y gráficos, el sistema está vivo.
   Si falla: revisa [README §12](../README.md) (BD, puerto, Python).

## 2. Qué puede hacer (tour guiado, 15 minutos)

Sigue este orden con la cuenta **admin**:

| # | Haz esto | Dónde | Qué debe pasar (así sabes que funciona) |
|---|---|---|---|
| 1 | Mira el Dashboard | `?p=dashboard` | 4 KPIs (3 usuarios, 6 fuentes, 18 activos, 32 metadatos), barras por categoría/fuente, tira de alertas |
| 2 | Busca `informacion de compradores` | Búsqueda → modo Inteligente | Aparecen `clientes` y `usuarios_crm` con % (aunque no escribiste esas palabras) |
| 3 | Sube un CSV de 3 columnas | Captura → Subir CSV (fuente *Ventas CSV 2025*) | Te redirige a la ficha del activo nuevo con sus columnas detectadas |
| 4 | Analiza y valida | Clasificación → Analizar todos → Validar uno | El activo cambia a estado activo con su categoría |
| 5 | Evalúa calidad | Calidad → Evaluar todos | Promedio visible; los <60% en rojo con sus motivos |
| 6 | Crea una relación | Trazabilidad (origen `clientes` → destino `facturas`) | Aparece en el mapa y en la tabla |
| 7 | Exporta | Reportes → Activos (CSV) | Se descarga el archivo y abre en Excel |
| 8 | Guarda foto | Reportes → nombre + Guardar foto actual | Queda en *Reportes guardados* con su resumen |
| 9 | Borra y restaura | Activos → Editar → Eliminar → Papelera → Restaurar | El activo vuelve con metadatos y etiquetas |
| 10 | Audita | Bitácora (filtra por módulo `captura`) | Ves cada paso anterior con usuario, fecha e IP |

Si los 10 pasos salen, la app está **100% operativa**.

## 3. Qué puede hacer cada módulo (referencia rápida)

- **Dashboard:** salud general, accesos directos, actividad reciente.
- **Búsqueda inteligente:** motor tolerante a sinónimos (Python TF-IDF) + modo exacto MySQL; guarda top global y tus recientes.
- **Catálogo:** filtros, paginación, crear/editar/eliminar (a Papelera), ficha con metadatos, calidad, clasificación, enriquecimiento y relaciones.
- **Fuentes:** CRUD de orígenes con conteo de activos.
- **Metadatos:** CRUD técnico/operativo/negocio por campo.
- **Captura:** MySQL (tablas→activos, columnas→metadatos), CSV/JSON (≤10 MB, analizados por Python), API (registro descriptivo anti-SSRF), historial y gestor de archivos.
- **Clasificación:** sugerencias Python con % de confianza; validar aplica al activo.
- **Enriquecimiento:** descripciones/keywords/relaciones sugeridas; aceptar las aplica.
- **Calidad:** 0–100% con problemas y recomendaciones accionables; evaluar uno o todos.
- **Trazabilidad:** relaciones tipadas + mapa visual.
- **Reportes:** KPIs, distribuciones, 4 exports CSV, fotos guardadas.
- **Papelera:** restaura fuentes (con todo), activos y usuarios; purga definitiva.
- **Bitácora:** auditoría con filtros de módulo, texto y fechas.
- **Configuración:** parámetros, test de Python y tokens API (`?token=` o Bearer).
- **API:** `activos`, `fuentes`, `busqueda` en JSON (sesión o token; 401 sin auth).

## 4. Cómo saber que algo anda mal (y qué hacer)

| Señal | Significado | Acción |
|---|---|---|
| Dashboard con ceros | BD vacía o distinta | Reimporta `database.sql`, revisa `DB_PORT` |
| *Probar Python* dice FALLO | Python inalcanzable | Cambia `python_path` a `py`/`python3` |
| 419 en formularios | Sesión caducada | Vuelve a entrar |
| 403 en un módulo | Tu rol no tiene permiso | Entra como admin |
| Página 404 del sistema | Ruta `?p=` inválida | Usa el menú lateral |

## 5. Estado del proyecto

Terminado y verificado: lint PHP/JS/Python limpio, 19/19 rutas OK, batería funcional
22/22 OK, base con datos empresariales de ejemplo. Detalle de pruebas en `PRUEBAS.md`.
