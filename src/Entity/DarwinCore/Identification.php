<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_identification')]
class Identification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'identificationID', type: 'string', unique: true)]
    private string $identificationID;

    /**
     * Reference to Taxon
     */
    #[ORM\ManyToOne(targetEntity: Taxon::class, inversedBy: 'identifications')]
    #[ORM\JoinColumn(name: 'taxonID', referencedColumnName: 'id', nullable: true)]
    private ?Taxon $taxon = null;

    #[ORM\OneToMany(mappedBy: 'identification', targetEntity: Occurrence::class)]
    private Collection $occurrences;

    #[ORM\Column(name: 'verbatimIdentification', type: 'string', nullable: true)]
    private ?string $verbatimIdentification = null;

    #[ORM\Column(name: 'identificationQualifier', type: 'string', nullable: true)]
    private ?string $identificationQualifier = null;

    #[ORM\Column(name: 'typeStatus', type: 'string', nullable: true)]
    private ?string $typeStatus = null;

    #[ORM\Column(name: 'identifiedBy', type: 'string', nullable: true)]
    private ?string $identifiedBy = null;

    #[ORM\Column(name: 'identifiedByID', type: 'string', nullable: true)]
    private ?string $identifiedByID = null;

    #[ORM\Column(name: 'dateIdentified', type: 'string', nullable: true)]
    private ?string $dateIdentified = null;

    #[ORM\Column(name: 'identificationReferences', type: 'string', nullable: true)]
    private ?string $identificationReferences = null;

    #[ORM\Column(name: 'identificationVerificationStatus', type: 'string', nullable: true)]
    private ?string $identificationVerificationStatus = null;

    #[ORM\Column(name: 'identificationRemarks', type: 'string', nullable: true)]
    private ?string $identificationRemarks = null;

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

    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentificationID(): ?string
    {
        return $this->identificationID;
    }

    public function setIdentificationID(string $identificationID): static
    {
        $this->identificationID = $identificationID;
        return $this;
    }

    public function getVerbatimIdentification(): ?string
    {
        return $this->verbatimIdentification;
    }

    public function setVerbatimIdentification(string $verbatimIdentification): static
    {
        $this->verbatimIdentification = $verbatimIdentification;
        return $this;
    }

    public function getIdentificationQualifier(): ?string
    {
        return $this->identificationQualifier;
    }

    public function setIdentificationQualifier(string $identificationQualifier): static
    {
        $this->identificationQualifier = $identificationQualifier;
        return $this;
    }

    public function getTypeStatus(): ?string
    {
        return $this->typeStatus;
    }

    public function setTypeStatus(string $typeStatus): static
    {
        $this->typeStatus = $typeStatus;
        return $this;
    }

    public function getIdentifiedBy(): ?string
    {
        return $this->identifiedBy;
    }

    public function setIdentifiedBy(string $identifiedBy): static
    {
        $this->identifiedBy = $identifiedBy;
        return $this;
    }

    public function getIdentifiedByID(): ?string
    {
        return $this->identifiedByID;
    }

    public function setIdentifiedByID(string $identifiedByID): static
    {
        $this->identifiedByID = $identifiedByID;
        return $this;
    }

    public function getDateIdentified(): ?string
    {
        return $this->dateIdentified;
    }

    public function setDateIdentified(string $dateIdentified): static
    {
        $this->dateIdentified = $dateIdentified;
        return $this;
    }

    public function getIdentificationReferences(): ?string
    {
        return $this->identificationReferences;
    }

    public function setIdentificationReferences(string $identificationReferences): static
    {
        $this->identificationReferences = $identificationReferences;
        return $this;
    }

    public function getIdentificationVerificationStatus(): ?string
    {
        return $this->identificationVerificationStatus;
    }

    public function setIdentificationVerificationStatus(string $identificationVerificationStatus): static
    {
        $this->identificationVerificationStatus = $identificationVerificationStatus;
        return $this;
    }

    public function getIdentificationRemarks(): ?string
    {
        return $this->identificationRemarks;
    }

    public function setIdentificationRemarks(string $identificationRemarks): static
    {
        $this->identificationRemarks = $identificationRemarks;
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

    public function getTaxon(): ?Taxon
    {
        return $this->taxon;
    }

    public function setTaxon(?Taxon $taxon): static
    {
        $this->taxon = $taxon;
        return $this;
    }

    /**
     * @return Collection<int, Occurrence>
     */
    public function getOccurrences(): Collection
    {
        return $this->occurrences;
    }

    public function addOccurrence(Occurrence $occurrence): static
    {
        if (!$this->occurrences->contains($occurrence)) {
            $this->occurrences->add($occurrence);
        }
        return $this;
    }

    public function removeOccurrence(Occurrence $occurrence): static
    {
        $this->occurrences->removeElement($occurrence);
        return $this;
    }

}
