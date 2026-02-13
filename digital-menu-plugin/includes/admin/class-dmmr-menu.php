<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Admin_Menu
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_pages']);
    }

    public function add_pages(): void
    {
        add_menu_page(
            'Carta Digital',
            'Carta Digital',
            'dmmr_manage_assigned_restaurant',
            'dmmr-restaurants',
            [$this, 'render_restaurants_page'],
            'dashicons-food',
            56
        );

        add_submenu_page('dmmr-restaurants', 'Restaurantes', 'Restaurantes', 'dmmr_manage_assigned_restaurant', 'dmmr-restaurants', [$this, 'render_restaurants_page']);
        add_submenu_page('dmmr-restaurants', 'Cartas', 'Cartas', 'dmmr_manage_menus', 'dmmr-menus', [$this, 'render_menus_page']);
        add_submenu_page('dmmr-restaurants', 'Importar CSV', 'Importar CSV', 'dmmr_import_csv', 'dmmr-import', [$this, 'render_import_page']);
        add_submenu_page('dmmr-restaurants', 'Alérgenos', 'Alérgenos', 'dmmr_manage_menus', 'dmmr-allergens', [$this, 'render_allergens_page']);
    }

    public function render_restaurants_page(): void
    {
        echo '<div class="wrap"><h1>Restaurantes</h1><p>Listado y edición de restaurantes multi-tenant.</p></div>';
    }

    public function render_menus_page(): void
    {
        echo '<div class="wrap"><h1>Cartas</h1><p>Gestión de cartas, secciones, ítems e idiomas.</p></div>';
    }

    public function render_import_page(): void
    {
        echo '<div class="wrap"><h1>Importación CSV</h1><p>Preview, mapeo y confirmación.</p></div>';
    }

    public function render_allergens_page(): void
    {
        echo '<div class="wrap"><h1>Alérgenos</h1><p>Catálogo de alérgenos y activación por restaurante.</p></div>';
    }
}
