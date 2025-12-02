<?php

namespace App\Service;

use App\Entity\User;
use App\Service\Mapping\EasydbDwCMappingInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * High-level service for importing specimens from EasyDB.
 * Orchestrates EasyDB API calls, mapping selection, and import processing.
 */
class SpecimenImportService
{
    public function __construct(
        private readonly EasydbApiService $easydbApiService,
        private readonly OccurrenceImportProcessingService $importProcessingService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        #[AutowireIterator("app.easydb_dwc_mapping")] private readonly iterable $mappings,
    ) {}

    /**
     * Import a specimen by its global object ID.
     *
     * @param string $globalObjectId The global object ID (e.g., "123@abc-123")
     * @param User|null $user The user triggering the import (optional)
     * @param bool $flush Whether to flush changes to the database (default: true)
     * @param bool $force Force re-mapping even if remote data hasn't changed (default: false)
     * @throws \RuntimeException If import fails
     */
    public function importByGlobalObjectId(
        string $globalObjectId,
        ?User $user = null,
        bool $flush = true,
        bool $force = false
    ): void {
        $this->logger->info('Starting import by global object ID', [
            'globalObjectId' => $globalObjectId,
            'userId' => $user?->getId(),
        ]);

        try {
            // Load entity data from EasyDB
            $entityData = $this->easydbApiService->loadEntityByGlobalObjectID($globalObjectId);
            $type = $entityData['_objecttype'] ?? null;

            if (!$type) {
                throw new \RuntimeException('Entity missing _objecttype field');
            }

            // Find the appropriate mapping for this type
            $mapping = $this->findMappingForType($type);

            if (!$mapping) {
                throw new \RuntimeException(sprintf('No mapping found for type: %s', $type));
            }

            // Process the entity using the import processing service
            $this->importProcessingService->processEntity($entityData, $mapping, $user);

            // Flush changes to database if requested
            if ($flush) {
                $this->entityManager->flush();
            }

            $this->logger->info('Successfully imported specimen', [
                'globalObjectId' => $globalObjectId,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to import specimen', [
                'globalObjectId' => $globalObjectId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                sprintf('Import of specimen %s failed: %s', $globalObjectId, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Import a specimen by UUID and system object ID.
     *
     * @param string $type The object type
     * @param string $uuid The UUID
     * @param int $systemObjectId The system object ID
     * @param User|null $user The user triggering the import (optional)
     * @param bool $flush Whether to flush changes to the database (default: true)
     * @throws \RuntimeException If import fails
     */
    public function importByUuid(
        string $type,
        string $uuid,
        int $systemObjectId,
        ?User $user = null,
        bool $flush = true
    ): void {
        $this->logger->info('Starting import by UUID', [
            'type' => $type,
            'uuid' => $uuid,
            'systemObjectId' => $systemObjectId,
            'userId' => $user?->getId(),
        ]);

        try {
            // Load entity data from EasyDB
            $entityData = $this->easydbApiService->loadEntityByUUIDAndSystemObjectID($uuid, $systemObjectId);

            $globalObjectId = $entityData['_global_object_id'] ?? null;
            if (!$globalObjectId) {
                throw new \RuntimeException('Entity missing global object ID');
            }

            // Find the appropriate mapping for this type
            $mapping = $this->findMappingForType($type);

            if (!$mapping) {
                throw new \RuntimeException(sprintf('No mapping found for type: %s', $type));
            }

            // Process the entity using the import processing service
            $this->importProcessingService->processEntity($entityData, $mapping, $user);

            // Flush changes to database if requested
            if ($flush) {
                $this->entityManager->flush();
            }

            $this->logger->info('Successfully imported specimen', [
                'type' => $type,
                'uuid' => $uuid,
                'systemObjectId' => $systemObjectId,
                'globalObjectId' => $globalObjectId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to import specimen', [
                'type' => $type,
                'uuid' => $uuid,
                'systemObjectId' => $systemObjectId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                sprintf('Import of specimen %s/%s failed: %s', $type, $uuid, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Find the mapping that supports the given object type.
     *
     * @param string $type The object type
     * @return EasydbDwCMappingInterface|null The mapping or null if not found
     */
    private function findMappingForType(string $type): ?EasydbDwCMappingInterface
    {
        foreach ($this->mappings as $mapping) {
            if (in_array($type, $mapping->supportsPools())) {
                return $mapping;
            }
        }
        return null;
    }
}
