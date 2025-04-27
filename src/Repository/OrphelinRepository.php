<?php

namespace App\Repository;

use App\Entity\Orphelin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orphelin>
 */
class OrphelinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orphelin::class);
    }

    public function searchOrphelins(string $query, string $sortField = 'nomO', string $sortOrder = 'asc')
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.tuteur', 't') // Joindre la table Tuteur
            ->addSelect('t') // Récupérer les données du tuteur pour les afficher

            // Correction du tri sur le tuteur
            ->orderBy(
                $sortField === 'tuteur' ? 't.nomT' : "o.$sortField",
                $sortOrder
            );

        // Gestion de la recherche par Nom, Prénom et Nom du Tuteur
        $qb->where('o.nomO LIKE :query')
            ->orWhere('o.prenomO LIKE :query')
            ->orWhere('t.nomT LIKE :query') // Recherche par Tuteur
            ->setParameter('query', "%$query%");

        // ✅ Vérification si l'entrée est une date (JJ/MM/AAAA)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $query)) {
            $dateParts = explode('/', $query);
            $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // Convertir JJ/MM/AAAA → YYYY-MM-DD
            $qb->orWhere('o.dateNaissance = :dateNaissance')
                ->setParameter('dateNaissance', $formattedDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function countOrphelinsByTuteur(): array
    {
        return $this->createQueryBuilder('o')
            ->select('t.nomT, COUNT(o.idO) as orphelinCount')
            ->leftJoin('o.tuteur', 't')
            ->groupBy('t.idT')
            ->orderBy('orphelinCount', 'DESC')
            ->getQuery()
            ->getResult();
    }





    //    /**
    //     * @return Orphelin[] Returns an array of Orphelin objects
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

    //    public function findOneBySomeField($value): ?Orphelin
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
