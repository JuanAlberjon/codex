<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Frontend_Renderer
{
    public function register(): void
    {
        add_shortcode('dmmr_menu', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void
    {
        wp_register_style('dmmr-frontend', DMMR_URL . 'assets/dmmr-frontend.css', [], DMMR_VERSION);
        wp_register_script('dmmr-frontend', DMMR_URL . 'assets/dmmr-frontend.js', [], DMMR_VERSION, true);
    }

    public function render_shortcode(array $atts): string
    {
        $atts = shortcode_atts([
            'restaurant' => '',
            'menu' => '',
        ], $atts);

        wp_enqueue_style('dmmr-frontend');
        wp_enqueue_script('dmmr-frontend');

        ob_start();
        ?>
        <section class="dmmr-menu" data-restaurant="<?php echo esc_attr($atts['restaurant']); ?>" data-menu="<?php echo esc_attr($atts['menu']); ?>">
            <header>
                <h1 class="dmmr-menu-title">Carta digital</h1>
                <p class="dmmr-menu-subtitle">Contenido cargado dinámicamente vía API.</p>
            </header>
            <div class="dmmr-floating-controls">
                <button class="dmmr-fab" data-action="language" aria-label="Cambiar idioma">🌐 Idioma</button>
                <button class="dmmr-fab" data-action="allergens" aria-label="Filtrar alérgenos">⚠️ Alérgenos</button>
            </div>
            <div id="dmmr-menu-items"></div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
