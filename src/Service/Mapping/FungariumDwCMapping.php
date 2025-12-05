<?php

namespace App\Service\Mapping;

use App\Entity\DarwinCore\Occurrence;
use App\Entity\DarwinCore\Taxon;
use App\Entity\DarwinCore\Event;
use App\Entity\DarwinCore\Location;
use App\Entity\DarwinCore\Identification;
use App\Entity\OccurrenceImport;
use Doctrine\ORM\EntityManagerInterface;

class FungariumDwCMapping implements EasydbDwCMappingInterface
{
    private const ETHZ_INSTITUTION_CODE = 'ETHZ';
    private const ETHZ_INSTITUTION_ID = 'adee7883-8290-4050-b643-8e2816f92e9a';
    private const ETHZ_COLLECTION_CODE = 'ZT';
    private const ETHZ_COLLECTION_ID = 'bdb3660d-2a20-4bad-8993-af16c5fbf502';

    private const UZH_INSTITUTION_CODE = 'UZH:Z';
    private const UZH_INSTITUTION_ID = '5b487a79-76ef-4615-93d9-f4ea25a40c33';
    private const UZH_COLLECTION_CODE = 'Z';
    private const UZH_COLLECTION_ID = '322ce107-3156-4420-8a2b-7f17efeaa472';

    public function __construct(private EntityManagerInterface $entityManager) {}
    public function mapOccurrence(array $source, OccurrenceImport $target): void
    {
        // Set basic import metadata
        $target->setGlobalObjectID($source["_global_object_id"]);
        $target->setRemoteLastUpdatedAt(new \DateTimeImmutable($source["_last_modified"] ?? $source["_created"]));

        // Resolve or create the occurrence entity by unique occurrenceID
        $occurrenceId = $source["_global_object_id"];
        $occurrence = $target->getOccurrence();
        if (!$occurrence) {
            $occurrence = $this->entityManager->getRepository(Occurrence::class)
                ->findOneBy(['occurrenceID' => $occurrenceId]);
        }
        if (!$occurrence) {
            $occurrence = new Occurrence();
            $occurrence->setOccurrenceID($occurrenceId);
        }

        // Resolve or create organism by unique organismID
        if (!$occurrence->getOrganism()) {
            $organismId = $source["_global_object_id"];
            $organism = $this->entityManager->getRepository(\App\Entity\DarwinCore\Organism::class)
                ->findOneBy(['organismID' => $organismId]);
            if (!$organism) {
                $organism = new \App\Entity\DarwinCore\Organism();
                $organism->setOrganismID($organismId);
            }
            $occurrence->setOrganism($organism);
        }

        // Map basic occurrence information
        $this->mapBasicOccurrenceInfo($source, $occurrence);

        // Map taxonomic information
        $this->mapTaxonomicInfo($source, $occurrence);

        // Map collection event information
        $this->mapCollectionEventInfo($source, $occurrence);

        // Map location information
        $this->mapLocationInfo($source, $occurrence);

        // Set institutional information
        $this->mapInstitutionalInfo($source, $occurrence);

        // Map media
        $this->mapMedia($source, $occurrence);

        // Associate the occurrence with the import
        $target->setOccurrence($occurrence);
    }

    private function mapBasicOccurrenceInfo(array $source, Occurrence $occurrence): void
    {
        // Extract catalog number from fungarium.zugangsnummer
        if (isset($source["fungarium"]["zugangsnummer"])) {
            $occurrence->setCatalogNumber($source["fungarium"]["zugangsnummer"]);
        }

        // Set basis of record
        $occurrence->setBasisOfRecord("PreservedSpecimen");

        // Set occurrence status
        $occurrence->setOccurrenceStatus("present");
    }

