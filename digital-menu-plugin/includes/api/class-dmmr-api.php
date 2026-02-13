<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Rest_Api
{
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
            'args' => [
                'lang' => ['required' => false],
                'allergens' => ['required' => false],
            ],
        ]);

        register_rest_route('dmmr/v1', '/imports/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'preview_import'],
            'permission_callback' => fn () => current_user_can('dmmr_import_csv'),
        ]);
    }

    public function get_public_menu(WP_REST_Request $request): WP_REST_Response
    {
        $payload = [
            'restaurant' => $request['restaurant'],
            'menu' => $request['menu'],
            'lang' => $request->get_param('lang') ?: 'es',
            'allergens' => array_filter(explode(',', (string) $request->get_param('allergens'))),
            'message' => 'Implementar query consolidada con caché por locale/allergen fingerprint.',
        ];

        return new WP_REST_Response($payload, 200);
    }

    public function preview_import(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'status' => 'ok',
            'preview' => [],
            'notes' => 'Conectar con DMMR_Csv_Importer::build_preview().',
        ], 200);
    }
}
