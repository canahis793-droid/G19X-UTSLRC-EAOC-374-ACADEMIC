# MVP — MetaData Manager v1.0

Producto mínimo viable entregado y funcionando. El [PRD](PRD.md) define **qué debe
hacer** el sistema; este documento define **qué se demostró** y **cómo se comprueba**.

## 1. Flujos mínimos que se demuestran

| Flujo | Qué hace el sistema | Cómo se comprueba |
|---|---|---|
| **Captura** | Toma un origen (CSV, JSON, MySQL o API), lo procesa con Python y crea el activo con sus metadatos. | Automático: `tests/aceptacion.py` bloque 3 sube un CSV real por `multipart/form-data` y exige que redirija al detalle y que el activo muestre sus metadatos. Manual: **Captura → CSV** con cualquier archivo de dos columnas. |
| **Clasificación** | Python sugiere categoría y confianza; el steward valida o rechaza. Ninguna sugerencia se aplica sola. | Automático: bloque 4 ejecuta el análisis masivo y verifica que la página quede operativa con su historial. Manual: **Clasificación → Analizar todos** y revisar el % de confianza de cada sugerencia. |
| **Búsqueda** | Busca por significado (TF-IDF + sinónimos), no solo por coincidencia exacta, y muestra el % de coincidencia. | Automático: bloque 5 busca "información de compradores" y exige que aparezca "clientes" y un porcentaje. Manual: **Búsqueda** con el mismo texto. |
| **Calidad** | Califica cada activo de 0 a 100 % con problemas detectados y recomendaciones. | Automático: bloque 6 corre la evaluación masiva y comprueba que el promedio quede visible. Manual: **Calidad → Evaluar todos**. |

El recorrido completo es: **Captura → Clasificación → Búsqueda → Calidad**, y cada paso
queda registrado en la **bitácora** con usuario, acción y fecha.

## 2. Qué queda fuera del MVP

SSO/LDAP, multi-idioma, conectores propietarios, tiempo real, aplicación móvil, roles
configurables por interfaz, escritura en la API (hoy es de solo lectura) y webhooks.
Está declarado también en el PRD (§9) con su roadmap (§10): no es un defecto, es
alcance decidido.

## 3. Cómo se comprueba todo

```bash
py tests/crawl.py        # 20 pantallas, 0 errores de PHP o MySQL
py tests/aceptacion.py   # 23 comprobaciones funcionales
```

Ambas suites hablan con la aplicación real servida por Apache, crean y borran sus
propios datos y no requieren instalar nada más que Python 3. Resultado de la última
ejecución: **crawl 20/20** y **aceptación 23/23**. La instalación se verifica
importando solo `database.sql` en una base vacía (18 tablas y datos de ejemplo).

Guion de demostración: [`GUIA_USO.md`](GUIA_USO.md).