    private function mapTaxonomicInfo(array $source, Occurrence $occurrence): void
    {
        // Look for taxonomic information in bestimmung (identification) reverse nested
        $bestimmungen = $source["fungarium"]["_reverse_nested:bestimmung:fungarium"] ?? [];

        if (!empty($bestimmungen)) {
            $bestimmung = $bestimmungen[0]; // Take the first identification

            // Create taxon entity
            $taxon = $this->parseTaxonFromIdentification($bestimmung);

            // Set type status if specimen is a type
            if (isset($bestimmung["typus"]) && $bestimmung["typus"] === true) {
                $identificationId = $bestimmung["_global_object_id"] ?? uniqid('id_');
                $identification = $this->entityManager->getRepository(Identification::class)
                    ->findOneBy(['identificationID' => $identificationId]);
                if (!$identification) {
                    $identification = new Identification();
                    $identification->setIdentificationID($identificationId);
                }
                $identification->setTypeStatus("Type");
                $occurrence->setIdentification($identification);
            }

            $occurrence->setTaxon($taxon);

            if (count($bestimmungen) > 1) {
                // TODO: add scientificNames of other bestimmungen as previous identification to organism
                $previousIds = [];
                for ($i = 1; $i < count($bestimmungen); $i++) {
                    $prevBestimmung = $bestimmungen[$i];
                    $prevTaxon = $this->parseTaxonFromIdentification($prevBestimmung);
                    $previousIds[] = $prevTaxon->getScientificName();
                }
                $occurrence->getOrganism()->setPreviousIdentifications(implode(' | ', $previousIds));
            }
        }
    }

    private function parseTaxonFromIdentification(array $bestimmung): Taxon
    {

        // Try to load existing Taxon by taxonID
        $taxonId = $bestimmung["_global_object_id"] ?? uniqid('taxon_');
        $taxon = $this->entityManager->getRepository(Taxon::class)
            ->findOneBy(['taxonID' => $taxonId]);
        if (!$taxon) {
            $taxon = new Taxon();
            $taxon->setTaxonID($taxonId);
        }

        // Extract scientific name from taxonname or taxonnametrans
        $scientificName = null;
        if (isset($bestimmung["taxonname"]["_standard"]["1"]["text"]["en-US"])) {
            $scientificName = $bestimmung["taxonname"]["_standard"]["1"]["text"]["en-US"];
        } elseif (isset($bestimmung["taxonname"]["_standard"]["1"]["text"]["de-DE"])) {
            $scientificName = $bestimmung["taxonname"]["_standard"]["1"]["text"]["de-DE"];
        } elseif (isset($bestimmung["taxonnametrans"])) {
            $scientificName = $bestimmung["taxonnametrans"];
        }

        if ($scientificName) {
            $taxon->setScientificName($scientificName);

            // Extract genus from scientific name or genus field
            if (isset($bestimmung["genus"]["_standard"]["1"]["text"]["en-US"])) {
                $taxon->setGenus($bestimmung["genus"]["_standard"]["1"]["text"]["en-US"]);
            } elseif (isset($bestimmung["genus"]["_standard"]["1"]["text"]["de-DE"])) {
                $taxon->setGenus($bestimmung["genus"]["_standard"]["1"]["text"]["de-DE"]);
            } else {
                // Try to extract genus from scientific name
                $parts = explode(' ', $scientificName);
                if (count($parts) >= 1) {
                    $taxon->setGenus($parts[0]);
                }
            }

            // Extract specific epithet from scientific name
            $parts = explode(' ', $scientificName);
            if (count($parts) >= 2) {
                $taxon->setSpecificEpithet($parts[1]);
            }
        }

        // Set taxonomic authority if available
        if (isset($bestimmung["fungarium"]["autor"]["_standard"]["1"]["text"]["en-US"])) {
            $taxon->setScientificNameAuthorship($bestimmung["fungarium"]["autor"]["_standard"]["1"]["text"]["en-US"]);
        } elseif (isset($bestimmung["fungarium"]["autor"]["_standard"]["1"]["text"]["de-DE"])) {
            $taxon->setScientificNameAuthorship($bestimmung["fungarium"]["autor"]["_standard"]["1"]["text"]["de-DE"]);
        } elseif (isset($bestimmung["taxonnametrans"])) {
            $taxon->setScientificNameAuthorship($this->extractAuthorship($bestimmung["taxonnametrans"]));
        }

        // Set specific epithet
        if (isset($bestimmung["art"]["_standard"]["1"]["text"]["en-US"])) {
            $taxon->setSpecificEpithet($bestimmung["art"]["_standard"]["1"]["text"]["en-US"]);
        } elseif (isset($bestimmung["art"]["_standard"]["1"]["text"]["de-DE"])) {
            $taxon->setSpecificEpithet($bestimmung["art"]["_standard"]["1"]["text"]["de-DE"]);
        } elseif (!$taxon->getSpecificEpithet()) {
            $taxon->setSpecificEpithet($bestimmung["arttrans"] ?? null);
        }

        // set infraspecific epithet if available
        if (isset($bestimmung["infraspezifischestaxon"]["_standard"]["1"]["text"]["en-US"])) {
            $taxon->setInfraspecificEpithet($bestimmung["infraspezifischestaxon"]["_standard"]["1"]["text"]["en-US"]);
        } elseif (isset($bestimmung["infraspezifischestaxon"]["_standard"]["1"]["text"]["de-DE"])) {
            $taxon->setInfraspecificEpithet($bestimmung["infraspezifischestaxon"]["_standard"]["1"]["text"]["de-DE"]);
        } elseif (!$taxon->getInfraspecificEpithet()) {
            $taxon->setInfraspecificEpithet($bestimmung["infraspezifischestaxontrans"] ?? null);
        }

        // Set infraspecific rank
        if (isset($bestimmung["infraspezifischerrang"]["_standard"]["1"]["text"]["en-US"])) {
            $taxon->setTaxonRank($bestimmung["infraspezifischerrang"]["_standard"]["1"]["text"]["en-US"]);
        } elseif (isset($bestimmung["infraspezifischerrang"]["_standard"]["1"]["text"]["de-DE"])) {
            $taxon->setTaxonRank($bestimmung["infraspezifischerrang"]["_standard"]["1"]["text"]["de-DE"]);
        } elseif (!$taxon->getTaxonRank()) {
            $taxon->setTaxonRank($bestimmung["infraspezifischerrangtrans"] ?? null);
        }

        // Set kingdom (fungi for fungarium)
        $taxon->setKingdom("Fungi");

        return $taxon;
    }

