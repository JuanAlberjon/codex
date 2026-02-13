<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Roles
{
    public function register(): void
    {
        add_role('dmmr_restaurant_manager', 'Gestor de Restaurante', [
            'read' => true,
            'dmmr_manage_assigned_restaurant' => true,
            'dmmr_manage_menus' => true,
            'dmmr_import_csv' => true,
        ]);

        add_role('dmmr_editor', 'Editor de Carta', [
            'read' => true,
            'dmmr_edit_menu_content' => true,
        ]);

        $admin = get_role('administrator');
        if ($admin) {
            $capabilities = [
                'dmmr_manage_all_restaurants',
                'dmmr_manage_assigned_restaurant',
                'dmmr_manage_menus',
                'dmmr_import_csv',
                'dmmr_manage_settings',
                'dmmr_edit_menu_content',
            ];
            foreach ($capabilities as $capability) {
                $admin->add_cap($capability);
            }
        }
    }
}
