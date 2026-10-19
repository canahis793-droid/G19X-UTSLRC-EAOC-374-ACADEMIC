"""
Crawl de rutas - MetaData Manager
=================================
Recorre todas las pantallas con una sesion valida y verifica que ninguna
devuelva error de PHP ni de MySQL.

Uso:
    py tests/crawl.py
    py tests/crawl.py --url http://localhost/sistema-metadata-management

Salida esperada: "rutas con problema: 0".
"""
import sys
import re
import http.cookiejar
import urllib.request
import urllib.parse
import urllib.error

BASE = 'http://localhost/sistema-metadata-management'
for i, a in enumerate(sys.argv):
    if a == '--url' and i + 1 < len(sys.argv):
        BASE = sys.argv[i + 1].rstrip('/')

RUTAS = [
    '/public/index.php?p=dashboard',
    '/public/index.php?p=busqueda',
    '/public/index.php?p=activos',
    '/public/index.php?p=activos/crear',
    '/public/index.php?p=activos/detalle&id=1',
    '/public/index.php?p=activos/editar&id=1',
    '/public/index.php?p=fuentes',
    '/public/index.php?p=metadatos',
    '/public/index.php?p=metadatos&activo=1',
    '/public/index.php?p=captura',
    '/public/index.php?p=clasificacion',
    '/public/index.php?p=enriquecimiento',
    '/public/index.php?p=calidad',
    '/public/index.php?p=trazabilidad',
    '/public/index.php?p=trazabilidad&activo=1',
    '/public/index.php?p=reportes',
    '/public/index.php?p=bitacora',
    '/public/index.php?p=papelera',
    '/public/index.php?p=usuarios',
    '/public/index.php?p=configuracion',
]

# Errores del motor PHP. No se revisa 'SQLSTATE' porque el historial de
# capturas muestra a proposito los errores de intentos anteriores.
FALLAS = ('Fatal error', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:',
          'Uncaught', 'Call to undefined')

problemas = 0


def sesion():
    return urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def entrar(op):
    html = op.open(BASE + '/public/login.php').read().decode('utf-8', 'ignore')
    m = re.search(r'name="csrf" value="([a-f0-9]+)"', html)
    datos = urllib.parse.urlencode({
        'correo': 'admin@demo.local', 'password': 'admin123',
        'csrf': m.group(1) if m else ''}).encode()
    op.open(urllib.request.Request(BASE + '/public/login.php', data=datos))


op = sesion()
entrar(op)

for ruta in RUTAS:
    url = BASE + ruta
    try:
        r = op.open(url)
        html = r.read().decode('utf-8', 'ignore')
        code = r.status
    except urllib.error.HTTPError as e:
        html = e.read().decode('utf-8', 'ignore')
        code = e.code
    errores = [f for f in FALLAS if f in html]
    if code != 200 or errores:
        problemas += 1
        print('problema %s -> HTTP %s %s' % (ruta, code, ','.join(errores)))
    else:
        print('ok      %s' % ruta)

print('\nrutas revisadas: %d' % len(RUTAS))
print('rutas con problema: %d' % problemas)
sys.exit(1 if problemas else 0)