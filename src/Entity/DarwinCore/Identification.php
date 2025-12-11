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
     * Reverse relationship from Occurrence
     */
    #[ORM\OneToMany(mappedBy: 'occurrence', targetEntity: Occurrence::class, cascade: ['persist'])]
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

    #[ORM\Column(name: 'identificationRemarks', type: 'text', nullable: true)]
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

    #[ORM\Column(name: 'dynamicProperties', type: 'text', nullable: true)]
    private ?string $dynamicProperties = null;

    #[ORM\Column(name: 'type', type: 'string', nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: 'modified', type: 'string', nullable: true)]
    private ?string $modified = null;

    #[ORM\Column(name: 'language', type: 'string', nullable: true)]
    private ?string $language = null;

    #[ORM\Column(name: 'license', type: 'text', nullable: true)]
    private ?string $license = null;

    #[ORM\Column(name: 'rightsHolder', type: 'text', nullable: true)]
    private ?string $rightsHolder = null;

    #[ORM\Column(name: 'accessRights', type: 'text', nullable: true)]
    private ?string $accessRights = null;

    #[ORM\Column(name: 'bibliographicCitation', type: 'text', nullable: true)]
    private ?string $bibliographicCitation = null;

    #[ORM\Column(name: '"references"', type: 'text', nullable: true)]
    private ?string $references = null;

    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentificationID(): string
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

    public function setVerbatimIdentification(?string $verbatimIdentification): static
    {
        $this->verbatimIdentification = $verbatimIdentification;
        return $this;
    }

    public function getIdentificationQualifier(): ?string
    {
        return $this->identificationQualifier;
    }

    public function setIdentificationQualifier(?string $identificationQualifier): static
    {
        $this->identificationQualifier = $identificationQualifier;
        return $this;
    }

    public function getTypeStatus(): ?string
    {
        return $this->typeStatus;
    }

    public function setTypeStatus(?string $typeStatus): static
    {
        $this->typeStatus = $typeStatus;
        return $this;
    }

    public function getIdentifiedBy(): ?string
    {
        return $this->identifiedBy;
    }

    public function setIdentifiedBy(?string $identifiedBy): static
    {
        $this->identifiedBy = $identifiedBy;
        return $this;
    }

    public function getIdentifiedByID(): ?string
    {
        return $this->identifiedByID;
    }

    public function setIdentifiedByID(?string $identifiedByID): static
    {
        $this->identifiedByID = $identifiedByID;
        return $this;
    }

    public function getDateIdentified(): ?string
    {
        return $this->dateIdentified;
    }

    public function setDateIdentified(?string $dateIdentified): static
    {
        $this->dateIdentified = $dateIdentified;
        return $this;
    }

    public function getIdentificationReferences(): ?string
    {
        return $this->identificationReferences;
    }

    public function setIdentificationReferences(?string $identificationReferences): static
    {
        $this->identificationReferences = $identificationReferences;
        return $this;
    }

    public function getIdentificationVerificationStatus(): ?string
    {
        return $this->identificationVerificationStatus;
    }

    public function setIdentificationVerificationStatus(?string $identificationVerificationStatus): static
    {
        $this->identificationVerificationStatus = $identificationVerificationStatus;
        return $this;
    }

    public function getIdentificationRemarks(): ?string
    {
        return $this->identificationRemarks;
    }

    public function setIdentificationRemarks(?string $identificationRemarks): static
    {
        $this->identificationRemarks = $identificationRemarks;
        return $this;
    }

    public function getInstitutionID(): ?string
    {
        return $this->institutionID;
    }

    public function setInstitutionID(?string $institutionID): static
    {
        $this->institutionID = $institutionID;
        return $this;
    }

    public function getCollectionID(): ?string
    {
        return $this->collectionID;
    }

    public function setCollectionID(?string $collectionID): static
    {
        $this->collectionID = $collectionID;
        return $this;
    }

    public function getDatasetID(): ?string
    {
        return $this->datasetID;
    }

    public function setDatasetID(?string $datasetID): static
    {
        $this->datasetID = $datasetID;
        return $this;
    }

    public function getInstitutionCode(): ?string
    {
        return $this->institutionCode;
    }

    public function setInstitutionCode(?string $institutionCode): static
    {
        $this->institutionCode = $institutionCode;
        return $this;
    }

    public function getCollectionCode(): ?string
    {
        return $this->collectionCode;
    }

    public function setCollectionCode(?string $collectionCode): static
    {
        $this->collectionCode = $collectionCode;
        return $this;
    }

    public function getDatasetName(): ?string
    {
        return $this->datasetName;
    }

    public function setDatasetName(?string $datasetName): static
    {
        $this->datasetName = $datasetName;
        return $this;
    }

    public function getOwnerInstitutionCode(): ?string
    {
        return $this->ownerInstitutionCode;
    }

    public function setOwnerInstitutionCode(?string $ownerInstitutionCode): static
    {
        $this->ownerInstitutionCode = $ownerInstitutionCode;
        return $this;
    }

    public function getBasisOfRecord(): ?string
    {
        return $this->basisOfRecord;
    }

    public function setBasisOfRecord(?string $basisOfRecord): static
    {
        $this->basisOfRecord = $basisOfRecord;
        return $this;
    }

    public function getInformationWithheld(): ?string
    {
        return $this->informationWithheld;
    }

    public function setInformationWithheld(?string $informationWithheld): static
    {
        $this->informationWithheld = $informationWithheld;
        return $this;
    }

    public function getDataGeneralizations(): ?string
    {
        return $this->dataGeneralizations;
    }

    public function setDataGeneralizations(?string $dataGeneralizations): static
    {
        $this->dataGeneralizations = $dataGeneralizations;
        return $this;
    }

    public function getDynamicProperties(): ?string
    {
        return $this->dynamicProperties;
    }

    public function setDynamicProperties(?string $dynamicProperties): static
    {
        $this->dynamicProperties = $dynamicProperties;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getModified(): ?string
    {
        return $this->modified;
    }

    public function setModified(?string $modified): static
    {
        $this->modified = $modified;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function setLicense(?string $license): static
    {
        $this->license = $license;
        return $this;
    }

    public function getRightsHolder(): ?string
    {
        return $this->rightsHolder;
    }

    public function setRightsHolder(?string $rightsHolder): static
    {
        $this->rightsHolder = $rightsHolder;
        return $this;
    }

    public function getAccessRights(): ?string
    {
        return $this->accessRights;
    }

    public function setAccessRights(?string $accessRights): static
    {
        $this->accessRights = $accessRights;
        return $this;
    }

    public function getBibliographicCitation(): ?string
    {
        return $this->bibliographicCitation;
    }

    public function setBibliographicCitation(?string $bibliographicCitation): static
    {
        $this->bibliographicCitation = $bibliographicCitation;
        return $this;
    }

    public function getReferences(): ?string
    {
        return $this->references;
    }

    public function setReferences(?string $references): static
    {
        $this->references = $references;
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
