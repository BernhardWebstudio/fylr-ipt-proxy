<?php

namespace App\Repository;

use App\Entity\OccurrenceImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<OccurrenceImport>
 */
class OccurrenceImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OccurrenceImport::class);
    }

    /**
     * Returns total count of imported occurrences.
     */
    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('oi')
            ->select('COUNT(oi.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns counts grouped by objectType.
     *
     * @return array<int, array{objectType: ?string, count: int}>
     */
    public function getCountsByObjectType(): array
    {
        $rows = $this->createQueryBuilder('oi')
            ->select('oi.objectType AS objectType, COUNT(oi.id) AS count')
            ->groupBy('oi.objectType')
            ->orderBy('count', SortDirection::Descending)
            ->getQuery()
            ->getArrayResult();

        // Normalize nulls to null (already) and ints
        return array_map(static function(array $row): array {
            return [
                'objectType' => $row['objectType'] ?? null,
                'count' => (int) $row['count'],
            ];
        }, $rows);
    }

    /**
     * Returns counts grouped by tagId.
     *
     * @return array<int, array{tagId: ?int, count: int}>
     */
    public function getCountsByTagId(): array
    {
        $rows = $this->createQueryBuilder('oi')
            ->select('oi.tagId AS tagId, COUNT(oi.id) AS count')
            ->groupBy('oi.tagId')
            ->orderBy('count', SortDirection::Descending)
            ->getQuery()
            ->getArrayResult();

        return array_map(static function(array $row): array {
            return [
                'tagId' => isset($row['tagId']) ? (int) $row['tagId'] : null,
                'count' => (int) $row['count'],
            ];
        }, $rows);
    }

    /**
     * Count matching entities for filtering or pre-export validation.
     */
    public function countEntities(?string $globalObjectId = null, string|int|null $tagId = null, ?string $objectType = null): int
    {
        $qb = $this->createQueryBuilder('oi')
            ->select('COUNT(oi.id)');

        if ($globalObjectId) {
            $qb->andWhere('oi.globalObjectID = :globalObjectId')
                ->setParameter('globalObjectId', $globalObjectId);
        }

        if ($tagId !== null && $tagId !== '') {
            $qb->andWhere('oi.tagId = :tagId')
                ->setParameter('tagId', (int) $tagId);
        }

        if ($objectType) {
            $qb->andWhere('oi.objectType = :objectType')
                ->setParameter('objectType', $objectType);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Generator yielding batches of OccurrenceImport entities for memory-safe processing.
     *
     * @return \Generator<int, OccurrenceImport[]>
     */
    public function getBatches(?string $globalObjectId = null, string|int|null $tagId = null, ?string $objectType = null, int $batchSize = 250): \Generator
    {
        $offset = 0;
        while (true) {
            $entities = $this->searchEntities(
                $globalObjectId,
                $tagId !== null && $tagId !== '' ? (string) $tagId : null,
                $objectType,
                $batchSize,
                $offset
            );

            if (empty($entities)) {
                break;
            }

            yield $entities;

            if (count($entities) < $batchSize) {
                break;
            }

            $offset += $batchSize;
        }
    }

    public function searchEntities(?string $globalObjectId = null, string|int|null $tagId = null, ?string $objectType = null, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('oi')
            ->innerJoin('oi.occurrence', 'o')
            ->addSelect('o');

        if ($globalObjectId) {
            $qb->andWhere('oi.globalObjectID = :globalObjectId')
                ->setParameter('globalObjectId', $globalObjectId);
        }

        if ($tagId !== null && $tagId !== '') {
            $qb->andWhere('oi.tagId = :tagId')
                ->setParameter('tagId', (int) $tagId);
        }

        if ($objectType) {
            $qb->andWhere('oi.objectType = :objectType')
                ->setParameter('objectType', $objectType);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        $qb->orderBy('oi.id', SortDirection::Descending);

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return OccurrenceImport[] Returns an array of OccurrenceImport objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?OccurrenceImport
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
