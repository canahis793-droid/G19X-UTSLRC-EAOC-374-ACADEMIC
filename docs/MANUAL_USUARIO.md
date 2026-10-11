# Manual básico de usuario

## Roles
- **Administrador**: todo (usuarios, fuentes, capturas, reportes, bitácora, configuración).
- **Data Steward**: valida y corrige (activos, metadatos, clasificación, enriquecimiento, calidad, trazabilidad).
- **Usuario**: consulta (dashboard, búsqueda, detalle, reportes permitidos).

## 1. Iniciar sesión
Ve a `/public/login.php`, ingresa correo y contraseña. Si te equivocas verás el error. Usa el menú lateral para navegar. Cierra con "Cerrar sesión".

## 2. Dashboard
Tarjetas (usuarios, fuentes, activos, metadatos, pendientes, calidad<60%), gráfico por categoría (Canvas), barras por fuente, top peor calidad y últimas actividades.

## 3. Fuentes
Filtra por texto/tipo. Como steward/admin: Agrega con nombre, tipo, ubicación, responsable. "Ver activos" filtra el catálogo. Solo admin elimina.

## 4. Activos
Filtra por texto/categoría/fuente/estado. "Detalle" muestra ficha completa. Crear: elige fuente, nombre, tipo, categoría inicial, descripción. Al guardar, Python sugiere clasificación.

## 5. Metadatos
Filtra por activo/tipo (técnico/operativo/negocio). Registra campo con tipo de dato, PK/FK, nulos, formato, responsable, definición de negocio, importancia, regla, etc.

## 6. Captura
- MySQL: datos de conexión + BD → crea tablas/columnas.
- CSV/JSON: sube archivo → Python lo analiza.
- API: pega URL + nombre.
Revisa el historial al final.

## 7. Clasificación
"Analizar" genera categoría con Python. En pendientes elige la categoría final y "Validar" (aplica al activo) o "Rechazar".

## 8. Enriquecimiento
"Generar" crea descripción sugerida, keywords, categoría y relaciones. "Aceptar y aplicar" actualiza el activo y crea etiquetas. Todo se etiqueta como sugerencia Python.

## 9. Búsqueda
Modo **Inteligente** (Python TF-IDF + sinónimos): prueba "información de compradores". Verás % de coincidencia. Modo **Normal**: LIKE MySQL.

## 10. Trazabilidad
Crea Origen→Destino con tipo (uno-a-muchos, deriva, etc.). El mapa dibuja tarjetas. Ejemplo: clientes → facturas → detalle_factura.

## 11. Calidad
"Evaluar" calcula % con Python, lista problemas y recomendaciones. Prioriza los <60%.

## 12. Reportes y bitácora
Reportes: conteos por categoría/fuente/tipo + exportar CSV. Bitácora (admin): quién hizo qué, cuándo, desde qué IP; filtrable por módulo.
