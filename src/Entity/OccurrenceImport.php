<?php

namespace App\Entity;

use App\Entity\DarwinCore\Occurrence;
use App\Repository\OccurrenceImportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OccurrenceImportRepository::class)]
class OccurrenceImport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private ?\DateTimeImmutable $firstImportedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private ?\DateTimeImmutable $lastUpdatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $globalObjectID = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tagId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $objectType = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $remoteLastUpdatedAt = null;

    #[ORM\ManyToOne]
    private ?User $manualImportTrigger = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Occurrence $occurrence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstImportedAt(): ?\DateTimeImmutable
    {
        return $this->firstImportedAt;
    }

    public function setFirstImportedAt(\DateTimeImmutable $firstImportedAt): static
    {
        $this->firstImportedAt = $firstImportedAt;

        return $this;
    }

    public function getLastUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->lastUpdatedAt;
    }

    public function setLastUpdatedAt(\DateTimeImmutable $lastUpdatedAt): static
    {
        $this->lastUpdatedAt = $lastUpdatedAt;

        return $this;
    }

    public function getGlobalObjectID(): ?string
    {
        return $this->globalObjectID;
    }

    public function setGlobalObjectID(string $globalObjectID): static
    {
        $this->globalObjectID = $globalObjectID;

        return $this;
    }

    public function getTagId(): ?int
    {
        return $this->tagId;
    }

    public function setTagId(?int $tagId): static
    {
        $this->tagId = $tagId;

        return $this;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function setObjectType(?string $objectType): static
    {
        $this->objectType = $objectType;

        return $this;
    }

    public function getRemoteLastUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->remoteLastUpdatedAt;
    }

    public function setRemoteLastUpdatedAt(\DateTimeImmutable $remoteLastUpdatedAt): static
    {
        $this->remoteLastUpdatedAt = $remoteLastUpdatedAt;

        return $this;
    }

    public function getManualImportTrigger(): ?User
    {
        return $this->manualImportTrigger;
    }

    public function setManualImportTrigger(?User $manualImportTrigger): static
    {
        $this->manualImportTrigger = $manualImportTrigger;

        return $this;
    }

    public function getOccurrence(): ?Occurrence
    {
        return $this->occurrence;
    }

    public function setOccurrence(Occurrence $occurrence): static
    {
        $this->occurrence = $occurrence;

        return $this;
    }
}