    private function mapCollectionEventInfo(array $source, Occurrence $occurrence): void
    {
        // Look for collection information in aufsammlung reverse nested
        $aufsammlungen = $source["fungarium"]["_reverse_nested:aufsammlung:fungarium"] ?? [];

        if (!empty($aufsammlungen)) {
            $aufsammlung = $aufsammlungen[0]; // Take the first collection event

            // Resolve or create event entity by unique eventID
            $eventId = $aufsammlung["_global_object_id"] ?? uniqid('event_');
            $event = $this->entityManager->getRepository(Event::class)
                ->findOneBy(['eventID' => $eventId]);
            if (!$event) {
                $event = new Event();
                $event->setEventID($eventId);
            }

            // Map collection date
            if (isset($aufsammlung["sammeldatum"])) {
                $dateInfo = $aufsammlung["sammeldatum"];
                if (isset($dateInfo["value"])) {
                    $parsedDate = $this->parseEventDate($dateInfo["value"]);
                    if ($parsedDate) {
                        $event->setEventDate($parsedDate['iso_date']);
                        $event->setYear($parsedDate['year']);
                        $event->setMonth($parsedDate['month']);
                    }
                }
            }
            $event->setVerbatimEventDate($aufsammlung["sammeldatumtrans"] ?? null);

            // Map habitat information
            if (isset($aufsammlung["neuhabitat"]["de-DE"])) {
                $event->setHabitat($aufsammlung["neuhabitat"]["de-DE"]);
            } elseif (isset($aufsammlung["habitattrans"])) {
                $event->setHabitat($aufsammlung["habitattrans"]);
            }

            // Map collector information
            $sammler = $aufsammlung["_nested:aufsammlung__sammler"] ?? [];
            if (!empty($sammler)) {
                $collectors = [];
                foreach ($sammler as $sammlerInfo) {
                    if (isset($sammlerInfo["sammler"]["_standard"]["1"]["text"]["en-US"])) {
                        $collectors[] = $sammlerInfo["sammler"]["_standard"]["1"]["text"]["en-US"];
                    } elseif (isset($sammlerInfo["sammler"]["_standard"]["1"]["text"]["de-DE"])) {
                        $collectors[] = $sammlerInfo["sammler"]["_standard"]["1"]["text"]["de-DE"];
                    }
                }
                if (!empty($collectors)) {
                    $occurrence->setRecordedBy(implode(' | ', $collectors));
                }
            } else {
                $occurrence->setRecordedBy($aufsammlung["sammlertrans"] ?? null);
            }

            // Set associated taxa
            $occurrence->setAssociatedTaxa($aufsammlung["traegerorganismus"]["de-DE"] ?? $aufsammlung["traegerorganismustrans"] ?? null);

            $occurrence->setEvent($event);
        }
    }

