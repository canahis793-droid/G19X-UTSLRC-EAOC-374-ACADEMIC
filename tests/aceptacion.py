"""
Bateria funcional de aceptacion - MetaData Manager
==================================================
Verifica los flujos principales contra la aplicacion ya servida por Apache.

Requisitos: Python 3 (solo libreria estandar) y la app corriendo.
Uso:
    py tests/aceptacion.py
    py tests/aceptacion.py --url http://localhost/sistema-metadata-management

Usuarios de prueba (creados por database.sql):
    admin@demo.local / admin123
    steward@demo.local / steward123
    usuario@demo.local / user123

La prueba crea y luego elimina sus propios datos de prueba.
"""
import sys
import re
import uuid
import http.cookiejar
import urllib.request
import urllib.parse
import urllib.error

BASE = 'http://localhost/sistema-metadata-management'
for i, a in enumerate(sys.argv):
    if a == '--url' and i + 1 < len(sys.argv):
        BASE = sys.argv[i + 1].rstrip('/')

R = '/public/index.php?p='
ok = fail = 0


def check(nombre, cond):
    global ok, fail
    if cond:
        ok += 1
        print('  OK    ' + nombre)
    else:
        fail += 1
        print('  FALLA ' + nombre)


def sesion():
    return urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def get(op, path):
    try:
        r = op.open(BASE + path)
        return r.read().decode('utf-8', 'ignore'), r.status
    except urllib.error.HTTPError as e:
        return e.read().decode('utf-8', 'ignore'), e.code


def post(op, path, campos):
    datos = urllib.parse.urlencode(campos).encode()
    try:
        r = op.open(urllib.request.Request(BASE + path, data=datos))
        return r.read().decode('utf-8', 'ignore'), r.url, 200
    except urllib.error.HTTPError as e:
        return e.read().decode('utf-8', 'ignore'), '', e.code


def post_archivo(op, path, campos, nombre_archivo, contenido):
    limite = uuid.uuid4().hex.encode()
    cuerpo = b''
    for k, v in campos.items():
        cuerpo += (b'--' + limite + b'\r\nContent-Disposition: form-data; name="'
                   + k.encode() + b'"\r\n\r\n' + str(v).encode() + b'\r\n')
    cuerpo += (b'--' + limite + b'\r\nContent-Disposition: form-data; name="archivo"; filename="'
               + nombre_archivo.encode() + b'"\r\nContent-Type: text/csv\r\n\r\n'
               + contenido.encode() + b'\r\n--' + limite + b'--\r\n')
    try:
        r = op.open(urllib.request.Request(
            BASE + path, data=cuerpo,
            headers={'Content-Type': 'multipart/form-data; boundary=' + limite.decode()}))
        return r.read().decode('utf-8', 'ignore'), r.url, 200
    except urllib.error.HTTPError as e:
        return e.read().decode('utf-8', 'ignore'), '', e.code


def token_csrf(html):
    m = re.search(r'name="csrf" value="([a-f0-9]+)"', html)
    return m.group(1) if m else ''


def entrar(correo, clave):
    op = sesion()
    html, _ = get(op, '/public/login.php')
    cuerpo, url, _ = post(op, '/public/login.php', {
        'correo': correo, 'password': clave, 'csrf': token_csrf(html)})
    return op, cuerpo, url


print('MetaData Manager - bateria de aceptacion')
print('Servidor: ' + BASE + '\n')

print('== 1. Acceso y roles por permisos ==')
opA, cuerpo, url = entrar('admin@demo.local', 'admin123')
check('administrador inicia sesion', 'Cerrar sesi' in cuerpo)
opS, cuerpoS, _ = entrar('steward@demo.local', 'steward123')
check('data steward inicia sesion', 'Cerrar sesi' in cuerpoS)
opU, cuerpoU, _ = entrar('usuario@demo.local', 'user123')
check('usuario de consulta inicia sesion', 'Cerrar sesi' in cuerpoU)
_, code = get(opU, R + 'usuarios')
check('consulta bloqueado en usuarios (403)', code == 403)
_, code = get(opS, R + 'usuarios')
check('steward bloqueado en usuarios (403)', code == 403)
_, code = get(opU, R + 'configuracion')
check('consulta bloqueado en configuracion (403)', code == 403)

