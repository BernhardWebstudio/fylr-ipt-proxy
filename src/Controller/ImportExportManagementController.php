<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\OccurrenceImport;
use App\Form\ExportSelectionType;
use App\Message\ImportDataMessage;
// use App\Message\ExportDataMessage;
use App\Service\EasydbApiService;
use App\Service\DataExportService;
use App\Service\JobStatusService;
use App\Service\OccurrenceImportProcessingService;
use App\Service\TableConfigurationService;
use App\Entity\DarwinCore\Occurrence;
use App\Form\ImportSelectionType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\MessageBusInterface;

/** @package App\Controller */
final class ImportExportManagementController extends AbstractController
{
    #[Route('/import', name: 'app_import_management')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        EasydbApiService $easydbApiService,
        EntityManagerInterface $entityManager,
        TableConfigurationService $tableConfigService,
        LoggerInterface $logger,
        JobStatusService $jobStatusService,
        MessageBusInterface $messageBus,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        try {
            $objectTypeChoices = $this->loadAccessibleObjectTypes($mappings);
        } catch (\RuntimeException $th) {
            // redirect to object type sync if user has no object types
            return $this->redirectToRoute('app_sync_roles');
        }

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

        // Load data from query parameters to persist form values after redirect
        $queryData = [
            'globalObjectId' => $request->query->get('globalObjectId'),
            'tagId' => $request->query->getInt('tagId') ?: null,
            'objectType' => $request->query->get('objectType'),
        ];

        // create a form to select the EasyDB data to import, pre-populated with query params
        $form = $this->createForm(ImportSelectionType::class, $queryData, [
            'tag_choices' => $tagChoices,
            'object_type_choices' => $objectTypeChoices,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData() ?? [];

            // Check which button was clicked by looking at the form data
            $isImportClicked = $request->request->has('import_selection') &&
                array_key_exists('import', $request->request->all('import_selection'));

            // Create and dispatch import job
            if ($isImportClicked) {
                /** @var User $user */
                $user = $this->getUser();

                $jobId = $jobStatusService->generateJobId();
                $criteria = [
                    'globalObjectId' => $data['globalObjectId'] ?? null,
                    'tagId' => $data['tagId'] ?? null,
                    'objectType' => $data['objectType'] ?? null,
                ];

                // Create job status record
                $jobStatusService->createJob($jobId, 'import', $user, $criteria);

                // Dispatch import message
                $importMessage = new ImportDataMessage(
                    $jobId,
                    $data['objectType'] ?? null,
                    $criteria,
                    $user->getId()
                );
                $messageBus->dispatch($importMessage);

                $this->addFlash('success', "Import job started. Job ID: {$jobId}");
                return $this->redirectToRoute('app_job_status', ['jobId' => $jobId]);
            }

            // If preview button was clicked (or any other submission),
            // redirect to same page with query parameters
            $queryParams = array_filter([
                'globalObjectId' => $data['globalObjectId'] ?? null,
                'tagId' => $data['tagId'] ?? null,
                'objectType' => $data['objectType'] ?? null,
                'page' => 0, // Reset to first page on new search
                'submitted' => 1, // Mark as submitted to distinguish from initial load
            ]);
            return $this->redirectToRoute('app_import_management', $queryParams);
        }

        $nEntriesPerPage = 100;
        $hasSearchCriteria = ($queryData['tagId'] ?? false) || ($queryData['objectType'] ?? false) || ($queryData['globalObjectId'] ?? false);
        $isSubmitted = $request->query->getBoolean('submitted', false);

        $entities = [];
        if (($queryData['globalObjectId'] ?? false)) {
            $entities = $easydbApiService->loadEntityByGlobalObjectID($queryData['globalObjectId']);
        } else if ($hasSearchCriteria) {
            $entities = $easydbApiService->searchByTag(
                $queryData['tagId'] ?? null,
                $queryData['objectType'] ?? null,
                $request->query->getInt('page', 0) * $nEntriesPerPage,
                $nEntriesPerPage
            );
        }

        // Add flash message if search was performed but no results found
        if ($isSubmitted && $hasSearchCriteria && (empty($entities) || empty($entities['objects']))) {
            $this->addFlash('warning', 'No specimens found matching your search criteria. Please adjust your filters and try again.');
        }

        // then, render the import form
        return $this->render('import_export_management/import.html.twig', [
            'form' => $form->createView(),
            'availableObjectTypes' => $objectTypeChoices,
            'availableTags' => $tagChoices,
            'easydbData' => $entities,
            'currentPage' => $request->query->getInt('page', 0),
            'nEntriesPerPage' => $nEntriesPerPage,
            'hasMore' => array_key_exists('objects', $entities) && count($entities['objects']) === $nEntriesPerPage,
            'tableColumns' => $tableConfigService->getVisibleColumns(),
            'tableConfigService' => $tableConfigService,
            'isSubmitted' => $isSubmitted,
            'hasSearchCriteria' => $hasSearchCriteria,
        ]);
    }

