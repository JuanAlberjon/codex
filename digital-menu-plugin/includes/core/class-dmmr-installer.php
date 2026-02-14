<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Installer
{
    public static function activate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            "CREATE TABLE {$wpdb->prefix}dmmr_restaurants (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                slug VARCHAR(190) NOT NULL,
                name VARCHAR(190) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                settings LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_menus (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                restaurant_id BIGINT UNSIGNED NOT NULL,
                slug VARCHAR(190) NOT NULL,
                type VARCHAR(30) NOT NULL,
                title VARCHAR(190) NOT NULL,
                subtitle VARCHAR(255) NULL,
                description TEXT NULL,
                anchor_label VARCHAR(190) NULL,
                anchor_url VARCHAR(255) NULL,
                default_locale VARCHAR(10) NOT NULL DEFAULT 'es',
                design_variant VARCHAR(30) NOT NULL DEFAULT 'minimal',
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_slug_per_restaurant (restaurant_id, slug),
                KEY restaurant_id (restaurant_id)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_languages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                restaurant_id BIGINT UNSIGNED NULL,
                menu_id BIGINT UNSIGNED NULL,
                locale VARCHAR(10) NOT NULL,
                label VARCHAR(80) NOT NULL,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY menu_id (menu_id),
                KEY restaurant_id (restaurant_id)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_sections (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                menu_id BIGINT UNSIGNED NOT NULL,
                external_id VARCHAR(120) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY menu_id (menu_id)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_items (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                menu_id BIGINT UNSIGNED NOT NULL,
                section_id BIGINT UNSIGNED NOT NULL,
                sku VARCHAR(120) NULL,
                external_id VARCHAR(120) NULL,
                item_type VARCHAR(20) NOT NULL DEFAULT 'food',
                base_price DECIMAL(10,2) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY menu_id (menu_id),
                KEY section_id (section_id),
                KEY external_id (external_id),
                KEY sku (sku)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_item_prices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                item_id BIGINT UNSIGNED NOT NULL,
                option_slug VARCHAR(120) NOT NULL,
                option_label VARCHAR(120) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY item_id (item_id)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_translations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                entity_type VARCHAR(20) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                locale VARCHAR(10) NOT NULL,
                name VARCHAR(255) NOT NULL,
                subtitle VARCHAR(255) NULL,
                description TEXT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_translation (entity_type, entity_id, locale),
                KEY entity_type_entity_id (entity_type, entity_id)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_allergens (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                restaurant_id BIGINT UNSIGNED NULL,
                slug VARCHAR(120) NOT NULL,
                name VARCHAR(120) NOT NULL,
                icon_url VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY unique_slug_scope (restaurant_id, slug)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_item_allergens (
                item_id BIGINT UNSIGNED NOT NULL,
                allergen_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (item_id, allergen_id),
                KEY allergen_id (allergen_id)
            ) {$charset_collate};",
            "CREATE TABLE {$wpdb->prefix}dmmr_import_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                restaurant_id BIGINT UNSIGNED NOT NULL,
                menu_id BIGINT UNSIGNED NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mode VARCHAR(30) NOT NULL,
                total_rows INT NOT NULL DEFAULT 0,
                success_rows INT NOT NULL DEFAULT 0,
                failed_rows INT NOT NULL DEFAULT 0,
                errors LONGTEXT NULL,
                created_by BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY restaurant_id (restaurant_id),
                KEY menu_id (menu_id)
            ) {$charset_collate};",
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        add_option('dmmr_db_version', DMMR_VERSION);

        if (class_exists('DMMR_Router')) {
            (new DMMR_Router())->add_rewrite_rules();
        }
        flush_rewrite_rules();
    }
}