    private function mapLocationInfo(array $source, Occurrence $occurrence): void
    {
        // Look for location information in aufsammlung.sammelort
        $aufsammlungen = $source["fungarium"]["_reverse_nested:aufsammlung:fungarium"] ?? [];

        if (!empty($aufsammlungen)) {
            $aufsammlung = $aufsammlungen[0];

            // Resolve or create Location entity by unique locationID
            $location = null;

            if (isset($aufsammlung["sammelort"])) {
                $sammelort = $aufsammlung["sammelort"];
                $resolvedLocationId = $sammelort["_global_object_id"] ?? null;
                if ($resolvedLocationId) {
                    $location = $this->entityManager->getRepository(Location::class)
                        ->findOneBy(['locationID' => $resolvedLocationId]);
                }
                if (!$location) {
                    $location = new Location();
                    $location->setLocationID($resolvedLocationId ?? uniqid('loc_'));
                }

                // Extract country information
                if (isset($sammelort["_standard"]["1"]["text"]["en-US"])) {
                    $locationText = $sammelort["_standard"]["1"]["text"]["en-US"];
                } elseif (isset($sammelort["_standard"]["1"]["text"]["de-DE"])) {
                    $locationText = $sammelort["_standard"]["1"]["text"]["de-DE"];
                }

                // Set higher geography from path if available
                if (isset($sammelort["_path"])) {
                    $pathElements = [];
                    foreach ($sammelort["_path"] as $pathItem) {
                        $pathElement = null;
                        if (isset($pathItem["_standard"]["1"]["text"]["en-US"])) {
                            $pathElement = $pathItem["_standard"]["1"]["text"]["en-US"];
                        } elseif (isset($pathItem["_standard"]["1"]["text"]["de-DE"])) {
                            $pathElement = $pathItem["_standard"]["1"]["text"]["de-DE"];
                        }

                        if ($pathElement) {
                            if (str_contains($pathElement, '(Kontinent)')) {
                                $location->setContinent(trim(str_replace('(Kontinent)', '', $pathElement)));
                            } else if (str_contains($pathElement, '(Land)')) {
                                $location->setCountry(trim(str_replace('(Land)', '', $pathElement)));
                            } else if (str_contains($pathElement, '(Kanton/Provinz/Bundesland)')) {
                                $location->setStateProvince(trim(str_replace('(Kanton/Provinz/Bundesland)', '', $pathElement)));
                            } else {
                                $pathElements[] = $pathElement;
                            }
                        }
                    }

                    if (!empty($pathElements)) {
                        $location->setHigherGeography(implode(' | ', $pathElements));
                    }
                }

                $location->setLocality($aufsammlung["lokalitaet"] ?? null);
            }

            if (!$location) {
                $location = new Location();
                $location->setLocationID(uniqid('loc_'));
            }

            $location->setGeodeticDatum(
                $aufsammlung["datumsformatgeodaeischeskooordinatensystem"]["_standard"]["en-US"] ?? null
            );

            $location->setCoordinateUncertaintyInMeters(
                $aufsammlung["fehlerradius"] ?? null
            );

            $location->setDecimalLatitude($this->parseCoordinate($aufsammlung["breitengraddezimal"] ?? null));
            $location->setDecimalLongitude($this->parseCoordinate($aufsammlung["langngraddzimal"] ?? $aufsammlung['laengengraddezimal'] ?? null));

            $location->setVerbatimLocality(implode(" | ", array_filter(array_map(function ($coll) {
                return $coll["lokalitaettrans"] ?? null;
            }, $aufsammlungen))));

            $occurrence->setLocation($location);
        }
    }

