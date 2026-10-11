// Gráficos limpios con Canvas puro: barras horizontales (sin pastel, sin leyendas encimadas)
function drawBars(canvasId, data, labelKey) {
  const c = document.getElementById(canvasId);
  if (!c || !data || !data.length) return;
  const rows = data.slice(0, 6);
  const W = Math.max(280, c.parentElement.clientWidth - 4);
  const rowH = 30, top = 6;
  c.width = W; c.height = rows.length * rowH + top + 6;
  const ctx = c.getContext('2d');
  const total = rows.reduce((s, d) => s + (+d.c || 0), 0) || 1;
  const max = Math.max(...rows.map(d => +d.c || 0), 1);
  const colors = ['#7C3AED', '#38BDF8', '#A855F7', '#89CFF0', '#6D28D9', '#4C1D95', '#C4B5FD', '#BAE6FD', '#DDD0FA'];
  const labelW = 132, valW = 56;
  const barX = labelW, barW = W - labelW - valW;
  ctx.font = '12.5px Inter, Segoe UI, Arial';
  rows.forEach((d, i) => {
    const y = top + i * rowH;
    const label = String(d[labelKey] || '').slice(0, 18);
    ctx.fillStyle = '#2A2350';
    ctx.fillText(label, 4, y + 13);
    const pct = Math.round((+d.c) / total * 100);
    ctx.fillStyle = '#8f8cb8';
    ctx.fillText(pct + '%', 4, y + 26);
    const w = Math.max(8, Math.round((+d.c) / max * barW));
    ctx.fillStyle = '#EDE9FE';
    roundBar(ctx, barX, y + 5, barW, 14, 7);
    ctx.fillStyle = colors[i % colors.length];
    roundBar(ctx, barX, y + 5, w, 14, 7);
    ctx.fillStyle = '#2A2350';
    ctx.font = 'bold 12.5px Inter, Segoe UI, Arial';
    ctx.fillText(String(d.c), barX + w + 8, y + 17);
    ctx.font = '12.5px Inter, Segoe UI, Arial';
  });
}
function roundBar(ctx, x, y, w, h, r) {
  r = Math.min(r, h / 2, w / 2);
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
  ctx.fill();
}
document.addEventListener('DOMContentLoaded', () => {
  if (window.__catData) drawBars('chartCat', window.__catData, 'categoria');
  if (window.__fuenteData) drawBars('chartFuente', window.__fuenteData, 'nombre');
  window.addEventListener('resize', () => {
    if (window.__catData) drawBars('chartCat', window.__catData, 'categoria');
    if (window.__fuenteData) drawBars('chartFuente', window.__fuenteData, 'nombre');
  });
});
