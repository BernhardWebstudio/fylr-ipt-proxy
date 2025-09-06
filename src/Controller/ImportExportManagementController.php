<?php

namespace App\Controller;

use App\Entity\DarwinCore\Occurrence;
use App\Entity\User;
use App\Form\ExportSelectionType;
use App\Service\DataExportService;
use App\Service\EasydbApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ImportExportManagementController extends AbstractController
{
    #[Route('/import', name: 'app_import_management')]
    #[IsGranted('ROLE_USER')]
    public function index(
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        $typesToSelectFrom = array_merge(...array_map(fn($mapping) => $mapping->supportsPools(), $mappings));
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
        Request $request,
        string $type,
        EasydbApiService $easydbApiService,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        $mapping = null;
        foreach ($mappings as $map) {
            if (in_array($type, $map->supportsPools())) {
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

        // Filter object types by user access
        /** @var User $user */
        $user = $this->getUser();
        $userAccessibleTypes = $user->getAccessibleObjectTypes();
        if (!empty($userAccessibleTypes)) {
            $objectTypes = array_intersect($objectTypes, $userAccessibleTypes);
        }

        $objectTypeChoices = array_combine($objectTypes, $objectTypes);

        // Get available tags from EasyDB
        $tagChoices = [];
        try {
            if ($easydbApiService->hasValidSession()) {
                $tagsData = $easydbApiService->fetchTags();
                foreach ($tagsData as $tagGroup) {
                    if (isset($tagGroup['_tags'])) {
                        foreach ($tagGroup['_tags'] as $tagItem) {
                            $tag = $tagItem['tag'];
                            $displayName = $tag['displayname']['en-US'] ??
                                $tag['displayname']['de-DE'] ??
                                'Tag #' . $tag['_id'];
                            $tagChoices[$displayName] = $tag['_id'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Could not load tags: ' . $e->getMessage());
        }

        // Create form
        $form = $this->createForm(ExportSelectionType::class, null, [
            'tag_choices' => $tagChoices,
            'object_type_choices' => $objectTypeChoices,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Search for entities based on form criteria
            $entities = $easydbApiService->searchEntities(
                $data['globalObjectId'],
                $data['tagId'],
                $data['objectType']
            );

            if (empty($entities)) {
                $this->addFlash('warning', 'No entities found matching your criteria.');
                return $this->redirectToRoute('app_export_management');
            }
        }

        // then, render the import form
        return $this->render('import_export_management/import_type.html.twig', [
            'type' => $type,
            'mapping' => $mapping,
            'easydbData' => $easydbApiService->loadEntitiesForPool($type)
        ]);
    }

    #[Route('/export', name: 'app_export_management')]
    #[IsGranted('ROLE_USER')]
    public function exportManagement(
        Request $request,
        EasydbApiService $easydbApiService,
        DataExportService $dataExportService,
        EntityManagerInterface $entityManager,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        // Get available object types from mappings
        $objectTypes = array_merge(...array_map(fn($mapping) => $mapping->supportsPools(), $mappings));
        $objectTypes = array_unique($objectTypes);

        // Filter object types by user access
        /** @var User $user */
        $user = $this->getUser();
        $userAccessibleTypes = $user->getAccessibleObjectTypes();
        if (!empty($userAccessibleTypes)) {
            $objectTypes = array_intersect($objectTypes, $userAccessibleTypes);
        }

        $objectTypeChoices = array_combine($objectTypes, $objectTypes);

        // Get available tags from EasyDB
        $tagChoices = [];
        try {
            if ($easydbApiService->hasValidSession()) {
                $tagsData = $easydbApiService->fetchTags();
                foreach ($tagsData as $tagGroup) {
                    if (isset($tagGroup['_tags'])) {
                        foreach ($tagGroup['_tags'] as $tagItem) {
                            $tag = $tagItem['tag'];
                            $displayName = $tag['displayname']['en-US'] ??
                                $tag['displayname']['de-DE'] ??
                                'Tag #' . $tag['_id'];
                            $tagChoices[$displayName] = $tag['_id'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Could not load tags: ' . $e->getMessage());
        }

        // Create form
        $form = $this->createForm(ExportSelectionType::class, null, [
            'tag_choices' => $tagChoices,
            'object_type_choices' => $objectTypeChoices,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                // Search for entities based on form criteria
                $entities = $entityManager->getRepository(Occurrence::class)->searchEntities(
                    $data['globalObjectId'],
                    $data['tagId'],
                    $data['objectType']
                );

                if (empty($entities)) {
                    $this->addFlash('warning', 'No entities found matching your criteria.');
                    return $this->redirectToRoute('app_export_management');
                }

                // For now, convert EasyDB entities to the expected format for DataExportService
                // In a real implementation, you might want to create a mapping service
                $format = $data['exportFormat'];

                switch ($format) {
                    case 'csv':
                        $response = new Response();
                        $response->headers->set('Content-Type', 'text/csv');
                        $response->setContent($dataExportService->convertToCsv($entities));
                        $response->headers->set('Content-Disposition', 'attachment; filename="export_' . date('Y-m-d_H-i-s') . '.csv"');
                        return $response;
                    case 'json':
                        return new Response(json_encode($entities), 200, [
                            'Content-Type' => 'application/json',
                            'Content-Disposition' => 'attachment; filename="export_' . date('Y-m-d_H-i-s') . '.json"'
                        ]);
                    case 'xml':
                        $response = new Response();
                        $response->headers->set('Content-Type', 'application/xml');
                        $response->setContent($dataExportService->convertToXml($entities));
                        $response->headers->set('Content-Disposition', 'attachment; filename="export_' . date('Y-m-d_H-i-s') . '.xml"');
                        return $response;
                    default:
                        $this->addFlash('error', 'Unsupported export format: ' . $format);
                        return $this->redirectToRoute('app_export_management');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Export failed: ' . $e->getMessage());
            }
        }

        // Render the export management page with form
        return $this->render('import_export_management/export.html.twig', [
            'form' => $form->createView(),
            'availableObjectTypes' => $objectTypeChoices,
            'availableTags' => $tagChoices,
        ]);
    }

    #[Route('/export/{type}/{format}', name: 'app_export_management_type')]
    #[IsGranted('ROLE_USER')]
    public function exportType(
        string $type,
        string $format,
        EntityManagerInterface $entityManager,
        DataExportService $dataExportService
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
                $response->setContent($dataExportService->convertToCsv($data));
                $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $type . '.csv"');
                return $response;
            case 'json':
                return new Response($dataExportService->convertToJson($data), 200, [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="export_' . $type . '.json"'
                ]);
            case 'xml':
                $response = new Response();
                $response->headers->set('Content-Type', 'application/xml');
                $response->setContent($dataExportService->convertToXml($data));
                $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $type . '.xml"');
                return $response;
            default:
                $this->addFlash('error', 'Unsupported export format: ' . $format);
                return $this->redirectToRoute('app_import_management');
        }
    }
}
