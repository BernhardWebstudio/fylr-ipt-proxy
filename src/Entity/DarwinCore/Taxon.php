<?php

namespace App\Entity\DarwinCore;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'dwc_taxon')]
class Taxon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'taxonID', type: 'string', unique: true)]
    private string $taxonID;

    #[ORM\OneToMany(mappedBy: 'taxon', targetEntity: Occurrence::class)]
    private Collection $occurrences;

    #[ORM\OneToMany(mappedBy: 'taxon', targetEntity: Identification::class)]
    private Collection $identifications;

    #[ORM\OneToMany(mappedBy: 'taxon', targetEntity: MeasurementOrFact::class)]
    private Collection $measurementOrFacts;

    #[ORM\Column(name: 'scientificNameID', type: 'string', nullable: true)]
    private ?string $scientificNameID = null;

    #[ORM\Column(name: 'acceptedNameUsageID', type: 'string', nullable: true)]
    private ?string $acceptedNameUsageID = null;

    #[ORM\Column(name: 'parentNameUsageID', type: 'string', nullable: true)]
    private ?string $parentNameUsageID = null;

    #[ORM\Column(name: 'originalNameUsageID', type: 'string', nullable: true)]
    private ?string $originalNameUsageID = null;

    #[ORM\Column(name: 'nameAccordingToID', type: 'string', nullable: true)]
    private ?string $nameAccordingToID = null;

    #[ORM\Column(name: 'namePublishedInID', type: 'string', nullable: true)]
    private ?string $namePublishedInID = null;

    #[ORM\Column(name: 'taxonConceptID', type: 'string', nullable: true)]
    private ?string $taxonConceptID = null;

    #[ORM\Column(name: 'scientificName', type: 'string', nullable: true)]
    private ?string $scientificName = null;

    #[ORM\Column(name: 'acceptedNameUsage', type: 'string', nullable: true)]
    private ?string $acceptedNameUsage = null;

    #[ORM\Column(name: 'parentNameUsage', type: 'string', nullable: true)]
    private ?string $parentNameUsage = null;

    #[ORM\Column(name: 'originalNameUsage', type: 'string', nullable: true)]
    private ?string $originalNameUsage = null;

    #[ORM\Column(name: 'nameAccordingTo', type: 'string', nullable: true)]
    private ?string $nameAccordingTo = null;

    #[ORM\Column(name: 'namePublishedIn', type: 'string', nullable: true)]
    private ?string $namePublishedIn = null;

    #[ORM\Column(name: 'namePublishedInYear', type: 'string', nullable: true)]
    private ?string $namePublishedInYear = null;

    #[ORM\Column(name: 'higherClassification', type: 'string', nullable: true)]
    private ?string $higherClassification = null;

    #[ORM\Column(name: 'kingdom', type: 'string', nullable: true)]
    private ?string $kingdom = null;

    #[ORM\Column(name: 'phylum', type: 'string', nullable: true)]
    private ?string $phylum = null;

    #[ORM\Column(name: 'class', type: 'string', nullable: true)]
    private ?string $class = null;

    #[ORM\Column(name: 'order', type: 'string', nullable: true)]
    private ?string $order = null;

    #[ORM\Column(name: 'superfamily', type: 'string', nullable: true)]
    private ?string $superfamily = null;

    #[ORM\Column(name: 'family', type: 'string', nullable: true)]
    private ?string $family = null;

    #[ORM\Column(name: 'subfamily', type: 'string', nullable: true)]
    private ?string $subfamily = null;

    #[ORM\Column(name: 'tribe', type: 'string', nullable: true)]
    private ?string $tribe = null;

    #[ORM\Column(name: 'subtribe', type: 'string', nullable: true)]
    private ?string $subtribe = null;

    #[ORM\Column(name: 'genus', type: 'string', nullable: true)]
    private ?string $genus = null;

    #[ORM\Column(name: 'genericName', type: 'string', nullable: true)]
    private ?string $genericName = null;

    #[ORM\Column(name: 'subgenus', type: 'string', nullable: true)]
    private ?string $subgenus = null;

    #[ORM\Column(name: 'infragenericEpithet', type: 'string', nullable: true)]
    private ?string $infragenericEpithet = null;

    #[ORM\Column(name: 'specificEpithet', type: 'string', nullable: true)]
    private ?string $specificEpithet = null;

    #[ORM\Column(name: 'infraspecificEpithet', type: 'string', nullable: true)]
    private ?string $infraspecificEpithet = null;

    #[ORM\Column(name: 'cultivarEpithet', type: 'string', nullable: true)]
    private ?string $cultivarEpithet = null;

    #[ORM\Column(name: 'taxonRank', type: 'string', nullable: true)]
    private ?string $taxonRank = null;

    #[ORM\Column(name: 'verbatimTaxonRank', type: 'string', nullable: true)]
    private ?string $verbatimTaxonRank = null;

    #[ORM\Column(name: 'scientificNameAuthorship', type: 'string', nullable: true)]
    private ?string $scientificNameAuthorship = null;

    #[ORM\Column(name: 'vernacularName', type: 'string', nullable: true)]
    private ?string $vernacularName = null;

    #[ORM\Column(name: 'nomenclaturalCode', type: 'string', nullable: true)]
    private ?string $nomenclaturalCode = null;

    #[ORM\Column(name: 'taxonomicStatus', type: 'string', nullable: true)]
    private ?string $taxonomicStatus = null;

    #[ORM\Column(name: 'nomenclaturalStatus', type: 'string', nullable: true)]
    private ?string $nomenclaturalStatus = null;

    #[ORM\Column(name: 'taxonRemarks', type: 'string', nullable: true)]
    private ?string $taxonRemarks = null;

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
        $this->identifications = new ArrayCollection();
        $this->measurementOrFacts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaxonID(): string
    {
        return $this->taxonID;
    }

    public function setTaxonID(string $taxonID): static
    {
        $this->taxonID = $taxonID;
        return $this;
    }

    public function getScientificNameID(): ?string
    {
        return $this->scientificNameID;
    }

    public function setScientificNameID(?string $scientificNameID): static
    {
        $this->scientificNameID = $scientificNameID;
        return $this;
    }

    public function getAcceptedNameUsageID(): ?string
    {
        return $this->acceptedNameUsageID;
    }

    public function setAcceptedNameUsageID(?string $acceptedNameUsageID): static
    {
        $this->acceptedNameUsageID = $acceptedNameUsageID;
        return $this;
    }

    public function getParentNameUsageID(): ?string
    {
        return $this->parentNameUsageID;
    }

    public function setParentNameUsageID(?string $parentNameUsageID): static
    {
        $this->parentNameUsageID = $parentNameUsageID;
        return $this;
    }

    public function getOriginalNameUsageID(): ?string
    {
        return $this->originalNameUsageID;
    }

    public function setOriginalNameUsageID(?string $originalNameUsageID): static
    {
        $this->originalNameUsageID = $originalNameUsageID;
        return $this;
    }

    public function getNameAccordingToID(): ?string
    {
        return $this->nameAccordingToID;
    }

    public function setNameAccordingToID(?string $nameAccordingToID): static
    {
        $this->nameAccordingToID = $nameAccordingToID;
        return $this;
    }

    public function getNamePublishedInID(): ?string
    {
        return $this->namePublishedInID;
    }

    public function setNamePublishedInID(?string $namePublishedInID): static
    {
        $this->namePublishedInID = $namePublishedInID;
        return $this;
    }

    public function getTaxonConceptID(): ?string
    {
        return $this->taxonConceptID;
    }

    public function setTaxonConceptID(?string $taxonConceptID): static
    {
        $this->taxonConceptID = $taxonConceptID;
        return $this;
    }

    public function getScientificName(): ?string
    {
        return $this->scientificName;
    }

    public function setScientificName(?string $scientificName): static
    {
        $this->scientificName = $scientificName;
        return $this;
    }

    public function getAcceptedNameUsage(): ?string
    {
        return $this->acceptedNameUsage;
    }

    public function setAcceptedNameUsage(?string $acceptedNameUsage): static
    {
        $this->acceptedNameUsage = $acceptedNameUsage;
        return $this;
    }

    public function getParentNameUsage(): ?string
    {
        return $this->parentNameUsage;
    }

    public function setParentNameUsage(?string $parentNameUsage): static
    {
        $this->parentNameUsage = $parentNameUsage;
        return $this;
    }

    public function getOriginalNameUsage(): ?string
    {
        return $this->originalNameUsage;
    }

    public function setOriginalNameUsage(?string $originalNameUsage): static
    {
        $this->originalNameUsage = $originalNameUsage;
        return $this;
    }

    public function getNameAccordingTo(): ?string
    {
        return $this->nameAccordingTo;
    }

    public function setNameAccordingTo(?string $nameAccordingTo): static
    {
        $this->nameAccordingTo = $nameAccordingTo;
        return $this;
    }

    public function getNamePublishedIn(): ?string
    {
        return $this->namePublishedIn;
    }

    public function setNamePublishedIn(?string $namePublishedIn): static
    {
        $this->namePublishedIn = $namePublishedIn;
        return $this;
    }

    public function getNamePublishedInYear(): ?string
    {
        return $this->namePublishedInYear;
    }

    public function setNamePublishedInYear(?string $namePublishedInYear): static
    {
        $this->namePublishedInYear = $namePublishedInYear;
        return $this;
    }

    public function getHigherClassification(): ?string
    {
        return $this->higherClassification;
    }

    public function setHigherClassification(?string $higherClassification): static
    {
        $this->higherClassification = $higherClassification;
        return $this;
    }

    public function getKingdom(): ?string
    {
        return $this->kingdom;
    }

    public function setKingdom(?string $kingdom): static
    {
        $this->kingdom = $kingdom;
        return $this;
    }

    public function getPhylum(): ?string
    {
        return $this->phylum;
    }

    public function setPhylum(?string $phylum): static
    {
        $this->phylum = $phylum;
        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): static
    {
        $this->class = $class;
        return $this;
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function setOrder(?string $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getSuperfamily(): ?string
    {
        return $this->superfamily;
    }

    public function setSuperfamily(?string $superfamily): static
    {
        $this->superfamily = $superfamily;
        return $this;
    }

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(?string $family): static
    {
        $this->family = $family;
        return $this;
    }

    public function getSubfamily(): ?string
    {
        return $this->subfamily;
    }

    public function setSubfamily(?string $subfamily): static
    {
        $this->subfamily = $subfamily;
        return $this;
    }

    public function getTribe(): ?string
    {
        return $this->tribe;
    }

    public function setTribe(?string $tribe): static
    {
        $this->tribe = $tribe;
        return $this;
    }

    public function getSubtribe(): ?string
    {
        return $this->subtribe;
    }

    public function setSubtribe(?string $subtribe): static
    {
        $this->subtribe = $subtribe;
        return $this;
    }

    public function getGenus(): ?string
    {
        return $this->genus;
    }

    public function setGenus(?string $genus): static
    {
        $this->genus = $genus;
        return $this;
    }

    public function getGenericName(): ?string
    {
        return $this->genericName;
    }

    public function setGenericName(?string $genericName): static
    {
        $this->genericName = $genericName;
        return $this;
    }

    public function getSubgenus(): ?string
    {
        return $this->subgenus;
    }

    public function setSubgenus(?string $subgenus): static
    {
        $this->subgenus = $subgenus;
        return $this;
    }

    public function getInfragenericEpithet(): ?string
    {
        return $this->infragenericEpithet;
    }

    public function setInfragenericEpithet(?string $infragenericEpithet): static
    {
        $this->infragenericEpithet = $infragenericEpithet;
        return $this;
    }

    public function getSpecificEpithet(): ?string
    {
        return $this->specificEpithet;
    }

    public function setSpecificEpithet(?string $specificEpithet): static
    {
        $this->specificEpithet = $specificEpithet;
        return $this;
    }

    public function getInfraspecificEpithet(): ?string
    {
        return $this->infraspecificEpithet;
    }

    public function setInfraspecificEpithet(?string $infraspecificEpithet): static
    {
        $this->infraspecificEpithet = $infraspecificEpithet;
        return $this;
    }

    public function getCultivarEpithet(): ?string
    {
        return $this->cultivarEpithet;
    }

    public function setCultivarEpithet(?string $cultivarEpithet): static
    {
        $this->cultivarEpithet = $cultivarEpithet;
        return $this;
    }

    public function getTaxonRank(): ?string
    {
        return $this->taxonRank;
    }

    public function setTaxonRank(?string $taxonRank): static
    {
        $this->taxonRank = $taxonRank;
        return $this;
    }

    public function getVerbatimTaxonRank(): ?string
    {
        return $this->verbatimTaxonRank;
    }

    public function setVerbatimTaxonRank(?string $verbatimTaxonRank): static
    {
        $this->verbatimTaxonRank = $verbatimTaxonRank;
        return $this;
    }

    public function getScientificNameAuthorship(): ?string
    {
        return $this->scientificNameAuthorship;
    }

    public function setScientificNameAuthorship(?string $scientificNameAuthorship): static
    {
        $this->scientificNameAuthorship = $scientificNameAuthorship;
        return $this;
    }

    public function getVernacularName(): ?string
    {
        return $this->vernacularName;
    }

    public function setVernacularName(?string $vernacularName): static
    {
        $this->vernacularName = $vernacularName;
        return $this;
    }

    public function getNomenclaturalCode(): ?string
    {
        return $this->nomenclaturalCode;
    }

    public function setNomenclaturalCode(?string $nomenclaturalCode): static
    {
        $this->nomenclaturalCode = $nomenclaturalCode;
        return $this;
    }

    public function getTaxonomicStatus(): ?string
    {
        return $this->taxonomicStatus;
    }

    public function setTaxonomicStatus(?string $taxonomicStatus): static
    {
        $this->taxonomicStatus = $taxonomicStatus;
        return $this;
    }

    public function getNomenclaturalStatus(): ?string
    {
        return $this->nomenclaturalStatus;
    }

    public function setNomenclaturalStatus(?string $nomenclaturalStatus): static
    {
        $this->nomenclaturalStatus = $nomenclaturalStatus;
        return $this;
    }

    public function getTaxonRemarks(): ?string
    {
        return $this->taxonRemarks;
    }

    public function setTaxonRemarks(?string $taxonRemarks): static
    {
        $this->taxonRemarks = $taxonRemarks;
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

    /**
     * @return Collection<int, Identification>
     */
    public function getIdentifications(): Collection
    {
        return $this->identifications;
    }

    public function addIdentification(Identification $identification): static
    {
        if (!$this->identifications->contains($identification)) {
            $this->identifications->add($identification);
        }
        return $this;
    }

    public function removeIdentification(Identification $identification): static
    {
        $this->identifications->removeElement($identification);
        return $this;
    }

    /**
     * @return Collection<int, MeasurementOrFact>
     */
    public function getMeasurementOrFacts(): Collection
    {
        return $this->measurementOrFacts;
    }

    public function addMeasurementOrFact(MeasurementOrFact $measurementOrFact): static
    {
        if (!$this->measurementOrFacts->contains($measurementOrFact)) {
            $this->measurementOrFacts->add($measurementOrFact);
        }
        return $this;
    }

    public function removeMeasurementOrFact(MeasurementOrFact $measurementOrFact): static
    {
        $this->measurementOrFacts->removeElement($measurementOrFact);
        return $this;
    }

}
