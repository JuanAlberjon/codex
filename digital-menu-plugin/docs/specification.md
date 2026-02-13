# Especificación técnica: Plugin Carta Digital Multi-Restaurante (DMMR)

## 1) Arquitectura y stack recomendado

- **Plataforma objetivo**: WordPress 6.4+ / PHP 8.1+ / MySQL 8+.
- **Arquitectura modular**:
  - `core`: bootstrap, activación, migraciones, roles/capabilities.
  - `admin`: pantallas de gestión (restaurantes, cartas, secciones, ítems, idiomas, alérgenos, importación).
  - `importer`: parser CSV, mapeo de columnas, preview, validación fila a fila, persistencia parcial.
  - `frontend`: render público, comportamiento de filtros, botones flotantes.
  - `api`: endpoints REST para panel y frontend.
- **UI admin**: React + WP components (fase v1) o inicialmente WP Admin Forms (MVP).
- **Persistencia**: tablas custom (evitar CPT/meta por volumen + consultas complejas de traducciones y alérgenos).
- **Caching**:
  - Transients/object cache por combinación `restaurant + menu + locale + allergen_fingerprint`.
  - Invalidación al guardar carta, ítem, traducción o alérgenos.

## 2) Roles y permisos

### Roles
1. **Admin del plugin**
   - Cap: `dmmr_manage_all_restaurants`, `dmmr_manage_settings`.
   - Acceso total multi-tenant.
2. **Gestor de restaurante**
   - Cap: `dmmr_manage_assigned_restaurant`, `dmmr_manage_menus`, `dmmr_import_csv`.
   - Solo su restaurante (scope por `restaurant_id`).
3. **Editor (opcional)**
   - Cap: `dmmr_edit_menu_content`.
   - Edita secciones/items/traducciones sin tocar configuración global.

## 3) Modelo de datos

> Implementado en `DMMR_Installer` con tablas normalizadas.

### Tablas principales
- `wp_dmmr_restaurants`
  - `id`, `slug`, `name`, `status`, `settings(json)`, timestamps.
- `wp_dmmr_menus`
  - `id`, `restaurant_id`, `slug`, `type(food|drinks)`, `title`, `subtitle`, `description`, `anchor_label`, `anchor_url`, `default_locale`, `is_published`.
- `wp_dmmr_languages`
  - Idiomas activos por restaurante o carta: `locale`, `label`, `is_default`, `is_active`, `sort_order`.
- `wp_dmmr_sections`
  - `menu_id`, `external_id`, `sort_order`.
- `wp_dmmr_items`
  - `menu_id`, `section_id`, `sku`, `external_id`, `item_type(food|drink)`, `base_price`, `sort_order`, `is_active`.
- `wp_dmmr_item_prices`
  - Precios múltiples para bebidas: `item_id`, `option_slug`, `option_label`, `amount`, `currency`, `sort_order`.
- `wp_dmmr_translations`
  - Traducciones por entidad (`menu|section|item`) y `locale`: `name`, `subtitle`, `description`.
- `wp_dmmr_allergens`
  - Catálogo: `restaurant_id?`, `slug`, `name`, `icon_url`, `sort_order`, `is_active`.
- `wp_dmmr_item_allergens`
  - Relación N:M item ↔ alérgeno.
- `wp_dmmr_import_logs`
  - Historial importaciones: archivo, modo, totales, errores, usuario, fecha.

## 4) Gestión de alérgenos (reglas)

- Catálogo con campos: `id`, `name`, `slug`, `icon_url`, `sort_order`, `is_active`.
- En CSV el campo `allergen_slugs` usa slugs separados por coma, ej: `gluten,lactosa`.
- Estrategia de filtrado frontend:
  - Usuario selecciona alérgenos “a evitar”.
  - Si item contiene **cualquiera** de los seleccionados, se oculta.
  - Fórmula booleana: `visible = NOT(intersection(item_allergens, selected_allergens).length > 0)`.
- Bebidas por defecto: `allergen_slugs` vacío.

## 5) Especificación CSV (con ejemplos)

## 5.1 Encabezados base

- `section_slug`
- `section_order`
- `section_title_es`, `section_subtitle_es` (+ idiomas ilimitados por patrón `*_LOCALE`)
- `item_type` (`food` o `drink`)
- `item_sku`
- `item_external_id`
- `item_order`
- `item_name_es`, `item_description_es` (+ traducciones por idioma)
- `price_base`
- `price_options` (JSON para múltiples precios)
- `allergen_slugs`

## 5.2 Ejemplo CSV comida

```csv
section_slug,section_order,section_title_es,section_title_en,item_type,item_sku,item_external_id,item_order,item_name_es,item_name_en,item_description_es,item_description_en,price_base,price_options,allergen_slugs
entrantes,10,Entrantes,Starters,food,F-1001,ext-food-1,10,Croquetas caseras,Homemade croquettes,Crujientes y cremosas,Crunchy and creamy,7.50,,gluten,lactosa
ensaladas,20,Ensaladas,Salads,food,F-2001,ext-food-2,20,Ensalada César,Caesar salad,Con pollo y parmesano,With chicken and parmesan,10.00,,huevo,lactosa
```

