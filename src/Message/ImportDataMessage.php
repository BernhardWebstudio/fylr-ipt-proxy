<?php

namespace App\Message;

/**
 * Message to handle data import operations from EasyDB to Darwin Core entities
 */
final class ImportDataMessage
{
    public function __construct(
        private string $jobId,
        private string $type,
        private array $criteria,
        private int $userId,
        private ?string $easydbToken = null,
        private ?array $easydbSessionContent = null,
        private bool $isFylr = false,
        private int $page = 1,
        private int $pageSize = 100
    ) {}

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEasydbToken(): ?string
    {
        return $this->easydbToken;
    }

    public function getEasydbSessionContent(): ?array
    {
        return $this->easydbSessionContent;
    }

    public function isFylr(): bool
    {
        return $this->isFylr;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
