document.addEventListener('click', (event) => {
  const target = event.target;
  const action = target?.dataset?.action;
  if (!action) return;

  if (action === 'language') {
    const baseUrl = target.dataset.menuUrl;
    if (baseUrl) {
      window.location.href = baseUrl;
    }
  }

  if (action === 'allergens') {
    const input = window.prompt('Introduce slugs de alérgenos a evitar separados por coma (ej: gluten,lactosa)');
    const selected = (input || '')
      .split(',')
      .map((slug) => slug.trim())
      .filter(Boolean);

    document.querySelectorAll('.dmmr-item').forEach((item) => {
      const itemAllergens = (item.dataset.allergens || '').split(',').map((s) => s.trim()).filter(Boolean);
      const shouldHide = selected.some((slug) => itemAllergens.includes(slug));
      item.style.display = shouldHide ? 'none' : '';
    });
  }
});