> Nota: si un alérgeno no existe, la importación lo reporta y (según modo) crea warning o error.

## 5.3 Ejemplo CSV bebidas (múltiples precios)

```csv
section_slug,section_order,section_title_es,section_title_en,item_type,item_sku,item_external_id,item_order,item_name_es,item_name_en,item_description_es,item_description_en,price_base,price_options,allergen_slugs
vinos,10,Vinos,Wines,drink,D-1001,ext-drink-1,10,Rioja crianza,Rioja reserva,Tinto de la casa,House red,,"[{""slug"":""copa"",""label"":""Copa"",""amount"":3.50},{""slug"":""botella"",""label"":""Botella"",""amount"":18.00}]",
refrescos,20,Refrescos,Soft Drinks,drink,D-2001,ext-drink-2,20,Cola,Cola,33cl,33cl,2.80,,
```

## 5.4 Matching para updates

Orden de matching configurable:
1. `item_sku`
2. `item_external_id`
3. `item_name_{default_locale} + section_slug`

## 5.5 Estrategia de errores

- Preview (25 filas por defecto).
- Validación por fila con severidad `error|warning`.
- Modo importación:
  - `strict`: si hay errores, no importa.
  - `partial`: importa válidas y loguea fallidas.

## 6) Endpoints/API

### Públicos
- `GET /wp-json/dmmr/v1/restaurants/{restaurant_slug}/menus/{menu_slug}?lang=es&allergens=gluten,lactosa`
  - Devuelve carta ya traducida + secciones + items + precios + metadatos de filtros.

### Privados (panel)
- `POST /wp-json/dmmr/v1/imports/preview`
- `POST /wp-json/dmmr/v1/imports/commit`
- `GET /wp-json/dmmr/v1/restaurants`
- `POST /wp-json/dmmr/v1/restaurants`
- `GET /wp-json/dmmr/v1/menus?restaurant_id=...`
- `POST /wp-json/dmmr/v1/menus`
- `POST /wp-json/dmmr/v1/items/reorder`
- `POST /wp-json/dmmr/v1/sections/reorder`
- `GET /wp-json/dmmr/v1/import-logs?restaurant_id=...&menu_id=...`

## 7) UX panel (wireframe textual)

1. **Restaurantes**
   - Tabla: Nombre | Slug | Estado | Cartas | Acciones
   - Botón “+ Nuevo restaurante”

2. **Detalle restaurante (tabs)**
   - Tab Cartas
   - Tab Idiomas
   - Tab Alérgenos
   - Tab Configuración visual (botones flotantes)

3. **Detalle carta (tabs)**
   - General: título/subtítulo/párrafo/texto ancla
   - Secciones: CRUD + drag&drop
   - Items: tabla editable + drag&drop + modal de edición
   - Importar CSV: upload → mapping → preview → confirmar
   - Preview pública: link borrador

4. **Modal Alérgenos en frontend**
   - Checkbox list + icono + etiqueta
   - Estado activo visible con chips
   - Botones: Aplicar / Limpiar

## 8) Comportamiento frontend (idioma + alérgenos)

- Idioma por defecto:
  1. Si configuración = navegador y locale activo existe, usarlo.
  2. Si no, usar idioma por defecto de carta.
- Cambio de idioma:
  - Recarga datos por API (`lang=...`) sin recargar página completa.
- Filtro alérgenos:
  - Multi-selección acumulativa.
  - Oculta items no aptos en cliente (si datos cargados) o reconsulta API con `allergens`.
  - Persistencia opcional en `localStorage` por carta.

## 9) Seguridad y no funcionales

- Sanitización/escape en todos los inputs/outputs (`sanitize_text_field`, `esc_html`, `wp_kses_post`).
- Nonces y `current_user_can` para acciones administrativas.
- Protección XSS en campos traducidos y HTML permitido limitado.
- Índices DB para consultas por `restaurant_id`, `menu_id`, `locale`, `slug`.
- Migraciones versionadas con `dmmr_db_version`.
- URLs limpias recomendadas: `/{restaurant_slug}/carta/{menu_slug}` mediante rewrite + fallback shortcode.

## 10) Plan por fases

### MVP (4-6 semanas)
- Multi-restaurante básico.
- Cartas + secciones + items + traducción ES/EN.
- Catálogo alérgenos y filtro frontend.
- CSV con preview y import parcial.

### v1 (6-8 semanas)
- Mapeo avanzado columnas.
- Precios múltiples bebidas completos.
- Logs detallados + export CSV.
- UI React admin + drag&drop robusto.

### Mejoras
- CDN/image optimization.
- Métricas de uso de carta.
- QR generator por carta/idioma.
- Modo franquicia (plantillas de carta replicables).
