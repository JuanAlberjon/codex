<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Router
{
    public function register(): void
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'render_menu_route']);
    }

    public function add_rewrite_rules(): void
    {
        add_rewrite_rule('^menu/([^/]+)/([^/]+)/?$', 'index.php?dmmr_restaurant=$matches[1]&dmmr_menu=$matches[2]', 'top');
        add_rewrite_rule('^menu/([^/]+)/([^/]+)/([a-zA-Z_-]+)/?$', 'index.php?dmmr_restaurant=$matches[1]&dmmr_menu=$matches[2]&dmmr_lang=$matches[3]', 'top');
    }

    public function register_query_vars(array $queryVars): array
    {
        $queryVars[] = 'dmmr_restaurant';
        $queryVars[] = 'dmmr_menu';
        $queryVars[] = 'dmmr_lang';

        return $queryVars;
    }

    public function render_menu_route(): void
    {
        $restaurantSlug = sanitize_title((string) get_query_var('dmmr_restaurant'));
        $menuSlug = sanitize_title((string) get_query_var('dmmr_menu'));

        if ($restaurantSlug === '' || $menuSlug === '') {
            return;
        }

        $lang = sanitize_text_field((string) get_query_var('dmmr_lang'));
        $frontendRenderer = new DMMR_Frontend_Renderer();

        status_header(200);
        nocache_headers();
        echo $frontendRenderer->render_public_page($restaurantSlug, $menuSlug, $lang);
        exit;
    }

    public static function build_menu_url(string $restaurantSlug, string $menuSlug, string $locale = ''): string
    {
        $path = trailingslashit(home_url('/menu/' . $restaurantSlug . '/' . $menuSlug));

        if ($locale !== '') {
            $path = trailingslashit($path . $locale);
        }

        return $path;
    }
}
