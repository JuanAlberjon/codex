<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Frontend_Renderer
{
    private DMMR_Repository $repository;

    public function __construct()
    {
        $this->repository = new DMMR_Repository();
    }

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
        $menu = $this->repository->get_menu_by_slugs($restaurantSlug, $menuSlug);
        if (!$menu) {
            return $this->render_not_found();
        }

        $defaultLocale = $menu['default_locale'] ?: 'es';
        $activeLocale = $lang !== '' ? $lang : $defaultLocale;
        $languages = $this->repository->get_languages((int) $menu['restaurant_id'], (int) $menu['id']);
        if (!$languages) {
            $languages = [
                ['locale' => $defaultLocale, 'label' => strtoupper($defaultLocale)],
            ];
        }

        $isSelectorPage = $lang === '';
        $sections = $isSelectorPage ? [] : $this->repository->get_sections_with_items((int) $menu['id'], $activeLocale, $defaultLocale);

        wp_enqueue_style('dmmr-frontend');
        wp_enqueue_script('dmmr-frontend');

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
        <body class="dmmr-public dmmr-design-<?php echo esc_attr($menu['design_variant']); ?>">
        <main class="dmmr-container">
            <header class="dmmr-header">
                <h1><?php echo esc_html($menu['title']); ?></h1>
                <p><?php echo esc_html((string) $menu['subtitle']); ?></p>
            </header>

            <?php if ($isSelectorPage) : ?>
                <section class="dmmr-language-selector-page">
                    <h2>Selecciona idioma</h2>
                    <ul>
                        <?php foreach ($languages as $language) : ?>
                            <li><a href="<?php echo esc_url(DMMR_Router::build_menu_url($restaurantSlug, $menuSlug, $language['locale'])); ?>"><?php echo esc_html($language['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php else : ?>
                <?php foreach ($sections as $section) : ?>
                    <section class="dmmr-section">
                        <h2><?php echo esc_html($section['name']); ?></h2>
                        <?php if (!empty($section['subtitle'])) : ?><p><?php echo esc_html($section['subtitle']); ?></p><?php endif; ?>
                        <?php foreach ($section['items'] as $item) : ?>
                            <article class="dmmr-item" data-allergens="<?php echo esc_attr(implode(',', $item['allergens'])); ?>">
                                <h3><?php echo esc_html($item['name']); ?></h3>
                                <?php if (!empty($item['description'])) : ?><p><?php echo esc_html($item['description']); ?></p><?php endif; ?>
                                <?php if ((float) $item['base_price'] > 0) : ?><p class="dmmr-price"><?php echo esc_html(number_format((float) $item['base_price'], 2, ',', '.') . ' €'); ?></p><?php endif; ?>
                                <?php if (!empty($item['price_options'])) : ?>
                                    <ul class="dmmr-price-options">
                                        <?php foreach ($item['price_options'] as $priceOption) : ?>
                                            <li><?php echo esc_html($priceOption['option_label'] . ': ' . number_format((float) $priceOption['amount'], 2, ',', '.') . ' €'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (!empty($item['allergens'])) : ?><p class="dmmr-allergens">Alérgenos: <?php echo esc_html(implode(', ', $item['allergens'])); ?></p><?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
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

    private function render_not_found(): string
    {
        status_header(404);
        return '<h1>Carta no encontrada</h1>';
    }
}
