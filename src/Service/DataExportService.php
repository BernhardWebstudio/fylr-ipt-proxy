<?php

namespace App\Service;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Service for exporting data in various formats (CSV, XML, JSON)
 */
class DataExportService
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Convert data to CSV format.
     *
     * @param array<object> $data
     * @return string
     */
    public function convertToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Collect all unique flattened properties across all data
        $allKeys = [];
        foreach ($data as $occurrence) {
            $flattenedProperties = $this->getFlattenedProperties($occurrence);
            $allKeys = array_merge($allKeys, array_keys($flattenedProperties));
        }
        $allKeys = array_unique($allKeys);

        // Filter keys to only include those with at least one non-empty value
        $validKeys = [];
        foreach ($allKeys as $key) {
            $hasValue = false;
            foreach ($data as $occurrence) {
                $flattenedData = $this->getFlattenedProperties($occurrence);
                $value = $flattenedData[$key] ?? '';
                if ($value !== null && $value !== '') {
                    $hasValue = true;
                    break;
                }
            }
            if ($hasValue) {
                $validKeys[] = $key;
            }
        }

        // Write header row
        fputcsv($output, $validKeys);

        // Write data rows
        foreach ($data as $occurrence) {
            $flattenedData = $this->getFlattenedProperties($occurrence);
            $row = array_map(fn($key) => $flattenedData[$key] ?? '', $validKeys);
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Convert data to XML format.
     *
     * @param array<object> $data
     * @return string
     */
    public function convertToXml(array $data): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('occurrences');
        $dom->appendChild($root);

        foreach ($data as $occurrence) {
            $occurrenceElement = $dom->createElement('occurrence');
            $root->appendChild($occurrenceElement);

            $this->addObjectToXmlElement($dom, $occurrenceElement, $occurrence);
        }

        return $dom->saveXML();
    }

    /**
     * Convert data to JSON format.
     *
     * @param array<object> $data
     * @return string
     */
    public function convertToJson(array $data): string
    {
        $filteredData = array_map([$this, 'filterEmptyProperties'], $data);
        return json_encode($filteredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively add object properties to XML element
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $parentElement
     * @param object $object
     */
    private function addObjectToXmlElement(\DOMDocument $dom, \DOMElement $parentElement, object $object): void
    {
        $reflection = new \ReflectionClass($object);
        $parentReflection = $reflection->getParentClass();
        $properties = $parentReflection ? array_map(fn($prop) => $prop->getName(), $parentReflection->getProperties()) : array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $this->propertyAccessor->getValue($object, $property);
                $elementName = $this->sanitizeXmlElementName($property);

                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    continue; // Skip null, empty string, or empty array values
                }

                if (is_object($value)) {
                    $element = $dom->createElement($elementName);
                    $parentElement->appendChild($element);
                    $this->addObjectToXmlElement($dom, $element, $value);
                } elseif (is_array($value)) {
                    $element = $dom->createElement($elementName);
                    $parentElement->appendChild($element);

                    foreach ($value as $index => $item) {
                        $itemElement = $dom->createElement('item');
                        $element->appendChild($itemElement);

                        if (is_object($item)) {
                            $this->addObjectToXmlElement($dom, $itemElement, $item);
                        } else {
                            $itemElement->textContent = (string) $item;
                        }
                    }
                } else {
                    $element = $dom->createElement($elementName);
                    $element->textContent = (string) $value;
                    $parentElement->appendChild($element);
                }
            } catch (\Exception $e) {
                // Skip properties that are not accessible
                continue;
            }
        }
    }

    /**
     * Sanitize element name for XML compatibility
     *
     * @param string $name
     * @return string
     */
    private function sanitizeXmlElementName(string $name): string
    {
        // Replace dots and invalid characters with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);

        // Ensure it starts with a letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $sanitized)) {
            $sanitized = '_' . $sanitized;
        }

        return $sanitized;
    }

    /**
     * Recursively flatten object properties using PropertyAccessor
     *
     * @param object $object
     * @param string $prefix
     * @param array $visited
     * @return array<string, mixed>
     */
    private function getFlattenedProperties(object $object, string $prefix = '', array &$visited = []): array
    {
        $flattened = [];
        $objectId = spl_object_id($object);

        if (in_array($objectId, $visited, true)) {
            // Circular reference detected, skip to prevent infinite recursion
            return $flattened;
        }

        $visited[] = $objectId;

        $reflection = new \ReflectionClass($object);
        $parentReflection = $reflection->getParentClass();
        $properties = $parentReflection ? array_map(fn($prop) => $prop->getName(), $parentReflection->getProperties()) : array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $this->propertyAccessor->getValue($object, $property);
                $key = $prefix ? $prefix . '.' . $property : $property;

                if (is_object($value)) {
                    // Get the classname without namespace as prefix
                    $className = (new \ReflectionClass($value))->getShortName();
                    $nestedPrefix = $prefix ? $prefix . '.' . $className : $className;
                    $nestedProperties = $this->getFlattenedProperties($value, $nestedPrefix, $visited);
                    $flattened = array_merge($flattened, $nestedProperties);
                } elseif (is_array($value)) {
                    $flattened[$key] = json_encode($value);
                } else {
                    $flattened[$key] = $value;
                }
            } catch (\Exception $e) {
                $key = $prefix ? $prefix . '.' . $property : $property;
                $flattened[$key] = ''; // Empty value if property is not accessible
            }
        }

        // Remove from visited after processing to allow other branches
        array_pop($visited);

        return $flattened;
    }

    /**
     * Recursively filter out empty properties from an object
     *
     * @param object $object
     * @return array
     */
    private function filterEmptyProperties(object $object): array
    {
        $filtered = [];
        $reflection = new \ReflectionClass($object);
        $parentReflection = $reflection->getParentClass();
        $properties = $parentReflection ? array_map(fn($prop) => $prop->getName(), $parentReflection->getProperties()) : array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $this->propertyAccessor->getValue($object, $property);
                if ($value !== null && $value !== '' && (!is_array($value) || !empty($value))) {
                    if (is_object($value)) {
                        $filtered[$property] = $this->filterEmptyProperties($value);
                    } elseif (is_array($value)) {
                        $filtered[$property] = array_map(fn($item) => is_object($item) ? $this->filterEmptyProperties($item) : $item, $value);
                    } else {
                        $filtered[$property] = $value;
                    }
                }
            } catch (\Exception $e) {
                // Skip properties that are not accessible
            }
        }

        return $filtered;
    }
}
