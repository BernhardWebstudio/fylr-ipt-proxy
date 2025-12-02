<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_organism')]
class Organism
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'organismID', type: 'string', unique: true)]
    private string $organismID;

    #[ORM\OneToMany(mappedBy: 'organism', targetEntity: Occurrence::class, cascade: ['persist'])]
    private Collection $occurrences;

    #[ORM\Column(name: 'organismName', type: 'string', nullable: true)]
    private ?string $organismName = null;

    #[ORM\Column(name: 'organismScope', type: 'string', nullable: true)]
    private ?string $organismScope = null;

    #[ORM\Column(name: 'associatedOrganisms', type: 'string', nullable: true)]
    private ?string $associatedOrganisms = null;

    #[ORM\Column(name: 'previousIdentifications', type: 'string', nullable: true)]
    private ?string $previousIdentifications = null;

    #[ORM\Column(name: 'organismRemarks', type: 'string', nullable: true)]
    private ?string $organismRemarks = null;

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

    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganismID(): string
    {
        return $this->organismID;
    }

    public function setOrganismID(string $organismID): static
    {
        $this->organismID = $organismID;
        return $this;
    }

    public function getOrganismName(): ?string
    {
        return $this->organismName;
    }

    public function setOrganismName(?string $organismName): static
    {
        $this->organismName = $organismName;
        return $this;
    }

    public function getOrganismScope(): ?string
    {
        return $this->organismScope;
    }

    public function setOrganismScope(?string $organismScope): static
    {
        $this->organismScope = $organismScope;
        return $this;
    }

    public function getAssociatedOrganisms(): ?string
    {
        return $this->associatedOrganisms;
    }

    public function setAssociatedOrganisms(?string $associatedOrganisms): static
    {
        $this->associatedOrganisms = $associatedOrganisms;
        return $this;
    }

    public function getPreviousIdentifications(): ?string
    {
        return $this->previousIdentifications;
    }

    public function setPreviousIdentifications(?string $previousIdentifications): static
    {
        $this->previousIdentifications = $previousIdentifications;
        return $this;
    }

    public function getOrganismRemarks(): ?string
    {
        return $this->organismRemarks;
    }

    public function setOrganismRemarks(?string $organismRemarks): static
    {
        $this->organismRemarks = $organismRemarks;
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
