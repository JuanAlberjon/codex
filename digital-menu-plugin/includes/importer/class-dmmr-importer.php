<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMMR_Csv_Importer
{
    public function register(): void
    {
        add_filter('dmmr_csv_required_columns', [$this, 'required_columns']);
    }

    public function required_columns(array $columns): array
    {
        return [
            'section_slug',
            'section_order',
            'item_type',
            'item_sku',
            'item_external_id',
            'item_order',
            'price_base',
            'price_options',
            'allergen_slugs',
        ];
    }

    public function build_preview(string $csvPath): array
    {
        $rows = [];
        if (!file_exists($csvPath)) {
            return ['errors' => ['CSV no encontrado']];
        }

        if (($handle = fopen($csvPath, 'rb')) === false) {
            return ['errors' => ['No se pudo abrir el archivo CSV']];
        }

        $headers = fgetcsv($handle) ?: [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false && $line <= 25) {
            $line++;
            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_preview' => count($rows),
        ];
    }
}
