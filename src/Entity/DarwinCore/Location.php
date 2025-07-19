<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_location')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'locationID', type: 'string')]
    private string $locationID;

    #[ORM\Column(name: 'higherGeographyID', type: 'string', nullable: true)]
    private ?string $higherGeographyID = null;

    #[ORM\Column(name: 'higherGeography', type: 'string', nullable: true)]
    private ?string $higherGeography = null;

    #[ORM\Column(name: 'continent', type: 'string', nullable: true)]
    private ?string $continent = null;

    #[ORM\Column(name: 'waterBody', type: 'string', nullable: true)]
    private ?string $waterBody = null;

    #[ORM\Column(name: 'islandGroup', type: 'string', nullable: true)]
    private ?string $islandGroup = null;

    #[ORM\Column(name: 'island', type: 'string', nullable: true)]
    private ?string $island = null;

    #[ORM\Column(name: 'country', type: 'string', nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'countryCode', type: 'string', nullable: true)]
    private ?string $countryCode = null;

    #[ORM\Column(name: 'stateProvince', type: 'string', nullable: true)]
    private ?string $stateProvince = null;

    #[ORM\Column(name: 'county', type: 'string', nullable: true)]
    private ?string $county = null;

    #[ORM\Column(name: 'municipality', type: 'string', nullable: true)]
    private ?string $municipality = null;

    #[ORM\Column(name: 'locality', type: 'string', nullable: true)]
    private ?string $locality = null;

    #[ORM\Column(name: 'verbatimLocality', type: 'string', nullable: true)]
    private ?string $verbatimLocality = null;

    #[ORM\Column(name: 'minimumElevationInMeters', type: 'float', nullable: true)]
    private ?float $minimumElevationInMeters = null;

    #[ORM\Column(name: 'maximumElevationInMeters', type: 'float', nullable: true)]
    private ?float $maximumElevationInMeters = null;

    #[ORM\Column(name: 'verbatimElevation', type: 'string', nullable: true)]
    private ?string $verbatimElevation = null;

    #[ORM\Column(name: 'verticalDatum', type: 'string', nullable: true)]
    private ?string $verticalDatum = null;

    #[ORM\Column(name: 'minimumDepthInMeters', type: 'float', nullable: true)]
    private ?float $minimumDepthInMeters = null;

    #[ORM\Column(name: 'maximumDepthInMeters', type: 'float', nullable: true)]
    private ?float $maximumDepthInMeters = null;

    #[ORM\Column(name: 'verbatimDepth', type: 'string', nullable: true)]
    private ?string $verbatimDepth = null;

    #[ORM\Column(name: 'minimumDistanceAboveSurfaceInMeters', type: 'float', nullable: true)]
    private ?float $minimumDistanceAboveSurfaceInMeters = null;

    #[ORM\Column(name: 'maximumDistanceAboveSurfaceInMeters', type: 'float', nullable: true)]
    private ?float $maximumDistanceAboveSurfaceInMeters = null;

    #[ORM\Column(name: 'locationAccordingTo', type: 'string', nullable: true)]
    private ?string $locationAccordingTo = null;

    #[ORM\Column(name: 'locationRemarks', type: 'string', nullable: true)]
    private ?string $locationRemarks = null;

    #[ORM\Column(name: 'decimalLatitude', type: 'float', nullable: true)]
    private ?float $decimalLatitude = null;

    #[ORM\Column(name: 'decimalLongitude', type: 'float', nullable: true)]
    private ?float $decimalLongitude = null;

    #[ORM\Column(name: 'geodeticDatum', type: 'string', nullable: true)]
    private ?string $geodeticDatum = null;

    #[ORM\Column(name: 'coordinateUncertaintyInMeters', type: 'float', nullable: true)]
    private ?float $coordinateUncertaintyInMeters = null;

    #[ORM\Column(name: 'coordinatePrecision', type: 'string', nullable: true)]
    private ?string $coordinatePrecision = null;

    #[ORM\Column(name: 'pointRadiusSpatialFit', type: 'string', nullable: true)]
    private ?string $pointRadiusSpatialFit = null;

    #[ORM\Column(name: 'verbatimCoordinates', type: 'string', nullable: true)]
    private ?string $verbatimCoordinates = null;

    #[ORM\Column(name: 'verbatimLatitude', type: 'string', nullable: true)]
    private ?string $verbatimLatitude = null;

    #[ORM\Column(name: 'verbatimLongitude', type: 'string', nullable: true)]
    private ?string $verbatimLongitude = null;

    #[ORM\Column(name: 'verbatimCoordinateSystem', type: 'string', nullable: true)]
    private ?string $verbatimCoordinateSystem = null;

    #[ORM\Column(name: 'verbatimSRS', type: 'string', nullable: true)]
    private ?string $verbatimSRS = null;

    #[ORM\Column(name: 'footprintWKT', type: 'string', nullable: true)]
    private ?string $footprintWKT = null;

    #[ORM\Column(name: 'footprintSRS', type: 'string', nullable: true)]
    private ?string $footprintSRS = null;

    #[ORM\Column(name: 'footprintSpatialFit', type: 'string', nullable: true)]
    private ?string $footprintSpatialFit = null;

    #[ORM\Column(name: 'georeferencedBy', type: 'string', nullable: true)]
    private ?string $georeferencedBy = null;

    #[ORM\Column(name: 'georeferencedDate', type: 'string', nullable: true)]
    private ?string $georeferencedDate = null;

    #[ORM\Column(name: 'georeferenceProtocol', type: 'string', nullable: true)]
    private ?string $georeferenceProtocol = null;

    #[ORM\Column(name: 'georeferenceSources', type: 'string', nullable: true)]
    private ?string $georeferenceSources = null;

    #[ORM\Column(name: 'georeferenceRemarks', type: 'string', nullable: true)]
    private ?string $georeferenceRemarks = null;

    #[ORM\Column(name: 'institutionID', type: 'string', nullable: true)]
    private ?string $institutionID = null;

    #[ORM\Column(name: 'collectionID', type: 'string', nullable: true)]
    private ?string $collectionID = null;

    #[ORM\Column(name: 'datasetID', type: 'string', nullable: true)]
    private ?string $datasetID = null;

    #[ORM\Column(name: 'institutionCode', type: 'string', nullable: true)]
    private ?string $institutionCode = null;

    #[ORM\Column(name: 'collectionCode', type: 'string', nullable: true)]
    private ?string $collectionCode = null;

    #[ORM\Column(name: 'datasetName', type: 'string', nullable: true)]
    private ?string $datasetName = null;

    #[ORM\Column(name: 'ownerInstitutionCode', type: 'string', nullable: true)]
    private ?string $ownerInstitutionCode = null;

    #[ORM\Column(name: 'basisOfRecord', type: 'string', nullable: true)]
    private ?string $basisOfRecord = null;

    #[ORM\Column(name: 'informationWithheld', type: 'string', nullable: true)]
    private ?string $informationWithheld = null;

    #[ORM\Column(name: 'dataGeneralizations', type: 'string', nullable: true)]
    private ?string $dataGeneralizations = null;

    #[ORM\Column(name: 'dynamicProperties', type: 'string', nullable: true)]
    private ?string $dynamicProperties = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocationID(): ?string
    {
        return $this->locationID;
    }

    public function setLocationID(string $locationID): static
    {
        $this->locationID = $locationID;
        return $this;
    }

    public function getHigherGeographyID(): ?string
    {
        return $this->higherGeographyID;
    }

    public function setHigherGeographyID(string $higherGeographyID): static
    {
        $this->higherGeographyID = $higherGeographyID;
        return $this;
    }

    public function getHigherGeography(): ?string
    {
        return $this->higherGeography;
    }

    public function setHigherGeography(string $higherGeography): static
    {
        $this->higherGeography = $higherGeography;
        return $this;
    }

    public function getContinent(): ?string
    {
        return $this->continent;
    }

    public function setContinent(string $continent): static
    {
        $this->continent = $continent;
        return $this;
    }

    public function getWaterBody(): ?string
    {
        return $this->waterBody;
    }

    public function setWaterBody(string $waterBody): static
    {
        $this->waterBody = $waterBody;
        return $this;
    }

    public function getIslandGroup(): ?string
    {
        return $this->islandGroup;
    }

    public function setIslandGroup(string $islandGroup): static
    {
        $this->islandGroup = $islandGroup;
        return $this;
    }

    public function getIsland(): ?string
    {
        return $this->island;
    }

    public function setIsland(string $island): static
    {
        $this->island = $island;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getStateProvince(): ?string
    {
        return $this->stateProvince;
    }

    public function setStateProvince(string $stateProvince): static
    {
        $this->stateProvince = $stateProvince;
        return $this;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function setCounty(string $county): static
    {
        $this->county = $county;
        return $this;
    }

    public function getMunicipality(): ?string
    {
        return $this->municipality;
    }

    public function setMunicipality(string $municipality): static
    {
        $this->municipality = $municipality;
        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(string $locality): static
    {
        $this->locality = $locality;
        return $this;
    }

    public function getVerbatimLocality(): ?string
    {
        return $this->verbatimLocality;
    }

    public function setVerbatimLocality(string $verbatimLocality): static
    {
        $this->verbatimLocality = $verbatimLocality;
        return $this;
    }

    public function getMinimumElevationInMeters(): ?float
    {
        return $this->minimumElevationInMeters;
    }

    public function setMinimumElevationInMeters(float $minimumElevationInMeters): static
    {
        $this->minimumElevationInMeters = $minimumElevationInMeters;
        return $this;
    }

    public function getMaximumElevationInMeters(): ?float
    {
        return $this->maximumElevationInMeters;
    }

    public function setMaximumElevationInMeters(float $maximumElevationInMeters): static
    {
        $this->maximumElevationInMeters = $maximumElevationInMeters;
        return $this;
    }

    public function getVerbatimElevation(): ?string
    {
        return $this->verbatimElevation;
    }

    public function setVerbatimElevation(string $verbatimElevation): static
    {
        $this->verbatimElevation = $verbatimElevation;
        return $this;
    }

    public function getVerticalDatum(): ?string
    {
        return $this->verticalDatum;
    }

    public function setVerticalDatum(string $verticalDatum): static
    {
        $this->verticalDatum = $verticalDatum;
        return $this;
    }

    public function getMinimumDepthInMeters(): ?float
    {
        return $this->minimumDepthInMeters;
    }

    public function setMinimumDepthInMeters(float $minimumDepthInMeters): static
    {
        $this->minimumDepthInMeters = $minimumDepthInMeters;
        return $this;
    }

    public function getMaximumDepthInMeters(): ?float
    {
        return $this->maximumDepthInMeters;
    }

    public function setMaximumDepthInMeters(float $maximumDepthInMeters): static
    {
        $this->maximumDepthInMeters = $maximumDepthInMeters;
        return $this;
    }

    public function getVerbatimDepth(): ?string
    {
        return $this->verbatimDepth;
    }

    public function setVerbatimDepth(string $verbatimDepth): static
    {
        $this->verbatimDepth = $verbatimDepth;
        return $this;
    }

    public function getMinimumDistanceAboveSurfaceInMeters(): ?float
    {
        return $this->minimumDistanceAboveSurfaceInMeters;
    }

    public function setMinimumDistanceAboveSurfaceInMeters(float $minimumDistanceAboveSurfaceInMeters): static
    {
        $this->minimumDistanceAboveSurfaceInMeters = $minimumDistanceAboveSurfaceInMeters;
        return $this;
    }

    public function getMaximumDistanceAboveSurfaceInMeters(): ?float
    {
        return $this->maximumDistanceAboveSurfaceInMeters;
    }

    public function setMaximumDistanceAboveSurfaceInMeters(float $maximumDistanceAboveSurfaceInMeters): static
    {
        $this->maximumDistanceAboveSurfaceInMeters = $maximumDistanceAboveSurfaceInMeters;
        return $this;
    }

    public function getLocationAccordingTo(): ?string
    {
        return $this->locationAccordingTo;
    }

    public function setLocationAccordingTo(string $locationAccordingTo): static
    {
        $this->locationAccordingTo = $locationAccordingTo;
        return $this;
    }

    public function getLocationRemarks(): ?string
    {
        return $this->locationRemarks;
    }

    public function setLocationRemarks(string $locationRemarks): static
    {
        $this->locationRemarks = $locationRemarks;
        return $this;
    }

    public function getDecimalLatitude(): ?float
    {
        return $this->decimalLatitude;
    }

    public function setDecimalLatitude(float $decimalLatitude): static
    {
        $this->decimalLatitude = $decimalLatitude;
        return $this;
    }

    public function getDecimalLongitude(): ?float
    {
        return $this->decimalLongitude;
    }

    public function setDecimalLongitude(float $decimalLongitude): static
    {
        $this->decimalLongitude = $decimalLongitude;
        return $this;
    }

    public function getGeodeticDatum(): ?string
    {
        return $this->geodeticDatum;
    }

    public function setGeodeticDatum(string $geodeticDatum): static
    {
        $this->geodeticDatum = $geodeticDatum;
        return $this;
    }

    public function getCoordinateUncertaintyInMeters(): ?float
    {
        return $this->coordinateUncertaintyInMeters;
    }

    public function setCoordinateUncertaintyInMeters(float $coordinateUncertaintyInMeters): static
    {
        $this->coordinateUncertaintyInMeters = $coordinateUncertaintyInMeters;
        return $this;
    }

    public function getCoordinatePrecision(): ?string
    {
        return $this->coordinatePrecision;
    }

    public function setCoordinatePrecision(string $coordinatePrecision): static
    {
        $this->coordinatePrecision = $coordinatePrecision;
        return $this;
    }

    public function getPointRadiusSpatialFit(): ?string
    {
        return $this->pointRadiusSpatialFit;
    }

    public function setPointRadiusSpatialFit(string $pointRadiusSpatialFit): static
    {
        $this->pointRadiusSpatialFit = $pointRadiusSpatialFit;
        return $this;
    }

    public function getVerbatimCoordinates(): ?string
    {
        return $this->verbatimCoordinates;
    }

    public function setVerbatimCoordinates(string $verbatimCoordinates): static
    {
        $this->verbatimCoordinates = $verbatimCoordinates;
        return $this;
    }

    public function getVerbatimLatitude(): ?string
    {
        return $this->verbatimLatitude;
    }

    public function setVerbatimLatitude(string $verbatimLatitude): static
    {
        $this->verbatimLatitude = $verbatimLatitude;
        return $this;
    }

    public function getVerbatimLongitude(): ?string
    {
        return $this->verbatimLongitude;
    }

    public function setVerbatimLongitude(string $verbatimLongitude): static
    {
        $this->verbatimLongitude = $verbatimLongitude;
        return $this;
    }

    public function getVerbatimCoordinateSystem(): ?string
    {
        return $this->verbatimCoordinateSystem;
    }

    public function setVerbatimCoordinateSystem(string $verbatimCoordinateSystem): static
    {
        $this->verbatimCoordinateSystem = $verbatimCoordinateSystem;
        return $this;
    }

    public function getVerbatimSRS(): ?string
    {
        return $this->verbatimSRS;
    }

    public function setVerbatimSRS(string $verbatimSRS): static
    {
        $this->verbatimSRS = $verbatimSRS;
        return $this;
    }

    public function getFootprintWKT(): ?string
    {
        return $this->footprintWKT;
    }

    public function setFootprintWKT(string $footprintWKT): static
    {
        $this->footprintWKT = $footprintWKT;
        return $this;
    }

    public function getFootprintSRS(): ?string
    {
        return $this->footprintSRS;
    }

    public function setFootprintSRS(string $footprintSRS): static
    {
        $this->footprintSRS = $footprintSRS;
        return $this;
    }

    public function getFootprintSpatialFit(): ?string
    {
        return $this->footprintSpatialFit;
    }

    public function setFootprintSpatialFit(string $footprintSpatialFit): static
    {
        $this->footprintSpatialFit = $footprintSpatialFit;
        return $this;
    }

    public function getGeoreferencedBy(): ?string
    {
        return $this->georeferencedBy;
    }

    public function setGeoreferencedBy(string $georeferencedBy): static
    {
        $this->georeferencedBy = $georeferencedBy;
        return $this;
    }

    public function getGeoreferencedDate(): ?string
    {
        return $this->georeferencedDate;
    }

    public function setGeoreferencedDate(string $georeferencedDate): static
    {
        $this->georeferencedDate = $georeferencedDate;
        return $this;
    }

    public function getGeoreferenceProtocol(): ?string
    {
        return $this->georeferenceProtocol;
    }

    public function setGeoreferenceProtocol(string $georeferenceProtocol): static
    {
        $this->georeferenceProtocol = $georeferenceProtocol;
        return $this;
    }

    public function getGeoreferenceSources(): ?string
    {
        return $this->georeferenceSources;
    }

    public function setGeoreferenceSources(string $georeferenceSources): static
    {
        $this->georeferenceSources = $georeferenceSources;
        return $this;
    }

    public function getGeoreferenceRemarks(): ?string
    {
        return $this->georeferenceRemarks;
    }

    public function setGeoreferenceRemarks(string $georeferenceRemarks): static
    {
        $this->georeferenceRemarks = $georeferenceRemarks;
        return $this;
    }

    public function getInstitutionID(): ?string
    {
        return $this->institutionID;
    }

    public function setInstitutionID(string $institutionID): static
    {
        $this->institutionID = $institutionID;
        return $this;
    }

    public function getCollectionID(): ?string
    {
        return $this->collectionID;
    }

    public function setCollectionID(string $collectionID): static
    {
        $this->collectionID = $collectionID;
        return $this;
    }

    public function getDatasetID(): ?string
    {
        return $this->datasetID;
    }

    public function setDatasetID(string $datasetID): static
    {
        $this->datasetID = $datasetID;
        return $this;
    }

    public function getInstitutionCode(): ?string
    {
        return $this->institutionCode;
    }

    public function setInstitutionCode(string $institutionCode): static
    {
        $this->institutionCode = $institutionCode;
        return $this;
    }

    public function getCollectionCode(): ?string
    {
        return $this->collectionCode;
    }

    public function setCollectionCode(string $collectionCode): static
    {
        $this->collectionCode = $collectionCode;
        return $this;
    }

    public function getDatasetName(): ?string
    {
        return $this->datasetName;
    }

    public function setDatasetName(string $datasetName): static
    {
        $this->datasetName = $datasetName;
        return $this;
    }

    public function getOwnerInstitutionCode(): ?string
    {
        return $this->ownerInstitutionCode;
    }

    public function setOwnerInstitutionCode(string $ownerInstitutionCode): static
    {
        $this->ownerInstitutionCode = $ownerInstitutionCode;
        return $this;
    }

    public function getBasisOfRecord(): ?string
    {
        return $this->basisOfRecord;
    }

    public function setBasisOfRecord(string $basisOfRecord): static
    {
        $this->basisOfRecord = $basisOfRecord;
        return $this;
    }

    public function getInformationWithheld(): ?string
    {
        return $this->informationWithheld;
    }

    public function setInformationWithheld(string $informationWithheld): static
    {
        $this->informationWithheld = $informationWithheld;
        return $this;
    }

    public function getDataGeneralizations(): ?string
    {
        return $this->dataGeneralizations;
    }

    public function setDataGeneralizations(string $dataGeneralizations): static
    {
        $this->dataGeneralizations = $dataGeneralizations;
        return $this;
    }

    public function getDynamicProperties(): ?string
    {
        return $this->dynamicProperties;
    }

    public function setDynamicProperties(string $dynamicProperties): static
    {
        $this->dynamicProperties = $dynamicProperties;
        return $this;
    }

}
