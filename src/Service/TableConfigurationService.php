<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class TableConfigurationService
{

    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Get table column configuration for EasyDB entities display
     *
     * @return array<string, array<string, mixed>>
     */
    public function getImportTableColumns(): array
    {
        return [
            'zugangsnummer' => [
                'label' => 'Accession Number',
                'path' => 'fungarium.zugangsnummer',
                'sortable' => true,
                'searchable' => true,
                'class' => 'font-weight-bold',
            ],
            'taxon_name' => [
                'label' => 'Taxon Name',
                'path' => '_reverse_nested:bestimmung:fungarium.0.taxonnametrans',
                'fallback_path' => '_reverse_nested:bestimmung:fungarium.0.taxonname._standard.1.text.en-US',
                'sortable' => true,
                'searchable' => true,
            ],
            'genus' => [
                'label' => 'Genus',
                'path' => '_reverse_nested:bestimmung:fungarium.0.genustrans',
                'fallback_path' => '_reverse_nested:bestimmung:fungarium.0.genus._standard.1.text.en-US',
                'sortable' => true,
                'searchable' => true,
            ],
            'species' => [
                'label' => 'Species',
                'path' => '_reverse_nested:bestimmung:fungarium.0.arttrans',
                'sortable' => true,
                'searchable' => true,
            ],
            'author' => [
                'label' => 'Author',
                'path' => '_reverse_nested:bestimmung:fungarium.0.autor._standard.1.text.en-US',
                'fallback_path' => '_reverse_nested:bestimmung:fungarium.0.autor._standard.1.text.de-DE',
                'sortable' => false,
                'searchable' => true,
            ],
            'collector' => [
                'label' => 'Collector',
                'path' => '_reverse_nested:bestimmung:fungarium.0.bestimmertrans',
                'sortable' => true,
                'searchable' => true,
            ],
            'collection_location' => [
                'label' => 'Collection Location',
                'path' => '_reverse_nested:aufsammlung:fungarium.0.sammelort._standard.1.text.en-US',
                'fallback_path' => '_reverse_nested:aufsammlung:fungarium.0.sammelort._standard.1.text.de-DE',
                'sortable' => false,
                'searchable' => true,
            ],
            'habitat' => [
                'label' => 'Habitat',
                'path' => '_reverse_nested:aufsammlung:fungarium.0.habitattrans',
                'sortable' => false,
                'searchable' => true,
            ],
            'global_object_id' => [
                'label' => 'Global Object ID',
                'path' => '_global_object_id',
                'sortable' => true,
                'searchable' => true,
                'class' => 'font-monospace text-muted',
            ],
        ];
    }

    /**
     * Extract value from nested object using dot notation path
     */
    public function extractValue($data, string $path, ?string $fallbackPath = null): ?string
    {
        if ($path === "_global_object_id") {
            // log the data structure for debugging
            $this->logger->debug('Extracting global_object_id from data', ['data' => $data]);
        }

        $value = $this->getNestedValue($data, $path);

        if (empty($value) && $fallbackPath) {
            $value = $this->getNestedValue($data, $fallbackPath);
        }

        return $value ? (string)$value : null;
    }

    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue($data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_array($current) && is_numeric($key) && isset($current[(int)$key])) {
                $current = $current[(int)$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Get paginated and filtered column configuration
     */
    public function getVisibleColumns(?array $visibleColumnKeys = null): array
    {
        $allColumns = $this->getImportTableColumns();

        if ($visibleColumnKeys === null) {
            // Default visible columns
            $visibleColumnKeys = ['zugangsnummer', 'taxon_name', 'genus', 'species', 'collector', 'global_object_id'];
        }

        return array_intersect_key($allColumns, array_flip($visibleColumnKeys));
    }
}
