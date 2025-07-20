<?php

namespace App\Controller;

use App\Entity\DarwinCore\Occurrence;
use App\Service\EasydbApiService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ImportExportManagementController extends AbstractController
{
    #[Route('/import', name: 'app_import_management')]
    #[IsGranted('ROLE_USER')]
    public function index(
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        $typesToSelectFrom = array_merge(...array_map(fn($mapping) => $mapping->supportsTypes(), $mappings));
        // remove duplicates
        $typesToSelectFrom = array_unique($typesToSelectFrom);

        if (count($typesToSelectFrom) === 0) {
            $this->addFlash('error', 'No mappings available for import.');
            return $this->redirectToRoute('app_home');
        }

        if (count($typesToSelectFrom) === 1) {
            // If only one type is available, redirect to the import page for that type
            $type = reset($typesToSelectFrom);
            return $this->redirectToRoute('app_import_management_type', ['type' => $type]);
        }


        return $this->render('import_export_management/import.html.twig', [
            'types' => $typesToSelectFrom
        ]);
    }

    #[Route('/import/{type}', name: 'app_import_management_type')]
    #[IsGranted('ROLE_USER')]
    public function importType(
        string $type,
        EasydbApiService $easydbApiService,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        $mapping = null;
        foreach ($mappings as $map) {
            if (in_array($type, $map->supportsTypes())) {
                $mapping = $map;
                break;
            }
        }

        if (!$mapping) {
            $this->addFlash('error', 'No mapping found for type: ' . $type);
            return $this->redirectToRoute('app_import_management');
        }

        // load EasyDB data for the given type,
        // create a form to select the EasyDB data to import

        // then, render the import form
        return $this->render('import_export_management/import_type.html.twig', [
            'type' => $type,
            'mapping' => $mapping,
            'easydbData' => $easydbApiService->getDataForType($type)
        ]);
    }

    #[Route('/export', name: 'app_export_management')]
    #[IsGranted('ROLE_USER')]
    public function exportManagement(): Response
    {
        // Render the export management page
        return $this->render('import_export_management/export.html.twig');
    }

    #[Route('/export/{type}/{format}', name: 'app_export_management_type')]
    #[IsGranted('ROLE_USER')]
    public function exportType(
        string $type,
        string $format,
        EntityManagerInterface $entityManager
    ): Response {
        // export the database content of the given type in the specified format
        if (!in_array($format, ['csv', 'json', 'xml'])) {
            $this->addFlash('error', 'Unsupported export format: ' . $format);
            return $this->redirectToRoute('app_import_management');
        }

        $repository = $entityManager->getRepository(Occurrence::class);
        $data = $repository->findBy(['type' => $type]);
        if (!$data) {
            $this->addFlash('error', 'No data found for type: ' . $type);
            return $this->redirectToRoute('app_import_management');
        }

        // Convert data to the requested format
        switch ($format) {
            case 'csv':
                $response = new Response();
                $response->headers->set('Content-Type', 'text/csv');
                $response->setContent($this->convertToCsv($data));
                $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $type . '.csv"');
                return $response;
            case 'json':
                return new Response(json_encode($data), 200, [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="export_' . $type . '.json"'
                ]);
            case 'xml':
                $response = new Response();
                $response->headers->set('Content-Type', 'application/xml');
                $response->setContent($this->convertToXml($data));
                $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $type . '.xml"');
                return $response;
            default:
                $this->addFlash('error', 'Unsupported export format: ' . $format);
                return $this->redirectToRoute('app_import_management');
        }

        return new Response('Export completed successfully.', 200, [
            'Content-Type' => 'text/plain'
        ]);
    }

    /**
     * Convert the occurrences to CSV.
     *
     * @param list<Occurrence> $data
     * @return string
     */
    private function convertToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $output = fopen('php://temp', 'r+');

        // Get all flattened properties from the first occurrence to create header
        $flattenedProperties = $this->getFlattenedProperties($data[0], $propertyAccessor);
        $headers = array_keys($flattenedProperties);

        // Write header row
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $occurrence) {
            $flattenedData = $this->getFlattenedProperties($occurrence, $propertyAccessor);
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
     * Convert the occurrences to XML.
     *
     * @param list<Occurrence> $data
     * @return string
     */
    private function convertToXml(array $data): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('occurrences');
        $dom->appendChild($root);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($data as $occurrence) {
            $occurrenceElement = $dom->createElement('occurrence');
            $root->appendChild($occurrenceElement);

            $this->addObjectToXmlElement($dom, $occurrenceElement, $occurrence, $propertyAccessor);
        }

        return $dom->saveXML();
    }

    /**
     * Recursively add object properties to XML element
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $parentElement
     * @param object $object
     * @param \Symfony\Component\PropertyAccess\PropertyAccessorInterface $propertyAccessor
     */
    private function addObjectToXmlElement(\DOMDocument $dom, \DOMElement $parentElement, object $object, $propertyAccessor): void
    {
        $reflection = new \ReflectionClass($object);
        $properties = array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $propertyAccessor->getValue($object, $property);
                $elementName = $this->sanitizeXmlElementName($property);

                if ($value === null) {
                    continue; // Skip null values
                }

                if (is_object($value)) {
                    $element = $dom->createElement($elementName);
                    $parentElement->appendChild($element);
                    $this->addObjectToXmlElement($dom, $element, $value, $propertyAccessor);
                } elseif (is_array($value)) {
                    $element = $dom->createElement($elementName);
                    $parentElement->appendChild($element);

                    foreach ($value as $index => $item) {
                        $itemElement = $dom->createElement('item');
                        $element->appendChild($itemElement);

                        if (is_object($item)) {
                            $this->addObjectToXmlElement($dom, $itemElement, $item, $propertyAccessor);
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
     * @param \Symfony\Component\PropertyAccess\PropertyAccessorInterface $propertyAccessor
     * @param string $prefix
     * @return array<string, mixed>
     */
    private function getFlattenedProperties(object $object, $propertyAccessor, string $prefix = ''): array
    {
        $flattened = [];
        $reflection = new \ReflectionClass($object);
        $properties = array_map(fn($prop) => $prop->getName(), $reflection->getProperties());

        foreach ($properties as $property) {
            try {
                $value = $propertyAccessor->getValue($object, $property);
                $key = $prefix ? $prefix . '.' . $property : $property;

                if (is_object($value)) {
                    // Get the classname without namespace as prefix
                    $className = (new \ReflectionClass($value))->getShortName();
                    $nestedPrefix = $prefix ? $prefix . '.' . $className : $className;
                    $nestedProperties = $this->getFlattenedProperties($value, $propertyAccessor, $nestedPrefix);
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
