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
     * @return OccurrenceImport The processed import entity
     * @throws \InvalidArgumentException If the entity data is invalid
     */
    public function processEntity(
        array $entityData,
        EasydbDwCMappingInterface $mapping,
        ?User $user = null
    ): OccurrenceImport {
        $globalObjectId = $entityData['_global_object_id'] ?? null;

        if (!$globalObjectId) {
            $this->logger->warning('Entity without global object ID, skipping');
            throw new \InvalidArgumentException('Entity must have a global object ID');
        }

        // Check if this entity was already imported
        $existingImport = $this->entityManager->getRepository(OccurrenceImport::class)
            ->findOneBy(['globalObjectID' => $globalObjectId]);

        if ($existingImport) {
            return $this->updateExistingImport($existingImport, $entityData, $mapping, $user);
        } else {
            return $this->createNewImport($entityData, $mapping, $user);
        }
    }

    /**
     * Update an existing import if the remote data is newer.
     *
     * @param OccurrenceImport $existingImport
     * @param array $entityData
     * @param EasydbDwCMappingInterface $mapping
     * @param User|null $user
     * @return OccurrenceImport
     */
    private function updateExistingImport(
        OccurrenceImport $existingImport,
        array $entityData,
        EasydbDwCMappingInterface $mapping,
        ?User $user
    ): OccurrenceImport {
        $lastModified = new \DateTimeImmutable($entityData['_last_modified'] ?? $entityData['_created'] ?? 'now');
        $globalObjectId = $entityData['_global_object_id'];

        // Only update if the remote data is newer
        if ($lastModified > $existingImport->getRemoteLastUpdatedAt()) {
            $mapping->mapOccurrence($entityData, $existingImport);
            
            if ($user) {
                $existingImport->setManualImportTrigger($user);
            }
            
            $this->logger->debug('Updated existing import', ['globalObjectId' => $globalObjectId]);
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
        ?User $user
    ): OccurrenceImport {
        $import = new OccurrenceImport();
        
        if ($user) {
            $import->setManualImportTrigger($user);
        }

        $mapping->mapOccurrence($entityData, $import);

        $this->entityManager->persist($import);
        
        $globalObjectId = $entityData['_global_object_id'];
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