    private function mapInstitutionalInfo(array $source, Occurrence $occurrence): void
    {
        $barcode = $source["fungarium"]["zugangsnummer"] ?? null;

        $institutionCode = self::ETHZ_INSTITUTION_CODE;
        $institutionId = self::ETHZ_INSTITUTION_ID;
        $collectionCode = self::ETHZ_COLLECTION_CODE;
        $collectionId = self::ETHZ_COLLECTION_ID;

        if ($barcode) {
            $normalizedBarcode = strtoupper(trim($barcode));
            if (str_starts_with($normalizedBarcode, 'Z MYC')) {
                $institutionCode = self::UZH_INSTITUTION_CODE;
                $institutionId = self::UZH_INSTITUTION_ID;
                $collectionCode = self::UZH_COLLECTION_CODE;
                $collectionId = self::UZH_COLLECTION_ID;
            }
        }

        $occurrence->setInstitutionCode($institutionCode);
        $occurrence->setInstitutionID($institutionId);
        $occurrence->setCollectionCode($collectionCode);
        $occurrence->setCollectionID($collectionId);

        $organism = $occurrence->getOrganism();
        if ($organism) {
            $organism->setInstitutionCode($institutionCode);
            $organism->setInstitutionID($institutionId);
            $organism->setCollectionCode($collectionCode);
            $organism->setCollectionID($collectionId);
        }
    }

    private function mapMedia(array $source, Occurrence $occurrence): void
    {
        // Look for media information in media reverse nested
        $mediaItems = $source["fungarium"]["_reverse_nested:fungarium_mediaassetpublic:fungarium"] ?? [];

        $mediaUrls = [];
        foreach ($mediaItems as $mediaItem) {
            $publicEas = $mediaItem["mediaassetpublic"]["_standard"]["eas"] ?? [];
            if (empty($publicEas)) {
                continue; // Skip if no public eas found
            }

            $assets = array_merge(...array_values($publicEas));
            if (empty($assets)) {
                continue; // Skip if no assets found
            }

            foreach ($assets as $asset) {
                $versions = $asset["versions"];
                $mediaUrls[] = $versions["original"]["download_url"] ?? reset($versions)["download_url"];
            }
        }
        $occurrence->setAssociatedMedia(implode(' | ', $mediaUrls));

        $occurrence->setAssociatedReferences("https://www.nahima.ethz.ch/#/detail/" . $source["_system_object_id"]);
    }

    private function extractAuthorship(string $scientificName): string
    {
        // Simple regex to extract authorship (everything after the species name)
        if (preg_match('/^[A-Z][a-z]+ [a-z]+ (.+)$/', $scientificName, $matches)) {
            return $matches[1];
        }

        // If it's just genus + author
        if (preg_match('/^[A-Z][a-z]+ (.+)$/', $scientificName, $matches)) {
            // Check if this looks like an author (contains typical abbreviations)
            $potential_author = $matches[1];
            if (preg_match('/[A-Z]/', $potential_author)) {
                return $potential_author;
            }
        }

        return '';
    }

    public function supportsPools(): array
    {
        // Return the types this mapping supports
        return ['fungarium'];
    }

