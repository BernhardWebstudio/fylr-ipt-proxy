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
        $this->streamCsv([$data], $output);
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '';
    }

    /**
     * Stream data to CSV format in batches to minimize memory consumption.
     *
     * @param iterable<array<object>> $batches An iterable yielding batches of entities
     * @param resource $outputStream Stream to write CSV data to (e.g. php://output or php://temp)
     * @param callable|null $onBatchProcessed Optional callback invoked after each batch (e.g. to clear EntityManager)
     */
    public function streamCsv(iterable $batches, $outputStream, ?callable $onBatchProcessed = null): void
    {
        $tempFile = fopen('php://temp', 'r+');
        $validKeyMap = [];
        $hasData = false;

        foreach ($batches as $batch) {
            foreach ($batch as $occurrence) {
                $hasData = true;
                $flattened = $this->getFlattenedProperties($occurrence);
                foreach ($flattened as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $validKeyMap[$key] = true;
                    }
                }
                fwrite($tempFile, json_encode($flattened) . "\n");
            }

            if ($onBatchProcessed !== null) {
                $onBatchProcessed();
            }
        }

        if (!$hasData) {
            fclose($tempFile);
            return;
        }

        $validKeys = array_keys($validKeyMap);

        if (!empty($validKeys)) {
            fputcsv($outputStream, $validKeys, escape: '\\');

            rewind($tempFile);
            while (($line = fgets($tempFile)) !== false) {
                $flattened = json_decode($line, true);
                if (is_array($flattened)) {
                    $row = array_map(fn($key) => $flattened[$key] ?? '', $validKeys);
                    fputcsv($outputStream, $row, escape: '\\');
                }
            }
        }

        fclose($tempFile);
    }

    /**
     * Convert data to XML format.
     *
     * @param array<object> $data
     * @return string
     */
    public function convertToXml(array $data): string
    {
        if (empty($data)) {
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<occurrences/>\n";
        }

        $output = fopen('php://temp', 'r+');
        $this->streamXml([$data], $output);
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '';
    }

    /**
     * Stream data to XML format in batches to minimize memory consumption.
     *
     * @param iterable<array<object>> $batches An iterable yielding batches of entities
     * @param resource $outputStream Stream to write XML data to
     * @param callable|null $onBatchProcessed Optional callback invoked after each batch
     */
    public function streamXml(iterable $batches, $outputStream, ?callable $onBatchProcessed = null): void
    {
        fwrite($outputStream, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<occurrences>\n");

        foreach ($batches as $batch) {
            foreach ($batch as $occurrence) {
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->formatOutput = true;

                $occurrenceElement = $dom->createElement('occurrence');
                $dom->appendChild($occurrenceElement);

                $this->addObjectToXmlElement($dom, $occurrenceElement, $occurrence);

                $xml = $dom->saveXML($occurrenceElement);
                if ($xml) {
                    $indented = implode("\n", array_map(fn($line) => '  ' . $line, explode("\n", trim($xml))));
                    fwrite($outputStream, $indented . "\n");
                }
            }

            if ($onBatchProcessed !== null) {
                $onBatchProcessed();
            }
        }

        fwrite($outputStream, "</occurrences>\n");
    }

    /**
     * Convert data to JSON format.
     *
     * @param array<object> $data
     * @return string
     */
    public function convertToJson(array $data): string
    {
        if (empty($data)) {
            return "[]";
        }

        $output = fopen('php://temp', 'r+');
        $this->streamJson([$data], $output);
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '[]';
    }

    /**
     * Stream data to JSON format in batches to minimize memory consumption.
     *
     * @param iterable<array<object>> $batches An iterable yielding batches of entities
     * @param resource $outputStream Stream to write JSON data to
     * @param callable|null $onBatchProcessed Optional callback invoked after each batch
     */
    public function streamJson(iterable $batches, $outputStream, ?callable $onBatchProcessed = null): void
    {
        fwrite($outputStream, "[\n");
        $isFirst = true;

        foreach ($batches as $batch) {
            foreach ($batch as $occurrence) {
                $filtered = $this->filterEmptyProperties($occurrence);
                $json = json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                if (!$isFirst) {
                    fwrite($outputStream, ",\n");
                } else {
                    $isFirst = false;
                }

                $indented = implode("\n", array_map(fn($line) => '  ' . $line, explode("\n", $json)));
                fwrite($outputStream, $indented);
            }

            if ($onBatchProcessed !== null) {
                $onBatchProcessed();
            }
        }

        fwrite($outputStream, "\n]\n");
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