print('\n== 2. CRUD de fuentes ==')
html, _ = get(opA, R + 'fuentes')
nombre_prueba = 'FuenteTest' + uuid.uuid4().hex[:6]
post(opA, R + 'fuentes', {
    'op': 'crear', 'nombre': nombre_prueba, 'tipo': 'API',
    'ubicacion': 'http://prueba.local', 'responsable': 'QA',
    'estado': 'activo', 'descripcion': 'Fuente de prueba automatica',
    'csrf': token_csrf(html)})
html, _ = get(opA, R + 'fuentes&q=' + urllib.parse.quote(nombre_prueba))
check('fuente creada y visible en el listado', ('<h3>' + nombre_prueba) in html)
m = re.search(r'name="id_fuente" value="(\d+)"', html)
id_fuente = m.group(1) if m else ''
html, _ = get(opA, R + 'fuentes')
post(opA, R + 'fuentes', {
    'op': 'eliminar', 'id_fuente': id_fuente, 'csrf': token_csrf(html)})
html, _ = get(opA, R + 'fuentes&q=' + urllib.parse.quote(nombre_prueba))
check('fuente eliminada (pasa a papelera)', ('<h3>' + nombre_prueba) not in html)

print('\n== 3. Captura de archivo CSV con Python ==')
html, _ = get(opA, R + 'captura')
csv_prueba = 'nombre,email,total\nAna,a@x.com,10\nLuis,,20\n'
cuerpo, url, _ = post_archivo(opA, R + 'captura/procesar_archivo', {
    'tipo': 'csv', 'id_fuente': '3', 'csrf': token_csrf(html)},
    'qa_aceptacion.csv', csv_prueba)
check('CSV procesado y redirige al detalle', 'detalle&id=' in url)
m = re.search(r'detalle&id=(\d+)', url)
check('el activo creado muestra sus metadatos', bool(m) and 'Metadato' in cuerpo)

print('\n== 4. Clasificacion asistida ==')
post(opA, R + 'clasificacion&analizar=todos', {})
html, _ = get(opA, R + 'clasificacion')
check('pagina de clasificacion operativa', 'Pendiente' in html or 'Historial' in html)

print('\n== 5. Busqueda inteligente ==')
html, _ = get(opU, R + 'busqueda&q=' + urllib.parse.quote('informacion de compradores'))
check('encuentra clientes por sinonimo', 'clientes' in html.lower())
check('muestra porcentaje de coincidencia', '%' in html)

print('\n== 6. Calidad de datos ==')
get(opA, R + 'calidad&analizar=todos')
html, _ = get(opA, R + 'calidad')
check('calidad evaluada con promedio', '%' in html)

print('\n== 7. Trazabilidad ==')
html, _ = get(opA, R + 'trazabilidad')
post(opA, R + 'trazabilidad', {
    'id_origen': '1', 'id_destino': '2', 'tipo_relacion': 'relacionado',
    'descripcion': 'QA aceptacion', 'csrf': token_csrf(html)})
html, _ = get(opA, R + 'trazabilidad')
check('relacion registrada', 'QA aceptacion' in html)
m = re.search(r'eliminar=(\d+)', html)
if m:
    get(opA, R + 'trazabilidad&eliminar=' + m.group(1))
    html, _ = get(opA, R + 'trazabilidad')
    check('relacion eliminada', 'QA aceptacion' not in html)
else:
    check('relacion eliminada', False)

print('\n== 8. Reportes, bitacora y configuracion ==')
try:
    r = opA.open(BASE + R + 'reportes/exportar&tipo=activos')
    check('exportacion CSV de activos', 'csv' in r.headers.get_content_type())
except Exception:
    check('exportacion CSV de activos', False)
_, code = get(opA, R + 'bitacora')
check('bitacora accesible para administrador', code == 200)
html, _ = get(opA, R + 'configuracion&probar_python=1')
check('prueba de conexion con Python', 'OK' in html)
html, code = get(opA, '/public/404.php')
check('pagina 404 responde 404', code == 404)
html, code = get(opA, '/api/activos.php')
check('API JSON responde con la sesion', code == 200 and '"ok":true' in html)

print('\n== 9. Proteccion contra fuerza bruta ==')
opX = sesion()
html, _ = get(opX, '/public/login.php')
for _ in range(6):
    cuerpo, _, _ = post(opX, '/public/login.php', {
        'correo': 'admin@demo.local', 'password': 'claveIncorrecta',
        'csrf': token_csrf(html)})
    html = cuerpo
check('bloquea tras varios intentos fallidos', 'intentos' in html.lower())

print('\n' + '=' * 46)
print('RESULTADO: %d OK, %d FALLAS' % (ok, fail))
sys.exit(1 if fail else 0)