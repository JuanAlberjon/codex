<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Frontend_Renderer
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void
    {
        wp_register_style('dmmr-frontend', DMMR_URL . 'assets/dmmr-frontend.css', [], DMMR_VERSION);
        wp_register_script('dmmr-frontend', DMMR_URL . 'assets/dmmr-frontend.js', [], DMMR_VERSION, true);
    }

    public function render_public_page(string $restaurantSlug, string $menuSlug, string $lang): string
    {
        $menu = $this->resolve_menu($restaurantSlug, $menuSlug);
        if (!$menu) {
            return $this->render_not_found();
        }

        $languages = $this->resolve_languages((int) $menu['id'], (int) $menu['restaurant_id']);
        $defaultLocale = $menu['default_locale'] ?: 'es';
        $activeLocale = $lang !== '' ? $lang : $defaultLocale;
        $isSelectorPage = $lang === '';

        wp_enqueue_style('dmmr-frontend');
        wp_enqueue_script('dmmr-frontend');

        $designVariant = $menu['design_variant'] ?: 'minimal';
        $items = $this->demo_items();

        ob_start();
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($menu['title']); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="dmmr-public dmmr-design-<?php echo esc_attr($designVariant); ?>">
        <main class="dmmr-container">
            <header class="dmmr-header">
                <h1><?php echo esc_html($menu['title']); ?></h1>
                <?php if (!empty($menu['subtitle'])) : ?>
                    <p><?php echo esc_html($menu['subtitle']); ?></p>
                <?php endif; ?>
                <?php if (!empty($menu['description'])) : ?>
                    <div><?php echo esc_html($menu['description']); ?></div>
                <?php endif; ?>
            </header>

            <?php if ($isSelectorPage) : ?>
                <section class="dmmr-language-selector-page">
                    <h2>Selecciona idioma</h2>
                    <ul>
                        <?php foreach ($languages as $locale => $label) : ?>
                            <li><a href="<?php echo esc_url(DMMR_Router::build_menu_url($restaurantSlug, $menuSlug, $locale)); ?>"><?php echo esc_html($label); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php else : ?>
                <section class="dmmr-menu-list" data-locale="<?php echo esc_attr($activeLocale); ?>">
                    <?php foreach ($items as $item) : ?>
                        <article class="dmmr-item" data-allergens="<?php echo esc_attr(implode(',', $item['allergens'])); ?>">
                            <h3><?php echo esc_html($item['name']); ?></h3>
                            <p class="dmmr-price"><?php echo esc_html($item['price']); ?></p>
                            <p class="dmmr-allergens"><?php echo esc_html(implode(', ', $item['allergens'])); ?></p>
                        </article>
                    <?php endforeach; ?>
                </section>
                <div class="dmmr-floating-controls">
                    <button class="dmmr-fab" data-action="language" data-menu-url="<?php echo esc_url(DMMR_Router::build_menu_url($restaurantSlug, $menuSlug)); ?>">🌐 Idioma</button>
                    <button class="dmmr-fab" data-action="allergens">⚠️ Alérgenos</button>
                </div>
            <?php endif; ?>
        </main>
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php

        return (string) ob_get_clean();
    }

    private function resolve_menu(string $restaurantSlug, string $menuSlug): ?array
    {
        global $wpdb;
        $restaurants = $wpdb->prefix . 'dmmr_restaurants';
        $menus = $wpdb->prefix . 'dmmr_menus';

        $menu = $wpdb->get_row($wpdb->prepare(
            "SELECT m.* FROM {$menus} m INNER JOIN {$restaurants} r ON r.id = m.restaurant_id WHERE r.slug = %s AND m.slug = %s LIMIT 1",
            $restaurantSlug,
            $menuSlug
        ), ARRAY_A);

        if (!is_array($menu)) {
            return [
                'id' => 0,
                'restaurant_id' => 0,
                'title' => ucfirst($menuSlug),
                'subtitle' => 'Carta digital',
                'description' => 'Demo de URL pública por idioma.',
                'default_locale' => 'es',
                'design_variant' => 'minimal',
            ];
        }

        return $menu;
    }

    private function resolve_languages(int $menuId, int $restaurantId): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'dmmr_languages';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT locale, label FROM {$table} WHERE (menu_id = %d OR (menu_id IS NULL AND restaurant_id = %d)) AND is_active = 1 ORDER BY sort_order ASC",
            $menuId,
            $restaurantId
        ), ARRAY_A);

        if (!$rows) {
            return ['es' => 'Español', 'en' => 'English', 'fr' => 'Français'];
        }

        $languages = [];
        foreach ($rows as $row) {
            $languages[$row['locale']] = $row['label'];
        }

        return $languages;
    }

    private function demo_items(): array
    {
        return [
            ['name' => 'Croquetas', 'price' => '7,50 €', 'allergens' => ['gluten', 'lactosa']],
            ['name' => 'Ensalada César', 'price' => '10,00 €', 'allergens' => ['huevo', 'lactosa']],
            ['name' => 'Agua mineral', 'price' => '2,00 €', 'allergens' => []],
        ];
    }

    private function render_not_found(): string
    {
        status_header(404);
        return '<h1>Carta no encontrada</h1>';
    }
}
