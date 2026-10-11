// Mejora UX de busqueda: atajo "/" enfoca el buscador
document.addEventListener('keydown', (e) => {
  if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
    const s = document.querySelector('.search-big input');
    if (s) { e.preventDefault(); s.focus(); }
  }
});