    private function parseEventDate(string $dateValue): ?array
    {
        $dateValue = trim($dateValue);

        // Handle date ranges (e.g., "2023-05-15/2023-05-20" or "15.05.2023 - 20.05.2023")
        if (preg_match('/(.+?)\s*[-\/]\s*(.+)/', $dateValue, $matches)) {
            $startDate = $this->parseSingleDate($matches[1]);
            $endDate = $this->parseSingleDate($matches[2]);

            if ($startDate && $endDate) {
                return [
                    'iso_date' => $startDate['iso'] . '/' . $endDate['iso'],
                    'year' => $startDate['year'],
                    'month' => $startDate['month']
                ];
            }
        }

        // Handle single date
        $singleDate = $this->parseSingleDate($dateValue);
        if ($singleDate) {
            return [
                'iso_date' => $singleDate['iso'],
                'year' => $singleDate['year'],
                'month' => $singleDate['month']
            ];
        }

        return null;
    }

    private function parseSingleDate(string $dateValue): ?array
    {
        $dateValue = trim($dateValue);

        // ISO format: YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateValue, $matches)) {
            return [
                'iso' => $dateValue,
                'year' => $matches[1],
                'month' => $matches[2]
            ];
        }

        // European format: DD.MM.YYYY
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dateValue, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return [
                'iso' => "$year-$month-$day",
                'year' => $year,
                'month' => $month
            ];
        }

        // American format: MM/DD/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateValue, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return [
                'iso' => "$year-$month-$day",
                'year' => $year,
                'month' => $month
            ];
        }

        // Year and month only: YYYY-MM or MM.YYYY
        if (preg_match('/^(\d{4})-(\d{2})$/', $dateValue, $matches)) {
            return [
                'iso' => $dateValue,
                'year' => $matches[1],
                'month' => $matches[2]
            ];
        }

        if (preg_match('/^(\d{1,2})\.(\d{4})$/', $dateValue, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $year = $matches[2];
            return [
                'iso' => "$year-$month",
                'year' => $year,
                'month' => $month
            ];
        }

        // Year only: YYYY
        if (preg_match('/^(\d{4})$/', $dateValue, $matches)) {
            return [
                'iso' => $dateValue,
                'year' => $matches[1],
                'month' => null
            ];
        }

        // Try to parse with DateTime for other formats
        try {
            $date = new \DateTime($dateValue);
            return [
                'iso' => $date->format('Y-m-d'),
                'year' => $date->format('Y'),
                'month' => $date->format('m')
            ];
        } catch (\Exception $e) {
            // If all parsing fails, return null
            return null;
        }
    }

    /**
     * Parse coordinate string that may contain direction prefix (N/S/E/W) and degree symbol.
     * Examples: "E 8.660184°", "N 47.290916°", "8.660184", "-47.290916"
     *
     * @param string|null $coordinate The coordinate string to parse
     * @return float|null The parsed coordinate as a float, or null if parsing fails
     */
    private function parseCoordinate(?string $coordinate): ?float
    {
        if (!$coordinate) {
            return null;
        }

        // Remove whitespace and degree symbol
        $coordinate = trim($coordinate);
        $coordinate = str_replace('°', '', $coordinate);
        $coordinate = trim($coordinate);

        // Check for direction prefix (N/S/E/W)
        $direction = null;
        if (preg_match('/^([NSEW])\s*(.+)$/', $coordinate, $matches)) {
            $direction = $matches[1];
            $coordinate = $matches[2];
        }

        // Try to extract the numeric value
        $numericValue = floatval($coordinate);

        if ($numericValue == 0 && $coordinate !== '0' && $coordinate !== '0.0') {
            // Parsing failed
            return null;
        }

        // Apply direction: S and W are negative
        if ($direction === 'S' || $direction === 'W') {
            $numericValue = -abs($numericValue);
        } else {
            $numericValue = abs($numericValue);
        }

        return $numericValue;
    }
}