    #[Route('/import/{globalObjectId}', name: 'app_import_management_one', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function importOne(
        Request $request,
        string $globalObjectId,
        EntityManagerInterface $entityManager,
        EasydbApiService $easydbApiService,
        OccurrenceImportProcessingService $importProcessingService,
        LoggerInterface $logger,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        // Implementation for importing a single object by its global ID.
        // Useful as Webhook endpoint.
        try {
            $entityData = $easydbApiService->loadEntityByGlobalObjectID($globalObjectId);
            $type = $entityData['_objecttype'] ?? null;

            $mapping = null;
            foreach ($mappings as $map) {
                if (in_array($type, $map->supportsPools())) {
                    $mapping = $map;
                    break;
                }
            }

            if (!$mapping) {
                $logger->error('No mapping found for type', ['type' => $type, 'globalObjectId' => $globalObjectId]);
                return new Response('No mapping found for type: ' . $type, 400);
            }

            // Process the entity using the service
            $importProcessingService->processEntity($entityData, $mapping);

            // Flush changes to database
            $entityManager->flush();

            $logger->info('Successfully imported single entity', ['globalObjectId' => $globalObjectId, 'type' => $type]);
            return new Response('Import successful', 200);
        } catch (\Exception $e) {
            $logger->error('Failed to import single entity', [
                'globalObjectId' => $globalObjectId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return new Response('Import failed: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/export', name: 'app_export_management')]
    #[IsGranted('ROLE_USER')]
    public function exportManagement(
        Request $request,
        EasydbApiService $easydbApiService,
        DataExportService $dataExportService,
        EntityManagerInterface $entityManager,
        TableConfigurationService $tableConfigService,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        try {
            $objectTypeChoices = $this->loadAccessibleObjectTypes($mappings);
        } catch (\Throwable $th) {
            // redirect to object type sync if user has no object types
            return $this->redirectToRoute('app_sync_roles');
        }

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

        // Load data from query parameters to persist form values after redirect
        $queryData = [
            'globalObjectId' => $request->query->get('globalObjectId'),
            'tagId' => $request->query->getInt('tagId') ?: null,
            'objectType' => $request->query->get('objectType'),
            'exportFormat' => $request->query->get('exportFormat', 'csv'),
        ];

        // Create form
        $form = $this->createForm(ExportSelectionType::class, $queryData, [
            'tag_choices' => $tagChoices,
            'object_type_choices' => $objectTypeChoices,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Check which button was clicked
            $isExportClicked = $request->request->has('export_selection') &&
                array_key_exists('export', $request->request->all('export_selection'));

            try {
                // Search for entities based on form criteria
                $entities = $entityManager->getRepository(OccurrenceImport::class)->searchEntities(
                    $data['globalObjectId'],
                    $data['tagId'],
                    $data['objectType']
                );

                if (empty($entities)) {
                    $this->addFlash('warning', 'No entities found matching your criteria.');
                    return $this->redirectToRoute('app_export_management');
                }

                // If Export button was clicked, perform the export
                if ($isExportClicked) {
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
                }

                // If Preview button was clicked, redirect to same page with query parameters
                $queryParams = array_filter([
                    'globalObjectId' => $data['globalObjectId'] ?? null,
                    'tagId' => $data['tagId'] ?? null,
                    'objectType' => $data['objectType'] ?? null,
                    'exportFormat' => $data['exportFormat'] ?? 'csv',
                    'page' => 0,
                    'submitted' => 1,
                ]);
                return $this->redirectToRoute('app_export_management', $queryParams);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Export failed: ' . $e->getMessage());
            }
        }

        // Handle preview display
        $nEntriesPerPage = 100;
        $hasSearchCriteria = ($queryData['tagId'] ?? false) || ($queryData['objectType'] ?? false) || ($queryData['globalObjectId'] ?? false);
        $isSubmitted = $request->query->getBoolean('submitted', false);

        $previewEntities = [];
        $totalCount = 0;

        if ($hasSearchCriteria && $isSubmitted) {
            $currentPage = $request->query->getInt('page', 0);
            $offset = $currentPage * $nEntriesPerPage;

            // Get entities for preview
            $previewEntities = $entityManager->getRepository(OccurrenceImport::class)->searchEntities(
                $queryData['globalObjectId'],
                $queryData['tagId'],
                $queryData['objectType'],
                $nEntriesPerPage + 1, // Get one extra to check if there are more
                $offset
            );

            // Check if there are more results
            $hasMore = count($previewEntities) > $nEntriesPerPage;
            if ($hasMore) {
                array_pop($previewEntities); // Remove the extra entity
            }

            // Add flash message if no results found
            if (empty($previewEntities)) {
                $this->addFlash('warning', 'No entities found matching your criteria. Please adjust your filters and try again.');
            }
        } else {
            $hasMore = false;
            $currentPage = 0;
        }

        // Render the export management page with form
        return $this->render('import_export_management/export.html.twig', [
            'form' => $form->createView(),
            'availableObjectTypes' => $objectTypeChoices,
            'availableTags' => $tagChoices,
            'previewEntities' => $previewEntities,
            'currentPage' => $currentPage ?? 0,
            'nEntriesPerPage' => $nEntriesPerPage,
            'hasMore' => $hasMore ?? false,
            'tableColumns' => $tableConfigService->getVisibleExportColumns(),
            'tableConfigService' => $tableConfigService,
            'isSubmitted' => $isSubmitted,
            'hasSearchCriteria' => $hasSearchCriteria,
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

    /**
     * @param iterable $mappings
     * @return array
     * @throws LogicException
     * @throws RuntimeException
     */
    private function loadAccessibleObjectTypes(iterable $mappings): array
    {

        // Get available object types from mappings
        $objectTypes = array_merge(...array_map(fn($mapping) => $mapping->supportsPools(), iterator_to_array($mappings)));
        $objectTypes = array_unique($objectTypes);

        // Filter object types by user access
        /** @var User $user */
        $user = $this->getUser();
        $userAccessibleTypes = $user->getAccessibleObjectTypes();
        if (!empty($userAccessibleTypes)) {
            $objectTypes = array_intersect($objectTypes, $userAccessibleTypes->toArray());
        } else {
            // redirect to object type sync if user has no object types
            throw new \RuntimeException('User has no accessible object types. Please sync roles first.');
        }

        $objectTypeChoices = array_combine($objectTypes, $objectTypes);

        return $objectTypeChoices;
    }
}
