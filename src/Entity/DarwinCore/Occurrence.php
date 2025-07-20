<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_occurrence')]
class Occurrence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'occurrenceID', type: 'string')]
    private string $occurrenceID;

    /**
     * Reference to Organism
     */
    #[ORM\ManyToOne(targetEntity: Organism::class)]
    #[ORM\JoinColumn(name: 'organismID', referencedColumnName: 'organismID', nullable: true)]
    private ?Organism $organism = null;

    /**
     * Reference to MaterialEntity
     */
    #[ORM\ManyToOne(targetEntity: MaterialEntity::class)]
    #[ORM\JoinColumn(name: 'materialEntityID', referencedColumnName: 'materialEntityID', nullable: true)]
    private ?MaterialEntity $materialentity = null;

    /**
     * Reference to Event
     */
    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'eventID', referencedColumnName: 'eventID', nullable: true)]
    private ?Event $event = null;

    /**
     * Reference to Location
     */
    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(name: 'locationID', referencedColumnName: 'locationID', nullable: true)]
    private ?Location $location = null;

    /**
     * Reference to Identification
     */
    #[ORM\ManyToOne(targetEntity: Identification::class)]
    #[ORM\JoinColumn(name: 'identificationID', referencedColumnName: 'identificationID', nullable: true)]
    private ?Identification $identification = null;

    /**
     * Reference to Taxon
     */
    #[ORM\ManyToOne(targetEntity: Taxon::class)]
    #[ORM\JoinColumn(name: 'taxonID', referencedColumnName: 'taxonID', nullable: true)]
    private ?Taxon $taxon = null;

    #[ORM\Column(name: 'catalogNumber', type: 'string', nullable: true)]
    private ?string $catalogNumber = null;

    #[ORM\Column(name: 'recordNumber', type: 'string', nullable: true)]
    private ?string $recordNumber = null;

    #[ORM\Column(name: 'recordedBy', type: 'string', nullable: true)]
    private ?string $recordedBy = null;

    #[ORM\Column(name: 'recordedByID', type: 'string', nullable: true)]
    private ?string $recordedByID = null;

    #[ORM\Column(name: 'individualCount', type: 'integer', nullable: true)]
    private ?int $individualCount = null;

    #[ORM\Column(name: 'organismQuantity', type: 'string', nullable: true)]
    private ?string $organismQuantity = null;

    #[ORM\Column(name: 'organismQuantityType', type: 'string', nullable: true)]
    private ?string $organismQuantityType = null;

    #[ORM\Column(name: 'sex', type: 'string', nullable: true)]
    private ?string $sex = null;

    #[ORM\Column(name: 'lifeStage', type: 'string', nullable: true)]
    private ?string $lifeStage = null;

    #[ORM\Column(name: 'reproductiveCondition', type: 'string', nullable: true)]
    private ?string $reproductiveCondition = null;

    #[ORM\Column(name: 'caste', type: 'string', nullable: true)]
    private ?string $caste = null;

    #[ORM\Column(name: 'behavior', type: 'string', nullable: true)]
    private ?string $behavior = null;

    #[ORM\Column(name: 'vitality', type: 'string', nullable: true)]
    private ?string $vitality = null;

    #[ORM\Column(name: 'establishmentMeans', type: 'string', nullable: true)]
    private ?string $establishmentMeans = null;

    #[ORM\Column(name: 'degreeOfEstablishment', type: 'string', nullable: true)]
    private ?string $degreeOfEstablishment = null;

    #[ORM\Column(name: 'pathway', type: 'string', nullable: true)]
    private ?string $pathway = null;

    #[ORM\Column(name: 'georeferenceVerificationStatus', type: 'string', nullable: true)]
    private ?string $georeferenceVerificationStatus = null;

    #[ORM\Column(name: 'occurrenceStatus', type: 'string', nullable: true)]
    private ?string $occurrenceStatus = null;

    #[ORM\Column(name: 'associatedMedia', type: 'string', nullable: true)]
    private ?string $associatedMedia = null;

    #[ORM\Column(name: 'associatedOccurrences', type: 'string', nullable: true)]
    private ?string $associatedOccurrences = null;

    #[ORM\Column(name: 'associatedReferences', type: 'string', nullable: true)]
    private ?string $associatedReferences = null;

    #[ORM\Column(name: 'associatedTaxa', type: 'string', nullable: true)]
    private ?string $associatedTaxa = null;

    #[ORM\Column(name: 'otherCatalogNumbers', type: 'string', nullable: true)]
    private ?string $otherCatalogNumbers = null;

    #[ORM\Column(name: 'occurrenceRemarks', type: 'string', nullable: true)]
    private ?string $occurrenceRemarks = null;

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

    public function getOccurrenceID(): ?string
    {
        return $this->occurrenceID;
    }

    public function setOccurrenceID(string $occurrenceID): static
    {
        $this->occurrenceID = $occurrenceID;
        return $this;
    }

    public function getCatalogNumber(): ?string
    {
        return $this->catalogNumber;
    }

    public function setCatalogNumber(string $catalogNumber): static
    {
        $this->catalogNumber = $catalogNumber;
        return $this;
    }

    public function getRecordNumber(): ?string
    {
        return $this->recordNumber;
    }

    public function setRecordNumber(string $recordNumber): static
    {
        $this->recordNumber = $recordNumber;
        return $this;
    }

    public function getRecordedBy(): ?string
    {
        return $this->recordedBy;
    }

    public function setRecordedBy(string $recordedBy): static
    {
        $this->recordedBy = $recordedBy;
        return $this;
    }

    public function getRecordedByID(): ?string
    {
        return $this->recordedByID;
    }

    public function setRecordedByID(string $recordedByID): static
    {
        $this->recordedByID = $recordedByID;
        return $this;
    }

    public function getIndividualCount(): ?int
    {
        return $this->individualCount;
    }

    public function setIndividualCount(int $individualCount): static
    {
        $this->individualCount = $individualCount;
        return $this;
    }

    public function getOrganismQuantity(): ?string
    {
        return $this->organismQuantity;
    }

    public function setOrganismQuantity(string $organismQuantity): static
    {
        $this->organismQuantity = $organismQuantity;
        return $this;
    }

    public function getOrganismQuantityType(): ?string
    {
        return $this->organismQuantityType;
    }

    public function setOrganismQuantityType(string $organismQuantityType): static
    {
        $this->organismQuantityType = $organismQuantityType;
        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(string $sex): static
    {
        $this->sex = $sex;
        return $this;
    }

    public function getLifeStage(): ?string
    {
        return $this->lifeStage;
    }

    public function setLifeStage(string $lifeStage): static
    {
        $this->lifeStage = $lifeStage;
        return $this;
    }

    public function getReproductiveCondition(): ?string
    {
        return $this->reproductiveCondition;
    }

    public function setReproductiveCondition(string $reproductiveCondition): static
    {
        $this->reproductiveCondition = $reproductiveCondition;
        return $this;
    }

    public function getCaste(): ?string
    {
        return $this->caste;
    }

    public function setCaste(string $caste): static
    {
        $this->caste = $caste;
        return $this;
    }

    public function getBehavior(): ?string
    {
        return $this->behavior;
    }

    public function setBehavior(string $behavior): static
    {
        $this->behavior = $behavior;
        return $this;
    }

    public function getVitality(): ?string
    {
        return $this->vitality;
    }

    public function setVitality(string $vitality): static
    {
        $this->vitality = $vitality;
        return $this;
    }

    public function getEstablishmentMeans(): ?string
    {
        return $this->establishmentMeans;
    }

    public function setEstablishmentMeans(string $establishmentMeans): static
    {
        $this->establishmentMeans = $establishmentMeans;
        return $this;
    }

    public function getDegreeOfEstablishment(): ?string
    {
        return $this->degreeOfEstablishment;
    }

    public function setDegreeOfEstablishment(string $degreeOfEstablishment): static
    {
        $this->degreeOfEstablishment = $degreeOfEstablishment;
        return $this;
    }

    public function getPathway(): ?string
    {
        return $this->pathway;
    }

    public function setPathway(string $pathway): static
    {
        $this->pathway = $pathway;
        return $this;
    }

    public function getGeoreferenceVerificationStatus(): ?string
    {
        return $this->georeferenceVerificationStatus;
    }

    public function setGeoreferenceVerificationStatus(string $georeferenceVerificationStatus): static
    {
        $this->georeferenceVerificationStatus = $georeferenceVerificationStatus;
        return $this;
    }

    public function getOccurrenceStatus(): ?string
    {
        return $this->occurrenceStatus;
    }

    public function setOccurrenceStatus(string $occurrenceStatus): static
    {
        $this->occurrenceStatus = $occurrenceStatus;
        return $this;
    }

    public function getAssociatedMedia(): ?string
    {
        return $this->associatedMedia;
    }

    public function setAssociatedMedia(string $associatedMedia): static
    {
        $this->associatedMedia = $associatedMedia;
        return $this;
    }

    public function getAssociatedOccurrences(): ?string
    {
        return $this->associatedOccurrences;
    }

    public function setAssociatedOccurrences(string $associatedOccurrences): static
    {
        $this->associatedOccurrences = $associatedOccurrences;
        return $this;
    }

    public function getAssociatedReferences(): ?string
    {
        return $this->associatedReferences;
    }

    public function setAssociatedReferences(string $associatedReferences): static
    {
        $this->associatedReferences = $associatedReferences;
        return $this;
    }

    public function getAssociatedTaxa(): ?string
    {
        return $this->associatedTaxa;
    }

    public function setAssociatedTaxa(string $associatedTaxa): static
    {
        $this->associatedTaxa = $associatedTaxa;
        return $this;
    }

    public function getOtherCatalogNumbers(): ?string
    {
        return $this->otherCatalogNumbers;
    }

    public function setOtherCatalogNumbers(string $otherCatalogNumbers): static
    {
        $this->otherCatalogNumbers = $otherCatalogNumbers;
        return $this;
    }

    public function getOccurrenceRemarks(): ?string
    {
        return $this->occurrenceRemarks;
    }

    public function setOccurrenceRemarks(string $occurrenceRemarks): static
    {
        $this->occurrenceRemarks = $occurrenceRemarks;
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

    public function getOrganism(): ?Organism
    {
        return $this->organism;
    }

    public function setOrganism(?Organism $organism): static
    {
        $this->organism = $organism;
        return $this;
    }

    public function getMaterialentity(): ?MaterialEntity
    {
        return $this->materialentity;
    }

    public function setMaterialentity(?MaterialEntity $materialentity): static
    {
        $this->materialentity = $materialentity;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getIdentification(): ?Identification
    {
        return $this->identification;
    }

    public function setIdentification(?Identification $identification): static
    {
        $this->identification = $identification;
        return $this;
    }

    public function getTaxon(): ?Taxon
    {
        return $this->taxon;
    }

    public function setTaxon(?Taxon $taxon): static
    {
        $this->taxon = $taxon;
        return $this;
    }

}
