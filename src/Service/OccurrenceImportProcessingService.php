<?php

namespace App\Service;

use App\Entity\OccurrenceImport;
use App\Entity\User;
use App\Service\Mapping\EasydbDwCMappingInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for processing and importing entity data from EasyDB
 * into OccurrenceImport entities using the appropriate mapping.
 */
class OccurrenceImportProcessingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Process a single entity from EasyDB, either creating a new import
     * or updating an existing one if the remote data is newer.
     *
     * @param array $entityData The raw entity data from EasyDB
     * @param EasydbDwCMappingInterface $mapping The mapping to use for converting data
     * @param User|null $user The user triggering the import (optional)
     * @param array $criteria The import criteria (tagId, objectType, etc.)
     * @param bool $force Force re-mapping even if remote data hasn't changed (default: false)
     * @return OccurrenceImport The processed import entity
     * @throws \InvalidArgumentException If the entity data is invalid
     */
    public function processEntity(
        array $entityData,
        EasydbDwCMappingInterface $mapping,
        ?User $user = null,
        array $criteria = [],
        bool $force = false
    ): OccurrenceImport {
        $globalObjectId = $entityData['_global_object_id'] ?? null;

        if (!$globalObjectId) {
            $this->logger->warning('Entity without global object ID, skipping', ['entityData' => $entityData]);
            throw new \InvalidArgumentException('Entity must have a global object ID');
        }

        // Check if this entity was already imported
        $existingImport = $this->entityManager->getRepository(OccurrenceImport::class)
            ->findOneBy(['globalObjectID' => $globalObjectId]);

        if ($existingImport) {
            $this->logger->debug('Found existing import', ['globalObjectId' => $globalObjectId]);
            return $this->updateExistingImport($existingImport, $entityData, $mapping, $user, $criteria, $force);
        } else {
            $this->logger->debug('Creating new import', ['globalObjectId' => $globalObjectId]);
            return $this->createNewImport($entityData, $mapping, $user, $criteria);
        }
    }

    /**
     * Update an existing import if the remote data is newer or if forced.
     *
     * @param OccurrenceImport $existingImport
     * @param array $entityData
     * @param EasydbDwCMappingInterface $mapping
     * @param User|null $user
     * @param array $criteria
     * @param bool $force Force re-mapping regardless of timestamp
     * @return OccurrenceImport
     */
    private function updateExistingImport(
        OccurrenceImport $existingImport,
        array $entityData,
        EasydbDwCMappingInterface $mapping,
        ?User $user,
        array $criteria,
        bool $force = false
    ): OccurrenceImport {
        $lastModified = new \DateTimeImmutable($entityData['_last_modified'] ?? $entityData['_created'] ?? 'now');
        $globalObjectId = $entityData['_global_object_id'];

        // Update if the remote data is newer OR if force flag is set
        if ($force || $lastModified > $existingImport->getRemoteLastUpdatedAt()) {
            $occurrence = $existingImport->getOccurrence();
            $oldMedia = $occurrence?->getAssociatedMedia();

            $mapping->mapOccurrence($entityData, $existingImport);

            if ($user) {
                $existingImport->setManualImportTrigger($user);
            }

            $existingImport->setTagId($criteria['tagId'] ?? $existingImport->getTagId());
            $existingImport->setObjectType($entityData['_objecttype'] ?? $existingImport->getObjectType());

            // Explicitly persist the occurrence to ensure Doctrine tracks changes
            // This is important when the occurrence was retrieved from the repository
            $occurrence = $existingImport->getOccurrence();
            if ($occurrence) {
                $this->entityManager->persist($occurrence);
                $newMedia = $occurrence->getAssociatedMedia();
                $this->logger->info('Updated occurrence media', [
                    'globalObjectId' => $globalObjectId,
                    'oldMedia' => $oldMedia ? substr($oldMedia, 0, 50) . '...' : 'null',
                    'newMedia' => $newMedia ? substr($newMedia, 0, 50) . '...' : 'null',
                    'mediaChanged' => $oldMedia !== $newMedia,
                ]);
            }

            $this->logger->debug('Updated existing import', [
                'globalObjectId' => $globalObjectId,
                'forced' => $force
            ]);

            $existingImport->setLastUpdatedAt(new \DateTimeImmutable());
        } else {
            $this->logger->debug('Skipping entity - no changes', ['globalObjectId' => $globalObjectId]);
        }

        return $existingImport;
    }

    /**
     * Create a new import from entity data.
     *
     * @param array $entityData
     * @param EasydbDwCMappingInterface $mapping
     * @param User|null $user
     * @return OccurrenceImport
     */
    private function createNewImport(
        array $entityData,
        EasydbDwCMappingInterface $mapping,
        ?User $user,
        array $criteria
    ): OccurrenceImport {
        $import = new OccurrenceImport();

        // Set identifiers and metadata required for future lookups/updates
        $globalObjectId = $entityData['_global_object_id'];
        $import->setGlobalObjectID($globalObjectId);
        $remoteLastUpdated = new \DateTimeImmutable($entityData['_last_modified'] ?? $entityData['_created'] ?? 'now');
        $import->setRemoteLastUpdatedAt($remoteLastUpdated);

        if ($user) {
            $import->setManualImportTrigger($user);
        }

        $import->setTagId($criteria['tagId'] ?? null);
        $import->setObjectType($entityData['_objecttype'] ?? null);

        $mapping->mapOccurrence($entityData, $import);
        
        $now = new \DateTimeImmutable();

        $import->setFirstImportedAt($now);
        $import->setLastUpdatedAt($now);

        $this->entityManager->persist($import);

        $this->logger->debug('Created new import', ['globalObjectId' => $globalObjectId]);

        return $import;
    }

    /**
     * Process multiple entities in batch.
     *
     * @param array $entitiesData Array of entity data from EasyDB
     * @param EasydbDwCMappingInterface $mapping The mapping to use
     * @param User|null $user The user triggering the import
     * @return array Array of processed OccurrenceImport entities
     */
    public function processEntities(
        array $entitiesData,
        EasydbDwCMappingInterface $mapping,
        ?User $user = null
    ): array {
        $processedImports = [];

        foreach ($entitiesData as $entityData) {
            try {
                $import = $this->processEntity($entityData, $mapping, $user);
                $processedImports[] = $import;
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning('Skipping invalid entity', ['error' => $e->getMessage()]);
                // Continue processing other entities
            }
        }

        return $processedImports;
    }
}
