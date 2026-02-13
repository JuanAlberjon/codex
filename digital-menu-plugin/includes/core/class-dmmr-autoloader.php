<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload(string $class): void
    {
        if (strpos($class, 'DMMR_') !== 0) {
            return;
        }

        $parts = explode('_', strtolower($class));
        $parts = array_slice($parts, 1);
        $class_name = array_pop($parts);
        $base_dir = DMMR_PATH . 'includes/';

        if (!empty($parts)) {
            $base_dir .= implode('/', $parts) . '/';
        }

        $file = $base_dir . 'class-dmmr-' . $class_name . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
