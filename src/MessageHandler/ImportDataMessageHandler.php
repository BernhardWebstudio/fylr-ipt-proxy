<?php

namespace App\MessageHandler;

use App\Entity\OccurrenceImport;
use App\Entity\User;
use App\Message\ImportDataMessage;
use App\Service\EasydbApiService;
use App\Service\JobStatusService;
use App\Service\OccurrenceImportProcessingService;
use App\Service\Mapping\EasydbDwCMappingInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Handles asynchronous import data operations from EasyDB
 */
#[AsMessageHandler]
final class ImportDataMessageHandler
{
    public function __construct(
        private EasydbApiService $easydbApiService,
        private JobStatusService $jobStatusService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus,
        private OccurrenceImportProcessingService $importProcessingService,
        #[AutowireIterator("app.easydb_dwc_mapping")] private iterable $mappings
    ) {}

    public function __invoke(ImportDataMessage $message): void
    {
        $jobId = $message->getJobId();
        $type = $message->getType();
        $criteria = $message->getCriteria();
        $userId = $message->getUserId();
        $page = $message->getPage();
        $pageSize = $message->getPageSize();
        $easydbToken = $message->getEasydbToken();
        $easydbSessionContent = $message->getEasydbSessionContent();

        if (!$this->entityManager->isOpen()) {
            $this->logger->error('EntityManager is closed at start of import handler', ['jobId' => $jobId]);
            throw new \RuntimeException('EntityManager is closed, cannot process import');
        }

        $this->logger->info('Starting import job', [
            'jobId' => $jobId,
            'type' => $type,
            'page' => $page,
            'userId' => $userId
        ]);

        try {
            // Initialize EasyDB API service with credentials from the message
            $this->easydbApiService->initializeFromCredentials($easydbToken, $easydbSessionContent);

            // Update job status to running
            $this->jobStatusService->updateJobStatus($jobId, 'running');

            // Find the user
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                throw new \RuntimeException("User with ID {$userId} not found");
            }

            // Find the appropriate mapping for this type
            $objectType = $criteria['objectType'] ?? null;
            $mapping = $this->findMappingForType($objectType);
            if (!$mapping) {
                throw new \RuntimeException("No mapping found for type: {$objectType}");
            }

            // Calculate offset based on page and page size
            $offset = ($page - 1) * $pageSize;

            // Search for entities from EasyDB using the criteria
            $entities = $this->searchEasydbEntities($criteria, $offset, $pageSize);
            $this->logger->debug('Fetched entities from EasyDB', [
                'jobId' => $jobId,
                'page' => $page,
                'fetchedCount' => is_array($entities) ? count($entities) : 0,
                'criteria' => $criteria,
                'results' => $entities
            ]);
            $keysToCheck = ['data', 'entities', 'objects'];
            foreach ($keysToCheck as $key) {
                if (array_key_exists($key, $entities)) {
                    $entities = $entities[$key];
                }
            }

            if (empty($entities)) {
                $this->logger->info('No entities found for import', ['jobId' => $jobId, 'page' => $page]);

                // If this is page 1 and no entities found, complete the job
                if ($page === 1) {
                    $this->jobStatusService->updateJobStatus($jobId, 'completed');
                    return;
                }

                // If no entities on this page, we've reached the end
                $this->jobStatusService->updateJobStatus($jobId, 'completed');
                return;
            }

            // Update total items count for the first page
            if ($page === 1) {
                // Get total count from EasyDB (this is a rough estimate based on returned data)
                $totalEstimate = count($entities) === $pageSize ?
                    $this->estimateTotalItems($criteria) :
                    count($entities);

                $this->jobStatusService->updateJobProgress($jobId, 0, $totalEstimate);
            }

            // Process each entity
            $processedCount = 0;

            foreach ($entities as $entity) {
                $this->importProcessingService->processEntity($entity, $mapping, $user, $criteria);
                $this->entityManager->flush();
                $processedCount++;

                // Update progress every 10 items
                if ($processedCount % 10 === 0) {
                    $progress = ($page - 1) * $pageSize + $processedCount;
                    $this->jobStatusService->updateJobProgress($jobId, $progress);
                }
            }

            // Update final progress for this page
            $progress = ($page - 1) * $pageSize + $processedCount;
            $this->jobStatusService->updateJobProgress($jobId, $progress);

            $this->logger->info('Import page completed', [
                'jobId' => $jobId,
                'page' => $page,
                'processed' => $processedCount
            ]);

            // If we got a full page of results, there might be more data
            // Dispatch the next page
            if (count($entities) === $pageSize) {
                $nextPageMessage = new ImportDataMessage(
                    $jobId,
                    $type,
                    $criteria,
                    $userId,
                    $easydbToken,
                    $easydbSessionContent,
                    $page + 1,
                    $pageSize
                );

                $this->messageBus->dispatch($nextPageMessage);
            } else {
                // This was the last page
                $this->jobStatusService->updateJobStatus($jobId, 'completed');
            }
        } catch (\Exception $e) {
            $this->logger->error('Import job failed', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            try {
                if ($this->entityManager->isOpen()) {
                    $this->jobStatusService->setJobError($jobId, $e->getMessage());
                } else {
                    $this->logger->warning('Cannot set job error: EntityManager is closed', ['jobId' => $jobId]);
                }
            } catch (\Exception $setErrorException) {
                $this->logger->error('Failed to set job error', ['jobId' => $jobId, 'error' => $setErrorException->getMessage()]);
            }
        }
    }

    private function findMappingForType(string $type): ?EasydbDwCMappingInterface
    {
        foreach ($this->mappings as $mapping) {
            if (in_array($type, $mapping->supportsPools())) {
                return $mapping;
            }
        }
        return null;
    }

    private function searchEasydbEntities(array $criteria, int $offset, int $limit): array
    {
        $globalObjectId = $criteria['globalObjectId'] ?? null;
        $tagId = $criteria['tagId'] ?? null;
        $objectType = $criteria['objectType'] ?? null;

        // Use the existing searchEntities method from EasydbApiService
        return $this->easydbApiService->searchEntities($globalObjectId, $tagId, $objectType, $offset, $limit);
    }

    private function estimateTotalItems(array $criteria): int
    {
        // This is a simplified estimation
        // In a real implementation, you might want to do a count query first
        try {
            $sampleResults = $this->searchEasydbEntities($criteria, 0, 1000);
            return count($sampleResults);
        } catch (\Exception $e) {
            $this->logger->warning('Could not estimate total items', ['error' => $e->getMessage()]);
            return 100; // Default estimate
        }
    }
}
