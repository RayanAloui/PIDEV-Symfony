<?php

namespace App\Repository;

use App\Entity\Tuteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tuteur>
 */
class TuteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tuteur::class);
    }

    public function searchTuteurs(string $query, string $sortField = 'nomT', string $sortOrder = 'asc')
    {
        $qb = $this->createQueryBuilder('t');

        // Appliquer le tri
        $qb->orderBy("t.$sortField", $sortOrder);

        // Recherche sur les champs demandés
        $qb->where('t.cinT LIKE :query')
           ->orWhere('t.nomT LIKE :query')
           ->orWhere('t.prenomT LIKE :query')
           ->orWhere('t.telephoneT LIKE :query')
           ->orWhere('t.email LIKE :query')
           ->setParameter('query', "%$query%");

        return $qb->getQuery()->getResult();
    }


    //    /**
    //     * @return Tuteur[] Returns an array of Tuteur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tuteur
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
