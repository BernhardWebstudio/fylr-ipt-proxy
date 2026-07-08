<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Exception\UnsupportedObjectTypeException;
use App\Service\EasydbApiService;
use App\Service\OccurrenceImportProcessingService;
use App\Service\SpecimenImportService;
use App\Service\Mapping\EasydbDwCMappingInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SpecimenImportServiceTest extends TestCase
{
    public function testImportByEntityDataThrowsUnsupportedObjectTypeExceptionWhenNoMappingFound(): void
    {
        $easydbApiService = $this->createMock(EasydbApiService::class);
        $importProcessingService = $this->createMock(OccurrenceImportProcessingService::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Define a mock mapping that doesn't support the type
        $mapping = $this->createMock(EasydbDwCMappingInterface::class);
        $mapping->method('supportsPools')->willReturn(['supported_type']);

        $service = new SpecimenImportService(
            $easydbApiService,
            $importProcessingService,
            $entityManager,
            $logger,
            [$mapping]
        );

        $entityData = [
            '_global_object_id' => '123@abc-123',
            '_objecttype' => 'unsupported_type'
        ];

        $this->expectException(UnsupportedObjectTypeException::class);
        $this->expectExceptionMessage('No mapping found for type: unsupported_type');

        $service->importByEntityData($entityData);
    }
}
