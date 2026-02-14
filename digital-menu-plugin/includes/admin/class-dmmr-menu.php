<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Admin_Menu
{
    private DMMR_Repository $repository;

    public function __construct()
    {
        $this->repository = new DMMR_Repository();
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_pages']);
        add_action('admin_init', [$this, 'handle_forms']);
    }

    public function add_pages(): void
    {
        add_menu_page('Carta Digital', 'Carta Digital', 'dmmr_manage_assigned_restaurant', 'dmmr-restaurants', [$this, 'render_restaurants_page'], 'dashicons-food', 56);
        add_submenu_page('dmmr-restaurants', 'Restaurantes', 'Restaurantes', 'dmmr_manage_assigned_restaurant', 'dmmr-restaurants', [$this, 'render_restaurants_page']);
        add_submenu_page('dmmr-restaurants', 'Cartas', 'Cartas', 'dmmr_manage_menus', 'dmmr-menus', [$this, 'render_menus_page']);
        add_submenu_page('dmmr-restaurants', 'Secciones e Items', 'Secciones e Items', 'dmmr_manage_menus', 'dmmr-content', [$this, 'render_content_page']);
        add_submenu_page('dmmr-restaurants', 'Idiomas', 'Idiomas', 'dmmr_manage_menus', 'dmmr-languages', [$this, 'render_languages_page']);
        add_submenu_page('dmmr-restaurants', 'Alérgenos', 'Alérgenos', 'dmmr_manage_menus', 'dmmr-allergens', [$this, 'render_allergens_page']);
    }

    public function handle_forms(): void
    {
        if (!is_admin() || !isset($_POST['dmmr_action'])) {
            return;
        }

        check_admin_referer('dmmr_admin_action', 'dmmr_nonce');

        $action = sanitize_text_field(wp_unslash($_POST['dmmr_action']));

        if ($action === 'create_restaurant' && current_user_can('dmmr_manage_all_restaurants')) {
            $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
            $slug = sanitize_title(wp_unslash($_POST['slug'] ?? ''));
            if ($name && $slug) {
                $this->repository->create_restaurant($name, $slug);
            }
        }

        if ($action === 'create_menu' && current_user_can('dmmr_manage_menus')) {
            $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
            if ($restaurantId > 0) {
                $this->repository->create_menu($restaurantId, [
                    'slug' => sanitize_title(wp_unslash($_POST['slug'] ?? '')),
                    'type' => sanitize_key(wp_unslash($_POST['type'] ?? 'food')),
                    'title' => sanitize_text_field(wp_unslash($_POST['title'] ?? '')),
                    'subtitle' => sanitize_text_field(wp_unslash($_POST['subtitle'] ?? '')),
                    'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
                    'default_locale' => sanitize_text_field(wp_unslash($_POST['default_locale'] ?? 'es')),
                    'design_variant' => sanitize_key(wp_unslash($_POST['design_variant'] ?? 'minimal')),
                ]);
            }
        }

        if ($action === 'create_language' && current_user_can('dmmr_manage_menus')) {
            $this->repository->add_language((int) ($_POST['restaurant_id'] ?? 0), (int) ($_POST['menu_id'] ?? 0), sanitize_text_field(wp_unslash($_POST['locale'] ?? '')), sanitize_text_field(wp_unslash($_POST['label'] ?? '')));
        }

        if ($action === 'create_section' && current_user_can('dmmr_manage_menus')) {
            $this->repository->add_section_with_translation((int) ($_POST['menu_id'] ?? 0), sanitize_text_field(wp_unslash($_POST['name'] ?? '')), (int) ($_POST['sort_order'] ?? 0), sanitize_text_field(wp_unslash($_POST['locale'] ?? 'es')));
        }

        if ($action === 'create_item' && current_user_can('dmmr_manage_menus')) {
            $menuId = (int) ($_POST['menu_id'] ?? 0);
            $itemId = $this->repository->add_item_with_translation($menuId, (int) ($_POST['section_id'] ?? 0), [
                'item_type' => sanitize_key(wp_unslash($_POST['item_type'] ?? 'food')),
                'base_price' => (float) ($_POST['base_price'] ?? 0),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
                'description' => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            ], sanitize_text_field(wp_unslash($_POST['locale'] ?? 'es')));

            $allergens = array_filter(array_map('sanitize_title', explode(',', (string) wp_unslash($_POST['allergen_slugs'] ?? ''))));
            $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
            if ($restaurantId > 0) {
                $this->repository->assign_allergens_to_item($itemId, $allergens, $restaurantId);
            }
        }

        if ($action === 'create_allergen' && current_user_can('dmmr_manage_menus')) {
            $this->repository->add_allergen((int) ($_POST['restaurant_id'] ?? 0), sanitize_text_field(wp_unslash($_POST['name'] ?? '')), sanitize_title(wp_unslash($_POST['slug'] ?? '')));
        }

        wp_safe_redirect(add_query_arg('dmmr_saved', '1', wp_get_referer() ?: admin_url('admin.php?page=dmmr-restaurants')));
        exit;
    }

    public function render_restaurants_page(): void
    {
        $restaurants = $this->repository->get_restaurants();
        echo '<div class="wrap"><h1>Restaurantes</h1>';
        $this->flash();
        echo '<form method="post">';
        wp_nonce_field('dmmr_admin_action', 'dmmr_nonce');
        echo '<input type="hidden" name="dmmr_action" value="create_restaurant" />';
        echo '<table class="form-table"><tr><th>Nombre</th><td><input name="name" required /></td></tr><tr><th>Slug</th><td><input name="slug" required /></td></tr></table>';
        submit_button('Crear restaurante');
        echo '</form><hr/><h2>Listado</h2><table class="widefat"><thead><tr><th>ID</th><th>Nombre</th><th>Slug</th></tr></thead><tbody>';
        foreach ($restaurants as $restaurant) {
            echo '<tr><td>' . esc_html((string) $restaurant['id']) . '</td><td>' . esc_html($restaurant['name']) . '</td><td>' . esc_html($restaurant['slug']) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function render_menus_page(): void
    {
        $restaurants = $this->repository->get_restaurants();
        echo '<div class="wrap"><h1>Cartas</h1>';
        $this->flash();
        echo '<form method="post">';
        wp_nonce_field('dmmr_admin_action', 'dmmr_nonce');
        echo '<input type="hidden" name="dmmr_action" value="create_menu" />';
        echo '<table class="form-table">';
        echo '<tr><th>Restaurante</th><td><select name="restaurant_id" required>';
        foreach ($restaurants as $r) {
            echo '<option value="' . esc_attr((string) $r['id']) . '">' . esc_html($r['name']) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>Título</th><td><input name="title" required /></td></tr>';
        echo '<tr><th>Slug</th><td><input name="slug" required /></td></tr>';
        echo '<tr><th>Tipo</th><td><select name="type"><option value="food">Comida</option><option value="drink">Bebidas</option></select></td></tr>';
        echo '<tr><th>Idioma por defecto</th><td><input name="default_locale" value="es" /></td></tr>';
        echo '<tr><th>Diseño</th><td><select name="design_variant"><option value="minimal">minimal</option><option value="cards">cards</option><option value="elegant">elegant</option></select></td></tr>';
        echo '<tr><th>Subtítulo</th><td><input name="subtitle" /></td></tr>';
        echo '<tr><th>Descripción</th><td><textarea name="description"></textarea></td></tr>';
        echo '</table>';
        submit_button('Crear carta');
        echo '</form><hr/><h2>URLs</h2>';
        foreach ($restaurants as $r) {
            $menus = $this->repository->get_menus((int) $r['id']);
            if (!$menus) {
                continue;
            }
            echo '<h3>' . esc_html($r['name']) . '</h3><ul>';
            foreach ($menus as $m) {
                $selector = DMMR_Router::build_menu_url($r['slug'], $m['slug']);
                echo '<li><strong>' . esc_html($m['title']) . '</strong> - <a href="' . esc_url($selector) . '" target="_blank">' . esc_html($selector) . '</a></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    public function render_content_page(): void
    {
        $menuId = (int) ($_GET['menu_id'] ?? 0);
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);
        echo '<div class="wrap"><h1>Secciones e items</h1>';
        $this->flash();
        echo '<p>Usa menu_id y restaurant_id en la URL de esta pantalla para alta rápida de contenido.</p>';

        echo '<h2>Nueva sección</h2><form method="post">';
        wp_nonce_field('dmmr_admin_action', 'dmmr_nonce');
        echo '<input type="hidden" name="dmmr_action" value="create_section" />';
        echo '<input type="hidden" name="menu_id" value="' . esc_attr((string) $menuId) . '" />';
        echo '<p><input name="name" placeholder="Título sección" required /> <input name="locale" value="es" /> <input type="number" name="sort_order" value="0" />';
        submit_button('Crear sección', 'secondary', 'submit', false);
        echo '</p></form>';

        echo '<h2>Nuevo item</h2><form method="post">';
        wp_nonce_field('dmmr_admin_action', 'dmmr_nonce');
        echo '<input type="hidden" name="dmmr_action" value="create_item" /><input type="hidden" name="menu_id" value="' . esc_attr((string) $menuId) . '" /><input type="hidden" name="restaurant_id" value="' . esc_attr((string) $restaurantId) . '" />';
        echo '<p><input type="number" name="section_id" placeholder="section_id" required /> <input name="name" placeholder="Nombre" required /> <input name="description" placeholder="Descripción" /> <input name="locale" value="es" /> <select name="item_type"><option value="food">food</option><option value="drink">drink</option></select> <input type="number" step="0.01" name="base_price" placeholder="Precio" /> <input type="number" name="sort_order" value="0" /> <input name="allergen_slugs" placeholder="gluten,lactosa" />';
        submit_button('Crear item', 'secondary', 'submit', false);
        echo '</p></form></div>';
    }

    public function render_languages_page(): void
    {
        echo '<div class="wrap"><h1>Idiomas</h1>';
        $this->flash();
        echo '<form method="post">';
        wp_nonce_field('dmmr_admin_action', 'dmmr_nonce');
        echo '<input type="hidden" name="dmmr_action" value="create_language" />';
        echo '<p><input type="number" name="restaurant_id" placeholder="restaurant_id" required /> <input type="number" name="menu_id" placeholder="menu_id" required /> <input name="locale" placeholder="es" required /> <input name="label" placeholder="Español" required />';
        submit_button('Añadir idioma', 'secondary', 'submit', false);
        echo '</p></form></div>';
    }

    public function render_allergens_page(): void
    {
        $restaurantId = (int) ($_GET['restaurant_id'] ?? 0);
        $allergens = $restaurantId > 0 ? $this->repository->get_allergens_for_restaurant($restaurantId) : [];
        echo '<div class="wrap"><h1>Alérgenos</h1>';
        $this->flash();
        echo '<form method="post">';
        wp_nonce_field('dmmr_admin_action', 'dmmr_nonce');
        echo '<input type="hidden" name="dmmr_action" value="create_allergen" />';
        echo '<p><input type="number" name="restaurant_id" placeholder="restaurant_id" required /> <input name="name" placeholder="Nombre" required /> <input name="slug" placeholder="gluten" required />';
        submit_button('Crear alérgeno', 'secondary', 'submit', false);
        echo '</p></form>';
        if ($restaurantId > 0) {
            echo '<h2>Listado</h2><ul>';
            foreach ($allergens as $a) {
                echo '<li>' . esc_html($a['name']) . ' (' . esc_html($a['slug']) . ')</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    private function flash(): void
    {
        if (isset($_GET['dmmr_saved'])) {
            echo '<div class="notice notice-success"><p>Guardado correctamente.</p></div>';
        }
    }
}
