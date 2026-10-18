# Guion de video — 5 minutos (MetaData Manager v1.0)

Voz en off + acción en pantalla. Tiempos orientativos; practice antes de grabar.

---

## 0:00 – 0:25 · Introducción (portada de la app)

**PANTALLA:** abrir `http://localhost/sistema-metadata-management/public/index.php` (login).

**VOZ:** "Las empresas tienen sus datos regados: bases de datos, Excel, archivos y APIs. Nadie sabe qué existe, qué significa ni si es confiable. **MetaData Manager** resuelve eso: crea un catálogo central e inteligente de todos los datos de la empresa."

## 0:25 – 0:55 · Acceso y roles

**PANTALLA:** login con `admin@demo.local` / `admin123` → dashboard.

**VOZ:** "El acceso es por rol y hay tres. **Administrador**: control total. **Data Steward**: captura, valida y evalúa calidad. **Usuario de consulta**: solo busca y lee. Las restricciones se aplican en el servidor: un usuario sin permiso recibe 403 aunque escriba la URL a mano."

## 0:55 – 1:25 · Dashboard

**PANTALLA:** dashboard con los 4 KPIs, barras por categoría y fuente, alertas y actividad reciente.

**VOZ:** "El dashboard resume la salud del catálogo: cuántos activos, fuentes, metadatos y usuarios hay, cómo se distribuyen y qué requiere atención."

## 1:25 – 1:50 · Fuentes de datos

**PANTALLA:** módulo Fuentes → crear una fuente nueva → ver su conteo de activos.

**VOZ:** "Primero se registra **de dónde** vienen los datos: un ERP, un Excel, una API, una base MySQL. Cada fuente queda con su responsable, tipo y estado, y muestra cuántos activos aporta."

## 1:50 – 2:20 · Captura automática (lo más importante)

**PANTALLA:** Captura → *Subir CSV* → elegir archivo de ejemplo → *Analizar con Python* → pantalla de detalle del activo creado.

**VOZ:** "Aquí es donde entra la inteligencia. El sistema admite cuatro vías: **MySQL** —importa tablas y columnas solo, sin duplicar—, **CSV y JSON** —los sube el usuario y Python los analiza—, y **API**, que se registra con su endpoint de forma segura. Todo lo capturado queda con su autor, su fecha y su archivo, y el activo nace *pendiente* hasta que un steward lo valida."

## 2:20 – 2:45 · Clasificación y enriquecimiento

**PANTALLA:** Clasificación → *Analizar todos* → suggestions con % → *Validar*; luego Enriquecimiento → *Generar sugerencias* → *Aceptar*.

**VOZ:** "Python propone la categoría de cada activo con un porcentaje de confianza, además de descripciones, palabras clave y relaciones. Nada se aplica solo: el steward acepta y el activo queda listo para buscar."

## 2:45 – 3:05 · Calidad de datos

**PANTALLA:** Calidad → *Evaluar todos* → ver el promedio, los activos bajos en rojo con sus motivos.

**VOZ:** "Cada metadato recibe una calificación de 0 a 100 por completitud, validez y consistencia, con el motivo exacto de cada problema y una recomendación concreta para corregirlo. Es el KPI que demuestra si el catálogo sirve."

## 3:05 – 3:25 · Trazabilidad

**PANTALLA:** Trazabilidad → crear relación `clientes → facturas` → ver el mapa.

**VOZ:** "Los datos no están solos: se relacionan. La trazabilidad registra qué depende de qué, y el mapa visual muestra esas conexiones de un vistazo."

## 3:25 – 3:50 · Búsqueda inteligente

**PANTALLA:** Búsqueda → escribir *"informacion de compradores"* → resultados con % → repetir en modo exacto.

**VOZ:** "Este es el módulo estrella. La búsqueda normal es un LIKE de MySQL; la **inteligente** usa TF-IDF y similitud de coseno en Python con sinónimos: escribo *compradores* y encuentra *clientes* y *usuarios_crm*, aunque esas palabras no estén escritas. Muestra el porcentaje de coincidencia y guarda el historial."

## 3:50 – 4:10 · Metadatos, papelera y bitácora

**PANTALLA:** Metadatos (CRUD) → Activos → *Eliminar* → Papelera → *Restaurar* → Bitácora con filtro.

**VOZ:** "Cada campo se documenta como técnico, operativo o de negocio. Nada se borra de verdad: va a la **papelera** y se restaura con sus metadatos y etiquetas. Y toda acción queda registrada en la **bitácora** con usuario, fecha e IP."

## 4:10 – 4:30 · Reportes, configuración y API

**PANTALLA:** Reportes → KPIs → *Exportar CSV* → guardar foto de reporte; Configuración → *Probar Python* y *Generar token*.

**VOZ:** "Los reportes dan indicadores y exportan a CSV, y se pueden guardar como foto. En configuración se ajustan parámetros, se prueba la conexión con Python y se generan **tokens API** para integrar otros sistemas."

## 4:30 – 5:00 · Cierre

**PANTALLA:** volver al dashboard; pantalla con el nombre del proyecto y GitHub.

**VOZ:** "Para resumir: un solo lugar donde ver, entender y medir los datos de una empresa. Sin servicios de pago y sin IA en la nube: **PHP, MySQL y Python con librería estándar**. El sistema está terminado, documentado, probado con veintidós pruebas automatizadas y subido a GitHub. Gracias por su atención."

---

## Checklist antes de grabar
1. Apache y MariaDB encendidos; `http://localhost/sistema-metadata-management/public/login.php` responde.
2. Sesión cerrada antes de grabar (para mostrar el login limpio).
3. Tener a mano un CSV de ejemplo para la sección de captura.
4. Navegador en pantalla completa, zoom 100%, sin notifications.
5. Ensayar una vez con cronómetro: el bloque de captura es el que más se desborda.