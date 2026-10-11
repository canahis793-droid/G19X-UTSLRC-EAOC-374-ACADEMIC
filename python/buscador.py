"""Buscador inteligente TF-IDF + similitud coseno.
Entrada: {query, activos: [{id, nombre, descripcion, categoria, etiquetas, tipo}]}
Salida: {ok, resultados: [{id, score, coincidencias}]}
"""
import sys, os
sys.path.insert(0, os.path.dirname(__file__))
from nlp_utils import tokenizar, expandir_con_sinonimos, idf, tfidf_vector, coseno, leer_entrada, escribir_salida

def doc_texto(a):
    return " ".join([str(a.get("nombre", "")), str(a.get("descripcion", "")),
                     str(a.get("categoria", "")), str(a.get("etiquetas", "")),
                     str(a.get("tipo", ""))])

def buscar(query, activos):
    q_toks = expandir_con_sinonimos(tokenizar(query))
    docs_toks = [expandir_con_sinonimos(tokenizar(doc_texto(a))) for a in activos]
    corpus = [q_toks] + docs_toks
    idf_map = idf(corpus)
    q_vec = tfidf_vector(q_toks, idf_map)
    resultados = []
    for a, toks in zip(activos, docs_toks):
        v = tfidf_vector(toks, idf_map)
        s = coseno(q_vec, v)
        # boost por coincidencia literal
        ql = query.lower()
        blob = doc_texto(a).lower()
        if ql and ql in blob:
            s += 0.15
        for t in tokenizar(query):
            if t and t in blob:
                s += 0.05
        resultados.append({"id": a.get("id"), "score": round(min(1.0, s), 4)})
    resultados.sort(key=lambda x: x["score"], reverse=True)
    return [r for r in resultados if r["score"] > 0.02][:20]

def main():
    entrada = leer_entrada(sys.argv[1]) if len(sys.argv) > 1 else {}
    salida = sys.argv[2] if len(sys.argv) > 2 else None
    query = entrada.get("query", "")
    activos = entrada.get("activos", [])
    if not query.strip():
        res = {"ok": False, "error": "Query vacia"}
    else:
        res = {"ok": True, "query": query, "resultados": buscar(query, activos)}
    if salida:
        escribir_salida(salida, res)
    else:
        import json; print(json.dumps(res, ensure_ascii=False))

if __name__ == "__main__":
    main()
