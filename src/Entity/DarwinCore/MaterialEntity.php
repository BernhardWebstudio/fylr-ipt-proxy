<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_material_entity')]
class MaterialEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'materialEntityID', type: 'string', unique: true)]
    private string $materialEntityID;

    /**
     * Reference to MaterialSample
     */
    #[ORM\ManyToOne(targetEntity: MaterialSample::class, inversedBy: 'materialEntities', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'materialSampleID', referencedColumnName: 'id', nullable: true)]
    private ?MaterialSample $materialSample = null;

    /**
     * Reverse relationship from Occurrence
     */
    #[ORM\OneToMany(mappedBy: 'occurrence', targetEntity: Occurrence::class, cascade: ['persist'])]
    private Collection $occurrences;

    #[ORM\Column(name: 'preparations', type: 'string', nullable: true)]
    private ?string $preparations = null;

    #[ORM\Column(name: 'disposition', type: 'string', nullable: true)]
    private ?string $disposition = null;

    #[ORM\Column(name: 'verbatimLabel', type: 'string', nullable: true)]
    private ?string $verbatimLabel = null;

    #[ORM\Column(name: 'associatedSequences', type: 'string', nullable: true)]
    private ?string $associatedSequences = null;

    #[ORM\Column(name: 'materialEntityRemarks', type: 'string', nullable: true)]
    private ?string $materialEntityRemarks = null;

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

    #[ORM\Column(name: 'references', type: 'text', nullable: true)]
    private ?string $references = null;

    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaterialEntityID(): string
    {
        return $this->materialEntityID;
    }

    public function setMaterialEntityID(string $materialEntityID): static
    {
        $this->materialEntityID = $materialEntityID;
        return $this;
    }

    public function getPreparations(): ?string
    {
        return $this->preparations;
    }

    public function setPreparations(?string $preparations): static
    {
        $this->preparations = $preparations;
        return $this;
    }

    public function getDisposition(): ?string
    {
        return $this->disposition;
    }

    public function setDisposition(?string $disposition): static
    {
        $this->disposition = $disposition;
        return $this;
    }

    public function getVerbatimLabel(): ?string
    {
        return $this->verbatimLabel;
    }

    public function setVerbatimLabel(?string $verbatimLabel): static
    {
        $this->verbatimLabel = $verbatimLabel;
        return $this;
    }

    public function getAssociatedSequences(): ?string
    {
        return $this->associatedSequences;
    }

    public function setAssociatedSequences(?string $associatedSequences): static
    {
        $this->associatedSequences = $associatedSequences;
        return $this;
    }

    public function getMaterialEntityRemarks(): ?string
    {
        return $this->materialEntityRemarks;
    }

    public function setMaterialEntityRemarks(?string $materialEntityRemarks): static
    {
        $this->materialEntityRemarks = $materialEntityRemarks;
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

    public function getMaterialSample(): ?MaterialSample
    {
        return $this->materialSample;
    }

    public function setMaterialSample(?MaterialSample $materialSample): static
    {
        $this->materialSample = $materialSample;
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
