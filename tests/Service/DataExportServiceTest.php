<?php

namespace App\Tests\Service;

use App\Entity\DarwinCore\Occurrence;
use App\Entity\OccurrenceImport;
use App\Service\DataExportService;
use PHPUnit\Framework\TestCase;

class DataExportServiceTest extends TestCase
{
    private DataExportService $service;

    protected function setUp(): void
    {
        $this->service = new DataExportService();
    }

    public function testConvertToCsvWithEmptyData(): void
    {
        $this->assertSame('', $this->service->convertToCsv([]));
    }

    public function testConvertToCsvGeneratesHeadersAndRows(): void
    {
        $occurrenceImport1 = new OccurrenceImport();
        $occurrenceImport1->setGlobalObjectID('gid-1');
        $occurrenceImport1->setObjectType('specimen');
        $occurrence1 = new Occurrence();
        $occurrence1->setOccurrenceID('occ-1');
        $occurrenceImport1->setOccurrence($occurrence1);

        $occurrenceImport2 = new OccurrenceImport();
        $occurrenceImport2->setGlobalObjectID('gid-2');
        $occurrenceImport2->setObjectType('specimen');
        $occurrence2 = new Occurrence();
        $occurrence2->setOccurrenceID('occ-2');
        $occurrenceImport2->setOccurrence($occurrence2);

        $csv = $this->service->convertToCsv([$occurrenceImport1, $occurrenceImport2]);

        $this->assertNotEmpty($csv);
        $lines = array_filter(explode("\n", trim($csv)));
        $this->assertGreaterThanOrEqual(3, count($lines)); // header + 2 rows

        $header = str_getcsv($lines[0], escape: '\\');
        $this->assertContains('globalObjectID', $header);
        $this->assertContains('objectType', $header);
        $this->assertContains('Occurrence.occurrenceID', $header);

        $row1 = str_getcsv($lines[1], escape: '\\');
        $row2 = str_getcsv($lines[2], escape: '\\');

        $gidIndex = array_search('globalObjectID', $header);
        $this->assertSame('gid-1', $row1[$gidIndex]);
        $this->assertSame('gid-2', $row2[$gidIndex]);
    }

    public function testStreamCsvCallsOnBatchProcessed(): void
    {
        $item1 = new OccurrenceImport();
        $item1->setGlobalObjectID('gid-batch-1');
        $occ1 = new Occurrence();
        $occ1->setOccurrenceID('occ-b1');
        $item1->setOccurrence($occ1);

        $item2 = new OccurrenceImport();
        $item2->setGlobalObjectID('gid-batch-2');
        $occ2 = new Occurrence();
        $occ2->setOccurrenceID('occ-b2');
        $item2->setOccurrence($occ2);

        $batches = [[$item1], [$item2]];

        $output = fopen('php://temp', 'r+');
        $batchCount = 0;

        $this->service->streamCsv($batches, $output, function () use (&$batchCount) {
            $batchCount++;
        });

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $this->assertSame(2, $batchCount);
        $this->assertStringContainsString('gid-batch-1', $content);
        $this->assertStringContainsString('gid-batch-2', $content);
    }

    public function testStreamJsonGeneratesValidJson(): void
    {
        $item = new OccurrenceImport();
        $item->setGlobalObjectID('gid-json');
        $occ = new Occurrence();
        $occ->setOccurrenceID('occ-json');
        $item->setOccurrence($occ);

        $output = fopen('php://temp', 'r+');
        $this->service->streamJson([[$item]], $output);

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('gid-json', $decoded[0]['globalObjectID']);
    }

    public function testStreamXmlGeneratesValidXml(): void
    {
        $item = new OccurrenceImport();
        $item->setGlobalObjectID('gid-xml');
        $occ = new Occurrence();
        $occ->setOccurrenceID('occ-xml');
        $item->setOccurrence($occ);

        $output = fopen('php://temp', 'r+');
        $this->service->streamXml([[$item]], $output);

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);
        $this->assertSame('occurrences', $xml->getName());
        $this->assertCount(1, $xml->occurrence);
        $this->assertSame('gid-xml', (string) $xml->occurrence->globalObjectID);
    }
}
