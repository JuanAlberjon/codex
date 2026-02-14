<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Rest_Api
{
    private DMMR_Repository $repository;

    public function __construct()
    {
        $this->repository = new DMMR_Repository();
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        register_rest_route('dmmr/v1', '/restaurants/(?P<restaurant>[a-z0-9-]+)/menus/(?P<menu>[a-z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_public_menu'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('dmmr/v1', '/imports/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'preview_import'],
            'permission_callback' => fn () => current_user_can('dmmr_import_csv'),
        ]);
    }

    public function get_public_menu(WP_REST_Request $request): WP_REST_Response
    {
        $restaurant = sanitize_title((string) $request['restaurant']);
        $menuSlug = sanitize_title((string) $request['menu']);
        $lang = sanitize_text_field((string) ($request->get_param('lang') ?: 'es'));
        $selectedAllergens = array_filter(array_map('sanitize_title', explode(',', (string) $request->get_param('allergens'))));

        $menu = $this->repository->get_menu_by_slugs($restaurant, $menuSlug);
        if (!$menu) {
            return new WP_REST_Response(['message' => 'Menu not found'], 404);
        }

        $sections = $this->repository->get_sections_with_items((int) $menu['id'], $lang, (string) $menu['default_locale']);
        if ($selectedAllergens) {
            foreach ($sections as &$section) {
                $section['items'] = array_values(array_filter($section['items'], function ($item) use ($selectedAllergens) {
                    return count(array_intersect($item['allergens'], $selectedAllergens)) === 0;
                }));
            }
        }

        return new WP_REST_Response([
            'menu' => $menu,
            'sections' => $sections,
            'lang' => $lang,
            'allergens' => $selectedAllergens,
        ], 200);
    }

    public function preview_import(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'status' => 'ok',
            'preview' => [],
            'notes' => 'Pendiente conectar carga de archivo temporal con DMMR_Csv_Importer.',
        ], 200);
    }
}
