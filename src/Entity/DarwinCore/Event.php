<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'eventID', type: 'string', unique: true)]
    private string $eventID;

    /**
     * Reference to Location
     */
    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'locationID', referencedColumnName: 'id', nullable: true)]
    private ?Location $location = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Occurrence::class, cascade: ['persist'])]
    private Collection $occurrences;

    #[ORM\Column(name: 'parentEventID', type: 'string', nullable: true)]
    private ?string $parentEventID = null;

    #[ORM\Column(name: 'eventType', type: 'string', nullable: true)]
    private ?string $eventType = null;

    #[ORM\Column(name: 'fieldNumber', type: 'string', nullable: true)]
    private ?string $fieldNumber = null;

    #[ORM\Column(name: 'eventDate', type: 'string', nullable: true)]
    private ?string $eventDate = null;

    #[ORM\Column(name: 'eventTime', type: 'datetime', nullable: true)]
    private ?\DateTime $eventTime = null;

    #[ORM\Column(name: 'startDayOfYear', type: 'integer', nullable: true)]
    private ?int $startDayOfYear = null;

    #[ORM\Column(name: 'endDayOfYear', type: 'integer', nullable: true)]
    private ?int $endDayOfYear = null;

    #[ORM\Column(name: 'year', type: 'string', nullable: true)]
    private ?string $year = null;

    #[ORM\Column(name: 'month', type: 'string', nullable: true)]
    private ?string $month = null;

    #[ORM\Column(name: 'day', type: 'string', nullable: true)]
    private ?string $day = null;

    #[ORM\Column(name: 'verbatimEventDate', type: 'string', nullable: true)]
    private ?string $verbatimEventDate = null;

    #[ORM\Column(name: 'habitat', type: 'string', nullable: true)]
    private ?string $habitat = null;

    #[ORM\Column(name: 'samplingProtocol', type: 'string', nullable: true)]
    private ?string $samplingProtocol = null;

    #[ORM\Column(name: 'sampleSizeValue', type: 'string', nullable: true)]
    private ?string $sampleSizeValue = null;

    #[ORM\Column(name: 'sampleSizeUnit', type: 'string', nullable: true)]
    private ?string $sampleSizeUnit = null;

    #[ORM\Column(name: 'samplingEffort', type: 'string', nullable: true)]
    private ?string $samplingEffort = null;

    #[ORM\Column(name: 'fieldNotes', type: 'string', nullable: true)]
    private ?string $fieldNotes = null;

    #[ORM\Column(name: 'eventRemarks', type: 'string', nullable: true)]
    private ?string $eventRemarks = null;

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

    public function getEventID(): string
    {
        return $this->eventID;
    }

    public function setEventID(string $eventID): static
    {
        $this->eventID = $eventID;
        return $this;
    }

    public function getParentEventID(): ?string
    {
        return $this->parentEventID;
    }

    public function setParentEventID(?string $parentEventID): static
    {
        $this->parentEventID = $parentEventID;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getFieldNumber(): ?string
    {
        return $this->fieldNumber;
    }

    public function setFieldNumber(?string $fieldNumber): static
    {
        $this->fieldNumber = $fieldNumber;
        return $this;
    }

    public function getEventDate(): ?string
    {
        return $this->eventDate;
    }

    public function setEventDate(?string $eventDate): static
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getEventTime(): ?\DateTime
    {
        return $this->eventTime;
    }

    public function setEventTime(?\DateTime $eventTime): static
    {
        $this->eventTime = $eventTime;
        return $this;
    }

    public function getStartDayOfYear(): ?int
    {
        return $this->startDayOfYear;
    }

    public function setStartDayOfYear(?int $startDayOfYear): static
    {
        $this->startDayOfYear = $startDayOfYear;
        return $this;
    }

    public function getEndDayOfYear(): ?int
    {
        return $this->endDayOfYear;
    }

    public function setEndDayOfYear(?int $endDayOfYear): static
    {
        $this->endDayOfYear = $endDayOfYear;
        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function setMonth(?string $month): static
    {
        $this->month = $month;
        return $this;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function setDay(?string $day): static
    {
        $this->day = $day;
        return $this;
    }

    public function getVerbatimEventDate(): ?string
    {
        return $this->verbatimEventDate;
    }

    public function setVerbatimEventDate(?string $verbatimEventDate): static
    {
        $this->verbatimEventDate = $verbatimEventDate;
        return $this;
    }

    public function getHabitat(): ?string
    {
        return $this->habitat;
    }

    public function setHabitat(?string $habitat): static
    {
        $this->habitat = $habitat;
        return $this;
    }

    public function getSamplingProtocol(): ?string
    {
        return $this->samplingProtocol;
    }

    public function setSamplingProtocol(?string $samplingProtocol): static
    {
        $this->samplingProtocol = $samplingProtocol;
        return $this;
    }

    public function getSampleSizeValue(): ?string
    {
        return $this->sampleSizeValue;
    }

    public function setSampleSizeValue(?string $sampleSizeValue): static
    {
        $this->sampleSizeValue = $sampleSizeValue;
        return $this;
    }

    public function getSampleSizeUnit(): ?string
    {
        return $this->sampleSizeUnit;
    }

    public function setSampleSizeUnit(?string $sampleSizeUnit): static
    {
        $this->sampleSizeUnit = $sampleSizeUnit;
        return $this;
    }

    public function getSamplingEffort(): ?string
    {
        return $this->samplingEffort;
    }

    public function setSamplingEffort(?string $samplingEffort): static
    {
        $this->samplingEffort = $samplingEffort;
        return $this;
    }

    public function getFieldNotes(): ?string
    {
        return $this->fieldNotes;
    }

    public function setFieldNotes(?string $fieldNotes): static
    {
        $this->fieldNotes = $fieldNotes;
        return $this;
    }

    public function getEventRemarks(): ?string
    {
        return $this->eventRemarks;
    }

    public function setEventRemarks(?string $eventRemarks): static
    {
        $this->eventRemarks = $eventRemarks;
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

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;
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
