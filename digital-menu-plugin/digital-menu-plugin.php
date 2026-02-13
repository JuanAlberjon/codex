<?php
/**
 * Plugin Name: Digital Menu Multi-Restaurant
 * Description: Carta digital multi-restaurante y multi-idioma con importación CSV y filtro de alérgenos.
 * Version: 0.1.0
 * Author: Codex
 * Text Domain: dmmr
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DMMR_VERSION', '0.1.0');
define('DMMR_PATH', plugin_dir_path(__FILE__));
define('DMMR_URL', plugin_dir_url(__FILE__));

require_once DMMR_PATH . 'includes/core/class-dmmr-autoloader.php';
DMMR_Autoloader::register();

register_activation_hook(__FILE__, ['DMMR_Installer', 'activate']);

add_action('plugins_loaded', function () {
    DMMR_Plugin::instance()->init();
});
