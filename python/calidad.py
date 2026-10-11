"""Analisis de calidad de metadatos (reglas ponderadas).
Entrada: {activo: {nombre, descripcion, categoria, propietario, estado, ...}, metadatos: [...], total_campos_esperado?}
Salida: {ok, porcentaje, problemas[], recomendaciones[]}
"""
import sys, os
sys.path.insert(0, os.path.dirname(__file__))
from nlp_utils import leer_entrada, escribir_salida

def evaluar(activo, metadatos):
    problemas, recomendaciones = [], []
    puntaje = 100.0
    def penalizar(p, prob, rec):
        nonlocal puntaje
        puntaje -= p
        problemas.append(prob)
        recomendaciones.append(rec)

    if not (activo.get("descripcion") or "").strip() or len(activo.get("descripcion", "")) < 10:
        penalizar(15, "Sin descripcion general o muy corta", "Redactar descripcion de al menos 10 caracteres")
    if not activo.get("propietario"):
        penalizar(12, "Sin propietario asignado", "Asignar propietario responsable")
    if not activo.get("area_responsable"):
        penalizar(5, "Sin area responsable", "Definir area responsable")
    if (activo.get("categoria") or "Otro") == "Otro":
        penalizar(8, "Categoria generica (Otro) sin validar", "Validar categoria con Data Steward")
    if (activo.get("estado") or "") == "pendiente":
        penalizar(5, "Activo pendiente de validacion", "Validar y activar el activo")
    if not metadatos:
        penalizar(25, "Sin metadatos registrados", "Ejecutar captura o registrar metadatos manualmente")
    else:
        sin_desc = sum(1 for m in metadatos if not (m.get("descripcion") or m.get("definicion_negocio") or "").strip())
        if sin_desc:
            penalizar(min(15, sin_desc * 3), f"{sin_desc} campos sin descripcion/definicion", "Completar descripciones de negocio")
        sin_resp = sum(1 for m in metadatos if not (m.get("responsable") or "").strip())
        if sin_resp > len(metadatos) / 2:
            penalizar(5, "Mayoria de campos sin responsable", "Asignar responsables por campo")
        nombres_raros = [m for m in metadatos if len((m.get("nombre_campo") or "")) < 2 or (m.get("nombre_campo") or "").startswith("columna_")]
        if nombres_raros:
            penalizar(min(10, len(nombres_raros) * 2), f"{len(nombres_raros)} nombres genericos (columna_*)", "Renombrar campos con nombres de negocio")
        dups = len(metadatos) - len({(m.get("nombre_campo") or "").lower() for m in metadatos})
        if dups > 0:
            penalizar(8, f"{dups} campos duplicados", "Eliminar o renombrar duplicados")
    puntaje = round(max(0, min(100, puntaje)), 2)
    return puntaje, problemas, recomendaciones

def main():
    entrada = leer_entrada(sys.argv[1]) if len(sys.argv) > 1 else {}
    salida = sys.argv[2] if len(sys.argv) > 2 else None
    activo = entrada.get("activo", {})
    metadatos = entrada.get("metadatos", [])
    pct, probs, recs = evaluar(activo, metadatos)
    res = {"ok": True, "porcentaje": pct, "problemas": probs, "recomendaciones": recs}
    if salida:
        escribir_salida(salida, res)
    else:
        import json; print(json.dumps(res, ensure_ascii=False))

if __name__ == "__main__":
    main()
