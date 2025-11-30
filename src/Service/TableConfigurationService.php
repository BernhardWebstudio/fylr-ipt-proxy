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

    /**
     * Get table column configuration for exported data from the database
     *
     * @return array<string, array<string, mixed>>
     */
    public function getExportTableColumns(): array
    {
        return [
            'catalog_number' => [
                'label' => 'Catalog Number',
                'property' => 'occurrence.catalogNumber',
                'sortable' => true,
                'searchable' => true,
                'class' => 'font-weight-bold',
            ],
            'scientific_name' => [
                'label' => 'Scientific Name',
                'property' => 'occurrence.taxon.scientificName',
                'sortable' => true,
                'searchable' => true,
            ],
            'genus' => [
                'label' => 'Genus',
                'property' => 'occurrence.taxon.genus',
                'sortable' => true,
                'searchable' => true,
            ],
            'specific_epithet' => [
                'label' => 'Species',
                'property' => 'occurrence.taxon.specificEpithet',
                'sortable' => true,
                'searchable' => true,
            ],
            'recorded_by' => [
                'label' => 'Recorded By',
                'property' => 'occurrence.recordedBy',
                'sortable' => true,
                'searchable' => true,
            ],
            'locality' => [
                'label' => 'Locality',
                'property' => 'occurrence.location.locality',
                'sortable' => false,
                'searchable' => true,
            ],
            'event_date' => [
                'label' => 'Event Date',
                'property' => 'occurrence.event.eventDate',
                'sortable' => true,
                'searchable' => false,
            ],
            'global_object_id' => [
                'label' => 'Global Object ID',
                'property' => 'globalObjectID',
                'sortable' => true,
                'searchable' => true,
                'class' => 'font-monospace text-muted',
            ],
            'last_updated' => [
                'label' => 'Last Updated',
                'property' => 'lastUpdatedAt',
                'sortable' => true,
                'searchable' => false,
                'class' => 'text-muted',
            ],
        ];
    }

    /**
     * Get visible export columns
     */
    public function getVisibleExportColumns(?array $visibleColumnKeys = null): array
    {
        $allColumns = $this->getExportTableColumns();

        if ($visibleColumnKeys === null) {
            // Default visible columns
            $visibleColumnKeys = ['catalog_number', 'scientific_name', 'genus', 'specific_epithet', 'recorded_by', 'locality', 'global_object_id'];
        }

        return array_intersect_key($allColumns, array_flip($visibleColumnKeys));
    }

    /**
     * Extract value from OccurrenceImport entity using property path notation
     */
    public function extractEntityValue($entity, string $propertyPath): ?string
    {
        $parts = explode('.', $propertyPath);
        $current = $entity;

        foreach ($parts as $part) {
            if ($current === null) {
                return null;
            }

            // Convert property name to getter method
            $getter = 'get' . ucfirst($part);

            if (method_exists($current, $getter)) {
                $current = $current->$getter();
            } else {
                return null;
            }
        }

        // Handle DateTime objects
        if ($current instanceof \DateTimeInterface) {
            return $current->format('Y-m-d H:i:s');
        }

        return $current ? (string)$current : null;
    }
}
