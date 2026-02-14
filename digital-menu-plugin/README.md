# Digital Menu Multi-Restaurant Plugin (DMMR)

Plugin base para carta digital multi-restaurante, multi-idioma, con importación CSV y filtro de alérgenos.

## Estructura

- `digital-menu-plugin.php`: bootstrap.
- `includes/core`: autoload, instalación DB, roles.
- `includes/admin`: menú y pantallas base del panel.
- `includes/api`: rutas REST públicas/privadas.
- `includes/frontend`: router por URLs limpias y renderer público.
- `includes/importer`: lógica base de importación CSV.
- `docs/specification.md`: especificación completa de arquitectura/UX/API/CSV.
- `examples/*.csv`: plantillas de ejemplo para comida y bebidas.

## URLs públicas (sin shortcode)

- Selector de idioma por carta: `/menu/{restaurante}/{carta}/`
- Carta por idioma: `/menu/{restaurante}/{carta}/{locale}/`

## Diseños disponibles

- `minimal`
- `cards`
- `elegant`

## Estado

Scaffold funcional con routing por URL, selector de idioma y variantes de diseño (fase arquitectura/MVP inicial).
