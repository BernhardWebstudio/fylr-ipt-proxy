<?php

namespace App\Service;

use App\Entity\JobStatus;
use App\Entity\User;
use App\Repository\JobStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing job status tracking and progress updates
 */
class JobStatusService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JobStatusRepository $jobStatusRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Create a new job status record
     */
    public function createJob(
        string $jobId,
        string $type,
        User $user,
        array $criteria = [],
        ?string $format = null
    ): JobStatus {
        $jobStatus = new JobStatus();
        $jobStatus->setJobId($jobId);
        $jobStatus->setType($type);
        $jobStatus->setUser($user);
        $jobStatus->setCriteria($criteria);
        $jobStatus->setFormat($format);

        $this->entityManager->persist($jobStatus);
        $this->entityManager->flush();

        $this->logger->info('Created new job', [
            'jobId' => $jobId,
            'type' => $type,
            'userId' => $user->getId()
        ]);

        return $jobStatus;
    }

    /**
     * Update job status
     */
    public function updateJobStatus(string $jobId, string $status): bool
    {
        $jobStatus = $this->jobStatusRepository->findByJobId($jobId);

        if (!$jobStatus) {
            $this->logger->warning('Job not found for status update', ['jobId' => $jobId]);
            return false;
        }

        $jobStatus->setStatus($status);

        if ($status === JobStatus::STATUS_COMPLETED || $status === JobStatus::STATUS_FAILED) {
            $jobStatus->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        $this->logger->info('Updated job status', [
            'jobId' => $jobId,
            'status' => $status
        ]);

        return true;
    }

    /**
     * Update job progress
     */
    public function updateJobProgress(string $jobId, int $progress, int $totalItems = null): bool
    {
        $jobStatus = $this->jobStatusRepository->findByJobId($jobId);

        if (!$jobStatus) {
            $this->logger->warning('Job not found for progress update', ['jobId' => $jobId]);
            return false;
        }

        $jobStatus->setProgress($progress);

        if ($totalItems !== null) {
            $jobStatus->setTotalItems($totalItems);
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * Set job error message
     */
    public function setJobError(string $jobId, string $errorMessage): bool
    {
        $jobStatus = $this->jobStatusRepository->findByJobId($jobId);

        if (!$jobStatus) {
            $this->logger->warning('Job not found for error update', ['jobId' => $jobId]);
            return false;
        }

        $jobStatus->setStatus(JobStatus::STATUS_FAILED);
        $jobStatus->setErrorMessage($errorMessage);
        $jobStatus->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->error('Job failed', [
            'jobId' => $jobId,
            'error' => $errorMessage
        ]);

        return true;
    }

    /**
     * Set job result file path
     */
    public function setJobResult(string $jobId, string $filePath): bool
    {
        $jobStatus = $this->jobStatusRepository->findByJobId($jobId);

        if (!$jobStatus) {
            $this->logger->warning('Job not found for result update', ['jobId' => $jobId]);
            return false;
        }

        $jobStatus->setResultFilePath($filePath);
        $jobStatus->setStatus(JobStatus::STATUS_COMPLETED);
        $jobStatus->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Job completed with result', [
            'jobId' => $jobId,
            'filePath' => $filePath
        ]);

        return true;
    }

    /**
     * Get job status by job ID
     */
    public function getJobStatus(string $jobId): ?JobStatus
    {
        return $this->jobStatusRepository->findByJobId($jobId);
    }

    /**
     * Get user jobs
     *
     * @return JobStatus[]
     */
    public function getUserJobs(User $user, int $limit = 20): array
    {
        return $this->jobStatusRepository->findByUser($user, $limit);
    }

    /**
     * Generate unique job ID
     */
    public function generateJobId(): string
    {
        return uniqid('job_', true);
    }

    /**
     * Check if a job exists
     */
    public function jobExists(string $jobId): bool
    {
        return $this->jobStatusRepository->findByJobId($jobId) !== null;
    }

    /**
     * Clean up old completed jobs
     */
    public function cleanupOldJobs(int $daysOld = 30): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysOld} days");
        $deletedCount = $this->jobStatusRepository->cleanupOldJobs($cutoffDate);

        $this->logger->info('Cleaned up old jobs', [
            'deletedCount' => $deletedCount,
            'cutoffDate' => $cutoffDate->format('Y-m-d H:i:s')
        ]);

        return $deletedCount;
    }
}
