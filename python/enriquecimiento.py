"""Enriquecimiento: genera descripcion, keywords, categoria y relaciones sugeridas.
Entrada: {activo: {nombre, descripcion, categoria, tipo...}, metadatos: [...], todos: [{id,nombre,descripcion}]}
Salida: {ok, descripcion_sugerida, palabras_clave, categoria_sugerida, confianza, relaciones_sugeridas[], calidad_estimada}
"""
import sys, os
sys.path.insert(0, os.path.dirname(__file__))
from nlp_utils import tokenizar, palabras_clave, idf, tfidf_vector, coseno, leer_entrada, escribir_salida

# Reusa clasificador importandolo como modulo
import clasificador as clf_mod

PLANTILLAS = {
    "Cliente": "Tabla maestra de clientes. Almacena identificacion, contacto y datos de facturacion para procesos comerciales.",
    "Usuario": "Repositorio de usuarios y contactos. Incluye cuentas, perfiles y segmentacion para autenticacion y marketing.",
    "Venta": "Datos de ventas y actividad comercial. Contiene transacciones, montos y dimensiones temporales para analisis.",
    "Producto": "Catalogo de productos con identificadores (SKU), precios y atributos para e-commerce e inventario.",
    "Inventario": "Movimientos y existencias de almacen. Registra entradas, salidas y saldos por ubicacion.",
    "Finanzas": "Informacion financiera y contable. Incluye facturas, totales e impuestos para reportes fiscales.",
    "Recursos Humanos": "Datos de personal para analitica de RRHH. Incluye puestos, areas y vigencia laboral.",
    "Operaciones": "Datos operativos y logisticos. Describe rutas, movimientos y recursos de distribucion.",
    "Otro": "Activo de informacion registrado en el catalogo. Se recomienda completar su descripcion de negocio.",
}

def sugerir_relaciones(activo, todos, top=3):
    docs = [(t.get("nombre", "") + " " + t.get("descripcion", "")) for t in todos]
    toks_corpus = [tokenizar(d) for d in docs]
    base = tokenizar(activo.get("nombre", "") + " " + activo.get("descripcion", ""))
    if not base:
        return []
    idf_map = idf(toks_corpus + [base])
    bv = tfidf_vector(base, idf_map)
    sims = []
    for t, toks in zip(todos, toks_corpus):
        if t.get("id") == activo.get("id"):
            continue
        s = coseno(bv, tfidf_vector(toks, idf_map))
        if s > 0.08:
            sims.append((t.get("nombre", ""), round(float(s), 3)))
    sims.sort(key=lambda x: x[1], reverse=True)
    return [{"activo": n, "similitud": s} for n, s in sims[:top]]

def main():
    entrada = leer_entrada(sys.argv[1]) if len(sys.argv) > 1 else {}
    salida = sys.argv[2] if len(sys.argv) > 2 else None
    activo = entrada.get("activo", {})
    metadatos = entrada.get("metadatos", [])
    todos = entrada.get("todos", [])
    nombre = activo.get("nombre", "")
    desc = activo.get("descripcion", "") or ""
    r = clf_mod.clasificar(nombre, desc, " ".join([m.get("nombre_campo", "") for m in metadatos]))
    cat = r["categoria"]
    kws = palabras_clave(f"{nombre} {desc} " + " ".join(m.get("nombre_campo", "") for m in metadatos))
    campos = ", ".join([m.get("nombre_campo", "") for m in metadatos[:6]])
    base_desc = PLANTILLAS.get(cat, PLANTILLAS["Otro"])
    sugerida = f"{base_desc} Activo '{nombre}'"
    if campos:
        sugerida += f" con campos: {campos}."
    if len(desc) > 20:
        sugerida += f" Contexto actual: {desc[:160]}"
    rels = sugerir_relaciones({**activo, "id": activo.get("id")}, todos)
    # calidad estimada rapida
    pct = 100
    if len(desc) < 10: pct -= 20
    if not activo.get("propietario"): pct -= 15
    if cat == "Otro": pct -= 10
    if not metadatos: pct -= 20
    res = {"ok": True, "descripcion_sugerida": sugerida, "palabras_clave": kws,
           "categoria_sugerida": cat, "confianza": r["confianza"],
           "relaciones_sugeridas": rels, "calidad_estimada": max(0, pct)}
    if salida:
        escribir_salida(salida, res)
    else:
        import json; print(json.dumps(res, ensure_ascii=False))

if __name__ == "__main__":
    main()
