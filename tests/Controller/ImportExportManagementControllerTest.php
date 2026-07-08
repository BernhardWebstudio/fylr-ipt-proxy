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
        $this->assertStringContainsString('Webhook processed: 0 of 1 objects imported successfully', $response->getContent());
        $this->assertStringNotContainsString('Errors:', $response->getContent());
    }
}
