"""Clasificador automatico por reglas + scoring (sin IA externa).
Entrada: {nombre, descripcion, texto}
Salida: {ok, categoria, confianza, palabras_clave, detalle_scores}
Categorias: Cliente, Usuario, Venta, Producto, Inventario, Finanzas, Recursos Humanos, Operaciones, Otro
"""
import sys, os
sys.path.insert(0, os.path.dirname(__file__))
from nlp_utils import tokenizar, palabras_clave, leer_entrada, escribir_salida

REGLAS = {
    "Cliente": ["cliente", "clientes", "comprador", "compradores", "contacto", "persona", "crm", "lead", "consumidor", "sucursal"],
    "Usuario": ["usuario", "usuarios", "login", "cuenta", "password", "perfil", "rol", "sesion", "acceso", "crm"],
    "Venta": ["venta", "ventas", "pedido", "pedidos", "comercial", "oportunidad", "pipeline", "factura", "cotizacion", "vendedor", "mensual", "region"],
    "Producto": ["producto", "productos", "sku", "catalogo", "articulo", "precio", "stock", "variante", "ecommerce", "inventario"],
    "Inventario": ["inventario", "almacen", "stock", "existencia", "bodega", "movimiento", "kardex", "entrada", "salida"],
    "Finanzas": ["finanza", "finanzas", "factura", "facturacion", "contable", "impuesto", "iva", "pago", "cobro", "presupuesto", "tesoreria", "total"],
    "Recursos Humanos": ["empleado", "empleados", "nomina", "rrhh", "personal", "puesto", "salario", "recurso", "humano", "organigrama"],
    "Operaciones": ["operacion", "operaciones", "logistica", "ruta", "distribucion", "transporte", "planta", "produccion", "mantenimiento", "operativo"],
}

def clasificar(nombre, descripcion, texto_extra=""):
    texto = f"{nombre} {nombre} {descripcion} {texto_extra}"
    toks = set(tokenizar(texto))
    scores = {}
    for cat, claves in REGLAS.items():
        pts = sum(3 if k in texto.lower() else 0 for k in claves)
        # совпаidencias por token
        pts += sum(1 for k in claves if k in toks) * 2
        scores[cat] = pts
    mejor = max(scores, key=lambda k: scores[k])
    max_pts = scores[mejor]
    total = sum(scores.values())
    if max_pts == 0:
        return {"categoria": "Otro", "confianza": 35.0, "scores": scores}
    confianza = round(min(98.0, 55 + (max_pts / max(1, total)) * 40 + min(10, max_pts)), 2)
    # desempate: si dos categorias cercanas, bajar confianza
    orden = sorted(scores.values(), reverse=True)
    if len(orden) > 1 and orden[0] - orden[1] <= 1:
        confianza = round(max(55.0, confianza - 12), 2)
    return {"categoria": mejor, "confianza": confianza, "scores": scores}

def main():
    entrada = leer_entrada(sys.argv[1]) if len(sys.argv) > 1 else {}
    salida = sys.argv[2] if len(sys.argv) > 2 else None
    nombre = entrada.get("nombre", "")
    descripcion = entrada.get("descripcion", "")
    texto = entrada.get("texto", f"{nombre} {descripcion}")
    r = clasificar(nombre, descripcion, texto)
    kws = palabras_clave(f"{nombre} {descripcion} {texto}")
    res = {"ok": True, "categoria": r["categoria"], "confianza": r["confianza"],
           "palabras_clave": kws, "detalle_scores": r["scores"]}
    if salida:
        escribir_salida(salida, res)
    else:
        import json; print(json.dumps(res, ensure_ascii=False))

if __name__ == "__main__":
    main()
