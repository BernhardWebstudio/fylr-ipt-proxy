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
use Symfony\Contracts\Translation\TranslatorInterface;

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
        TranslatorInterface $translator,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        $nEntriesPerPage = 100;
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
            $this->addFlash('warning', $translator->trans('flash.could_not_load_tags', ['%message%' => $e->getMessage()]));
        }

        // Load data from query parameters to persist form values after redirect
        $queryData = [
            'globalObjectId' => $request->query->get('globalObjectId'),
            'tagId' => $request->query->getInt('tagId') ?: null,
            'objectType' => $request->query->get('objectType'),
        ];
        $form = $this->createForm(ImportSelectionType::class, $queryData, [
            'tag_choices' => $tagChoices,
            'object_type_choices' => $objectTypeChoices,
        ]);
        $form->handleRequest($request);

        $hasSearchCriteria = ($queryData['tagId'] ?? false) || ($queryData['objectType'] ?? false) || ($queryData['globalObjectId'] ?? false);
        $isSubmitted = $request->query->getBoolean('submitted', false);
        $currentPage = $request->query->getInt('page', 0);
        $offset = ($currentPage * $nEntriesPerPage);
        $previewEntities = [];
        $hasMore = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData() ?? [];

            // Check which button was clicked
            $clickedButton = $form->getClickedButton();
            $isImportClicked = $clickedButton && $clickedButton->getName() === 'import';

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

                // Get EasyDB credentials from session
                $session = $request->getSession();
                $easydbToken = $session->get('easydb_token');
                $easydbSessionContent = $session->get('easydb_session_content');

                if (!$easydbToken || !$easydbSessionContent) {
                    $this->addFlash('error', $translator->trans('flash.no_easydb_session'));
                    return $this->redirectToRoute('app_import_management');
                }

                // Create job status record
                $jobStatusService->createJob($jobId, 'import', $user, $criteria);

                // Dispatch import message
                $importMessage = new ImportDataMessage(
                    $jobId,
                    $data['objectType'] ?? null,
                    $criteria,
                    $user->getId(),
                    $easydbToken,
                    $easydbSessionContent
                );
                $messageBus->dispatch($importMessage);

                $this->addFlash('success', $translator->trans('flash.import_job_started', ['%jobId%' => $jobId]));
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
        if ($hasSearchCriteria) {
            $result = $easydbApiService->searchEntities(
                $queryData['globalObjectId'] ?? null,
                $queryData['tagId'] ?? null,
                $queryData['objectType'] ?? null,
                $offset,
                $nEntriesPerPage
            );
            $objects = [];
            if (isset($result['objects']) && is_array($result['objects'])) {
                $objects = $result['objects'];
            } elseif (isset($result['data']) && is_array($result['data'])) {
                $objects = $result['data'];
            } elseif (is_array($result)) {
                $objects = $result;
            }
            $hasMore = count($objects) > $nEntriesPerPage;
            if ($hasMore) {
                $objects = array_slice($objects, 0, $nEntriesPerPage);
            }
            $previewEntities = $objects;
        }
        if ($isSubmitted && $hasSearchCriteria && empty($previewEntities)) {
            $this->addFlash('warning', $translator->trans('flash.no_data_found'));
            $logger->info('No entities found for import preview', [
                'criteria' => $queryData,
                'entities' => $previewEntities,
            ]);
        }

        // then, render the import form
        return $this->render('import_export_management/import.html.twig', [
            'form' => $form->createView(),
            'availableObjectTypes' => $objectTypeChoices,
            'availableTags' => $tagChoices,
            'previewEntities' => $previewEntities,
            'currentPage' => $currentPage,
            'nEntriesPerPage' => $nEntriesPerPage,
            'hasMore' => $hasMore,
            'tableColumns' => $tableConfigService->getVisibleColumns(),
            'tableConfigService' => $tableConfigService,
            'isSubmitted' => $isSubmitted,
            'hasSearchCriteria' => $hasSearchCriteria
        ]);
    }

    #[Route('/import/webhook', name: 'app_import_management_webhook', methods: ['POST', 'GET'])]
    public function importWebhook(
        Request $request,
        LoggerInterface $logger
    ): Response {
        // log what we get, since we don't know the exact format yet
        $logger->info('Received webhook call', [
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent(),
            'query' => $request->query->all(),
            'request' => $request->request->all(),
        ]);

        // This endpoint is a placeholder to receive webhook calls from EasyDB.
        // Actual processing is done in the importOneByGlobalObjectID or
        // importOneByUUID methods below.
        return new Response('Webhook endpoint. Use /import/{globalObjectId} or /import/{type}/{uuid}/{systemObjectId} to trigger imports.', 200);
    }

    #[Route('/import/{globalObjectId}', name: 'app_import_management_one_goi', methods: ['POST'])]
    public function importOneByGlobalObjectID(
        Request $request,
        string $globalObjectId,
        EntityManagerInterface $entityManager,
        EasydbApiService $easydbApiService,
        OccurrenceImportProcessingService $importProcessingService,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {

        // Ensure EasyDB credentials are available: try HTTP session first,
        // then fall back to environment variables (for webhook/no-user contexts).
        if (!$easydbApiService->hasValidSession()) {
            // Prefer login/password from env to create a fresh authenticated session
            $envLogin = $_ENV['EASYDB_LOGIN'] ?? getenv('EASYDB_LOGIN') ?: null;
            $envPassword = $_ENV['EASYDB_PASSWORD'] ?? getenv('EASYDB_PASSWORD') ?: null;

            if (!$envLogin || !$envPassword || !$easydbApiService->initializeFromLoginPassword($envLogin, $envPassword)) {
                $logger->error('No EasyDB login/password available for webhook import or authentication failed');
                return new Response('Missing EasyDB credentials (login/password).', 401);
            }
        }

        $entityData = $easydbApiService->loadEntityByGlobalObjectID($globalObjectId);
        return $this->importOne(
            $request,
            $entityData['_objecttype'] ?? null,
            $globalObjectId,
            $entityManager,
            $easydbApiService,
            $importProcessingService,
            $logger,
            $translator,
            $mappings
        );
    }


    #[Route('/import/{type}/{uuid}/{systemObjectId}', name: 'app_import_management_one_uuid', methods: ['POST'])]
    public function importOneByUUID(
        Request $request,
        string $type,
        string $uuid,
        int $systemObjectId,
        EntityManagerInterface $entityManager,
        EasydbApiService $easydbApiService,
        OccurrenceImportProcessingService $importProcessingService,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        // Implementation for importing a single object by its UUID and systemObjectId.
        // Useful as Webhook endpoint.

        // Ensure EasyDB credentials are available: try HTTP session first,
        // then fall back to environment variables (for webhook/no-user contexts).
        if (!$easydbApiService->hasValidSession()) {
            // Prefer login/password from env to create a fresh authenticated session
            $envLogin = $_ENV['EASYDB_LOGIN'] ?? getenv('EASYDB_LOGIN') ?: null;
            $envPassword = $_ENV['EASYDB_PASSWORD'] ?? getenv('EASYDB_PASSWORD') ?: null;

            if (!$envLogin || !$envPassword || !$easydbApiService->initializeFromLoginPassword($envLogin, $envPassword)) {
                $logger->error('No EasyDB login/password available for webhook import or authentication failed');
                return new Response('Missing EasyDB credentials (login/password).', 401);
            }
        }

        $entityData = $easydbApiService->loadEntityByUUIDAndSystemObjectID($uuid, $systemObjectId);

        $globalObjectId = $entityData['_global_object_id'] ?? null;
        if (!$globalObjectId) {
            $logger->error('Entity without global object ID, skipping', ['entityData' => $entityData]);
            return new Response('Entity must have a global object ID', 400);
        }

        return $this->importOne(
            $request,
            $type,
            $globalObjectId,
            $entityManager,
            $easydbApiService,
            $importProcessingService,
            $logger,
            $translator,
            $mappings
        );
    }


    #[Route('/import/{type}/{globalObjectId}', name: 'app_import_management_one', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function importOne(
        Request $request,
        string $type,
        string $globalObjectId,
        EntityManagerInterface $entityManager,
        EasydbApiService $easydbApiService,
        OccurrenceImportProcessingService $importProcessingService,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        #[AutowireIterator("app.easydb_dwc_mapping")] iterable $mappings
    ): Response {
        // Implementation for importing a single object by its global ID.
        // Useful as Webhook endpoint.
        try {
            $entityData = $easydbApiService->loadEntityByGlobalObjectID($globalObjectId);
            $type = $type ?? $entityData['_objecttype'] ?? null;

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
            $this->addFlash('success', $translator->trans('flash.import_successful', ['%globalObjectId%' => $globalObjectId]));

            // Preserve the current view state by extracting query params from referer
            $referer = $request->headers->get('referer');
            $queryParams = [];
            if ($referer) {
                $parsedUrl = parse_url($referer);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                }
            }

            // Redirect back to import page with preserved query params
            return $this->redirectToRoute('app_import_management', $queryParams);
        } catch (\Exception $e) {
            $logger->error('Failed to import single entity', [
                'globalObjectId' => $globalObjectId,
                'type' => $type ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->addFlash('error', 'Import of Specimen with ID ' . $globalObjectId . ' failed: ' . $e->getMessage());

            // Preserve the current view state by extracting query params from referer
            $referer = $request->headers->get('referer');
            $queryParams = [];
            if ($referer) {
                $parsedUrl = parse_url($referer);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                }
            }

            // Redirect back to import page with preserved query params
            return $this->redirectToRoute('app_import_management', $queryParams);
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
        LoggerInterface $logger,
        TranslatorInterface $translator,
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
            $this->addFlash('warning', $translator->trans('flash.could_not_load_tags', ['%message%' => $e->getMessage()]));
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
            $isExportClicked = $form->get('export')->isClicked() || $form->getClickedButton()?->getName() === 'export';

            try {
                // Search for entities based on form criteria
                $entities = $entityManager->getRepository(OccurrenceImport::class)->searchEntities(
                    $data['globalObjectId'],
                    $data['tagId'],
                    $data['objectType']
                );

                if (empty($entities)) {
                    $this->addFlash('warning', 'No data found matching your criteria. Please adjust your filters and try again.');
                    return $this->redirectToRoute('app_export_management');
                }

                // If Export button was clicked, perform the export
                if ($isExportClicked) {
                    $logger->info('Export initiated', ['format' => $data['exportFormat']]);
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
                } else {
                    $logger->info('Preview requested for export', [
                        'globalObjectId' => $data['globalObjectId'] ?? null,
                        'tagId' => $data['tagId'] ?? null,
                        'objectType' => $data['objectType'] ?? null,
                        'export' => $isExportClicked,
                    ]);
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

        if ($isSubmitted) {
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
            $objectTypes = array_intersect($objectTypes, $userAccessibleTypes->map(fn($type) => $type->getName())->toArray());
        } else {
            // redirect to object type sync if user has no object types
            throw new \RuntimeException('User has no accessible object types. Please sync roles first.');
        }

        $objectTypeChoices = array_combine($objectTypes, $objectTypes);

        return $objectTypeChoices;
    }
}
