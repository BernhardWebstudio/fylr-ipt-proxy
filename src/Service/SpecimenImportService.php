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
     *
     * @return bool True if the specimen was imported/updated, false if no changes were made
     * @throws \RuntimeException If import fails
     */
    public function importByGlobalObjectId(
        string $globalObjectId,
        ?User $user = null,
        bool $flush = true,
        bool $force = false
    ): bool {
        $this->logger->info('Starting import by global object ID', [
            'globalObjectId' => $globalObjectId,
            'userId' => $user?->getId(),
        ]);

        try {
            // Load entity data from EasyDB
            $entityData = $this->easydbApiService->loadEntityByGlobalObjectID($globalObjectId);
            if ($entityData === null) {
                $this->logger->warning('No entity found for global object ID', [
                    'globalObjectId' => $globalObjectId,
                    'userId' => $user?->getId(),
                ]);
                return false;
            }

            return $this->importByEntityData(
                $entityData,
                $user,
                $flush,
                $force
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to import specimen by global object ID', [
                'globalObjectId' => $globalObjectId,
                'error' => $e->getMessage(),
                'exception' => $e,
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
     *
     * @return bool True if the specimen was imported/updated, false if no changes were made
     * @throws \RuntimeException If import fails
     */
    public function importByUuid(
        string $type,
        string $uuid,
        int $systemObjectId,
        ?User $user = null,
        bool $flush = true
    ): bool {
        $this->logger->info('Starting import by UUID', [
            'type' => $type,
            'uuid' => $uuid,
            'systemObjectId' => $systemObjectId,
            'userId' => $user?->getId(),
        ]);

        try {
            // Load entity data from EasyDB
            $entityData = $this->easydbApiService->loadEntityByUUIDAndSystemObjectID($uuid, $systemObjectId);
            if ($entityData === null) {
                $this->logger->warning('No entity found for UUID/system object id', [
                    'type' => $type,
                    'uuid' => $uuid,
                    'systemObjectId' => $systemObjectId,
                    'userId' => $user?->getId(),
                ]);
                return false;
            }

            return $this->importByEntityData(
                $entityData,
                $user,
                $flush,
                false
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to import specimen by UUID/system object id', [
                'type' => $type,
                'uuid' => $uuid,
                'systemObjectId' => $systemObjectId,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new \RuntimeException(
                sprintf('Import of specimen %s/%s failed: %s', $type, $uuid, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Import a specimen by its entity data.
     *
     * @param array $entityData The entity data from EasyDB
     * @param User|null $user The user triggering the import (optional)
     * @param bool $flush Whether to flush changes to the database (default: true)
     * @param bool $force Force re-mapping even if remote data hasn't changed (default: false)
     *
     * @return bool True if the specimen was imported/updated, false if no changes were made
     * @throws \RuntimeException If import fails
     */
    public function importByEntityData(
        array $entityData,
        ?User $user = null,
        bool $flush = true,
        bool $force = false
    ): bool {
        $globalObjectId = $entityData['_global_object_id'] ?? null;
        if (!$globalObjectId) {
            throw new \RuntimeException('Entity missing global object ID');
        }

        $this->logger->info('Starting import by entity data', [
            'globalObjectId' => $globalObjectId,
            'userId' => $user?->getId(),
        ]);

        try {
            $entity = $this->doImport($entityData, $user, $force);

            // check if entity was updated longer ago than the last few seconds
            // i.e., not updated during this import call
            if (
                $entity->getLastUpdatedAt() !== null &&
                $entity->getLastUpdatedAt() < (new \DateTimeImmutable())->modify('-10 seconds')
            ) {
                return false;
            }

            // Flush changes to database if requested
            if ($flush) {
                $this->entityManager->flush();
            }

            $this->logger->info('Successfully imported specimen', [
                'globalObjectId' => $globalObjectId,
                'type' => $entityData['_objecttype'],
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to import specimen given entity data', [
                'globalObjectId' => $globalObjectId,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new \RuntimeException(
                sprintf('Import of specimen %s failed: %s', $globalObjectId, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Perform the core import logic for entity data.
     *
     * @param array $entityData The entity data
     * @param User|null $user The user
     * @param bool $force Force re-mapping
     * @return object The processed entity
     * @throws \RuntimeException If type or mapping is missing
     */
    private function doImport(array $entityData, ?User $user, bool $force): object
    {
        $type = $entityData['_objecttype'] ?? null;
        if (!$type) {
            throw new \RuntimeException('Entity missing _objecttype field');
        }

        $mapping = $this->findMappingForType($type);
        if (!$mapping) {
            throw new \RuntimeException(sprintf('No mapping found for type: %s', $type));
        }

        return $this->importProcessingService->processEntity($entityData, $mapping, $user, [], $force);
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
