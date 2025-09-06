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

        // Get all flattened properties from the first occurrence to create header
        $flattenedProperties = $this->getFlattenedProperties($data[0]);
        $headers = array_keys($flattenedProperties);

        // Write header row
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $occurrence) {
            $flattenedData = $this->getFlattenedProperties($occurrence);
            $row = [];
            foreach ($headers as $header) {
                $row[] = $flattenedData[$header] ?? '';
            }
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
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
        $properties = array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $this->propertyAccessor->getValue($object, $property);
                $elementName = $this->sanitizeXmlElementName($property);

                if ($value === null) {
                    continue; // Skip null values
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
     * @return array<string, mixed>
     */
    private function getFlattenedProperties(object $object, string $prefix = ''): array
    {
        $flattened = [];
        $reflection = new \ReflectionClass($object);
        $properties = array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $this->propertyAccessor->getValue($object, $property);
                $key = $prefix ? $prefix . '.' . $property : $property;

                if (is_object($value)) {
                    // Get the classname without namespace as prefix
                    $className = (new \ReflectionClass($value))->getShortName();
                    $nestedPrefix = $prefix ? $prefix . '.' . $className : $className;
                    $nestedProperties = $this->getFlattenedProperties($value, $nestedPrefix);
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

        return $flattened;
    }
}
