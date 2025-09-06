<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_geological_context')]
class GeologicalContext
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'geologicalContextID', type: 'string', unique: true)]
    private string $geologicalContextID;

    #[ORM\OneToMany(mappedBy: 'geologicalContext', targetEntity: Location::class)]
    private Collection $locations;

    #[ORM\Column(name: 'earliestEonOrLowestEonothem', type: 'string', nullable: true)]
    private ?string $earliestEonOrLowestEonothem = null;

    #[ORM\Column(name: 'latestEonOrHighestEonothem', type: 'string', nullable: true)]
    private ?string $latestEonOrHighestEonothem = null;

    #[ORM\Column(name: 'earliestEraOrLowestErathem', type: 'string', nullable: true)]
    private ?string $earliestEraOrLowestErathem = null;

    #[ORM\Column(name: 'latestEraOrHighestErathem', type: 'string', nullable: true)]
    private ?string $latestEraOrHighestErathem = null;

    #[ORM\Column(name: 'earliestPeriodOrLowestSystem', type: 'string', nullable: true)]
    private ?string $earliestPeriodOrLowestSystem = null;

    #[ORM\Column(name: 'latestPeriodOrHighestSystem', type: 'string', nullable: true)]
    private ?string $latestPeriodOrHighestSystem = null;

    #[ORM\Column(name: 'earliestEpochOrLowestSeries', type: 'string', nullable: true)]
    private ?string $earliestEpochOrLowestSeries = null;

    #[ORM\Column(name: 'latestEpochOrHighestSeries', type: 'string', nullable: true)]
    private ?string $latestEpochOrHighestSeries = null;

    #[ORM\Column(name: 'earliestAgeOrLowestStage', type: 'string', nullable: true)]
    private ?string $earliestAgeOrLowestStage = null;

    #[ORM\Column(name: 'latestAgeOrHighestStage', type: 'string', nullable: true)]
    private ?string $latestAgeOrHighestStage = null;

    #[ORM\Column(name: 'lowestBiostratigraphicZone', type: 'string', nullable: true)]
    private ?string $lowestBiostratigraphicZone = null;

    #[ORM\Column(name: 'highestBiostratigraphicZone', type: 'string', nullable: true)]
    private ?string $highestBiostratigraphicZone = null;

    #[ORM\Column(name: 'lithostratigraphicTerms', type: 'string', nullable: true)]
    private ?string $lithostratigraphicTerms = null;

    #[ORM\Column(name: 'group', type: 'string', nullable: true)]
    private ?string $group = null;

    #[ORM\Column(name: 'formation', type: 'string', nullable: true)]
    private ?string $formation = null;

    #[ORM\Column(name: 'member', type: 'string', nullable: true)]
    private ?string $member = null;

    #[ORM\Column(name: 'bed', type: 'string', nullable: true)]
    private ?string $bed = null;

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
        $this->locations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGeologicalContextID(): ?string
    {
        return $this->geologicalContextID;
    }

    public function setGeologicalContextID(string $geologicalContextID): static
    {
        $this->geologicalContextID = $geologicalContextID;
        return $this;
    }

    public function getEarliestEonOrLowestEonothem(): ?string
    {
        return $this->earliestEonOrLowestEonothem;
    }

    public function setEarliestEonOrLowestEonothem(string $earliestEonOrLowestEonothem): static
    {
        $this->earliestEonOrLowestEonothem = $earliestEonOrLowestEonothem;
        return $this;
    }

    public function getLatestEonOrHighestEonothem(): ?string
    {
        return $this->latestEonOrHighestEonothem;
    }

    public function setLatestEonOrHighestEonothem(string $latestEonOrHighestEonothem): static
    {
        $this->latestEonOrHighestEonothem = $latestEonOrHighestEonothem;
        return $this;
    }

    public function getEarliestEraOrLowestErathem(): ?string
    {
        return $this->earliestEraOrLowestErathem;
    }

    public function setEarliestEraOrLowestErathem(string $earliestEraOrLowestErathem): static
    {
        $this->earliestEraOrLowestErathem = $earliestEraOrLowestErathem;
        return $this;
    }

    public function getLatestEraOrHighestErathem(): ?string
    {
        return $this->latestEraOrHighestErathem;
    }

    public function setLatestEraOrHighestErathem(string $latestEraOrHighestErathem): static
    {
        $this->latestEraOrHighestErathem = $latestEraOrHighestErathem;
        return $this;
    }

    public function getEarliestPeriodOrLowestSystem(): ?string
    {
        return $this->earliestPeriodOrLowestSystem;
    }

    public function setEarliestPeriodOrLowestSystem(string $earliestPeriodOrLowestSystem): static
    {
        $this->earliestPeriodOrLowestSystem = $earliestPeriodOrLowestSystem;
        return $this;
    }

    public function getLatestPeriodOrHighestSystem(): ?string
    {
        return $this->latestPeriodOrHighestSystem;
    }

    public function setLatestPeriodOrHighestSystem(string $latestPeriodOrHighestSystem): static
    {
        $this->latestPeriodOrHighestSystem = $latestPeriodOrHighestSystem;
        return $this;
    }

    public function getEarliestEpochOrLowestSeries(): ?string
    {
        return $this->earliestEpochOrLowestSeries;
    }

    public function setEarliestEpochOrLowestSeries(string $earliestEpochOrLowestSeries): static
    {
        $this->earliestEpochOrLowestSeries = $earliestEpochOrLowestSeries;
        return $this;
    }

    public function getLatestEpochOrHighestSeries(): ?string
    {
        return $this->latestEpochOrHighestSeries;
    }

    public function setLatestEpochOrHighestSeries(string $latestEpochOrHighestSeries): static
    {
        $this->latestEpochOrHighestSeries = $latestEpochOrHighestSeries;
        return $this;
    }

    public function getEarliestAgeOrLowestStage(): ?string
    {
        return $this->earliestAgeOrLowestStage;
    }

    public function setEarliestAgeOrLowestStage(string $earliestAgeOrLowestStage): static
    {
        $this->earliestAgeOrLowestStage = $earliestAgeOrLowestStage;
        return $this;
    }

    public function getLatestAgeOrHighestStage(): ?string
    {
        return $this->latestAgeOrHighestStage;
    }

    public function setLatestAgeOrHighestStage(string $latestAgeOrHighestStage): static
    {
        $this->latestAgeOrHighestStage = $latestAgeOrHighestStage;
        return $this;
    }

    public function getLowestBiostratigraphicZone(): ?string
    {
        return $this->lowestBiostratigraphicZone;
    }

    public function setLowestBiostratigraphicZone(string $lowestBiostratigraphicZone): static
    {
        $this->lowestBiostratigraphicZone = $lowestBiostratigraphicZone;
        return $this;
    }

    public function getHighestBiostratigraphicZone(): ?string
    {
        return $this->highestBiostratigraphicZone;
    }

    public function setHighestBiostratigraphicZone(string $highestBiostratigraphicZone): static
    {
        $this->highestBiostratigraphicZone = $highestBiostratigraphicZone;
        return $this;
    }

    public function getLithostratigraphicTerms(): ?string
    {
        return $this->lithostratigraphicTerms;
    }

    public function setLithostratigraphicTerms(string $lithostratigraphicTerms): static
    {
        $this->lithostratigraphicTerms = $lithostratigraphicTerms;
        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(string $group): static
    {
        $this->group = $group;
        return $this;
    }

    public function getFormation(): ?string
    {
        return $this->formation;
    }

    public function setFormation(string $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    public function getMember(): ?string
    {
        return $this->member;
    }

    public function setMember(string $member): static
    {
        $this->member = $member;
        return $this;
    }

    public function getBed(): ?string
    {
        return $this->bed;
    }

    public function setBed(string $bed): static
    {
        $this->bed = $bed;
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

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
        }
        return $this;
    }

    public function removeLocation(Location $location): static
    {
        $this->locations->removeElement($location);
        return $this;
    }

}
