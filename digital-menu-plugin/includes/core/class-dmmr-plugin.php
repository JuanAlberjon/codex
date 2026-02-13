<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Plugin
{
    private static ?DMMR_Plugin $instance = null;

    public static function instance(): DMMR_Plugin
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init(): void
    {
        (new DMMR_Roles())->register();
        (new DMMR_Admin_Menu())->register();
        (new DMMR_Rest_Api())->register();
        (new DMMR_Frontend_Renderer())->register();
        (new DMMR_Csv_Importer())->register();
    }
}
