"""Utilidades compartidas: normalizacion, tokens, TF-IDF y coseno (solo stdlib)."""
import re
import math
import unicodedata
from collections import Counter

STOPWORDS = set("""
de la el en y a los del se las por un para con no una su al lo como mas pero sus
the of and to in is that it for on with as at from by an are or be this which you
datos data info informacion sistema tabla archivo campo registro reporte
""".split())

SINONIMOS = {
    "comprador": ["cliente", "compradores", "clientes", "consumidor", "adquiriente"],
    "cliente": ["comprador", "usuario", "persona", "contacto", "consumidor"],
    "usuario": ["cliente", "persona", "contacto", "cuenta", "login"],
    "venta": ["ventas", "factura", "facturacion", "comercial", "pedido", "cobro"],
    "producto": ["productos", "articulo", "sku", "catalogo", "mercancia", "item"],
    "finanza": ["finanzas", "factura", "contable", "impuesto", "pago", "cobro", "presupuesto"],
    "inventario": ["stock", "almacen", "existencia", "bodega", "mercancia"],
    "empleado": ["empleados", "rrhh", "personal", "nomina", "recursos"],
    "operacion": ["operaciones", "logistica", "ruta", "distribucion", "transporte"],
}

def normalizar(texto: str) -> str:
    if not texto:
        return ""
    texto = texto.lower()
    texto = unicodedata.normalize("NFKD", texto)
    texto = "".join(c for c in texto if not unicodedata.combining(c))
    return texto

def tokenizar(texto: str):
    texto = normalizar(texto)
    texto = re.sub(r"[^a-z0-9_ ]", " ", texto)
    toks = [t.strip("_") for t in texto.split() if t]
    # expandir sinonimos: mantener original + agregar base
    out = []
    for t in toks:
        if len(t) < 2:
            continue
        if t in STOPWORDS:
            continue
        out.append(t)
    return out

def expandir_con_sinonimos(tokens):
    exp = list(tokens)
    for t in tokens:
        for base, lista in SINONIMOS.items():
            if t == base:
                exp.extend(lista)
            elif t in lista:
                exp.append(base)
    return exp

def tf(tokens):
    c = Counter(tokens)
    n = max(1, len(tokens))
    return {k: v / n for k, v in c.items()}

def idf(corpus_tokens):
    n = max(1, len(corpus_tokens))
    df = Counter()
    for toks in corpus_tokens:
        for t in set(toks):
            df[t] += 1
    return {t: math.log((n + 1) / (f + 1)) + 1.0 for t, f in df.items()}

def tfidf_vector(tokens, idf_map):
    t = tf(tokens)
    return {k: v * idf_map.get(k, 1.0) for k, v in t.items()}

def coseno(a: dict, b: dict) -> float:
    if not a or not b:
        return 0.0
    inter = set(a) & set(b)
    num = sum(a[k] * b[k] for k in inter)
    na = math.sqrt(sum(v * v for v in a.values()))
    nb = math.sqrt(sum(v * v for v in b.values()))
    if na == 0 or nb == 0:
        return 0.0
    return num / (na * nb)

def palabras_clave(texto: str, top_n: int = 8):
    toks = [t for t in tokenizar(texto) if len(t) > 2]
    if not toks:
        return []
    c = Counter(toks)
    # priorizar terminos largos y frecuentes
    scored = sorted(c.items(), key=lambda x: (x[1], len(x[0])), reverse=True)
    return [w for w, _ in scored[:top_n]]

def leer_entrada(path):
    import json
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)

def escribir_salida(path, data):
    import json
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(json.dumps(data, ensure_ascii=False))
