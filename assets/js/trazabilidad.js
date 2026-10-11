// Renderiza relaciones como tarjetas conectadas (HTML/CSS/JS puro)
document.addEventListener('DOMContentLoaded', () => {
  const box = document.getElementById('traceMap');
  if (!box || !window.__rels) return;
  const nodes = new Map();
  window.__rels.forEach(r => {
    if (!nodes.has(r.origen)) nodes.set(r.origen, []);
    nodes.get(r.origen).push(r);
  });
if (!window.__rels.length) { box.innerHTML = '<p class="muted">Sin relaciones.</p>'; return; }
window.__rels.slice(0, 30).forEach(r => {
  const d = document.createElement('div');
  d.className = 'trace-node-card';
  d.innerHTML = '<strong>' + r.origen.replace(/</g,'&lt;') + '</strong><div>—(' + r.tipo_relacion.replace(/</g,'&lt;') + ')→</div><strong>' + r.destino.replace(/</g,'&lt;') + '</strong>';
  box.appendChild(d);
});
});
