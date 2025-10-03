<?php

namespace App\Repository;

use App\Entity\JobStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobStatus>
 */
class JobStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobStatus::class);
    }

    /**
     * Find jobs by user
     *
     * @return JobStatus[]
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.user = :user')
            ->setParameter('user', $user)
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending jobs
     *
     * @return JobStatus[]
     */
    public function findPendingJobs(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->setParameter('status', JobStatus::STATUS_PENDING)
            ->orderBy('j.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find running jobs
     *
     * @return JobStatus[]
     */
    public function findRunningJobs(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->setParameter('status', JobStatus::STATUS_RUNNING)
            ->orderBy('j.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find job by jobId
     */
    public function findByJobId(string $jobId): ?JobStatus
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.jobId = :jobId')
            ->setParameter('jobId', $jobId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Clean up old completed jobs
     */
    public function cleanupOldJobs(\DateTimeImmutable $olderThan): int
    {
        return $this->createQueryBuilder('j')
            ->delete()
            ->where('j.status IN (:statuses)')
            ->andWhere('j.completedAt < :date')
            ->setParameter('statuses', [JobStatus::STATUS_COMPLETED, JobStatus::STATUS_FAILED])
            ->setParameter('date', $olderThan)
            ->getQuery()
            ->execute();
    }
}
