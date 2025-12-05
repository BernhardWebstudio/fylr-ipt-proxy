<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_resource_relationship')]
class ResourceRelationship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'resourceRelationshipID', type: 'string', unique: true)]
    private string $resourceRelationshipID;

    /**
     * Reference to Occurrence
     */
    #[ORM\ManyToOne(targetEntity: Occurrence::class, inversedBy: 'resourceRelationships', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'occurrenceID', referencedColumnName: 'id', nullable: true)]
    private ?Occurrence $occurrence = null;

    #[ORM\Column(name: 'resourceID', type: 'string', nullable: true)]
    private ?string $resourceID = null;

    #[ORM\Column(name: 'relationshipOfResourceID', type: 'string', nullable: true)]
    private ?string $relationshipOfResourceID = null;

    #[ORM\Column(name: 'relatedResourceID', type: 'string', nullable: true)]
    private ?string $relatedResourceID = null;

    #[ORM\Column(name: 'relationshipOfResource', type: 'string', nullable: true)]
    private ?string $relationshipOfResource = null;

    #[ORM\Column(name: 'relationshipAccordingTo', type: 'string', nullable: true)]
    private ?string $relationshipAccordingTo = null;

    #[ORM\Column(name: 'relationshipEstablishedDate', type: 'string', nullable: true)]
    private ?string $relationshipEstablishedDate = null;

    #[ORM\Column(name: 'relationshipRemarks', type: 'string', nullable: true)]
    private ?string $relationshipRemarks = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceRelationshipID(): string
    {
        return $this->resourceRelationshipID;
    }

    public function setResourceRelationshipID(string $resourceRelationshipID): static
    {
        $this->resourceRelationshipID = $resourceRelationshipID;
        return $this;
    }

    public function getResourceID(): ?string
    {
        return $this->resourceID;
    }

    public function setResourceID(?string $resourceID): static
    {
        $this->resourceID = $resourceID;
        return $this;
    }

    public function getRelationshipOfResourceID(): ?string
    {
        return $this->relationshipOfResourceID;
    }

    public function setRelationshipOfResourceID(?string $relationshipOfResourceID): static
    {
        $this->relationshipOfResourceID = $relationshipOfResourceID;
        return $this;
    }

    public function getRelatedResourceID(): ?string
    {
        return $this->relatedResourceID;
    }

    public function setRelatedResourceID(?string $relatedResourceID): static
    {
        $this->relatedResourceID = $relatedResourceID;
        return $this;
    }

    public function getRelationshipOfResource(): ?string
    {
        return $this->relationshipOfResource;
    }

    public function setRelationshipOfResource(?string $relationshipOfResource): static
    {
        $this->relationshipOfResource = $relationshipOfResource;
        return $this;
    }

    public function getRelationshipAccordingTo(): ?string
    {
        return $this->relationshipAccordingTo;
    }

    public function setRelationshipAccordingTo(?string $relationshipAccordingTo): static
    {
        $this->relationshipAccordingTo = $relationshipAccordingTo;
        return $this;
    }

    public function getRelationshipEstablishedDate(): ?string
    {
        return $this->relationshipEstablishedDate;
    }

    public function setRelationshipEstablishedDate(?string $relationshipEstablishedDate): static
    {
        $this->relationshipEstablishedDate = $relationshipEstablishedDate;
        return $this;
    }

    public function getRelationshipRemarks(): ?string
    {
        return $this->relationshipRemarks;
    }

    public function setRelationshipRemarks(?string $relationshipRemarks): static
    {
        $this->relationshipRemarks = $relationshipRemarks;
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

    public function getOccurrence(): ?Occurrence
    {
        return $this->occurrence;
    }

    public function setOccurrence(?Occurrence $occurrence): static
    {
        $this->occurrence = $occurrence;
        return $this;
    }

}
