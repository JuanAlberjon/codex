document.addEventListener('click', (event) => {
  const action = event.target?.dataset?.action;
  if (!action) return;

  if (action === 'language') {
    console.log('Abrir selector de idioma configurable');
  }

  if (action === 'allergens') {
    console.log('Abrir modal de alérgenos y aplicar filtro hide-if-contains-any');
  }
});
