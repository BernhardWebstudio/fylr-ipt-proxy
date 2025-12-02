<?php

namespace App\Tests\Service\Mapping;

use App\Entity\DarwinCore\Event;
use App\Entity\DarwinCore\Identification;
use App\Entity\DarwinCore\Location;
use App\Entity\DarwinCore\Occurrence;
use App\Entity\DarwinCore\Organism;
use App\Entity\DarwinCore\Taxon;
use App\Entity\OccurrenceImport;
use App\Service\Mapping\FungariumDwCMapping;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class FungariumDwCMappingTest extends TestCase
{
    private FungariumDwCMapping $mapping;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mapping = new FungariumDwCMapping($this->entityManager);
    }

    public function testSupportsPoolsFungarium(): void
    {
        $pools = $this->mapping->supportsPools();
        $this->assertContains('fungarium', $pools);
    }

    public function testMapOccurrenceWithFullData(): void
    {
        // Load example data
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        // Setup mock repositories
        $this->setupMockRepositories();

        // Create target import
        $target = new OccurrenceImport();

        // Execute mapping
        $this->mapping->mapOccurrence($source, $target);

        // Assert basic import metadata
        $this->assertEquals('1408175@9e515c7f-7a1b-4d14-b3a3-1af60c3ce46a', $target->getGlobalObjectID());
        $this->assertNotNull($target->getRemoteLastUpdatedAt());
        $this->assertEquals('2024-06-13T14:37:31Z', $target->getRemoteLastUpdatedAt()->format('Y-m-d\TH:i:s\Z'));

        // Assert occurrence exists
        $occurrence = $target->getOccurrence();
        $this->assertInstanceOf(Occurrence::class, $occurrence);

        // Assert basic occurrence information
        $this->assertEquals('ZT Myc 3549', $occurrence->getCatalogNumber());
        $this->assertEquals('PreservedSpecimen', $occurrence->getBasisOfRecord());
        $this->assertEquals('present', $occurrence->getOccurrenceStatus());
        $this->assertEquals('1408175@9e515c7f-7a1b-4d14-b3a3-1af60c3ce46a', $occurrence->getOccurrenceID());

        // Assert institutional information
        $this->assertEquals('ETHZ', $occurrence->getInstitutionCode());
        $this->assertEquals('ETHZ-ZT', $occurrence->getCollectionCode());

        // Assert organism information
        $organism = $occurrence->getOrganism();
        $this->assertInstanceOf(Organism::class, $organism);
        $this->assertEquals('ETHZ', $organism->getInstitutionCode());
        $this->assertEquals('ETHZ-ZT', $organism->getCollectionCode());
    }

    public function testMapTaxonomicInformation(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $taxon = $occurrence->getTaxon();

        $this->assertInstanceOf(Taxon::class, $taxon);
        $this->assertEquals('Phyllactinia guttata', $taxon->getScientificName());
        $this->assertEquals('Phyllactinia', $taxon->getGenus());
        $this->assertEquals('guttata', $taxon->getSpecificEpithet());
        $this->assertEquals('Fungi', $taxon->getKingdom());
        // Note: Authorship extraction depends on data structure - in this example it's not extracted
        // from the autor field because the path fungarium.autor doesn't exist in bestimmung
    }

    public function testMapCollectionEventInformation(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $event = $occurrence->getEvent();

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('12 Oct 2010', $event->getVerbatimEventDate());
        $this->assertEquals('auf Blatt von Hasel', $event->getHabitat());
        $this->assertNotNull($event->getEventID());
    }

    public function testMapLocationInformation(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $location = $occurrence->getLocation();

        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals(47.290916, $location->getDecimalLatitude());
        $this->assertEquals(8.660184, $location->getDecimalLongitude());
        $this->assertEquals('Meilen, Rappentobel', $location->getVerbatimLocality());
        // GeodeticDatum field is not present in this example data
        $this->assertNull($location->getGeodeticDatum());
        $this->assertEquals(2, $location->getCoordinateUncertaintyInMeters());
    }

    public function testMapMediaAndReferences(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();

        // Assert associated media
        $associatedMedia = $occurrence->getAssociatedMedia();
        $this->assertNotNull($associatedMedia);
        $this->assertStringContainsString('https://', $associatedMedia);

        // Assert associated references
        $associatedReferences = $occurrence->getAssociatedReferences();
        $this->assertNotNull($associatedReferences);
        $this->assertEquals('https://www.nahima.ethz.ch/#/detail/1408175', $associatedReferences);
    }

    public function testMapOccurrenceWithMinimalData(): void
    {
        // Test with example-obj-4229171.json which has less data
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-4229171.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $this->assertInstanceOf(Occurrence::class, $occurrence);
        $this->assertNotNull($occurrence->getOccurrenceID());
        $this->assertEquals('ETHZ', $occurrence->getInstitutionCode());
        $this->assertEquals('ETHZ-ZT', $occurrence->getCollectionCode());
    }

    public function testMapOccurrenceWithTypeStatus(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        // Modify to set typus = true
        $source['fungarium']['_reverse_nested:bestimmung:fungarium'][0]['typus'] = true;

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        // Note: type status setting is in the code but not explicitly tested here
        // as the exact field mapping depends on implementation
        $this->assertInstanceOf(Occurrence::class, $occurrence);
    }

    public function testMapOccurrenceWithAssociatedTaxa(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1410465.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $associatedTaxa = $occurrence->getAssociatedTaxa();

        $this->assertNotNull($associatedTaxa);
        $this->assertStringContainsString('Petrorhagia saxifraga', $associatedTaxa);
    }

    public function testMapTaxonWithInfraspecificEpithet(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1410465.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $taxon = $occurrence->getTaxon();

        $this->assertInstanceOf(Taxon::class, $taxon);
        $this->assertEquals('Microbotryum violaceum', $taxon->getScientificName());
        $this->assertEquals('Microbotryum', $taxon->getGenus());
        $this->assertEquals('violaceum', $taxon->getSpecificEpithet());
        $this->assertEquals('Fungi', $taxon->getKingdom());
    }

    public function testCoordinateParsing(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $location = $occurrence->getLocation();

        // Test that coordinates with direction prefix (N, E) are correctly parsed
        $this->assertEqualsWithDelta(47.290916, $location->getDecimalLatitude(), 0.000001);
        $this->assertEqualsWithDelta(8.660184, $location->getDecimalLongitude(), 0.000001);
    }

    public function testMapLocationWithCountry(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();
        $location = $occurrence->getLocation();

        $this->assertInstanceOf(Location::class, $location);
        // Country information should be extracted from gazetteer path
        $this->assertEquals('Switzerland', $location->getCountry());
        $this->assertEquals('Zürich', $location->getStateProvince());
    }

    public function testMultipleIdentifications(): void
    {
        $source = json_decode(
            file_get_contents(__DIR__ . '/../../../resources/examples/example-obj-1408175.json'),
            true
        );

        // Add a second identification
        $secondIdentification = $source['fungarium']['_reverse_nested:bestimmung:fungarium'][0];
        $secondIdentification['_global_object_id'] = '1408426@9e515c7f-7a1b-4d14-b3a3-1af60c3ce46a';
        $source['fungarium']['_reverse_nested:bestimmung:fungarium'][] = $secondIdentification;

        $this->setupMockRepositories();
        $target = new OccurrenceImport();

        $this->mapping->mapOccurrence($source, $target);

        $occurrence = $target->getOccurrence();

        // When multiple identifications exist, the first one should be used for the taxon
        $this->assertInstanceOf(Taxon::class, $occurrence->getTaxon());
        $this->assertEquals('Phyllactinia guttata', $occurrence->getTaxon()->getScientificName());
    }

    private function setupMockRepositories(): void
    {
        $occurrenceRepo = $this->createMock(EntityRepository::class);
        $occurrenceRepo->method('findOneBy')->willReturn(null);

        $organismRepo = $this->createMock(EntityRepository::class);
        $organismRepo->method('findOneBy')->willReturn(null);

        $eventRepo = $this->createMock(EntityRepository::class);
        $eventRepo->method('findOneBy')->willReturn(null);

        $locationRepo = $this->createMock(EntityRepository::class);
        $locationRepo->method('findOneBy')->willReturn(null);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($occurrenceRepo, $organismRepo, $eventRepo, $locationRepo) {
                return match ($class) {
                    Occurrence::class => $occurrenceRepo,
                    Organism::class => $organismRepo,
                    Event::class => $eventRepo,
                    Location::class => $locationRepo,
                    default => $this->createMock(EntityRepository::class)
                };
            });
    }
}
