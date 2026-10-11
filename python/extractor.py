"""Extractor inteligente: analiza CSV y JSON (solo stdlib).
Entrada JSON: {tipo: csv|json, archivo: ruta, nombre: nombre_original}
Salida: {ok, resultado: {...}}
"""
import sys, os, csv, json
sys.path.insert(0, os.path.dirname(__file__))
from nlp_utils import leer_entrada, escribir_salida

def inferir_tipo(valores):
    no_vacios = [v for v in valores if v not in (None, "")]
    if not no_vacios:
        return "TEXT"
    def es_int(s):
        try: int(str(s)); return True
        except: return False
    def es_float(s):
        try: float(str(s).replace(",", ".")); return True
        except: return False
    def es_fecha(s):
        import re
        return bool(re.match(r"^\d{4}-\d{2}-\d{2}", str(s).strip()))
    if all(es_int(v) for v in no_vacios): return "INT"
    if all(es_float(v) for v in no_vacios): return "DECIMAL"
    if all(es_fecha(v) for v in no_vacios): return "DATE"
    largos = [len(str(v)) for v in no_vacios]
    return "VARCHAR" if max(largos) < 255 else "TEXT"

def analizar_csv(path):
    with open(path, "r", encoding="utf-8-sig", newline="") as f:
        muestra = f.read(4096)
        f.seek(0)
        try:
            dialecto = csv.Sniffer().sniff(muestra, delimiters=[",", ";", "\t", "|"])
        except:
            dialecto = csv.excel
        lector = csv.DictReader(f, dialect=dialecto)
        columnas = lector.fieldnames or []
        filas = list(lector)
    info_cols = []
    for col in columnas:
        vals = [r.get(col, "") for r in filas]
        vacios = sum(1 for v in vals if v in (None, ""))
        info_cols.append({
            "nombre": col.strip(),
            "tipo_inferido": inferir_tipo(vals),
            "vacios": vacios,
            "ejemplos": [str(v)[:50] for v in vals[:3]],
        })
    return {
        "tipo": "csv",
        "num_columnas": len(columnas),
        "num_filas": len(filas),
        "total_vacios": sum(c["vacios"] for c in info_cols),
        "columnas": info_cols,
    }

def tipo_json(v):
    if isinstance(v, bool): return "BOOLEAN"
    if isinstance(v, int): return "INT"
    if isinstance(v, float): return "DECIMAL"
    if isinstance(v, list): return "ARRAY"
    if isinstance(v, dict): return "OBJECT"
    return "VARCHAR"

def analizar_json(path):
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)
    registros = data if isinstance(data, list) else data.get("data", data.get("registros", [data]))
    if isinstance(registros, dict):
        registros = [registros]
    props = {}
    for r in registros[:200]:
        if isinstance(r, dict):
            for k, v in r.items():
                props.setdefault(k, []).append(v)
    propiedades = [{"nombre": k, "tipo": tipo_json(v[0]) if v else "TEXT", "ejemplo": str(v[0])[:80] if v else ""} for k, v in props.items()]
    return {
        "tipo": "json",
        "num_registros": len(registros) if isinstance(registros, list) else 1,
        "propiedades": propiedades,
    }

def main():
    entrada = leer_entrada(sys.argv[1]) if len(sys.argv) > 1 else {}
    salida_path = sys.argv[2] if len(sys.argv) > 2 else None
    try:
        tipo = entrada.get("tipo", "csv")
        archivo = entrada.get("archivo", "")
        if not archivo or not os.path.exists(archivo):
            res = {"ok": False, "error": f"Archivo no encontrado: {archivo}"}
        elif tipo == "csv":
            res = {"ok": True, "resultado": analizar_csv(archivo)}
        elif tipo == "json":
            res = {"ok": True, "resultado": analizar_json(archivo)}
        else:
            res = {"ok": False, "error": f"Tipo no soportado: {tipo}"}
    except Exception as ex:
        res = {"ok": False, "error": str(ex)}
    if salida_path:
        escribir_salida(salida_path, res)
    else:
        print(json.dumps(res, ensure_ascii=False))

if __name__ == "__main__":
    main()
