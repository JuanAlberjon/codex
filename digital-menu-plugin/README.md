# Digital Menu Multi-Restaurant Plugin (DMMR)

Plugin base para carta digital multi-restaurante, multi-idioma, con importación CSV y filtro de alérgenos.

## Estructura

- `digital-menu-plugin.php`: bootstrap.
- `includes/core`: autoload, instalación DB, roles.
- `includes/admin`: menú y pantallas base del panel.
- `includes/api`: rutas REST públicas/privadas.
- `includes/frontend`: shortcode y assets frontend.
- `includes/importer`: lógica base de importación CSV.
- `docs/specification.md`: especificación completa de arquitectura/UX/API/CSV.
- `examples/*.csv`: plantillas de ejemplo para comida y bebidas.

## Shortcode

```txt
[dmmr_menu restaurant="restaurante-x" menu="comida"]
```

## Estado

Scaffold funcional (fase arquitectura/MVP inicial).
