<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findForListing(
        ?string $type,
        bool $onlyFree,
        int $page,
        int $pageSize,
        string $order
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.bookings', 'b')
            ->addSelect('COUNT(b.id) AS signed')
            ->groupBy('a.id');

        if ($type !== null) {
            $qb->andWhere('a.type = :type')
                ->setParameter('type', $type);
        }

        if ($onlyFree) {
            $qb->having('COUNT(b.id) < a.maxParticipants');
        }

        $qb->orderBy('a.dateStart', $order)
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return $qb->getQuery()->getResult();
    }


    //    /**
    //     * @return Activity[] Returns an array of Activity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Activity
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
