// MetaData Manager · Interactividad amigable (JS puro)
document.addEventListener('DOMContentLoaded', () => {
  /* 1. Alertas -> toasts flotantes con cierre y auto-ocultado */
  document.querySelectorAll('.content > .alert').forEach(a => {
    a.classList.add('toast', a.classList.contains('error') ? 'toast-error' : 'toast-ok');
    const x = document.createElement('button');
    x.className = 'toast-close'; x.textContent = '×'; x.setAttribute('aria-label', 'Cerrar');
    x.onclick = () => a.remove();
    a.prepend(x);
    setTimeout(() => { a.classList.add('toast-out'); setTimeout(() => a.remove(), 400); }, 5000);
  });

/* 2. Ripple en botones */
document.addEventListener('click', e => {
  const b = e.target.closest('.btn');
  if (!b) return;
  const r = document.createElement('span');
  r.className = 'ripple';
  const rect = b.getBoundingClientRect();
  const s = Math.max(rect.width, rect.height);
  r.style.width = r.style.height = s + 'px';
  r.style.left = (e.clientX - rect.left - s / 2) + 'px';
  r.style.top = (e.clientY - rect.top - s / 2) + 'px';
  b.appendChild(r);
  setTimeout(() => r.remove(), 600);
});

/* 3. Estado de carga en submits (evita doble clic) */
document.querySelectorAll('form').forEach(f => {
  f.addEventListener('submit', () => {
    const btn = f.querySelector('button[type="submit"], .btn-primary');
    if (btn && !btn.dataset.noload) { btn.classList.add('loading'); btn.disabled = true; }
  });
});

/* 4. Modal de confirmación amigable para [data-confirm] */
const modal = document.createElement('div');
modal.className = 'modal-back';
modal.innerHTML = '<div class="modal"><h3>¿Confirmar acción?</h3><p class="modal-msg"></p><div class="modal-btns"><button class="btn" data-m="no">Cancelar</button><button class="btn btn-primary" data-m="yes">Confirmar</button></div></div>';
document.body.appendChild(modal);
let pending = null;
modal.querySelector('[data-m="no"]').onclick = () => { modal.classList.remove('open'); pending = null; };
modal.querySelector('[data-m="yes"]').onclick = () => {
  modal.classList.remove('open');
  if (!pending) return;
  const el = pending; pending = null;
  if (el.tagName === 'FORM') el.submit();
  else window.location.href = el.getAttribute('href');
};
modal.addEventListener('click', e => { if (e.target === modal) { modal.classList.remove('open'); pending = null; } });
document.addEventListener('click', e => {
  const t = e.target.closest('[data-confirm]');
  if (!t) return;
  e.preventDefault();
  pending = t.tagName === 'FORM' ? t : t;
  if (t.tagName === 'A') pending = t;
  modal.querySelector('.modal-msg').textContent = t.getAttribute('data-confirm');
  modal.classList.add('open');
});
/* 5. Tablas vacías con mensaje amigable */
document.querySelectorAll('.table tbody').forEach(tb => {
  if (!tb.querySelector('tr')) {
    const cols = tb.closest('table').querySelectorAll('thead th').length || 1;
    tb.innerHTML = '<tr><td colspan="' + cols + '" class="empty-note"> Sin registros por aquí todavía.</td></tr>';
  }
});

/* 6. Cerrar sidebar en móvil al navegar */
document.querySelectorAll('.sidebar .nav a').forEach(a => {
  a.addEventListener('click', () => {
    if (window.innerWidth <= 900) document.getElementById('sidebar').classList.remove('open');
  });
});
});
