<?php

namespace App\Tests\Controller;

use App\Controller\ImportExportManagementController;
use App\Exception\UnsupportedObjectTypeException;
use App\Service\EasydbApiService;
use App\Service\SpecimenImportService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportExportManagementControllerTest extends TestCase
{
    public function testImportWebhookSkipsUnsupportedObjectType(): void
    {
        $controller = new ImportExportManagementController();

        $payload = json_encode([
            'action' => 'update',
            'operation' => 'insert',
            'objects' => [
                [
                    '_objecttype' => 'unsupported_type',
                    '_uuid' => '123e4567-e89b-12d3-a456-426614174000',
                    '_system_object_id' => 9999,
                ]
            ]
        ]);

        $request = new Request([], [], [], [], [], [], $payload);

        $logger = $this->createMock(LoggerInterface::class);
        $easydbApiService = $this->createMock(EasydbApiService::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $specimenImportService = $this->createMock(SpecimenImportService::class);
        $specimenImportService->expects($this->once())
            ->method('importByUuid')
            ->with('unsupported_type', '123e4567-e89b-12d3-a456-426614174000', 9999)
            ->willThrowException(new UnsupportedObjectTypeException('No mapping found for type: unsupported_type'));

        // The logger should receive info logs, but no error logs
        $logger->expects($this->never())->method('error');

        $response = $controller->importWebhook(
            $request,
            $logger,
            $easydbApiService,
            $specimenImportService,
            $translator
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Webhook processed: 0 of 1 objects imported successfully (1 skipped)', $response->getContent());
        $this->assertStringNotContainsString('Errors:', $response->getContent());
    }

    public function testImportWebhookImportsViaEntityDataWhenAvailable(): void
    {
        $controller = new ImportExportManagementController();

        $objectData = [
            '_objecttype' => 'fungarium',
            '_global_object_id' => '12048980@f9ffe071-931d-4998-8cee-35fef5a3dd2b',
            '_uuid' => 'cf22b842-e13f-4a6d-8687-0b6fa52912ec',
            '_system_object_id' => 12048980,
            'fungarium' => ['zugangsnummer' => 'ZT Myc 0099460']
        ];

        $payload = json_encode([
            'action' => 'update',
            'operation' => 'update',
            'objects' => [$objectData]
        ]);

        $request = new Request([], [], [], [], [], [], $payload);

        $logger = $this->createMock(LoggerInterface::class);
        $easydbApiService = $this->createMock(EasydbApiService::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $specimenImportService = $this->createMock(SpecimenImportService::class);
        $specimenImportService->expects($this->once())
            ->method('importByEntityData')
            ->with($objectData)
            ->willReturn(true);
        $specimenImportService->expects($this->never())
            ->method('importByUuid');

        $response = $controller->importWebhook(
            $request,
            $logger,
            $easydbApiService,
            $specimenImportService,
            $translator
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Webhook processed: 1 of 1 objects imported successfully', $response->getContent());
    }

    public function testImportWebhookHandlesMonitoringProbeWithoutError(): void
    {
        $controller = new ImportExportManagementController();

        $monitoringObject = [
            '_objecttype' => 'object',
            '_mask' => '_all_fields',
            '_callback_context' => [
                'hash' => 'monitoring-test'
            ]
        ];

        $payload = json_encode([
            'objects' => [$monitoringObject]
        ]);

        $request = new Request([], [], [], [], [], [], $payload);

        $logger = $this->createMock(LoggerInterface::class);
        $easydbApiService = $this->createMock(EasydbApiService::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $specimenImportService = $this->createMock(SpecimenImportService::class);
        $specimenImportService->expects($this->never())->method('importByEntityData');
        $specimenImportService->expects($this->never())->method('importByUuid');

        $logger->expects($this->never())->method('warning');
        $logger->expects($this->never())->method('error');

        $response = $controller->importWebhook(
            $request,
            $logger,
            $easydbApiService,
            $specimenImportService,
            $translator
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Webhook processed: 0 of 1 objects imported successfully (1 skipped)', $response->getContent());
        $this->assertStringNotContainsString('Errors:', $response->getContent());
    }

    public function testImportWebhookHandlesInvalidJson(): void
    {
        $controller = new ImportExportManagementController();

        $request = new Request([], [], [], [], [], [], '{invalid_json}');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $easydbApiService = $this->createMock(EasydbApiService::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $specimenImportService = $this->createMock(SpecimenImportService::class);

        $response = $controller->importWebhook(
            $request,
            $logger,
            $easydbApiService,
            $specimenImportService,
            $translator
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid webhook payload', $response->getContent());
    }
}
