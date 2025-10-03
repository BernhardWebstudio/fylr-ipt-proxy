<?php

namespace App\Repository;

use App\Entity\OccurrenceImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OccurrenceImport>
 */
class OccurrenceImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OccurrenceImport::class);
    }

    public function searchEntities(?string $globalObjectId, ?string $tagId, ?string $objectType, ?int $limit, ?int $offset): array
    {
        $qb = $this->createQueryBuilder('oi')
            ->innerJoin('oi.occurrence', 'o')
            ->addSelect('o');

        if ($globalObjectId) {
            $qb->andWhere('oi.globalObjectID = :globalObjectId')
                ->setParameter('globalObjectId', $globalObjectId);
        }

        if ($tagId) {
            $qb->andWhere('o.tagId = :tagId')
                ->setParameter('tagId', $tagId);
        }

        if ($objectType) {
            $qb->andWhere('o.objectType = :objectType')
                ->setParameter('objectType', $objectType);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        $qb->orderBy('oi.id', 'DESC');

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
