<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Repository
{
    public function get_restaurants(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_restaurants';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A) ?: [];
    }

    public function create_restaurant(string $name, string $slug): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_restaurants';
        $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function get_menus(int $restaurantId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_menus';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE restaurant_id = %d ORDER BY id DESC", $restaurantId), ARRAY_A) ?: [];
    }

    public function create_menu(int $restaurantId, array $data): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_menus';
        $wpdb->insert($table, [
            'restaurant_id' => $restaurantId,
            'slug' => $data['slug'],
            'type' => $data['type'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'description' => $data['description'],
            'default_locale' => $data['default_locale'],
            'design_variant' => $data['design_variant'],
            'is_published' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        return (int) $wpdb->insert_id;
    }

    public function get_menu_by_slugs(string $restaurantSlug, string $menuSlug): ?array
    {
        global $wpdb;
        $restaurants = $wpdb->prefix . 'dmmr_restaurants';
        $menus = $wpdb->prefix . 'dmmr_menus';
        $menu = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, r.slug AS restaurant_slug, r.name AS restaurant_name FROM {$menus} m INNER JOIN {$restaurants} r ON r.id = m.restaurant_id WHERE r.slug = %s AND m.slug = %s AND m.is_published = 1 LIMIT 1",
            $restaurantSlug,
            $menuSlug
        ), ARRAY_A);
        return is_array($menu) ? $menu : null;
    }

    public function get_languages(int $restaurantId, int $menuId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_languages';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT locale, label FROM {$table} WHERE (menu_id = %d OR (menu_id IS NULL AND restaurant_id = %d)) AND is_active = 1 ORDER BY sort_order ASC, id ASC",
            $menuId,
            $restaurantId
        ), ARRAY_A);
        return $rows ?: [];
    }

    public function add_language(int $restaurantId, int $menuId, string $locale, string $label): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_languages';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE menu_id = %d AND locale = %s", $menuId, $locale));
        if ($exists) {
            return;
        }
        $wpdb->insert($table, [
            'restaurant_id' => $restaurantId,
            'menu_id' => $menuId,
            'locale' => $locale,
            'label' => $label,
            'is_default' => 0,
            'is_active' => 1,
            'sort_order' => 0,
        ]);
    }

    public function get_sections_with_items(int $menuId, string $locale, string $defaultLocale): array
    {
        global $wpdb;
        $sectionsTable = $wpdb->prefix . 'dmmr_sections';
        $itemsTable = $wpdb->prefix . 'dmmr_items';
        $translationsTable = $wpdb->prefix . 'dmmr_translations';
        $itemAllergensTable = $wpdb->prefix . 'dmmr_item_allergens';
        $allergensTable = $wpdb->prefix . 'dmmr_allergens';
        $pricesTable = $wpdb->prefix . 'dmmr_item_prices';

        $sections = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.sort_order,
            COALESCE(ts.name, tsd.name, CONCAT('Sección ', s.id)) AS name,
            COALESCE(ts.subtitle, tsd.subtitle, '') AS subtitle
            FROM {$sectionsTable} s
            LEFT JOIN {$translationsTable} ts ON ts.entity_type='section' AND ts.entity_id=s.id AND ts.locale=%s
            LEFT JOIN {$translationsTable} tsd ON tsd.entity_type='section' AND tsd.entity_id=s.id AND tsd.locale=%s
            WHERE s.menu_id=%d ORDER BY s.sort_order ASC, s.id ASC",
            $locale,
            $defaultLocale,
            $menuId
        ), ARRAY_A) ?: [];

        foreach ($sections as &$section) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT i.id, i.item_type, i.base_price, i.sort_order,
                COALESCE(ti.name, tid.name, CONCAT('Item ', i.id)) AS name,
                COALESCE(ti.description, tid.description, '') AS description
                FROM {$itemsTable} i
                LEFT JOIN {$translationsTable} ti ON ti.entity_type='item' AND ti.entity_id=i.id AND ti.locale=%s
                LEFT JOIN {$translationsTable} tid ON tid.entity_type='item' AND tid.entity_id=i.id AND tid.locale=%s
                WHERE i.section_id=%d AND i.is_active=1 ORDER BY i.sort_order ASC, i.id ASC",
                $locale,
                $defaultLocale,
                (int) $section['id']
            ), ARRAY_A) ?: [];

            foreach ($items as &$item) {
                $item['allergens'] = $wpdb->get_col($wpdb->prepare(
                    "SELECT a.slug FROM {$itemAllergensTable} ia INNER JOIN {$allergensTable} a ON a.id = ia.allergen_id WHERE ia.item_id = %d",
                    (int) $item['id']
                ));

                $item['price_options'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT option_slug, option_label, amount FROM {$pricesTable} WHERE item_id = %d ORDER BY sort_order ASC, id ASC",
                    (int) $item['id']
                ), ARRAY_A) ?: [];
            }

            $section['items'] = $items;
        }

        return $sections;
    }

    public function add_section_with_translation(int $menuId, string $name, int $sortOrder, string $locale): int
    {
        global $wpdb;
        $sections = $wpdb->prefix . 'dmmr_sections';
        $wpdb->insert($sections, [
            'menu_id' => $menuId,
            'sort_order' => $sortOrder,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        $sectionId = (int) $wpdb->insert_id;
        $this->upsert_translation('section', $sectionId, $locale, $name, '', '');
        return $sectionId;
    }

    public function add_item_with_translation(int $menuId, int $sectionId, array $data, string $locale): int
    {
        global $wpdb;
        $items = $wpdb->prefix . 'dmmr_items';
        $wpdb->insert($items, [
            'menu_id' => $menuId,
            'section_id' => $sectionId,
            'item_type' => $data['item_type'],
            'base_price' => $data['base_price'],
            'sort_order' => $data['sort_order'],
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        $itemId = (int) $wpdb->insert_id;
        $this->upsert_translation('item', $itemId, $locale, $data['name'], '', $data['description']);
        return $itemId;
    }

    public function get_allergens_for_restaurant(int $restaurantId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_allergens';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE (restaurant_id = %d OR restaurant_id IS NULL) AND is_active = 1 ORDER BY sort_order ASC, id ASC", $restaurantId), ARRAY_A) ?: [];
    }

    public function add_allergen(int $restaurantId, string $name, string $slug): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_allergens';
        $wpdb->insert($table, [
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => 0,
            'is_active' => 1,
        ]);
    }

    public function assign_allergens_to_item(int $itemId, array $slugs, int $restaurantId): void
    {
        global $wpdb;
        $pivot = $wpdb->prefix . 'dmmr_item_allergens';
        $allergens = $wpdb->prefix . 'dmmr_allergens';
        $wpdb->delete($pivot, ['item_id' => $itemId]);

        foreach ($slugs as $slug) {
            $allergenId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$allergens} WHERE slug = %s AND (restaurant_id = %d OR restaurant_id IS NULL) LIMIT 1", $slug, $restaurantId));
            if ($allergenId > 0) {
                $wpdb->insert($pivot, ['item_id' => $itemId, 'allergen_id' => $allergenId]);
            }
        }
    }

    private function upsert_translation(string $entityType, int $entityId, string $locale, string $name, string $subtitle, string $description): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_translations';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE entity_type = %s AND entity_id = %d AND locale = %s", $entityType, $entityId, $locale));
        $data = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'locale' => $locale,
            'name' => $name,
            'subtitle' => $subtitle,
            'description' => $description,
        ];

        if ($exists) {
            $wpdb->update($table, $data, ['id' => (int) $exists]);
            return;
        }

        $wpdb->insert($table, $data);
    }
}
